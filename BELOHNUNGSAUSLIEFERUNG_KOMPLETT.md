# Automatische Belohnungsauslieferung - Komplett-Übersicht

## ✅ JA! Belohnungen werden automatisch über Customer-API ausgeliefert!

Die Belohnungen, die auf https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=belohnungsstufen eingerichtet wurden, werden **vollautomatisch** über die vom Kunden konfigurierte Email-Marketing-API ausgeliefert.

---

## 🔄 Kompletter Workflow

### 1. **Belohnung erstellen** (durch Kunde)
```
Dashboard → Belohnungsstufen → "Neue Belohnungsstufe"

Kunde legt fest:
├── Stufen-Level: 1, 2, 3, ...
├── Erforderliche Empfehlungen: 3, 5, 10, ...
├── Belohnungs-Titel: "Premium E-Book"
├── Belohnungs-Beschreibung: "Exklusiver Content"
└── Wird gespeichert in: reward_definitions
```

### 2. **Lead empfiehlt weiter**
```
Lead teilt seinen Empfehlungslink
↓
Neue Person klickt auf Link
↓
referral_clicks wird gespeichert
↓
Person registriert sich / kauft
↓
Conversion wird getrackt
```

### 3. **🎯 AUTOMATISCHE AUSLIEFERUNG** (2 Wege)

#### Weg A: Echtzeit bei Conversion
```php
// api/referral/track-conversion.php
trackConversion() → SUCCESS
    ↓
checkAndDeliverRewards($lead_id)  // ← AUTOMATISCH!
    ↓
RewardEmailDeliveryService::sendRewardEmail()
    ↓
    ├── API-Einstellungen laden (Quentn/Klick-Tipp/etc.)
    ├── Custom Fields aktualisieren beim Provider
    │   ├── reward_title = "Premium E-Book"
    │   ├── reward_description = "Exklusiver Content"
    │   ├── successful_referrals = 10
    │   └── current_points = 10
    ├── Start-Tag setzen (z.B. "reward-earned")
    └── Kampagne im Provider wird getriggert
        ↓
    Lead erhält E-Mail vom Provider
```

#### Weg B: Backup-Cronjob (alle 5-10 Minuten)
```php
// api/rewards/auto-deliver-cron.php
Läuft automatisch alle 5-10 Minuten
    ↓
SELECT alle Leads mit erreichten Belohnungen
    ↓
Für jeden Lead:
    ├── checkAndDeliverRewards($lead_id)
    ├── Email über Customer-API versenden
    └── reward_deliveries tracken
```

---

## 🛠️ Technische Integration

### Dateien die zusammenspielen:

```
1. BELOHNUNGEN ERSTELLEN
   └── customer/sections/belohnungsstufen.php
       └── speichert in → reward_definitions

2. CONVERSION TRACKING
   └── api/referral/track-conversion.php
       └── ruft auf → checkAndDeliverRewards()

3. REWARD DELIVERY LOGIC
   └── api/reward_delivery.php
       └── nutzt → RewardEmailDeliveryService

4. UNIVERSAL PROVIDER SERVICE
   └── api/rewards/email-delivery-service.php
       ├── updateQuentnFields()
       ├── updateActiveCampaignFields()
       ├── updateKlickTippFields()
       ├── updateBrevoFields()
       └── updateGetResponseFields()

5. BACKUP CRONJOB
   └── api/rewards/auto-deliver-cron.php
       └── prüft regelmäßig ausstehende Belohnungen
```

### Datenbank-Flow:

```sql
-- 1. Kunde erstellt Belohnung
INSERT INTO reward_definitions (
    user_id,
    tier_level,
    required_referrals,
    reward_title,
    ...
);

-- 2. Lead erreicht Empfehlungszahl
SELECT * FROM lead_users WHERE id = ?;
-- successful_referrals = 10

-- 3. System prüft ausstehende Belohnungen
SELECT * FROM reward_definitions 
WHERE user_id = ?
  AND required_referrals <= 10  -- ← Lead hat 10 Empfehlungen!
  AND NOT EXISTS (
      SELECT 1 FROM reward_deliveries 
      WHERE lead_id = ? AND reward_id = ?
  );

-- 4. Belohnung ausliefern
INSERT INTO reward_deliveries (
    lead_id,
    reward_id,
    delivery_method,
    delivered_at
);

-- 5. Custom Fields aktualisieren (beim Provider)
-- Über Provider-API: Quentn/Klick-Tipp/ActiveCampaign/etc.
```

---

## 📊 Beispiel-Szenario

### Setup:
```
Kunde "Max Mustermann" hat eingerichtet:
├── Provider: Klick-Tipp
├── Start-Tag: "reward-earned"
└── Belohnungsstufen:
    ├── Stufe 1: 3 Empfehlungen → "Bronze E-Book"
    ├── Stufe 2: 5 Empfehlungen → "Silber Videokurs"
    └── Stufe 3: 10 Empfehlungen → "Gold Beratung"
```

### Was passiert:

#### Lead hat 0 Empfehlungen
```
- Keine Belohnungen erreicht
- Nichts passiert
```

#### Lead erreicht 3 Empfehlungen
```
✅ Conversion getrackt
   ↓
✅ checkAndDeliverRewards() läuft automatisch
   ↓
✅ System findet: "Bronze E-Book" (Stufe 1)
   ↓
✅ Custom Fields bei Klick-Tipp aktualisiert:
   - reward_title = "Bronze E-Book"
   - successful_referrals = 3
   - current_points = 3
   ↓
✅ Tag "reward-earned" gesetzt
   ↓
✅ Klick-Tipp Kampagne startet automatisch
   ↓
✅ Lead erhält E-Mail mit Bronze E-Book
```

#### Lead erreicht 10 Empfehlungen
```
✅ Conversion getrackt
   ↓
✅ checkAndDeliverRewards() findet:
   - "Silber Videokurs" (Stufe 2) ← noch nicht ausgeliefert!
   - "Gold Beratung" (Stufe 3) ← auch noch nicht ausgeliefert!
   ↓
✅ BEIDE Belohnungen werden ausgeliefert:
   
   1. Silber Videokurs:
      - Custom Fields Update
      - Tag "reward-earned"
      - E-Mail vom Provider
   
   2. Gold Beratung:
      - Custom Fields Update (überschreibt Stufe 2)
      - Tag "reward-earned" (erneut)
      - E-Mail vom Provider
   ↓
✅ reward_deliveries Tabelle:
   - Lead #123 → Reward #1 (Bronze) ✓
   - Lead #123 → Reward #2 (Silber) ✓
   - Lead #123 → Reward #3 (Gold) ✓
```

---

## 🔍 Debugging & Prüfen

### 1. Prüfe ob Belohnung ausgeliefert wurde:
```sql
SELECT 
    lu.email,
    lu.successful_referrals,
    rd.reward_title,
    rd.required_referrals,
    rdel.delivered_at,
    rdel.delivery_method
FROM reward_deliveries rdel
JOIN lead_users lu ON rdel.lead_id = lu.id
JOIN reward_definitions rd ON rdel.reward_id = rd.id
WHERE lu.email = 'lead@example.com'
ORDER BY rdel.delivered_at DESC;
```

### 2. Prüfe ausstehende Belohnungen:
```sql
SELECT 
    lu.email,
    lu.successful_referrals,
    rd.reward_title,
    rd.required_referrals,
    rd.tier_level
FROM lead_users lu
CROSS JOIN reward_definitions rd
WHERE rd.user_id = lu.user_id
  AND rd.required_referrals <= lu.successful_referrals
  AND NOT EXISTS (
      SELECT 1 FROM reward_deliveries 
      WHERE lead_id = lu.id AND reward_id = rd.id
  );
```

### 3. Logs anschauen:
```bash
# Automatische Auslieferung
tail -f /var/log/php-error.log | grep "Reward"

# Wichtige Log-Einträge:
# 🎁 "Prüfe Belohnungen für Lead ID: X nach Conversion"
# ✅ "X Belohnungen ausgeliefert!"
# 📧 "Benachrichtigung erfolgreich via {provider}"
# ✅ "Custom Fields erfolgreich aktualisiert"
```

### 4. Manuell triggern (für Tests):
```bash
# Cronjob manuell ausführen
php /pfad/zu/api/rewards/auto-deliver-cron.php

# Oder über Browser:
https://app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php
```

---

## ✅ Checkliste

Damit alles funktioniert:

- [x] **Belohnungsstufen erstellt** auf belohnungsstufen.php
- [x] **API-Integration** im Empfehlungsprogramm eingerichtet
- [x] **Custom Fields** beim Provider angelegt
- [x] **Start-Tag** konfiguriert (z.B. "reward-earned")
- [x] **E-Mail-Kampagne** im Provider mit Tag-Trigger erstellt
- [x] **Kampagne aktiviert** im Provider
- [x] **Conversion-Tracking** funktioniert
- [x] **Test-Lead** durchgeführt

---

## 🚀 Fazit

**JA, die Belohnungen werden vollautomatisch ausgeliefert!**

1. Kunde erstellt Belohnungsstufen → `reward_definitions`
2. Lead erreicht Empfehlungszahl → Conversion getrackt
3. System prüft automatisch → `checkAndDeliverRewards()`
4. Belohnung wird über Customer-API ausgeliefert
5. Custom Fields beim Provider aktualisiert
6. Start-Tag gesetzt → Kampagne startet
7. Lead erhält E-Mail vom Provider

**Kein manueller Eingriff nötig!** 🎉

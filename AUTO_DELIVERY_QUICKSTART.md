# 🚀 Auto-Delivery System - Quickstart Guide

## 5-Minuten Setup

### Schritt 1: Installation (2 Minuten)

```bash
# 1. Im Browser öffnen:
https://app.mehr-infos-jetzt.de/install_auto_delivery.php

# 2. Auf "Jetzt installieren" klicken
# 3. Warten bis alle ✅ erscheinen
# 4. Datei install_auto_delivery.php löschen
```

### Schritt 2: Erste Belohnung konfigurieren (2 Minuten)

```sql
-- In der Datenbank oder via Admin-Interface:
UPDATE reward_definitions 
SET 
  delivery_url = 'https://example.com/download/ebook.pdf',
  delivery_instructions = 'Klicke auf den Link um das E-Book herunterzuladen.',
  auto_deliver = 1
WHERE id = 1; -- Deine erste Belohnung
```

**ODER** über das Admin-Interface:
1. Gehe zu "Empfehlungsprogramm" → "Belohnungen"
2. Bearbeite eine Belohnung
3. Fülle die neuen Felder aus:
   - **Download-Link**: `https://...`
   - **Einlöse-Anweisungen**: `Klicke auf...`
   - **Auto-Auslieferung**: ✅ aktiviert

### Schritt 3: Testen (1 Minute)

```bash
# API-Test: Belohnungen für einen Lead prüfen
curl -X POST https://app.mehr-infos-jetzt.de/api/reward_delivery.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "check_and_deliver",
    "lead_id": 1
  }'
```

**ODER** im Browser als Admin:
1. Öffne: `/admin/reward_deliveries.php`
2. Prüfe ob Tabelle angezeigt wird
3. Fertig! ✅

---

## 📋 Checkliste für Go-Live

### Vor dem Start

- [ ] Installation abgeschlossen
- [ ] `install_auto_delivery.php` gelöscht
- [ ] Mindestens eine Belohnung mit `delivery_url` oder `access_code` konfiguriert
- [ ] Admin-Dashboard getestet (`/admin/reward_deliveries.php`)
- [ ] Email-Versand getestet

### Nach dem Start

- [ ] Erste Conversion überwachen
- [ ] Email-Eingang beim Lead prüfen
- [ ] Lead-Dashboard als Lead öffnen
- [ ] Auslieferungs-Logs prüfen (`/webhook/conversion-logs.txt`)

---

## 🎯 Häufigste Anwendungsfälle

### Fall 1: E-Book als Belohnung

```php
// In reward_definitions:
[
  'tier_level' => 1,
  'reward_title' => 'E-Book "Lead Magnet Secrets"',
  'required_referrals' => 3,
  'delivery_url' => 'https://cdn.example.com/ebooks/lead-secrets.pdf',
  'delivery_instructions' => 'Klicke auf den Download-Link. Das E-Book wird automatisch heruntergeladen.',
  'auto_deliver' => 1
]
```

**Ergebnis:**
- Lead erreicht 3 Empfehlungen
- ✅ Email mit Download-Link wird automatisch gesendet
- ✅ Lead sieht Belohnung im Dashboard mit anklickbarem Button

### Fall 2: Kurs-Zugang mit Code

```php
[
  'tier_level' => 2,
  'reward_title' => 'Premium Kurs-Zugang',
  'required_referrals' => 5,
  'delivery_url' => 'https://kurs-plattform.de/einloesen',
  'access_code' => 'PREMIUM2024XYZ',
  'delivery_instructions' => '1. Gehe zu kurs-plattform.de
2. Klicke auf "Code einlösen"
3. Gib den Code ein: PREMIUM2024XYZ
4. Viel Erfolg!',
  'auto_deliver' => 1
]
```

**Ergebnis:**
- Lead erreicht 5 Empfehlungen
- ✅ Email mit Code UND Anweisungen
- ✅ Im Dashboard: Code kopierbar + Link + Anleitung

### Fall 3: Manuelle Belohnung (kein Auto-Deliver)

```php
[
  'tier_level' => 3,
  'reward_title' => '1:1 Beratungsgespräch',
  'required_referrals' => 10,
  'delivery_instructions' => 'Wir melden uns per Email für die Terminvereinbarung.',
  'auto_deliver' => 0  // ❗ Manuell!
]
```

**Ablauf:**
1. Lead erreicht 10 Empfehlungen
2. ❌ KEINE automatische Email (weil auto_deliver = 0)
3. Admin sieht Lead in `/admin/reward_deliveries.php` NICHT
4. ✅ Admin muss manuell via API ausliefern:

```bash
curl -X POST .../api/reward_delivery.php -d '{
  "action": "manual_delivery",
  "lead_id": 123,
  "reward_id": 45
}'
```

---

## 🔧 Integration in bestehende Webhooks

### Option 1: In Digistore24-Webhook integrieren

```php
// In webhook/digistore24.php
// Nach erfolgreicher Conversion:

if ($conversionSuccessful) {
    // Bestehender Code...
    
    // NEU: Belohnungen prüfen
    require_once __DIR__ . '/../api/reward_delivery.php';
    $result = checkAndDeliverRewards($pdo, $lead_id);
    
    logWebhook([
        'message' => 'Rewards checked',
        'result' => $result
    ], 'reward_delivery');
}
```

### Option 2: Separater Conversion-Webhook

```php
// Dein Autoresponder/System ruft auf:
POST /webhook/referral_conversion.php
Content-Type: application/json

{
  "email": "lead@example.com",
  "freebie_id": 33,
  "status": "converted"
}

// System macht automatisch:
// 1. Lead-Status aktualisieren
// 2. Referrer finden
// 3. Belohnungen prüfen
// 4. Ausliefern + Email senden
```

---

## 📊 Admin-Dashboard Funktionen

### Übersicht öffnen

```
https://app.mehr-infos-jetzt.de/admin/reward_deliveries.php
```

### Was du siehst:

1. **Statistiken** (oben)
   - Gesamt Auslieferungen
   - Unique Leads
   - Emails versendet
   - Heute ausgeliefert

2. **Filter** (mitte)
   - Status: Alle / Ausgeliefert / Eingelöst
   - Kunde: Dropdown
   - Suche: Lead-Name oder Email

3. **Tabelle** (unten)
   - Lead-Info
   - Belohnung + Tier
   - Status-Badge
   - Email-Status
   - Zeitstempel

### Häufige Admin-Aufgaben:

```sql
-- Alle heutigen Auslieferungen
SELECT * FROM reward_deliveries 
WHERE DATE(delivered_at) = CURDATE();

-- Welcher Lead hat welche Belohnungen?
SELECT 
  lu.name,
  lu.email,
  rd.reward_title,
  rd.delivered_at
FROM reward_deliveries rd
JOIN lead_users lu ON rd.lead_id = lu.id
WHERE lu.email = 'lead@example.com';

-- Emails die nicht gesendet wurden
SELECT * FROM reward_deliveries 
WHERE email_sent = 0;
```

---

## 🎨 Lead-Dashboard Ansicht

### Was Leads sehen

Wenn ein Lead `/lead_dashboard.php` öffnet, sieht er:

1. **"Meine Belohnungen" Sektion**
   - Card-Layout mit allen Belohnungen
   - "NEU" Badge für < 24h alte Belohnungen

2. **Für jede Belohnung:**
   - 🎁 Icon + Tier-Badge
   - Titel + Wert
   - 🔗 Download-Button (grün, anklickbar)
   - 🔑 Code (gelb, kopierbar via Button)
   - 📋 Anweisungen (blau, formatiert)
   - 📅 Datum

### Integration ins Dashboard

```php
// In lead_dashboard.php NACH dem Freebie-Kurse Bereich:

<?php
// Belohnungen anzeigen
require_once __DIR__ . '/includes/lead_rewards_section.php';
echo renderMyRewardsSection($pdo, $lead_id);
?>
```

**Fertig!** 🎉

---

## 🐛 Schnelle Problemlösungen

### Problem: "Tabelle reward_deliveries existiert nicht"

```bash
# Nochmal installieren:
https://app.mehr-infos-jetzt.de/install_auto_delivery.php
```

### Problem: Belohnung wird nicht ausgeliefert

```sql
-- Prüfen:
SELECT 
  rd.id,
  rd.reward_title,
  rd.required_referrals,
  rd.auto_deliver,
  rd.delivery_url,
  rd.access_code,
  (SELECT COUNT(*) FROM lead_referrals 
   WHERE referrer_id = 1 
   AND status IN ('active','converted')) as referral_count
FROM reward_definitions rd
WHERE rd.user_id = YOUR_USER_ID;

-- Wenn referral_count >= required_referrals UND auto_deliver = 1:
-- Dann SOLLTE es ausgeliefert werden!
```

**Lösung:** Manuell ausliefern via API:
```bash
curl -X POST .../api/reward_delivery.php -d '{
  "action": "check_and_deliver",
  "lead_id": 1
}'
```

### Problem: Email kommt nicht an

1. **SMTP-Konfiguration prüfen**
2. **Spam-Ordner checken**
3. **Email-Logs:**
   ```sql
   SELECT * FROM reward_deliveries 
   WHERE email_sent = 0;
   ```
4. **Manuell nochmal senden** (Feature kommt bald)

---

## 📈 Nächste Schritte

Sobald das System läuft:

1. **Mehr Belohnungen hinzufügen**
   - Verschiedene Tiers
   - Verschiedene Typen (Download, Code, Link)

2. **Analytics überwachen**
   - Welche Belohnungen sind beliebt?
   - Wie viele Leads erreichen welche Stufe?

3. **A/B-Testing**
   - Verschiedene Belohnungswerte testen
   - Required_referrals optimieren

4. **Feedback einholen**
   - Leads nach Zufriedenheit fragen
   - Belohnungen anpassen

---

## 💡 Pro-Tipps

### Tipp 1: Gestaffelte Belohnungen

```php
// Starter (niedrige Hürde für ersten Erfolg)
['tier' => 1, 'required' => 1, 'reward' => 'Checkliste PDF']

// Mittelstufe (guter Anreiz)
['tier' => 2, 'required' => 3, 'reward' => 'E-Book']

// Fortgeschritten (große Belohnung)
['tier' => 3, 'required' => 5, 'reward' => 'Kurs-Zugang']

// Master (exklusive Belohnung)
['tier' => 4, 'required' => 10, 'reward' => '1:1 Beratung']
```

### Tipp 2: Klare Anweisungen

```php
// ✅ GUT:
'delivery_instructions' => 
'1. Klicke auf den Download-Link oben
2. Datei wird automatisch heruntergeladen
3. Öffne die PDF mit Adobe Reader
4. Viel Erfolg!'

// ❌ SCHLECHT:
'delivery_instructions' => 'Link oben klicken'
```

### Tipp 3: Mehrwert kommunizieren

```php
// ✅ GUT:
'reward_title' => 'Premium E-Book "10 Lead Magnets die konvertieren"',
'reward_value' => 'Wert: 47€ - Heute kostenlos!',

// ❌ SCHLECHT:
'reward_title' => 'E-Book',
'reward_value' => null
```

---

## ✅ Das wars!

Jetzt hast du:
- ✨ Automatische Belohnungsauslieferung
- 📧 Email-Benachrichtigungen
- 📊 Tracking & Analytics
- 🎯 Lead-Dashboard Integration
- 🔧 Admin-Übersicht

**Viel Erfolg mit deinem Empfehlungsprogramm!** 🚀

---

## 📞 Weitere Hilfe

- 📚 Vollständige Doku: `AUTO_DELIVERY_README.md`
- 🔍 Logs prüfen: `/webhook/conversion-logs.txt`
- 🛠️ Admin-Dashboard: `/admin/reward_deliveries.php`

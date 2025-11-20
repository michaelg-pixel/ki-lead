# Universal Multi-Provider Reward System

## 🎯 Übersicht

Das Empfehlungsprogramm funktioniert jetzt **provider-unabhängig** mit allen gängigen deutschen E-Mail-Marketing-Anbietern:

- ✅ **Quentn**
- ✅ **ActiveCampaign**
- ✅ **Klick-Tipp**
- ✅ **Brevo** (Sendinblue)
- ✅ **GetResponse**

## 🔧 Wie es funktioniert

### 1. Lead-Registrierung
Wenn sich ein Lead über einen Empfehlungslink registriert:
- Lead wird in `lead_users` gespeichert
- `referral_code` und `referrer_code` werden vergeben
- Basis Custom Fields werden beim Provider gesetzt:
  - `referral_code` - Der eigene Empfehlungscode des Leads
  - `referrer_code` - Code des Empfehlungsgebers
  - `total_referrals` - Gesamte Empfehlungen
  - `successful_referrals` - Bestätigte Empfehlungen

### 2. Belohnungs-Freischaltung
Wenn ein Lead genug Empfehlungen erreicht:
- System prüft automatisch verfügbare Belohnungen
- **Custom Fields werden beim Provider aktualisiert:**
  - `reward_title` - Titel der freigeschalteten Belohnung
  - `reward_description` - Beschreibung
  - `reward_warning` - Wichtige Hinweise
  - `current_points` - Aktuelle Punktzahl
  - `rewards_earned` - Anzahl erhaltener Belohnungen
  - `company_name` - Dein Firmenname

- **Start-Tag wird gesetzt** (wenn konfiguriert)
  - Triggert deine Automation/Kampagne im Provider
  - Tag-Name kommt aus deinen API-Einstellungen

### 3. E-Mail-Versand
Je nach Provider:
- **Tag-Trigger** (Quentn, ActiveCampaign, Klick-Tipp, GetResponse):
  - Custom Fields werden aktualisiert
  - Tag wird hinzugefügt
  - Deine Kampagne im Provider wird getriggert
  - **Du musst die E-Mail-Vorlage in deinem Provider erstellen!**

- **Direkter Versand** (Brevo):
  - Custom Fields werden aktualisiert
  - E-Mail wird direkt über Provider API versendet
  - Verwendet Template aus dem System

- **Fallback** (kein Provider konfiguriert):
  - System-E-Mail wird versendet
  - Basis-Template wird verwendet

## 📋 Setup-Anleitung für Kunden

### Schritt 1: Custom Fields beim Provider anlegen

**WICHTIG:** Diese Felder müssen ZUERST im E-Mail-Marketing-System angelegt werden!

#### Basis-Felder (bei Lead-Registrierung):
- `referral_code` (Text)
- `referrer_code` (Text)
- `total_referrals` (Zahl)
- `successful_referrals` (Zahl)

#### Belohnungs-Felder (bei Freischaltung):
- `reward_title` (Text, max. 255 Zeichen)
- `reward_description` (Text, max. 500 Zeichen)
- `reward_warning` (Text, max. 500 Zeichen)
- `rewards_earned` (Zahl)
- `current_points` (Zahl)
- `company_name` (Text)

#### Provider-spezifische Namen:
```php
// Quentn, ActiveCampaign, Klick-Tipp, GetResponse
referral_code
successful_referrals
reward_title
// etc.

// Brevo (Großbuchstaben!)
REFERRAL_CODE
SUCCESSFUL_REFERRALS
REWARD_TITLE
// etc.
```

### Schritt 2: API-Integration einrichten

Im Dashboard unter **Empfehlungsprogramm**:

1. **Provider auswählen**
   - Quentn, ActiveCampaign, etc.

2. **API-Zugangsdaten eingeben**
   - API-Key
   - API-URL (falls erforderlich)
   - Username (nur Klick-Tipp)

3. **Start-Tag festlegen**
   - Beispiel: `"reward-earned"` oder `"Belohnung-freigeschaltet"`
   - Dieser Tag triggert deine E-Mail-Kampagne

4. **Double Opt-in aktivieren** (empfohlen)

5. **Testen & Speichern**

### Schritt 3: E-Mail-Kampagne im Provider erstellen

#### Für Tag-basierte Provider (Quentn, ActiveCampaign, Klick-Tipp, GetResponse):

1. **Automation/Kampagne erstellen** im Provider
2. **Trigger:** Tag `"reward-earned"` (oder dein gewählter Tag)
3. **E-Mail-Vorlage erstellen** mit Platzhaltern:

**Platzhalter-Syntax nach Provider:**

```php
// QUENTN
Hallo [[vorname]]!
Du hast die Belohnung "[[reward_title]]" freigeschaltet!
Mit [[successful_referrals]] Empfehlungen hast du [[current_points]] Punkte erreicht.
[[reward_warning]]

// ACTIVECAMPAIGN
Hallo %FIRSTNAME%!
Du hast die Belohnung "%REWARD_TITLE%" freigeschaltet!
Mit %SUCCESSFUL_REFERRALS% Empfehlungen hast du %CURRENT_POINTS% Punkte erreicht.
%REWARD_WARNING%

// KLICK-TIPP
Hallo {vorname}!
Du hast die Belohnung "{reward_title}" freigeschaltet!
Mit {successful_referrals} Empfehlungen hast du {current_points} Punkte erreicht.
{reward_warning}

// BREVO
Hallo {{ contact.FIRSTNAME }}!
Du hast die Belohnung "{{ contact.REWARD_TITLE }}" freigeschaltet!
Mit {{ contact.SUCCESSFUL_REFERRALS }} Empfehlungen hast du {{ contact.CURRENT_POINTS }} Punkte erreicht.
{{ contact.REWARD_WARNING }}

// GETRESPONSE
Hallo [[firstname]]!
Du hast die Belohnung "[[custom "reward_title"]]" freigeschaltet!
Mit [[custom "successful_referrals"]] Empfehlungen hast du [[custom "current_points"]] Punkte erreicht.
[[custom "reward_warning"]]
```

4. **Kampagne aktivieren**

#### Für Brevo (Direkter Versand):
- Keine Kampagne nötig
- E-Mail wird automatisch versendet
- Custom Fields werden trotzdem aktualisiert

## 🔍 Technische Details

### Dateistruktur
```
api/
├── reward_delivery.php              # Haupt-Logik für Belohnungsauslieferung
├── rewards/
│   ├── email-delivery-service.php   # Universal Provider Service
│   ├── auto-deliver-cron.php        # Cronjob für automatische Prüfung
│   └── ...
customer/
├── sections/
│   └── empfehlungsprogramm.php      # Frontend für Kunden
└── includes/
    └── EmailProviders.php            # Provider-Abstraktionen
```

### Workflow

```
Lead erreicht Belohnungsstufe
    ↓
reward_delivery.php → checkAndDeliverRewards()
    ↓
Belohnung in DB speichern (reward_deliveries)
    ↓
RewardEmailDeliveryService → sendRewardEmail()
    ↓
Custom Fields aktualisieren beim Provider
    ├── updateQuentnFields()
    ├── updateActiveCampaignFields()
    ├── updateKlickTippFields()
    ├── updateBrevoFields()
    └── updateGetResponseFields()
    ↓
Start-Tag hinzufügen (wenn konfiguriert)
    ↓
Kampagne im Provider wird getriggert
    ↓
Lead erhält E-Mail
```

### API-Endpoints

```php
// Belohnungen für einen Lead prüfen und ausliefern
POST /api/reward_delivery.php
{
    "action": "check_and_deliver",
    "lead_id": 123
}

// Manuelle Auslieferung
POST /api/reward_delivery.php
{
    "action": "manual_delivery",
    "lead_id": 123,
    "reward_id": 456
}

// Ausgelieferte Belohnungen abrufen
POST /api/reward_delivery.php
{
    "action": "get_delivered_rewards",
    "lead_id": 123
}
```

## 🐛 Debugging

### Logs prüfen
```bash
# PHP Error Log
tail -f /var/log/php-error.log | grep "Reward"

# Wichtige Log-Punkte:
# ✅ "Benachrichtigung erfolgreich via {provider}"
# ⚠️ "Custom Fields Update fehlgeschlagen"
# ❌ "Notification Error"
```

### Häufige Probleme

#### 1. Custom Fields werden nicht aktualisiert
**Lösung:**
- Prüfe ob Felder im Provider angelegt sind
- Prüfe Feld-Namen (Groß-/Kleinschreibung bei Brevo!)
- Prüfe API-Zugangsdaten

#### 2. E-Mail wird nicht versendet
**Lösung:**
- Prüfe ob Start-Tag konfiguriert ist
- Prüfe ob Kampagne im Provider aktiv ist
- Prüfe ob Kampagne auf richtigen Tag triggert
- Prüfe Logs: `grep "Tag.*erfolgreich" /var/log/php-error.log`

#### 3. Tag wird nicht gesetzt
**Lösung:**
- Prüfe API-Verbindung: Test-Button im Dashboard
- Prüfe ob Kontakt im Provider existiert
- Prüfe Tag-Name (keine Sonderzeichen!)

#### 4. Falscher Provider
**Lösung:**
- Nur EINE API-Konfiguration pro Kunde aktiv
- Alte Konfigurationen deaktivieren/löschen

## 📊 Monitoring

### Erfolgreiche Auslieferungen
```sql
SELECT 
    rd.reward_title,
    COUNT(*) as deliveries,
    DATE(rd.delivered_at) as date
FROM reward_deliveries rd
WHERE rd.delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(rd.delivered_at), rd.reward_title
ORDER BY date DESC;
```

### Fehlerhafte Benachrichtigungen
```sql
SELECT 
    lu.email,
    rd.reward_title,
    rd.delivered_at,
    rd.email_sent
FROM reward_deliveries rd
JOIN lead_users lu ON rd.lead_id = lu.id
WHERE rd.email_sent = 0
AND rd.delivered_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY rd.delivered_at DESC;
```

## 🎨 E-Mail-Vorlagen

Im Dashboard unter **Empfehlungsprogramm** findest du vorgefertigte E-Mail-Vorlagen:
- **Klassisch & Professionell**
- **Motivierend & Dynamisch**
- **Minimalistisch & Elegant**

Diese kannst du als Basis für deine Kampagnen im Provider verwenden.

## ✅ Checkliste für Go-Live

- [ ] Custom Fields im Provider angelegt
- [ ] API-Integration im Dashboard eingerichtet
- [ ] API-Verbindung getestet (grüner Haken)
- [ ] Start-Tag definiert
- [ ] E-Mail-Kampagne im Provider erstellt
- [ ] Kampagne auf richtigen Tag triggert
- [ ] Kampagne aktiviert
- [ ] Test-Lead durchgeführt
- [ ] Logs geprüft
- [ ] Belohnungs-E-Mail empfangen

## 📞 Support

Bei Problemen:
1. Logs prüfen
2. API-Verbindung testen
3. Custom Fields im Provider prüfen
4. Kampagnen-Status prüfen

Technische Details:
- System unterstützt ALLE gängigen Provider
- Automatisches Fallback auf System-E-Mail
- Detailliertes Logging für Debugging
- Provider-spezifische Feld-Mappings

# 🚀 Empfehlungsprogramm API-Integration

Vollständige Integration von Email-Marketing-Anbietern in das Empfehlungsprogramm mit automatischem Belohnungs-Email-Versand.

## 📋 Inhaltsverzeichnis

- [Übersicht](#übersicht)
- [Features](#features)
- [Installation](#installation)
- [Unterstützte Anbieter](#unterstützte-anbieter)
- [Konfiguration](#konfiguration)
- [Belohnungsstufen](#belohnungsstufen)
- [Automatischer Email-Versand](#automatischer-email-versand)
- [API-Endpunkte](#api-endpunkte)
- [Webhook-Integration](#webhook-integration)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Übersicht

Das erweiterte Empfehlungsprogramm ermöglicht:

1. **Email-Marketing-Integration**: Automatische Lead-Eintragung in Quentn, Klick-Tipp, GetResponse, Brevo, ActiveCampaign
2. **Belohnungsstufen**: Bis zu 50 konfigurierbare Belohnungsstufen pro Freebie
3. **Automatischer Email-Versand**: Belohnungs-Emails werden automatisch bei Erreichen einer Stufe versendet
4. **Tag-Management**: Automatisches Tagging von Leads basierend auf Belohnungsstufen
5. **Double Opt-in**: DSGVO-konforme Email-Bestätigung
6. **Tracking**: Vollständiges Tracking von Email-Versand, Öffnungen und Klicks

---

## ✨ Features

### Email-Marketing-Integration
- ✅ Quentn
- ✅ Klick-Tipp
- ✅ GetResponse
- ✅ Brevo (Sendinblue)
- ✅ ActiveCampaign

### Belohnungs-System
- 🎁 Bis zu 50 Belohnungsstufen
- 📧 Automatischer Email-Versand
- 🏷️ Automatisches Tag-Management
- 📎 Email-Anhänge (URLs)
- 🎨 Individuelles Email-Design
- 📊 Versand-Tracking

### Sicherheit & Compliance
- 🔒 Verschlüsselte API-Keys
- ✅ Double Opt-in Support
- 📝 Vollständiges Audit-Log
- 🇪🇺 DSGVO-konform

---

## 🔧 Installation

### Schritt 1: Datenbank-Migration

Öffne im Browser:
```
https://app.mehr-infos-jetzt.de/customer/migrations/migrate-referral-api-settings.html
```

Klicke auf **"Migration starten"** und warte bis alle 3 Schritte abgeschlossen sind:
1. ✅ Email-Marketing API Einstellungen
2. ✅ Erweiterte Belohnungsstufen
3. ✅ Lead Email-Tracking

### Schritt 2: API-Einstellungen konfigurieren

1. Gehe zu **Dashboard → Empfehlungsprogramm**
2. Klicke auf **"API-Einstellungen"** (neuer Button im Header)
3. Wähle deinen Email-Marketing-Anbieter
4. Trage API-Zugangsdaten ein
5. Konfiguriere Tags, Listen und Kampagnen
6. **"Speichern & Testen"** klicken

### Schritt 3: Belohnungsstufen einrichten

1. Gehe zu **Dashboard → Empfehlungsprogramm**
2. Wähle ein Freebie aus
3. Klicke auf **"Belohnungen verwalten"**
4. Erstelle Belohnungsstufen mit Email-Text
5. Aktiviere **"Auto-Zusendung"** Checkbox

---

## 📱 Unterstützte Anbieter

### Quentn

**Konfiguration:**
- API-Key: Von Quentn Dashboard → Einstellungen → API
- Basis-URL: `https://api.quentn.com/public/v1` (Standard)
- Start-Tag: z.B. `lead_empfehlungsprogramm`
- Kampagnen-ID: Optional für automatische Kampagnen

**Features:**
- ✅ Kontakt hinzufügen
- ✅ Tags zuweisen
- ✅ Kampagnen-Zuordnung
- ❌ Direkt-Email (nutze Kampagnen)

**Dokumentation:** [Quentn API Docs](https://help.quentn.com/hc/de/articles/4405815323537)

---

### Klick-Tipp

**Konfiguration:**
- Benutzername: Dein Klick-Tipp Login
- Passwort: Dein Klick-Tipp Passwort (wird als API-Key gespeichert)
- Start-Tag: z.B. `empfehlung_neu`
- Listen-ID: Optional

**Features:**
- ✅ Kontakt hinzufügen
- ✅ Tags zuweisen
- ✅ Listen-Zuordnung
- ❌ Direkt-Email (nutze Follow-up-Series)

**Dokumentation:** [Klick-Tipp API](https://www.klick-tipp.com/handbuch/api)

---

### GetResponse

**Konfiguration:**
- API-Key: Von GetResponse → Account → Integrations & API
- Kampagnen-ID: **Erforderlich!** (Liste in GetResponse)
- Start-Tag: Optional
- Sender-Email & Name: **Für Transaktional-Emails erforderlich**

**Features:**
- ✅ Kontakt hinzufügen
- ✅ Tags zuweisen
- ✅ Kampagnen-Zuordnung
- ✅ **Direkt-Email via Transactional API**

**Hinweis:** Transactional-Emails müssen in GetResponse aktiviert sein!

**Dokumentation:** [GetResponse API v3](https://apidocs.getresponse.com/v3)

---

### Brevo (Sendinblue)

**Konfiguration:**
- API-Key: Von Brevo → SMTP & API
- Listen-ID: Numeric ID der Liste (z.B. `12`)
- Sender-Email: **Muss in Brevo verifiziert sein!**
- Sender-Name: Anzeigename für Emails

**Features:**
- ✅ Kontakt hinzufügen
- ✅ Listen-Zuordnung
- ✅ Attribute/Tags setzen
- ✅ **Direkt-Email via SMTP API**
- ✅ Template-Support

**Hinweis:** Sender-Email muss verifiziert sein, sonst schlagen Emails fehl!

**Dokumentation:** [Brevo API](https://developers.brevo.com/docs)

---

### ActiveCampaign

**Konfiguration:**
- API-Key: Von ActiveCampaign → Settings → Developer
- Account-URL: **Deine spezifische URL** (z.B. `https://yourname.api-us1.com`)
- Listen-ID: Optional
- Start-Tag: Wird automatisch erstellt falls nicht vorhanden

**Features:**
- ✅ Kontakt hinzufügen
- ✅ Tags erstellen und zuweisen
- ✅ Listen-Zuordnung
- ✅ Custom Fields
- ❌ Direkt-Email (nutze Automations)

**Dokumentation:** [ActiveCampaign API](https://developers.activecampaign.com/reference)

---

## ⚙️ Konfiguration

### API-Einstellungen Seite

Die neue Seite `api-einstellungen.php` ist über das Empfehlungsprogramm erreichbar:

```
Dashboard → Empfehlungsprogramm → API-Einstellungen
```

**Felder:**

1. **Provider-Auswahl:**
   - Visuell: Karten-Interface mit Features
   - Klick auf Provider öffnet Konfigurationsformular

2. **API-Zugangsdaten:**
   - API-Key (Required)
   - Provider-spezifische Felder (Username, Account-URL, etc.)

3. **Listen & Tags:**
   - Start-Tag: Wird jedem neuen Lead zugewiesen
   - Listen-ID: Ziel-Liste für Leads
   - Kampagnen-ID: Für automatische Follow-ups

4. **Email-Versand:**
   - Sender-Email (für Provider mit Direkt-Email)
   - Sender-Name

5. **Double Opt-in:**
   - Checkbox: Double Opt-in aktivieren (empfohlen)
   - Formular-ID: Optional, für Provider-spezifische DOI-Formulare

**Test-Funktion:**
- Button **"Verbindung testen"**
- Prüft API-Verbindung
- Speichert Verifizierungsstatus
- Zeigt detaillierte Fehlermeldungen

---

## 🎁 Belohnungsstufen

### Belohnungen erstellen/bearbeiten

Über **Empfehlungsprogramm → Freebie → Belohnungen verwalten**

**Felder:**

1. **Grunddaten:**
   - Stufen-Level: 1-50
   - Stufen-Name: z.B. "Bronze", "Silber", "Gold"
   - Beschreibung: Optional
   - Erforderliche Empfehlungen: z.B. 3, 5, 10

2. **Belohnung:**
   - Typ: E-Book, PDF, Beratung, Kurs, Gutschein, Rabatt, etc.
   - Titel: z.B. "Kostenloses E-Book"
   - Beschreibung: Details zur Belohnung
   - Wert: z.B. "50€", "1h Beratung"

3. **Erweiterte Einstellungen:**
   - Download-URL: Link zum Download
   - Zugriffscode: Optional
   - Einlöse-Anweisungen: Wie Belohnung eingelöst wird
   - Icon & Farbe: Für visuelle Darstellung
   - **Auto-Zusendung:** ⚠️ **Wichtig!** Checkbox aktivieren für automatischen Versand

4. **Email-Konfiguration:** (neu!)
   - Email-Betreff: Custom Subject
   - Email-Text: HTML-Body mit Platzhaltern
   - Email-Template-ID: Provider-Template verwenden
   - Über API versenden: Checkbox (empfohlen)
   - Anhänge: URLs zu Dateien

**Platzhalter für Email-Text:**
```
{{name}} - Lead-Name
{{email}} - Lead-Email
{{tier_name}} - Belohnungsname
{{tier_level}} - Stufen-Nummer
{{required_referrals}} - Anzahl Empfehlungen
{{reward_title}} - Belohnungs-Titel
{{reward_description}} - Belohnungs-Beschreibung
{{reward_value}} - Wert
{{reward_instructions}} - Anweisungen
{{download_url}} - Download-Link
{{access_code}} - Zugriffscode
{{company_name}} - Firmenname des Kunden
```

**Standard-Email-Template:**

Falls kein Custom-Template angegeben, wird ein responsive HTML-Template verwendet:
- Header mit Gradient
- Belohnungs-Box
- Anweisungen
- Download-Button
- Zugriffscode
- Footer mit Firmenname

---

## 📧 Automatischer Email-Versand

### Ablauf

1. **Lead macht neue Empfehlung**
2. **Webhook wird getriggert:** `/api/webhooks/referral-success.php`
3. **System prüft Belohnungen:**
   - Lädt alle aktiven Belohnungen für den Kunden
   - Filtert nach `required_referrals <= current_referrals`
   - Prüft ob Email bereits versendet wurde
4. **Email-Versand:**
   - Erstellt Email-Log-Eintrag (Status: pending)
   - Lädt API-Einstellungen
   - Bereitet Email-Body vor (Platzhalter ersetzen)
   - Versendet via API
   - Aktualisiert Status: sent/failed
   - Fügt Tag hinzu (falls konfiguriert)
5. **Fehlerbehandlung:**
   - Bei Fehler: Status = failed
   - Retry-Counter erhöhen
   - Fehler in Log speichern
   - Automatischer Retry nach 1h (via Cronjob)

### Versand-Modi

**1. Automatisch via API** (empfohlen)
- Checkbox "Auto-Zusendung" + "Über API versenden" aktiv
- Email wird sofort versendet
- Status-Tracking in `lead_reward_emails`

**2. Manuell**
- "Auto-Zusendung" inaktiv ODER keine API konfiguriert
- Email wird vorbereitet (Status: pending)
- Kunde muss manuell versenden

**3. Verzögerter Versand**
- Bei API-Fehler: Status = failed
- Automatischer Retry via Cronjob
- Max 3 Versuche über 7 Tage

### Tracking

Jede Email wird getrackt in `lead_reward_emails`:

```sql
- send_status: pending, sent, failed, bounced
- send_method: api, smtp, manual
- api_provider: quentn, brevo, getresponse, ...
- api_message_id: Message-ID vom Provider
- sent_at: Versandzeitpunkt
- opened_at: Öffnung (falls Provider unterstützt)
- clicked_at: Klick (falls Provider unterstützt)
- open_count: Anzahl Öffnungen
- click_count: Anzahl Klicks
- error_message: Fehlermeldung bei Fehler
- retry_count: Anzahl Wiederholungen
```

---

## 🔗 API-Endpunkte

### Einstellungen speichern

**POST** `/api/email-settings/save.php`

Request:
```json
{
  "provider": "brevo",
  "api_key": "xkeysib-...",
  "start_tag": "lead_empfehlung",
  "list_id": "12",
  "double_optin_enabled": true,
  "sender_email": "info@example.com",
  "sender_name": "Meine Firma"
}
```

Response:
```json
{
  "success": true,
  "message": "API-Einstellungen gespeichert"
}
```

---

### Verbindung testen

**POST** `/api/email-settings/test.php`

Response:
```json
{
  "success": true,
  "message": "Verbindung zu Brevo erfolgreich",
  "details": {
    "email": "kunde@example.com",
    "company": "Meine Firma GmbH"
  }
}
```

---

### Einstellungen löschen

**POST** `/api/email-settings/delete.php`

Response:
```json
{
  "success": true,
  "message": "API-Einstellungen erfolgreich gelöscht"
}
```

---

### Webhook: Empfehlung erfolgreich

**POST** `/api/webhooks/referral-success.php`

Headers:
```
Content-Type: application/json
X-Webhook-Secret: your-webhook-secret
```

Request:
```json
{
  "lead_id": 123
}
```

Response:
```json
{
  "success": true,
  "message": "Belohnungen geprüft und 2 Email(s) versendet",
  "lead_id": 123,
  "lead_name": "Max Mustermann",
  "current_referrals": 5,
  "rewards_checked": 3,
  "emails_sent": 2,
  "details": [
    {
      "reward_id": 1,
      "reward_name": "Bronze",
      "required_referrals": 3,
      "success": true,
      "message": "Email erfolgreich versendet via brevo"
    },
    {
      "reward_id": 2,
      "reward_name": "Silber",
      "required_referrals": 5,
      "success": true,
      "message": "Email erfolgreich versendet via brevo"
    }
  ]
}
```

---

## 🤖 Webhook-Integration

### Automatischer Trigger

Um Belohnungs-Emails automatisch zu versenden, muss der Webhook nach jeder erfolgreichen Empfehlung aufgerufen werden.

**Option 1: In Lead-Registration einbauen**

In `/freebie/actions/register-lead.php` (oder ähnlich):

```php
// Nach erfolgreicher Empfehlung
if ($referral_registered) {
    // Webhook aufrufen
    $ch = curl_init('https://app.mehr-infos-jetzt.de/api/webhooks/referral-success.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Webhook-Secret: ' . getenv('WEBHOOK_SECRET')
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'lead_id' => $referrer_lead_id
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
}
```

**Option 2: Database Trigger**

```sql
DELIMITER $$

CREATE TRIGGER after_lead_referral_update
AFTER UPDATE ON lead_users
FOR EACH ROW
BEGIN
    IF NEW.successful_referrals > OLD.successful_referrals THEN
        -- Hier könnte ein externes Script aufgerufen werden
        -- Oder ein Queue-System befüllt werden
    END IF;
END$$

DELIMITER ;
```

**Option 3: Cronjob (empfohlen für Skalierung)**

```bash
# Jede Minute prüfen
* * * * * /usr/bin/php /path/to/check-pending-rewards.php

# check-pending-rewards.php
<?php
require_once 'config/database.php';
require_once 'api/rewards/reward-email-service.php';

$service = new RewardEmailService($pdo);

// Alle Leads mit neuen Empfehlungen der letzten Minute
$stmt = $pdo->query("
    SELECT id FROM lead_users
    WHERE successful_referrals_updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
");

while ($lead = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $service->checkAndSendRewards($lead['id']);
}
?>
```

### Retry-Mechanismus

**Cronjob für fehlgeschlagene Emails:**

```bash
# Täglich um 2 Uhr nachts
0 2 * * * /usr/bin/php /path/to/api/rewards/reward-email-service.php retry
```

---

## 🐛 Troubleshooting

### Problem: API-Verbindung fehlgeschlagen

**Lösung:**
1. API-Key korrekt eingegeben?
2. Bei GetResponse/ActiveCampaign: Account-URL korrekt?
3. Bei Brevo: Sender-Email verifiziert?
4. Firewall/Whitelist-Einstellungen prüfen

**Log prüfen:**
```sql
SELECT * FROM email_api_logs
WHERE customer_id = YOUR_ID
ORDER BY created_at DESC
LIMIT 10;
```

---

### Problem: Emails werden nicht versendet

**Checkliste:**
1. ✅ API-Einstellungen konfiguriert und verifiziert?
2. ✅ Belohnung hat "Auto-Zusendung" aktiviert?
3. ✅ Belohnung hat "Über API versenden" aktiviert?
4. ✅ Lead hat genug Empfehlungen?
5. ✅ Email wurde nicht bereits versendet?

**Status prüfen:**
```sql
SELECT * FROM lead_reward_emails
WHERE lead_id = LEAD_ID
ORDER BY created_at DESC;
```

**Manuell nachtrigger:**
```bash
php /path/to/api/rewards/reward-email-service.php check LEAD_ID
```

---

### Problem: Emails kommen als Spam an

**Lösungen:**
1. **SPF/DKIM/DMARC** Records für Sender-Domain einrichten
2. **Sender-Email verifizieren** bei Provider
3. **Warm-up-Phase**: Beginne mit wenigen Emails pro Tag
4. **Inhalt optimieren**: Keine Spam-Wörter, gutes Text/Bild-Verhältnis
5. **Unsubscribe-Link** einbauen (DSGVO)

---

### Problem: GetResponse sagt "Unauthorized"

**Lösung:**
- GetResponse API v3 erfordert `X-Auth-Token: api-key YOUR_KEY`
- Nicht nur `Authorization: Bearer`
- Key muss von GetResponse → Account → Integrations & API sein
- **Transactional Emails müssen aktiviert sein!**

---

### Problem: Quentn/Klick-Tipp - Keine Direkt-Emails

**Lösung:**
Diese Provider unterstützen keine Direkt-Emails via API. Alternativen:

**Option A: Kampagnen nutzen**
1. Erstelle eine Kampagne im Provider
2. Setze den entsprechenden Tag wenn Belohnung erreicht
3. Kampagne versendet automatisch Email

**Option B: Anderen Provider nutzen**
- GetResponse
- Brevo
- SMTP-Fallback implementieren

---

### Problem: Platzhalter werden nicht ersetzt

**Prüfen:**
1. Platzhalter korrekt geschrieben? `{{name}}` nicht `{name}`
2. Groß-/Kleinschreibung beachten
3. Leerzeichen in geschweiften Klammern vermeiden

**Verfügbare Platzhalter:**
```
{{name}}, {{email}}, {{tier_name}}, {{tier_level}}, 
{{required_referrals}}, {{reward_title}}, {{reward_description}}, 
{{reward_value}}, {{reward_instructions}}, {{download_url}}, 
{{access_code}}, {{company_name}}
```

---

### Problem: Performance bei vielen Leads

**Optimierungen:**

1. **Queue-System einführen:**
```php
// Statt direktem Versand
$stmt = $pdo->prepare("
    INSERT INTO email_queue (lead_id, reward_id, priority)
    VALUES (?, ?, 'normal')
");
```

2. **Batch-Processing:**
```bash
# Worker-Prozess
while true; do
    php process-email-queue.php --batch=50
    sleep 60
done
```

3. **Rate-Limiting:**
```php
// Max 100 Emails pro Minute
$rateLimiter = new RateLimiter(100, 60);
if ($rateLimiter->allow()) {
    $provider->sendEmail(...);
}
```

---

## 📊 Datenbank-Schema

### customer_email_api_settings

```sql
- id: INT PRIMARY KEY
- customer_id: INT (FK users.id)
- provider: VARCHAR(50) (quentn, klicktipp, getresponse, brevo, activecampaign)
- api_key: VARCHAR(500) (verschlüsselt!)
- api_secret: VARCHAR(500) (optional, z.B. OAuth)
- start_tag: VARCHAR(200)
- list_id: VARCHAR(200)
- campaign_id: VARCHAR(200)
- double_optin_enabled: BOOLEAN
- double_optin_form_id: VARCHAR(200)
- webhook_url: VARCHAR(500)
- webhook_secret: VARCHAR(255)
- is_active: BOOLEAN
- is_verified: BOOLEAN
- last_verified_at: DATETIME
- verification_error: TEXT
- custom_settings: JSON (provider-spezifische Einstellungen)
- created_at: DATETIME
- updated_at: DATETIME
```

### reward_definitions (erweitert)

```sql
-- Neue Felder:
- email_subject: VARCHAR(200)
- email_body: TEXT (HTML mit Platzhaltern)
- email_template_id: INT (Provider-Template)
- auto_send_email: BOOLEAN
- send_via_api: BOOLEAN
- attachment_urls: JSON
- notification_webhook: VARCHAR(500)
```

### lead_reward_emails

```sql
- id: INT PRIMARY KEY
- lead_id: INT (FK lead_users.id)
- customer_id: INT (FK users.id)
- reward_id: INT (FK reward_definitions.id)
- email_to: VARCHAR(255)
- email_subject: VARCHAR(200)
- email_body: TEXT
- send_status: ENUM (pending, sent, failed, bounced)
- send_method: ENUM (api, smtp, manual)
- api_provider: VARCHAR(50)
- api_message_id: VARCHAR(255)
- sent_at: DATETIME
- opened_at: DATETIME
- clicked_at: DATETIME
- open_count: INT
- click_count: INT
- error_message: TEXT
- retry_count: INT
- max_retries: INT (default: 3)
- created_at: DATETIME
- updated_at: DATETIME
```

### email_api_logs

```sql
- id: INT PRIMARY KEY
- customer_id: INT (FK users.id)
- provider: VARCHAR(50)
- endpoint: VARCHAR(255)
- method: VARCHAR(10)
- request_payload: JSON
- response_code: INT
- response_body: JSON
- success: BOOLEAN
- error_message: TEXT
- duration_ms: INT
- created_at: DATETIME
```

---

## 🎉 Zusammenfassung

Das erweiterte Empfehlungsprogramm bietet:

✅ **Vollständige Email-Marketing-Integration** für 5 deutsche Anbieter
✅ **Automatischer Belohnungs-Email-Versand** bei Stufenerreichung  
✅ **Flexibles Belohnungssystem** mit bis zu 50 Stufen
✅ **Umfassendes Tracking** von Versand bis Klick
✅ **DSGVO-konform** mit Double Opt-in
✅ **Skalierbar** mit Queue-System und Rate-Limiting
✅ **Benutzerfreundlich** mit visueller Konfiguration

---

## 📞 Support

Bei Fragen oder Problemen:
1. **Logs prüfen:** `email_api_logs` und `lead_reward_emails`
2. **Test-Endpunkt nutzen:** `/api/email-settings/test.php`
3. **Provider-Dokumentation:** Siehe jeweilige Anbieter-Sektion
4. **Manual-Trigger:** `php reward-email-service.php check LEAD_ID`

---

**Version:** 1.0.0  
**Letzte Aktualisierung:** November 2025  
**Autor:** Michael - mehr-infos-jetzt.de
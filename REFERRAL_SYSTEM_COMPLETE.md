# 🎁 VOLLSTÄNDIGES EMPFEHLUNGSPROGRAMM-SYSTEM
## DSGVO-konformes Referral-System für KI-Lead

---

## 📋 INHALTSVERZEICHNIS

1. [Systemübersicht](#systemübersicht)
2. [Architektur](#architektur)
3. [Installation & Setup](#installation--setup)
4. [Funktionsweise](#funktionsweise)
5. [Customer-Dashboard](#customer-dashboard)
6. [Admin-Monitoring](#admin-monitoring)
7. [API-Endpoints](#api-endpoints)
8. [E-Mail-System](#e-mail-system)
9. [Sicherheit & DSGVO](#sicherheit--dsgvo)
10. [Betrugsschutz](#betrugsschutz)
11. [Troubleshooting](#troubleshooting)

---

## SYSTEMÜBERSICHT

### Was ist das Empfehlungsprogramm?

Das Empfehlungsprogramm ist ein vollständig integriertes, DSGVO-konformes System, das es Customers ermöglicht:

- ✅ Eigene Referral-Links zu erstellen und zu teilen
- ✅ Klicks und Conversions in Echtzeit zu tracken
- ✅ Leads über Freebie- und Danke-Seiten zu erfassen
- ✅ Automatische Belohnungs-E-Mails an Leads zu senden
- ✅ Tracking-Pixel für externe Seiten zu nutzen

### Wichtige Grundsätze

1. **Customer-Zentrierung**: Jeder Customer verwaltet sein eigenes Programm
2. **DSGVO-Konformität**: Alle Daten werden verschlüsselt und datenschutzkonform gespeichert
3. **Technischer Dienstleister**: Das KI-Lead-System tritt nur als Infrastruktur auf
4. **Fraud-Protection**: Automatische Erkennung verdächtiger Aktivitäten
5. **White-Label**: E-Mails werden im Namen des Customers versendet

---

## ARCHITEKTUR

### Datenbank-Struktur

```
customers (erweitert)
├── referral_enabled (BOOLEAN)
├── company_name (VARCHAR)
├── company_email (VARCHAR)
├── company_imprint_html (TEXT)
└── referral_code (VARCHAR, UNIQUE)

referral_clicks
├── customer_id → customers.id
├── ref_code (Werber-Code)
├── ip_address_hash (SHA256)
├── fingerprint (Hash)
└── created_at

referral_conversions
├── customer_id → customers.id
├── ref_code
├── source (thankyou/pixel/api)
├── suspicious (BOOLEAN)
├── time_to_convert (INT seconds)
└── created_at

referral_leads
├── customer_id → customers.id
├── ref_code (Werber-Code)
├── email (Lead-E-Mail)
├── email_hash (SHA256)
├── confirmed (BOOLEAN)
├── reward_notified (BOOLEAN)
├── gdpr_consent (BOOLEAN)
└── created_at

referral_stats (aggregiert)
├── customer_id (UNIQUE)
├── total_clicks
├── unique_clicks
├── total_conversions
├── suspicious_conversions
├── total_leads
├── confirmed_leads
├── conversion_rate
└── updated_at

referral_rewards (Konfiguration)
├── customer_id (UNIQUE)
├── reward_type (email/none/webhook)
├── goal_referrals (INT)
├── reward_email_subject
├── reward_email_template
├── auto_send_reward (BOOLEAN)
└── webhook_url

referral_fraud_log
├── customer_id
├── ref_code
├── fraud_type
├── ip_address_hash
├── additional_data (JSON)
└── created_at
```

### URL-Struktur

```
Freebie-Seite:
https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=ABC123

Danke-Seite:
https://app.mehr-infos-jetzt.de/thankyou.php?customer=123&ref=ABC123

Tracking-Pixel:
https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=ABC123
```

---

## INSTALLATION & SETUP

### 1. Datenbank-Migration ausführen

```bash
# Migration ist bereits vorhanden
# Wird automatisch über database/migrations/004_referral_system.sql geladen
```

### 2. Cron-Job für Belohnungs-E-Mails einrichten

```bash
# Crontab bearbeiten
crontab -e

# Einmal täglich um 10:00 Uhr ausführen
0 10 * * * php /path/to/scripts/send-reward-emails.php >> /path/to/logs/cron.log 2>&1
```

### 3. Logs-Ordner erstellen

```bash
mkdir -p logs
chmod 755 logs
```

### 4. E-Mail-Konfiguration überprüfen

Stellen Sie sicher, dass PHP `mail()` oder SMTP korrekt konfiguriert ist.

---

## FUNKTIONSWEISE

### Tracking-Flow

```
1. FREEBIE-SEITE (mit ?ref=CODE)
   ↓
   [Klick-Tracking]
   • IP-Hash speichern
   • Fingerprint erstellen
   • LocalStorage-Flag setzen
   • ref in sessionStorage speichern
   ↓
2. DANKE-SEITE (ref aus Session)
   ↓
   [Conversion-Tracking]
   • Zeit-Check (< 5s = verdächtig)
   • IP-Duplikat-Check
   • Fingerprint-Check
   • Conversion speichern
   ↓
3. EMPFEHLUNGSPROGRAMM-FORMULAR
   ↓
   [Lead-Registrierung]
   • E-Mail erfassen
   • DSGVO-Consent prüfen
   • Lead speichern
   • Bestätigungs-E-Mail senden
   ↓
4. CRON-JOB (täglich)
   ↓
   [Belohnungs-E-Mails]
   • Goals prüfen
   • Unbenachrichtigte Leads finden
   • E-Mails versenden
   • Status aktualisieren
```

### Anti-Fraud-Mechanismen

| Mechanismus | Beschreibung | Schwellwert |
|-------------|--------------|-------------|
| **IP-Limitierung** | Max. 1 Klick/Conversion pro IP pro 24h | 24 Stunden |
| **Fingerprint** | Hash aus IP + UserAgent verhindert Doppelklicks | Permanent |
| **Zeit-Check** | Freebie → Danke < 5 Sekunden = verdächtig | 5 Sekunden |
| **LocalStorage** | Client-seitiger Schutz vor sofortigen Wiederholungen | Session-basiert |
| **Rate-Limiting** | API-Calls limitiert auf 100/Stunde | 100/h |

---

## CUSTOMER-DASHBOARD

### Zugriff

```
URL: /customer/dashboard.php?section=empfehlungsprogramm
```

### Funktionen

#### 1. Aktivierung/Deaktivierung

```javascript
// Toggle-Button im Dashboard
// API: /api/referral/toggle.php
```

#### 2. Statistik-Anzeige

- **Gesamt-Klicks** (unique/total)
- **Conversions** (valide/verdächtig)
- **Registrierte Leads** (total/bestätigt)
- **Conversion Rate** (automatisch berechnet)

#### 3. Referral-Links

```
Freebie-Link:
https://app.mehr-infos-jetzt.de/freebie.php?customer={id}&ref={code}

Referral-Code:
{CUSTOMER_UNIQUE_CODE}
```

#### 4. Tracking-Pixel

```html
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer={id}&ref={code}" 
     width="1" height="1" style="display:none;">
```

#### 5. Firmendaten & Impressum

Customers können hinterlegen:
- Firmenname
- E-Mail-Adresse (Absender)
- Impressum (HTML)

Diese Daten werden in allen E-Mails verwendet.

#### 6. Tabs

- **Letzte Klicks**: Zeigt die 20 neuesten Klicks
- **Conversions**: Zeigt alle Conversions mit Status
- **Leads**: Zeigt alle registrierten Leads

---

## ADMIN-MONITORING

### Zugriff

```
URL: /admin/dashboard.php?section=referral-overview
```

### Features

#### 1. Gesamt-Übersicht

- Anzahl aktiver Programme
- Gesamt-Klicks (über alle Customers)
- Gesamt-Conversions
- Registrierte Leads
- Durchschnittliche Conversion Rate

#### 2. Customer-Liste

Tabelle mit allen Customers und deren Performance:

| Spalte | Beschreibung |
|--------|--------------|
| Customer | Name & E-Mail |
| Referral-Code | Eindeutiger Code |
| Status | Aktiv/Inaktiv |
| Klicks | Total (Unique) |
| Conversions | Anzahl |
| Verdächtig | Fraud-Markierungen |
| Leads | Total (Bestätigt) |
| Conv. Rate | Prozentsatz |
| Letzte Aktivität | Timestamp |

#### 3. Filter & Suche

- **Suche**: E-Mail oder Firmenname
- **Status-Filter**: Alle/Nur Aktive/Nur Inaktive
- **Sortierung**: Nach Conversions, Klicks, Leads, Rate

#### 4. Detail-Ansichten

##### Customer-Details

```javascript
viewDetails(customerId)
```

Zeigt:
- Letzte 10 Klicks
- Letzte 10 Conversions (mit Suspicious-Markierung)
- Alle registrierten Leads

##### Fraud-Log

```javascript
viewFraudLog(customerId)
```

Zeigt alle Betrugsversuche:
- Typ (fast_conversion, duplicate_ip, etc.)
- Zeitstempel
- IP-Hash
- Additional Data (JSON)

#### 5. Export

```
CSV-Export: /api/referral/export-stats.php
```

Exportiert alle Statistiken als CSV-Datei.

---

## API-ENDPOINTS

### 1. Track Click

```
POST /api/referral/track-click.php

Body:
{
  "customer_id": 123,
  "ref_code": "ABC123",
  "referer": "https://example.com"
}

Response:
{
  "success": true,
  "message": "Click tracked"
}
```

### 2. Track Conversion

```
POST /api/referral/track-conversion.php

Body:
{
  "customer_id": 123,
  "ref_code": "ABC123",
  "source": "thankyou",
  "time_to_convert": 30
}

Response:
{
  "success": true,
  "message": "Conversion tracked",
  "suspicious": false
}
```

### 3. Register Lead

```
POST /api/referral/register-lead.php

Body:
{
  "customer_id": 123,
  "ref_code": "ABC123",
  "email": "lead@example.com",
  "gdpr_consent": true
}

Response:
{
  "success": true,
  "message": "Lead registered"
}
```

### 4. Toggle Program

```
POST /api/referral/toggle.php

Body:
{
  "enabled": true
}

Response:
{
  "success": true,
  "enabled": true,
  "message": "Programm aktiviert"
}
```

### 5. Update Company Data

```
POST /api/referral/update-company.php

Body:
{
  "company_name": "Firma GmbH",
  "company_email": "info@firma.de",
  "company_imprint_html": "<p>Impressum...</p>"
}

Response:
{
  "success": true,
  "message": "Firmendaten aktualisiert"
}
```

### 6. Get Stats

```
GET /api/referral/get-stats.php

Response:
{
  "success": true,
  "data": {
    "enabled": true,
    "ref_code": "ABC123",
    "stats": {
      "total_clicks": 150,
      "unique_clicks": 120,
      "total_conversions": 45,
      "suspicious_conversions": 2,
      "total_leads": 30,
      "confirmed_leads": 25,
      "conversion_rate": 30.0
    },
    "recent_clicks": [...],
    "recent_conversions": [...],
    "leads": [...]
  }
}
```

### 7. Tracking Pixel

```
GET /api/referral/track.php?customer=123&ref=ABC123

Returns: 1x1 transparent GIF
Triggers: Conversion-Tracking
```

---

## E-MAIL-SYSTEM

### Bestätigungs-E-Mail (Lead-Registrierung)

Wird automatisch gesendet nach Lead-Registrierung.

**Absender**: `{company_name}` <`{company_email}`>
**Betreff**: "Willkommen im Empfehlungsprogramm"

### Belohnungs-E-Mail (Cron-Job)

Wird automatisch gesendet, wenn `goal_referrals` erreicht ist.

**Absender**: `{company_name}` <`{company_email}`>
**Betreff**: `{reward_email_subject}` (anpassbar)
**Template**: `{reward_email_template}` (anpassbar)

### E-Mail-Template-Struktur

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎉 Herzlichen Glückwunsch!</h1>
        </div>
        <div class='content'>
            {TEMPLATE_CONTENT}
        </div>
        <div class='footer'>
            <hr>
            <small>
            Diese E-Mail wurde im Rahmen des Empfehlungsprogramms von {company_name} versendet.<br><br>
            {company_imprint_html}
            </small>
        </div>
    </div>
</body>
</html>
```

### Fallback-Regelung

Wenn `company_imprint_html` leer ist:

```html
<strong>KI-Lead-System</strong><br>
Technischer Dienstleister<br>
E-Mail: support@mehr-infos-jetzt.de
```

---

## SICHERHEIT & DSGVO

### Datenschutz-Maßnahmen

#### 1. IP-Adressen

```php
// IMMER als SHA256-Hash gespeichert
$salt = 'YOUR_SECRET_SALT';
$ip_hash = hash('sha256', $ip . $salt);
```

#### 2. E-Mail-Deduplizierung

```php
// E-Mails werden zusätzlich gehasht
$email_hash = hash('sha256', strtolower($email));
```

#### 3. GDPR-Consent

```sql
-- Jede Lead-Registrierung erfordert explizite Zustimmung
gdpr_consent BOOLEAN DEFAULT TRUE
gdpr_consent_date DATETIME DEFAULT CURRENT_TIMESTAMP
```

#### 4. Kaskadierende Löschung

```sql
-- Bei Customer-Löschung werden ALLE Daten automatisch gelöscht
ON DELETE CASCADE
```

### Rechtliche Hinweise

1. **Auftragsverarbeitung (Art. 28 DSGVO)**
   - KI-Lead-System = Auftragsverarbeiter
   - Customer = Verantwortlicher
   - AV-Vertrag erforderlich

2. **Impressumspflicht**
   - E-Mails enthalten Customer-Impressum
   - Fallback: KI-Lead-System-Impressum

3. **Einwilligungen**
   - Double-Opt-In optional (confirmed-Flag)
   - GDPR-Checkbox obligatorisch

4. **Datenminimierung**
   - Nur notwendige Daten gespeichert
   - IPs gehasht, nicht im Klartext

---

## BETRUGSSCHUTZ

### Erkennungsalgorithmen

#### 1. Fast Conversion Detection

```javascript
if (time_to_convert < 5) {
  suspicious = true;
  fraud_type = 'fast_conversion';
}
```

#### 2. IP-Duplikat-Check

```sql
SELECT COUNT(*) 
FROM referral_clicks 
WHERE ip_address_hash = ? 
  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
```

#### 3. Fingerprint-Abgleich

```javascript
const fingerprint = md5(ip + userAgent);
```

#### 4. Rate-Limiting

```php
// Max 100 API-Calls pro Stunde pro IP
if ($call_count > 100) {
  return ['success' => false, 'message' => 'Rate limit exceeded'];
}
```

### Fraud-Log

Alle Betrugsversuche werden protokolliert:

```sql
INSERT INTO referral_fraud_log (
  customer_id,
  ref_code,
  fraud_type,
  ip_address_hash,
  additional_data
) VALUES (?, ?, ?, ?, ?)
```

---

## TROUBLESHOOTING

### Problem: Klicks werden nicht getrackt

**Mögliche Ursachen:**
1. ref-Parameter fehlt in URL
2. LocalStorage blockiert (Inkognito-Modus)
3. 24h-Limit erreicht (gleiche IP)
4. JavaScript deaktiviert

**Lösung:**
```javascript
// Browser-Console überprüfen
console.log('Referral Config:', REFERRAL_CONFIG);
console.log('SessionStorage:', sessionStorage.getItem('pending_ref_code'));
```

### Problem: Conversions werden als "verdächtig" markiert

**Mögliche Ursachen:**
1. Zeit < 5 Sekunden (Bot-Verdacht)
2. Gleiche IP wie vorheriger Klick
3. Identischer Fingerprint

**Lösung:**
- Prüfe Fraud-Log im Admin-Dashboard
- Bei Fehlalarmen: Zeit-Schwellwert anpassen

### Problem: E-Mails werden nicht versendet

**Mögliche Ursachen:**
1. PHP `mail()` nicht konfiguriert
2. Cron-Job läuft nicht
3. `auto_send_reward` = FALSE

**Lösung:**
```bash
# Test-E-Mail senden
php scripts/send-reward-emails.php

# Cron-Job Status prüfen
crontab -l

# Logs überprüfen
tail -f logs/reward-emails-$(date +%Y-%m-%d).log
```

### Problem: Dashboard zeigt falsche Statistiken

**Mögliche Ursachen:**
1. Cache nicht aktualisiert
2. referral_stats nicht synchronisiert

**Lösung:**
```sql
-- Stats neu berechnen
UPDATE referral_stats rs
JOIN (
  SELECT customer_id, COUNT(*) as cnt
  FROM referral_clicks
  WHERE customer_id = ?
) c ON rs.customer_id = c.customer_id
SET rs.total_clicks = c.cnt;
```

### Problem: Tracking-Pixel funktioniert nicht

**Mögliche Ursachen:**
1. Pixel-Code falsch eingebettet
2. CORS-Probleme
3. ref-Parameter fehlt

**Lösung:**
```html
<!-- Korrekter Pixel-Code -->
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=ABC123" 
     width="1" height="1" style="display:none;" alt="">
```

---

## WICHTIGE HINWEISE

### ⚠️ Vor dem Go-Live

- [ ] Datenbank-Migration ausgeführt
- [ ] Cron-Job eingerichtet
- [ ] E-Mail-Versand getestet
- [ ] DSGVO-Texte aktualisiert
- [ ] Impressum hinterlegt
- [ ] Tracking auf Test-Seiten geprüft

### 🔒 Sicherheits-Checkliste

- [ ] Salt für IP-Hashing gesetzt
- [ ] API-Rate-Limiting aktiv
- [ ] HTTPS erzwungen
- [ ] Logs-Ordner geschützt
- [ ] Datenbank-Backups aktiviert

### 📊 Performance-Optimierung

- [ ] Indizes auf allen Referral-Tabellen
- [ ] Alte Logs regelmäßig archivieren
- [ ] Session-Storage in Redis (optional)
- [ ] CDN für Tracking-Pixel (optional)

---

## SUPPORT & ERWEITERUNGEN

### Geplante Features (Roadmap)

- [ ] Multi-Tier Rewards (Bronze/Silver/Gold)
- [ ] Webhook-Integration für externe CRMs
- [ ] A/B-Testing für E-Mail-Templates
- [ ] Grafische Reporting-Dashboards
- [ ] SMS-Benachrichtigungen (opt-in)
- [ ] Internationalisierung (i18n)

### Kontakt

Bei Fragen oder Problemen:
- **E-Mail**: support@mehr-infos-jetzt.de
- **Dokumentation**: /docs/REFERRAL_SYSTEM_README.md
- **GitHub Issues**: [Link zum Repository]

---

**Version**: 1.0.0  
**Letzte Aktualisierung**: 03.11.2025  
**Autor**: KI-Lead Development Team

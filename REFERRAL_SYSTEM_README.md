# 🎁 Referral System - Empfehlungsprogramm

## Übersicht

Vollständiges, DSGVO-konformes Empfehlungsprogramm-System für das KI-Lead-System. Kunden können ihr eigenes Empfehlungsprogramm betreiben, Tracking durchführen und Leads sammeln.

### ✨ Features

- **🔒 DSGVO-konform**: IP-Hashing, verschlüsselte Daten, Einwilligungsverwaltung
- **🎯 Multi-Tracking**: Freebie-Links, Danke-Seiten, externe Tracking-Pixel
- **🛡️ Anti-Fraud**: Automatische Erkennung verdächtiger Aktivitäten
- **📊 Echtzeit-Statistiken**: Live-Dashboard mit Charts und Metriken
- **📧 E-Mail-System**: Automatische Bestätigungs- und Belohnungs-E-Mails
- **👨‍💼 Customer-Self-Service**: Vollständige Verwaltung im eigenen Dashboard
- **👀 Admin-Monitoring**: Read-Only-Übersicht aller Aktivitäten

---

## 📋 Installation

### Voraussetzungen

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Bestehendes KI-Lead-System

### Schritt 1: Setup ausführen

```bash
php setup/setup-referral-system.php
```

Das Setup-Script:
- ✅ Erstellt alle Datenbank-Tabellen
- ✅ Generiert Referral-Codes für existierende Kunden
- ✅ Initialisiert Statistiken
- ✅ Prüft Installation

### Schritt 2: Navigation integrieren

**Customer-Dashboard** (`customer/dashboard.php`):
```php
<li>
    <a href="?section=empfehlungsprogramm" class="nav-link">
        🎁 Empfehlungsprogramm
    </a>
</li>
```

**Admin-Dashboard** (`admin/dashboard.php`):
```php
<li>
    <a href="?section=referral-overview" class="nav-link">
        🎯 Referral-Übersicht
    </a>
</li>
```

### Schritt 3: Tracking-Script integrieren

**In allen Freebie-Seiten** (`freebie/*.php`):
```html
<script src="/assets/js/referral-tracking.js"></script>
```

**In allen Danke-Seiten** (`thankyou.php`, `freebie/thankyou.php`):
```html
<script src="/assets/js/referral-tracking.js"></script>
<div id="referral-form-container"></div>
<script>
if (<?php echo $customer['referral_enabled'] ? 'true' : 'false'; ?>) {
    ReferralTracker.showReferralForm({
        customer_id: <?php echo $customer_id; ?>,
        ref: '<?php echo $_GET['ref'] ?? ''; ?>'
    });
}
</script>
```

---

## 🚀 Nutzung

### Für Customers

#### 1. Empfehlungsprogramm aktivieren

1. Dashboard → Empfehlungsprogramm
2. Toggle auf "Aktiviert" stellen
3. Firmendaten & Impressum eingeben (für E-Mails)

#### 2. Referral-Links teilen

Drei Möglichkeiten:

**A) Direkter Referral-Link** (am einfachsten):
```
https://app.mehr-infos-jetzt.de/freebie.php?customer=123&ref=REF000123ABC
```

**B) Parameter an eigene URLs anhängen**:
```
https://ihre-seite.de/angebot?ref=REF000123ABC
```

**C) Tracking-Pixel für externe Seiten**:
```html
<img src="https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=123&ref=REF000123ABC" 
     width="1" height="1" style="display:none;">
```

#### 3. Statistiken ansehen

Dashboard zeigt live:
- 📊 Klicks (gesamt & unique)
- ✅ Conversions
- 👥 Registrierte Leads
- 📈 Conversion Rate
- ⚠️ Verdächtige Aktivitäten

#### 4. Leads verwalten

Alle Leads werden automatisch:
- ✉️ Per E-Mail benachrichtigt (mit Ihrem Impressum)
- ✅ Mit Double-Opt-In bestätigt
- 📋 Im Dashboard angezeigt

---

## 🛡️ Anti-Fraud-System

### Automatische Erkennung

Das System erkennt automatisch:

1. **Zu schnelle Conversions** (< 5 Sekunden)
2. **Duplicate IPs** (max. 1 Klick/Conversion pro 24h)
3. **Fingerprint-Duplikate** (Browser + IP-Hash)
4. **Rate Limiting** (max. 10 Klicks pro IP/Tag)

### Suspicious Events

Verdächtige Conversions werden markiert aber **nicht blockiert**:
- ⚠️ Erscheinen in Statistiken als "verdächtig"
- 📝 Werden im Fraud-Log protokolliert
- 👀 Admin kann Details einsehen

### DSGVO-Konformität

- ✅ IPs werden als SHA256-Hash gespeichert
- ✅ E-Mails werden zusätzlich gehasht (Deduplizierung)
- ✅ Einwilligungen mit Zeitstempel
- ✅ Löschung kaskadierend bei Customer-Löschung
- ✅ Kein Tracking ohne explizite Einwilligung

---

## 📊 Datenbank-Struktur

### Haupttabellen

| Tabelle | Zweck |
|---------|-------|
| `referral_clicks` | Tracking von Klicks |
| `referral_conversions` | Erfolgreiche Conversions |
| `referral_leads` | Registrierte Teilnehmer |
| `referral_stats` | Aggregierte Statistiken |
| `referral_rewards` | Belohnungs-Konfiguration |
| `referral_fraud_log` | Betrugsversuche |

### Customer-Erweiterung

```sql
ALTER TABLE customers ADD COLUMN referral_enabled BOOLEAN;
ALTER TABLE customers ADD COLUMN referral_code VARCHAR(50);
ALTER TABLE customers ADD COLUMN company_name VARCHAR(255);
ALTER TABLE customers ADD COLUMN company_email VARCHAR(255);
ALTER TABLE customers ADD COLUMN company_imprint_html TEXT;
```

---

## 🔌 API-Endpoints

### Public APIs

| Endpoint | Methode | Zweck |
|----------|---------|-------|
| `/api/referral/track-click.php` | POST | Klick tracken |
| `/api/referral/track-conversion.php` | POST | Conversion tracken |
| `/api/referral/track.php` | GET | Tracking-Pixel (1x1 GIF) |
| `/api/referral/register-lead.php` | POST | Lead registrieren |
| `/api/referral/confirm-lead.php` | GET | E-Mail bestätigen |

### Customer APIs (Auth required)

| Endpoint | Methode | Zweck |
|----------|---------|-------|
| `/api/referral/get-stats.php` | GET | Statistiken abrufen |
| `/api/referral/toggle.php` | POST | Programm aktivieren/deaktivieren |
| `/api/referral/update-company.php` | POST | Firmendaten aktualisieren |

### Admin APIs (Admin-Auth required)

| Endpoint | Methode | Zweck |
|----------|---------|-------|
| `/api/referral/get-customer-details.php` | GET | Customer-Details |
| `/api/referral/get-fraud-log.php` | GET | Fraud-Log anzeigen |
| `/api/referral/export-stats.php` | GET | CSV-Export |

---

## 📧 E-Mail-System

### Automatische E-Mails

1. **Bestätigungsmail** (bei Lead-Registrierung)
   - Absender: Customer (mit Impressum)
   - Enthält: Bestätigungslink
   - Double-Opt-In

2. **Belohnungsmail** (optional, via Cron)
   - Wird gesendet wenn Goal erreicht
   - Konfigurierbar im Dashboard
   - Mit Customer-Branding

### E-Mail-Konfiguration

**Standard**: PHP `mail()` Funktion

**Empfohlen**: SMTP-Konfiguration in `config/settings.php`:
```php
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'password');
define('SMTP_FROM_NAME', 'KI-Lead-System');
```

---

## 🎨 Frontend-Integration

### Auto-Tracking

Das Tracking-Script erkennt automatisch:
- Freebie-Seiten → Track Click
- Danke-Seiten → Track Conversion
- URL-Parameter `?ref=...` → Speichert in SessionStorage

### Manuelles Tracking

```javascript
// Klick tracken
ReferralTracker.trackClick({
    customer_id: 123,
    ref: 'REF000123ABC'
});

// Conversion tracken
ReferralTracker.trackConversion({
    customer_id: 123,
    ref: 'REF000123ABC',
    source: 'thankyou' // oder 'pixel'
});

// Formular anzeigen
ReferralTracker.showReferralForm({
    customer_id: 123,
    ref: 'REF000123ABC',
    container_id: 'referral-form-container'
});
```

---

## 🔧 Konfiguration

### Rate Limits (in `ReferralHelper.php`)

```php
private const CLICK_RATE_LIMIT = 24; // Stunden
private const CONVERSION_RATE_LIMIT = 24; // Stunden
private const SUSPICIOUS_CONVERSION_TIME = 5; // Sekunden
private const MAX_CLICKS_PER_IP_PER_DAY = 10;
```

### Salt für IP-Hashing

Setze in `.env` oder als Umgebungsvariable:
```bash
REFERRAL_SALT="IhrGeheimerSaltWert2025"
```

---

## 📈 Monitoring & Analytics

### Admin-Dashboard

- 📊 Gesamt-Übersicht aller Programme
- 🔍 Filter & Suche nach Customers
- 📥 CSV-Export
- 🚨 Fraud-Log-Ansicht
- 📊 Conversion-Rate-Ranking

### Customer-Dashboard

- 📈 Echtzeit-Statistiken
- 📋 Letzte Klicks/Conversions
- 👥 Lead-Management
- 🎯 Tracking-Links & Pixel
- ⚙️ Firmendaten-Verwaltung

---

## 🔄 Cron-Jobs (Optional)

### Belohnungs-E-Mails senden

```bash
# Täglich um 10:00 Uhr
0 10 * * * php /path/to/scripts/send-reward-emails.php
```

### Alte Daten bereinigen (DSGVO)

```bash
# Monatlich am 1.
0 0 1 * * php /path/to/scripts/cleanup-old-data.php
```

---

## 🐛 Troubleshooting

### Problem: Tracking funktioniert nicht

**Lösung:**
1. Prüfe Browser-Konsole auf JavaScript-Fehler
2. Prüfe ob `referral-tracking.js` geladen wird
3. Prüfe URL-Parameter: `?customer=X&ref=Y`
4. Prüfe ob Programm aktiviert ist

### Problem: E-Mails kommen nicht an

**Lösung:**
1. Prüfe PHP-Mail-Funktion: `php -r "mail('test@example.com', 'Test', 'Test');"`
2. Konfiguriere SMTP statt PHP-Mail
3. Prüfe Spam-Ordner
4. Prüfe Server-Mail-Logs

### Problem: Verdächtige Conversions

**Lösung:**
1. Normal bei Tests (< 5 Sekunden)
2. Prüfe Fraud-Log im Admin
3. Erhöhe `SUSPICIOUS_CONVERSION_TIME` wenn nötig

---

## 📝 Changelog

### Version 1.0.0 (2025-11-03)

- ✨ Initiales Release
- 🔒 DSGVO-konforme Implementierung
- 🎯 Multi-Channel-Tracking
- 🛡️ Anti-Fraud-System
- 📊 Echtzeit-Dashboard
- 📧 E-Mail-Automatisierung
- 👀 Admin-Monitoring

---

## 🤝 Support

Bei Fragen oder Problemen:

1. Prüfe diese README
2. Prüfe die Code-Dokumentation
3. Kontaktiere den Support

---

## 📜 Lizenz

Proprietär - Nur für KI-Lead-System

---

**Made with ❤️ for KI-Lead-System**

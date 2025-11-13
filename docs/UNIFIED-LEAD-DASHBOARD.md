# Vereintes Lead-Dashboard System

## 📋 Übersicht

Das vereinte Lead-Dashboard kombiniert mehrere Features in einer nahtlosen Benutzererfahrung:

- **Freebie-Kurse**: Übersicht aller verfügbaren Kurse mit Mockups
- **Videoplayer**: Integrierter Player mit Drip-Content
- **Empfehlungsprogramm**: Vollständige Integration mit Belohnungen
- **One-Click-Login**: Sicherer, tokenbasierter Zugang

## 🚀 Installation

### 1. Datenbank-Migration ausführen

```bash
# Im Browser aufrufen:
https://app.mehr-infos-jetzt.de/migrations/unified-lead-dashboard.php
```

Die Migration erstellt/prüft folgende Tabellen:
- `lead_login_tokens` - Für One-Click-Login
- `lead_users` - Lead-Benutzer
- `lead_referrals` - Empfehlungen
- `reward_definitions` - Belohnungsstufen
- `referral_claimed_rewards` - Eingelöste Belohnungen

### 2. Alte thankyou.php umbenennen

```bash
# Alte Version sichern
mv freebie/thankyou.php freebie/thankyou-old.php

# Neue Version aktivieren
mv freebie/thankyou-new.php freebie/thankyou.php
```

## 📁 Datei-Struktur

```
/
├── freebie/
│   └── thankyou.php           # Neue Danke-Seite mit One-Click-Login Button
├── lead-dashboard-unified.php  # Vereintes Dashboard
├── lead_logout.php             # Logout-Funktion
└── migrations/
    └── unified-lead-dashboard.php  # Datenbank-Migration
```

## 🔄 User-Flow

### Neuer Lead-Flow:

1. **Freebie-Anmeldung** (freebie/index.php)
   - Lead trägt sich ein
   - E-Mail wird gespeichert

2. **Danke-Seite** (freebie/thankyou.php)
   - One-Click-Login-Token wird generiert
   - Lead sieht nur noch einen Button: "Zum Dashboard"
   - Token ist 24h gültig

3. **Vereintes Dashboard** (lead-dashboard-unified.php)
   - Automatischer Login via Token
   - Lead-User wird erstellt/geladen
   - Session wird gesetzt
   
4. **Dashboard-Features**:
   - **Kursübersicht**: Alle Freebies mit Mockups
   - **Videoplayer**: Click auf Kurs → Videoplayer öffnet sich
   - **Empfehlungsprogramm** (wenn aktiviert):
     - Persönlicher Empfehlungslink
     - Belohnungsstufen (vom Kunden)
     - Fortschrittsanzeige
     - Liste aller Empfehlungen

## 🎯 Features im Detail

### One-Click-Login

```php
// Token-Generierung in thankyou.php
$login_token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

// URL: /lead-dashboard-unified.php?token=...
```

**Sicherheit:**
- Token ist einmalig verwendbar
- Verfällt nach 24 Stunden
- Wird nach Verwendung als "used" markiert
- Verknüpft mit E-Mail und Customer-ID

### Freebie-Kurse Anzeige

Das Dashboard lädt automatisch:
- Alle Customer-Freebies des Kunden
- Zugehörige Freebie-Kurse
- Mockup-Bilder
- Kursbeschreibungen

```php
// Kurs-URL mit E-Mail-Tracking
/customer/freebie-course-player.php?id={course_id}&email={lead_email}
```

### Empfehlungsprogramm

**Nur aktiv wenn:**
1. Kunde hat `referral_enabled = 1`
2. Kunde hat `ref_code` gesetzt
3. Belohnungsstufen sind konfiguriert

**Empfehlungslink:**
```
https://app.mehr-infos-jetzt.de/freebie/index.php?id={freebie_unique_id}&ref={lead_referral_code}
```

**Belohnungs-Status:**
- 🔒 `locked` - Noch nicht erreicht
- ⭐ `unlocked` - Erreicht, aber nicht eingelöst
- ✅ `claimed` - Eingelöst

### Videoplayer-Integration

Der bestehende `freebie-course-player.php` wird verwendet:
- Drip-Content-Support (unlock_after_days)
- Fortschritts-Tracking per E-Mail
- Module und Lektionen
- PDF-Downloads
- Custom-Buttons pro Lektion

## 🎨 Design & Styling

Das Dashboard verwendet:
- **Primary Color**: Vom Freebie/Customer übernommen
- **Responsive Design**: Mobile-first
- **Moderne UI**: Cards, Gradients, Shadows
- **Font**: Inter (System-Font-Fallbacks)

### Anpassung

Primary Color in `lead-dashboard-unified.php` ändern:
```php
$primary_color = '#8B5CF6'; // Kann aus DB geladen werden
```

CSS-Variablen:
```css
:root {
    --primary: <?php echo $primary_color; ?>;
    --primary-dark: color-mix(in srgb, var(--primary) 80%, black);
    --primary-light: color-mix(in srgb, var(--primary) 20%, white);
}
```

## 📊 Datenbank-Schema

### lead_login_tokens
```sql
id              INT PRIMARY KEY
token           VARCHAR(255) UNIQUE
email           VARCHAR(255)
name            VARCHAR(255)
customer_id     INT
freebie_id      INT
expires_at      DATETIME
used_at         DATETIME NULL
created_at      DATETIME
```

### lead_users
```sql
id                      INT PRIMARY KEY
name                    VARCHAR(255)
email                   VARCHAR(255)
user_id                 INT (customer_id)
referral_code           VARCHAR(20) UNIQUE
total_referrals         INT DEFAULT 0
successful_referrals    INT DEFAULT 0
created_at              DATETIME
```

### lead_referrals
```sql
id                  INT PRIMARY KEY
referrer_id         INT
referred_name       VARCHAR(255)
referred_email      VARCHAR(255)
status              ENUM('pending','active','converted','cancelled')
invited_at          DATETIME
converted_at        DATETIME NULL
```

## 🔐 Sicherheit

### Token-Verwaltung
- Token werden nach Verwendung markiert (`used_at`)
- Verfallzeit wird geprüft
- Ein Token = Eine E-Mail + Ein Customer
- Session-basierte Authentifizierung nach Login

### E-Mail-Validierung
```php
if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    die('Keine gültige E-Mail-Adresse');
}
```

### SQL-Injection-Schutz
Alle Queries verwenden Prepared Statements:
```php
$stmt = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
$stmt->execute([$lead_id]);
```

## 🐛 Troubleshooting

### Lead kann sich nicht einloggen

**Prüfen:**
1. Token noch nicht abgelaufen? (< 24h alt)
2. Token noch nicht verwendet?
3. Session-Cookies aktiviert?

```sql
-- Token prüfen
SELECT * FROM lead_login_tokens 
WHERE email = 'lead@example.com' 
ORDER BY created_at DESC 
LIMIT 1;
```

### Empfehlungsprogramm nicht sichtbar

**Prüfen:**
1. Customer hat `referral_enabled = 1`?
2. Customer hat `ref_code` gesetzt?
3. Lead hat `user_id` (verknüpft mit Customer)?

```sql
-- Customer prüfen
SELECT id, referral_enabled, ref_code FROM users WHERE id = ?;

-- Lead prüfen
SELECT * FROM lead_users WHERE email = 'lead@example.com';
```

### Kurse werden nicht angezeigt

**Prüfen:**
1. Customer-Freebies existieren?
2. Freebie-Courses sind verknüpft?
3. Lead hat richtigen Customer-ID?

```sql
-- Freebies prüfen
SELECT cf.*, fc.id as course_id
FROM customer_freebies cf
LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id
WHERE cf.customer_id = ?;
```

## 🔄 Migration von altem System

### Bestehende Leads migrieren

```sql
-- Alle Leads aus freebie_registrations in lead_users übertragen
INSERT INTO lead_users (name, email, user_id, referral_code, created_at)
SELECT 
    name,
    email,
    customer_id,
    UPPER(SUBSTRING(MD5(CONCAT(email, id)), 1, 8)),
    created_at
FROM freebie_registrations
WHERE email NOT IN (SELECT email FROM lead_users)
ON DUPLICATE KEY UPDATE name = VALUES(name);
```

## 📈 Erweiterungsmöglichkeiten

### Marktplatz-Belohnungen integrieren

Das System ist vorbereitet für Vendor-Belohnungen:

```sql
-- Vendor-Belohnungen-Tabelle
CREATE TABLE vendor_rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT,
    reward_title VARCHAR(255),
    reward_description TEXT,
    required_referrals INT,
    is_public TINYINT(1) DEFAULT 1
);

-- Customer importiert Belohnungen
INSERT INTO reward_definitions (user_id, ...)
SELECT {customer_id}, ... FROM vendor_rewards WHERE is_public = 1;
```

### E-Mail-Benachrichtigungen

Erweiterung um automatische E-Mails:
- Bei neuer Belohnung freigeschaltet
- Bei neuer Empfehlung
- Bei Drip-Content-Freischaltung

### Analytics & Tracking

Dashboard erweitern um:
- Conversion-Rate
- Durchschnittliche Empfehlungen pro Lead
- Beliebteste Kurse
- Abschluss-Rate

## 🤝 Support

Bei Problemen:
1. Migration-Log prüfen
2. PHP Error-Log checken
3. Browser-Console auf Fehler prüfen
4. Datenbank-Verbindung testen

## 📝 Changelog

### Version 1.0.0 (November 2025)
- ✅ One-Click-Login System
- ✅ Vereintes Dashboard
- ✅ Freebie-Kurse Integration
- ✅ Empfehlungsprogramm Integration
- ✅ Belohnungssystem
- ✅ Responsive Design
- ✅ Mobile-optimiert

## 📄 Lizenz

Proprietär - Alle Rechte vorbehalten

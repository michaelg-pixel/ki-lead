# 🔧 Migration Guide - Lead Dashboard

## ❌ 505-Fehler? Hier sind deine Optionen!

Der 505-Fehler tritt normalerweise bei Server-Konfigurationsproblemen auf. Hier sind **3 Alternativen** zur Migration:

---

## ✅ Option 1: Browser-basierte Migration (EMPFOHLEN)

**Vorteile:**
- ✅ Visuelles Feedback
- ✅ Schritt-für-Schritt Ausführung
- ✅ Detailliertes Error-Log
- ✅ Funktioniert meist wenn normale Migration fehlschlägt

**Anleitung:**
```
1. Öffne im Browser:
   https://app.mehr-infos-jetzt.de/migrations/migrate-browser.html

2. Klicke auf "Migration starten"

3. Warte bis alle 5 Schritte durchgelaufen sind

4. Bei Erfolg: Grüne Häkchen erscheinen
   Bei Fehler: Rote X und Fehlermeldung
```

**Files benötigt:**
- ✅ `migrations/migrate-browser.html` (Frontend)
- ✅ `migrations/migrate-step.php` (Backend)

---

## ✅ Option 2: Pure SQL-Datei (Am sichersten)

**Vorteile:**
- ✅ Funktioniert immer
- ✅ Kein PHP benötigt
- ✅ Kann in phpMyAdmin ausgeführt werden
- ✅ Volle Kontrolle

**Anleitung:**

### Via phpMyAdmin:
```
1. Öffne phpMyAdmin in deinem Hosting-Panel

2. Wähle deine Datenbank aus

3. Klicke auf "SQL" Tab

4. Öffne migrations/migration.sql in einem Editor

5. Kopiere den kompletten Inhalt

6. Füge ihn in das SQL-Feld ein

7. Klicke auf "Ausführen"

8. Prüfe die Ausgabe auf Erfolg
```

### Via SSH/Terminal:
```bash
# Mit MySQL CLI
mysql -u dein_user -p deine_datenbank < migrations/migration.sql

# Oder mit mysqldump
mysql -u dein_user -p deine_datenbank
mysql> source /pfad/zu/migrations/migration.sql
```

**File benötigt:**
- ✅ `migrations/migration.sql`

---

## ✅ Option 3: Manuelle Tabellen-Erstellung

Falls alles andere fehlschlägt, kannst du die Tabellen auch manuell erstellen.

### Schritt 1: lead_login_tokens

```sql
CREATE TABLE lead_login_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    customer_id INT,
    freebie_id INT,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schritt 2: lead_users

```sql
CREATE TABLE lead_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    referral_code VARCHAR(20) UNIQUE,
    total_referrals INT DEFAULT 0,
    successful_referrals INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_email (email),
    INDEX idx_user (user_id),
    UNIQUE KEY unique_email_user (email, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schritt 3: lead_referrals

```sql
CREATE TABLE lead_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referred_name VARCHAR(255),
    referred_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'converted', 'cancelled') DEFAULT 'pending',
    invited_at DATETIME NOT NULL,
    converted_at DATETIME NULL,
    INDEX idx_referrer (referrer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schritt 4: reward_definitions

```sql
CREATE TABLE reward_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tier_level INT NOT NULL,
    tier_name VARCHAR(255),
    tier_description TEXT,
    required_referrals INT NOT NULL,
    reward_title VARCHAR(255) NOT NULL,
    reward_description TEXT,
    reward_icon VARCHAR(100),
    reward_color VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schritt 5: referral_claimed_rewards

```sql
CREATE TABLE referral_claimed_rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    reward_id INT NOT NULL,
    reward_name VARCHAR(255),
    claimed_at DATETIME NOT NULL,
    INDEX idx_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🐛 Troubleshooting: 505 Fehler

### Mögliche Ursachen:

**1. PHP Version zu alt**
```bash
# Prüfe PHP Version
php -v

# Benötigt: PHP 7.4 oder höher
```

**2. Memory Limit zu niedrig**
```php
// In php.ini oder .htaccess
memory_limit = 256M
```

**3. Execution Time zu kurz**
```php
// In php.ini oder .htaccess
max_execution_time = 300
```

**4. Mod_security blockiert**
```
Kontaktiere deinen Hoster und bitte um Deaktivierung
von mod_security für /migrations/
```

**5. .htaccess Problem**
```bash
# Erstelle .htaccess in /migrations/
<IfModule mod_rewrite.c>
    RewriteEngine Off
</IfModule>
```

---

## ✅ Verifikation nach Migration

Nach erfolgreicher Migration prüfe:

### 1. Tabellen existieren
```sql
SHOW TABLES LIKE 'lead_%';
SHOW TABLES LIKE 'reward_%';
SHOW TABLES LIKE 'referral_%';
```

**Erwartete Ausgabe:**
- lead_login_tokens
- lead_referrals
- lead_users
- referral_claimed_rewards
- reward_definitions

### 2. Struktur prüfen
```sql
DESCRIBE lead_users;
```

**Erwartete Spalten:**
- id
- name
- email
- user_id
- referral_code
- total_referrals
- successful_referrals
- created_at

### 3. Test-Eintrag
```sql
INSERT INTO lead_login_tokens 
(token, email, name, customer_id, expires_at, created_at)
VALUES 
('test123', 'test@example.com', 'Test User', 1, 
 DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW());

SELECT * FROM lead_login_tokens WHERE email = 'test@example.com';

DELETE FROM lead_login_tokens WHERE token = 'test123';
```

---

## 📊 Status-Check

Verwende dieses Script um den Status zu prüfen:

```php
<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

$tables = [
    'lead_login_tokens',
    'lead_users',
    'lead_referrals',
    'reward_definitions',
    'referral_claimed_rewards'
];

echo "<h2>Migration Status</h2>";

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "✅ $table existiert<br>";
        
        // Anzahl Einträge
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "&nbsp;&nbsp;&nbsp;→ $count Einträge<br>";
    } else {
        echo "❌ $table fehlt!<br>";
    }
}
?>
```

---

## 🚀 Nach erfolgreicher Migration

1. ✅ Dateien aktivieren:
   ```bash
   mv freebie/thankyou.php freebie/thankyou-old.php
   mv freebie/thankyou-new.php freebie/thankyou.php
   ```

2. ✅ System testen:
   - Freebie-Anmeldung durchführen
   - "Zum Dashboard" klicken
   - Dashboard prüfen

3. ✅ Go-Live:
   - Backup erstellen
   - Live schalten
   - Monitoring aktivieren

---

## 📞 Support

**Problem nicht gelöst?**

1. Prüfe PHP Error-Log: `/logs/error_log` oder ähnlich
2. Prüfe MySQL Error-Log
3. Kontaktiere deinen Hoster für Server-Log-Zugriff
4. Stelle sicher dass PDO Extension aktiviert ist

**Hilfreiche Commands:**
```bash
# PHP Info
php -i | grep PDO

# MySQL Connection Test
mysql -u user -p -e "SHOW DATABASES;"

# Error Log anzeigen
tail -f /pfad/zu/error_log
```

---

## 📝 Zusammenfassung

| Methode | Schwierigkeit | Erfolgsrate | Empfohlen wenn... |
|---------|--------------|-------------|-------------------|
| **Browser-Migration** | Mittel | 85% | PHP läuft, aber normale Migration schlägt fehl |
| **SQL-Datei** | Einfach | 99% | Du phpMyAdmin-Zugriff hast |
| **Manuelle Tabellen** | Schwer | 100% | Alles andere fehlschlägt |

**Empfehlung:**
1. Versuche Browser-Migration
2. Falls fehlgeschlagen: SQL-Datei via phpMyAdmin
3. Als letztes Resort: Manuelle Erstellung

---

✅ **Nach erfolgreicher Migration:** Siehe [ACTIVATION-CHECKLIST.md](../docs/ACTIVATION-CHECKLIST.md)
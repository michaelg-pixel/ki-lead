# 🔐 KI-Lead Backup System

**Professionelles, automatisiertes Backup-System für dein gesamtes Projekt**

Dieses System erstellt automatisch Backups deiner Datenbank und Dateien, speichert sie sicher und bietet eine komfortable Admin-Oberfläche zur Verwaltung.

---

## 📋 Inhaltsverzeichnis

1. [Features](#-features)
2. [Schnellstart](#-schnellstart)
3. [Installation](#-installation)
4. [Konfiguration](#%EF%B8%8F-konfiguration)
5. [Admin-Interface](#-admin-interface)
6. [Cronjobs](#-cronjobs)
7. [Externe Speicherorte](#-externe-speicherorte)
8. [Manuelles Backup](#-manuelles-backup)
9. [Wiederherstellung](#-wiederherstellung)
10. [Sicherheit](#-sicherheit)
11. [Troubleshooting](#-troubleshooting)

---

## 🎯 Features

### Automatische Backups
- ✅ **Datenbank-Backups** (täglich)
- ✅ **Datei-Backups** (wöchentlich)
- ✅ **Vollständige Backups** (monatlich)
- ✅ Komprimierung mit GZIP
- ✅ Automatische Bereinigung alter Backups

### Admin-Interface
- ✅ **Separate Admin-Oberfläche** (nicht im Haupt-Dashboard)
- ✅ Übersichtliche Backup-Liste
- ✅ Download-Funktion
- ✅ Manuelle Backup-Erstellung
- ✅ Live-Statistiken
- ✅ Log-Viewer
- ✅ Responsive Design

### Externe Speicherorte
- ✅ FTP/SFTP-Upload
- ✅ Lokale externe Festplatten
- ✅ Cloud-Storage (vorbereitet)

### Sicherheit
- ✅ Eigene Authentifizierung
- ✅ .htaccess-Schutz für Backup-Verzeichnis
- ✅ Verschlüsselte Passwörter
- ✅ Session-basierte Zugriffskontrolle

---

## 🚀 Schnellstart

### 1. Zugangsdaten ändern
```php
// Bearbeite: backup-system/config.php
define('BACKUP_ADMIN_USER', 'dein-username');
define('BACKUP_ADMIN_PASS', password_hash('DeinSicheresPasswort123!', PASSWORD_DEFAULT));
```

### 2. Cronjobs installieren
```bash
cd backup-system
chmod +x install-cronjobs.sh
./install-cronjobs.sh
```

### 3. Admin-Interface aufrufen
```
https://deine-domain.de/backup-system/admin.php
```

---

## 📦 Installation

### Schritt 1: Dateien sind bereits da
Alle Dateien befinden sich bereits in deinem Repository unter `/backup-system/`.

### Schritt 2: Berechtigungen setzen
```bash
# Backup-Verzeichnisse erstellen (falls noch nicht vorhanden)
mkdir -p backup-system/backups/{database,files,logs}

# Schreibrechte setzen
chmod 755 backup-system
chmod 777 backup-system/backups
chmod 777 backup-system/backups/*

# Installationsskript ausführbar machen
chmod +x backup-system/install-cronjobs.sh
```

### Schritt 3: Konfiguration anpassen
Bearbeite `backup-system/config.php`:

```php
// Authentifizierung (UNBEDINGT ÄNDERN!)
define('BACKUP_ADMIN_USER', 'admin');
define('BACKUP_ADMIN_PASS', password_hash('DeinSicheresPasswort123!', PASSWORD_DEFAULT));

// Backup-Aufbewahrung
define('BACKUP_RETENTION_DAYS', 30); // Backups älter als X Tage werden gelöscht

// E-Mail-Benachrichtigungen (optional)
define('BACKUP_NOTIFY_EMAIL', 'deine@email.de');
define('BACKUP_NOTIFY_ON_ERROR', true);
```

### Schritt 4: Cronjobs installieren
```bash
cd backup-system
./install-cronjobs.sh
```

### Schritt 5: Test-Backup erstellen
```bash
# Manuelles Backup zur Überprüfung
php backup-system/engine.php database
```

Oder über das Admin-Interface:
```
https://deine-domain.de/backup-system/admin.php
```

---

## ⚙️ Konfiguration

### Grundeinstellungen

```php
// backup-system/config.php

// Backup-Aufbewahrung
define('BACKUP_RETENTION_DAYS', 30);  // Alte Backups werden nach 30 Tagen gelöscht
define('MAX_BACKUPS_PER_TYPE', 50);   // Max. Anzahl Backups pro Typ

// Kompression
define('BACKUP_COMPRESS', true);      // GZIP-Kompression aktivieren

// E-Mail-Benachrichtigungen
define('BACKUP_NOTIFY_EMAIL', '');
define('BACKUP_NOTIFY_ON_SUCCESS', false);
define('BACKUP_NOTIFY_ON_ERROR', true);
```

### Ausgeschlossene Verzeichnisse

Beim Datei-Backup werden folgende Verzeichnisse automatisch ausgeschlossen:

```php
$excludeDirectories = [
    '/backup-system',     // Backup-System selbst
    '/backups',          // Alte Backups
    '/.git',             // Git-Repository
    '/node_modules',     // NPM-Module
    '/vendor/cache'      // Composer-Cache
];
```

Du kannst weitere Verzeichnisse in `config.php` hinzufügen.

---

## 🌐 Admin-Interface

### Zugang
```
URL: https://deine-domain.de/backup-system/admin.php
```

### Features

#### Dashboard
- **Statistiken**: Anzahl Backups, Gesamtgröße, letztes Backup, freier Speicher
- **Schnellaktionen**: Datenbank-, Datei- und Vollbackup mit einem Klick

#### Backup-Verwaltung
- **Datenbank-Backups**: Liste aller DB-Backups mit Download/Löschen
- **Datei-Backups**: Liste aller File-Backups mit Download/Löschen
- **Logs**: Detaillierte Log-Ansicht aller Backup-Läufe

#### Funktionen
- ✅ Download einzelner Backups
- ✅ Backups löschen
- ✅ Manuelle Backups erstellen
- ✅ Live-Logs einsehen
- ✅ Speicherplatz-Überwachung

### Screenshots

**Dashboard:**
```
┌─────────────────────────────────────────────┐
│  🔐 Backup System Administration            │
├─────────────────────────────────────────────┤
│  Gesamt Backups: 15                         │
│  Gesamtgröße: 245 MB                        │
│  Letztes Backup: 04.11.2025 02:00          │
│  Speicherplatz frei: 4.2 GB                 │
├─────────────────────────────────────────────┤
│  [💾 Datenbank] [📁 Dateien] [🚀 Voll]     │
└─────────────────────────────────────────────┘
```

---

## ⏰ Cronjobs

### Standard-Zeitplan

Nach der Installation sind folgende Cronjobs aktiv:

```bash
# Datenbank-Backup - Täglich um 02:00 Uhr
0 2 * * * php /pfad/zu/backup-system/engine.php database

# Datei-Backup - Wöchentlich Sonntags um 03:00 Uhr
0 3 * * 0 php /pfad/zu/backup-system/engine.php files

# Vollständiges Backup - Monatlich am 1. um 04:00 Uhr
0 4 1 * * php /pfad/zu/backup-system/engine.php full
```

### Zeitplan anpassen

Um den Zeitplan anzupassen:

```bash
# Cronjobs bearbeiten
crontab -e

# Beispiel: Datenbank-Backup auf 03:30 Uhr ändern
30 3 * * * php /pfad/zu/backup-system/engine.php database
```

### Cronjob-Syntax

```
┌───────────── Minute (0 - 59)
│ ┌─────────── Stunde (0 - 23)
│ │ ┌───────── Tag des Monats (1 - 31)
│ │ │ ┌─────── Monat (1 - 12)
│ │ │ │ ┌───── Wochentag (0 - 7) (Sonntag ist 0 oder 7)
│ │ │ │ │
* * * * * Befehl
```

**Beispiele:**
```bash
# Jeden Tag um 02:00 Uhr
0 2 * * * command

# Jeden Sonntag um 03:00 Uhr
0 3 * * 0 command

# Am 1. jeden Monats um 04:00 Uhr
0 4 1 * * command

# Alle 6 Stunden
0 */6 * * * command
```

### Cronjobs überprüfen

```bash
# Aktive Cronjobs anzeigen
crontab -l

# Cronjob-Logs prüfen
tail -f backup-system/backups/logs/cron.log
```

---

## ☁️ Externe Speicherorte

### FTP/SFTP konfigurieren

Bearbeite `config.php`:

```php
$externalStorageConfig = [
    'ftp' => [
        'enabled' => true,
        'host' => 'ftp.beispiel.de',
        'port' => 21,
        'username' => 'dein-ftp-user',
        'password' => 'dein-ftp-passwort',
        'remote_path' => '/backups'
    ]
];
```

Nach jedem Backup werden die neuesten Dateien automatisch hochgeladen.

### Lokale externe Festplatte

Ideal für NAS oder externe USB-Festplatten:

```php
$externalStorageConfig = [
    'local_external' => [
        'enabled' => true,
        'path' => '/mnt/external-backup'
    ]
];
```

**Voraussetzung:** Das Verzeichnis muss vom Webserver beschreibbar sein.

### Cloud-Storage (AWS S3, Google Cloud)

Die Konfiguration ist vorbereitet, aber noch nicht vollständig implementiert:

```php
$externalStorageConfig = [
    'cloud' => [
        'enabled' => true,
        'provider' => 'aws',  // aws, google, azure
        'endpoint' => 'https://s3.amazonaws.com',
        'access_key' => 'dein-access-key',
        'secret_key' => 'dein-secret-key',
        'bucket' => 'dein-bucket-name'
    ]
];
```

**Status:** Funktion muss noch implementiert werden (siehe `engine.php` → `syncToCloud()`).

---

## 🔧 Manuelles Backup

### Via Command Line

```bash
# Datenbank-Backup
php backup-system/engine.php database

# Datei-Backup
php backup-system/engine.php files

# Vollständiges Backup
php backup-system/engine.php full
```

### Via Admin-Interface

1. Öffne `https://deine-domain.de/backup-system/admin.php`
2. Klicke auf einen der Backup-Buttons:
   - **💾 Datenbank-Backup**
   - **📁 Datei-Backup**
   - **🚀 Vollständiges Backup**

---

## 🔄 Wiederherstellung

### Datenbank wiederherstellen

#### Methode 1: Via phpMyAdmin
1. Lade das Backup von `/backup-system/backups/database/` herunter
2. Entpacke die `.gz`-Datei (falls komprimiert)
3. Öffne phpMyAdmin
4. Wähle deine Datenbank
5. Gehe zu "Importieren"
6. Wähle die `.sql`-Datei aus
7. Klicke auf "OK"

#### Methode 2: Via MySQL Command Line
```bash
# Backup herunterladen und entpacken
gunzip db_backup_2025-11-04_02-00-00.sql.gz

# In Datenbank importieren
mysql -u dein_user -p deine_datenbank < db_backup_2025-11-04_02-00-00.sql
```

### Dateien wiederherstellen

```bash
# Backup herunterladen
cd /tmp
wget https://deine-domain.de/backup-system/admin.php?action=download_backup&file=files_backup_2025-11-04.tar.gz

# Entpacken
tar -xzf files_backup_2025-11-04.tar.gz

# Dateien an den gewünschten Ort kopieren
cp -r * /pfad/zu/deinem/projekt/
```

**⚠️ WICHTIG:** Prüfe vor der Wiederherstellung immer die Backup-Integrität!

---

## 🔒 Sicherheit

### Zugriffsbeschränkung

Das Backup-System ist durch mehrere Sicherheitsebenen geschützt:

1. **Authentifizierung**: Eigenes Login-System
2. **.htaccess**: Direkter Zugriff auf Backup-Dateien wird blockiert
3. **Session-basiert**: Nur eingeloggte Nutzer haben Zugriff

### .htaccess-Schutz

Automatisch erstellt in `/backup-system/backups/.htaccess`:
```apache
Deny from all
```

### Passwort ändern

```bash
# Neues Passwort-Hash generieren
php -r "echo password_hash('DeinNeuesPasswort', PASSWORD_DEFAULT);"

# Ausgabe in config.php eintragen:
define('BACKUP_ADMIN_PASS', '$2y$10$...');
```

### Best Practices

✅ **Empfohlen:**
- Starkes Passwort verwenden (min. 12 Zeichen)
- Admin-Interface nur via HTTPS aufrufen
- Backup-Verzeichnis außerhalb des Webroot speichern (falls möglich)
- Regelmäßig Backups auf Integrität prüfen
- Test-Wiederherstellung durchführen

❌ **Vermeiden:**
- Standard-Passwörter wie "admin" / "password"
- Backup-Verzeichnis öffentlich zugänglich machen
- Sehr alte Backups aufheben (Speicherplatz!)

---

## 🩺 Troubleshooting

### Problem: Cronjobs laufen nicht

**Lösung:**
```bash
# Cronjobs überprüfen
crontab -l

# Cron-Logs prüfen (falls vorhanden)
grep CRON /var/log/syslog

# PHP-Pfad überprüfen
which php

# Manuell testen
php /pfad/zu/backup-system/engine.php database
```

### Problem: "Permission denied" Fehler

**Lösung:**
```bash
# Berechtigungen setzen
chmod -R 755 backup-system
chmod -R 777 backup-system/backups

# Eigentümer anpassen (ersetze 'www-data' mit deinem Webserver-User)
chown -R www-data:www-data backup-system/backups
```

### Problem: Backup-Datei zu groß

**Lösung:**

1. **Kompression aktivieren** (in `config.php`):
   ```php
   define('BACKUP_COMPRESS', true);
   ```

2. **Excludierte Verzeichnisse erweitern**:
   ```php
   $excludeDirectories = [
       '/backup-system',
       '/uploads/cache',
       '/uploads/temp',
       // ... weitere
   ];
   ```

3. **Alte Backups häufiger löschen**:
   ```php
   define('BACKUP_RETENTION_DAYS', 14); // Statt 30
   ```

### Problem: Login funktioniert nicht

**Lösung:**
```bash
# Neues Passwort-Hash generieren
php -r "echo password_hash('NeuesPasswort123', PASSWORD_DEFAULT) . PHP_EOL;"

# Hash in config.php eintragen
# Sessions löschen
rm -f /tmp/sess_*
```

### Problem: FTP-Upload schlägt fehl

**Lösung:**
```php
// In config.php: Detailliertes FTP-Logging aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FTP-Verbindung testen
$conn = ftp_connect('ftp.beispiel.de', 21);
if (!$conn) {
    die("FTP-Verbindung fehlgeschlagen!");
}

$login = ftp_login($conn, 'user', 'pass');
if (!$login) {
    die("FTP-Login fehlgeschlagen!");
}

// Passiv-Modus testen
ftp_pasv($conn, true);
```

### Problem: Speicherplatz voll

**Lösung:**
```bash
# Backups älter als 7 Tage löschen
find backup-system/backups -type f -mtime +7 -delete

# Nur die neuesten 10 Backups behalten
cd backup-system/backups/database
ls -t | tail -n +11 | xargs rm -f
```

### Logs prüfen

```bash
# Backup-Logs anzeigen
tail -f backup-system/backups/logs/backup_$(date +%Y-%m-%d).log

# Cronjob-Logs
tail -f backup-system/backups/logs/cron.log

# PHP-Fehler-Logs
tail -f /var/log/apache2/error.log
```

---

## 📞 Support

Bei Problemen:

1. **Logs prüfen**: `backup-system/backups/logs/`
2. **Berechtigungen prüfen**: `ls -la backup-system/backups/`
3. **Manuelles Backup testen**: `php engine.php database`
4. **Admin-Interface prüfen**: Statistiken und Logs im Dashboard

---

## 📝 Changelog

### Version 1.0.0 (04.11.2025)
- ✅ Initiale Version
- ✅ Automatische DB- und Datei-Backups
- ✅ Admin-Interface mit Login
- ✅ FTP/SFTP-Upload
- ✅ Cronjob-Installation
- ✅ Log-System
- ✅ Automatische Bereinigung

---

## 📄 Lizenz

Dieses Backup-System ist Teil des KI-Lead Projekts und für den internen Gebrauch bestimmt.

---

**Viel Erfolg mit deinem Backup-System! 🚀**

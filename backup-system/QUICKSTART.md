# 🔐 Backup System - Schnellübersicht

## 🚀 Quick Start (3 Schritte)

```bash
# 1. Installation ausführen
cd backup-system
chmod +x quick-install.sh
./quick-install.sh

# 2. Test durchführen
chmod +x test.sh
./test.sh

# 3. Admin-Interface öffnen
https://deine-domain.de/backup-system/admin.php
```

---

## 📁 Dateistruktur

```
backup-system/
├── admin.php              # 🌐 Admin-Interface (separate Oberfläche)
├── config.php             # ⚙️  Konfiguration
├── engine.php             # 🔧 Backup-Engine (wird von Cronjobs ausgeführt)
├── .htaccess              # 🔒 Sicherheit
│
├── quick-install.sh       # 🚀 Schnellinstallation
├── install-cronjobs.sh    # ⏰ Cronjob-Setup
├── test.sh                # 🧪 Test-Suite
├── README.md              # 📖 Vollständige Dokumentation
│
└── backups/               # 💾 Backup-Speicher
    ├── database/          #    → Datenbank-Backups
    ├── files/             #    → Datei-Backups
    └── logs/              #    → System-Logs
```

---

## ⚙️ Konfiguration

### Wichtigste Einstellungen in `config.php`:

```php
// 1. Zugangsdaten (ÄNDERN!)
define('BACKUP_ADMIN_USER', 'admin');
define('BACKUP_ADMIN_PASS', password_hash('DeinPasswort', PASSWORD_DEFAULT));

// 2. Aufbewahrung
define('BACKUP_RETENTION_DAYS', 30);  // Alte Backups löschen nach X Tagen

// 3. E-Mail (optional)
define('BACKUP_NOTIFY_EMAIL', 'deine@email.de');
define('BACKUP_NOTIFY_ON_ERROR', true);
```

---

## ⏰ Automatische Backups (Cronjobs)

Nach Installation via `install-cronjobs.sh`:

| Typ | Zeitplan | Beschreibung |
|-----|----------|--------------|
| 💾 Datenbank | Täglich 02:00 | Alle Tabellen exportieren |
| 📁 Dateien | Sonntags 03:00 | Gesamtes Projekt archivieren |
| 🚀 Vollständig | Monatlich (1.) 04:00 | DB + Dateien |

**Cronjobs anzeigen:**
```bash
crontab -l
```

**Cronjobs bearbeiten:**
```bash
crontab -e
```

---

## 🌐 Admin-Interface

**URL:** `https://deine-domain.de/backup-system/admin.php`

### Features:
- ✅ Dashboard mit Live-Statistiken
- ✅ Alle Backups anzeigen und herunterladen
- ✅ Manuelle Backups erstellen
- ✅ Logs einsehen
- ✅ Backups löschen
- ✅ Responsive Design

### Screenshots:

```
┌─────────────────────────────────────┐
│  Dashboard                          │
│  • Gesamt Backups: 15               │
│  • Gesamtgröße: 245 MB              │
│  • Letztes Backup: 04.11.2025       │
│  • Speicherplatz frei: 4.2 GB       │
│                                     │
│  [💾 Datenbank] [📁 Dateien] [🚀]  │
└─────────────────────────────────────┘
```

---

## 🔧 Manuelle Bedienung

### Via Command Line:

```bash
# Datenbank-Backup
php backup-system/engine.php database

# Datei-Backup
php backup-system/engine.php files

# Vollständiges Backup
php backup-system/engine.php full
```

### Via Admin-Interface:
Einfach auf die entsprechenden Buttons klicken.

---

## 🔄 Wiederherstellung

### Datenbank:
```bash
# Backup herunterladen
wget https://deine-domain.de/backup-system/admin.php?action=download_backup&file=db_backup_XXX.sql.gz

# Entpacken
gunzip db_backup_XXX.sql.gz

# Importieren
mysql -u user -p datenbank < db_backup_XXX.sql
```

### Dateien:
```bash
# Backup entpacken
tar -xzf files_backup_XXX.tar.gz

# An Ort kopieren
cp -r * /pfad/zu/deinem/projekt/
```

---

## ☁️ Externe Speicherorte

### FTP konfigurieren:
```php
// In config.php:
$externalStorageConfig = [
    'ftp' => [
        'enabled' => true,
        'host' => 'ftp.beispiel.de',
        'port' => 21,
        'username' => 'dein-user',
        'password' => 'dein-passwort',
        'remote_path' => '/backups'
    ]
];
```

Nach jedem Backup wird automatisch zum FTP hochgeladen.

### Lokaler externer Speicher:
```php
$externalStorageConfig = [
    'local_external' => [
        'enabled' => true,
        'path' => '/mnt/external-backup'
    ]
];
```

---

## 🔒 Sicherheit

### Checklist:
- ✅ Standard-Passwort ändern
- ✅ HTTPS verwenden
- ✅ Backup-Verzeichnis geschützt (via .htaccess)
- ✅ Regelmäßig Backups prüfen
- ✅ Test-Wiederherstellung durchführen

### Passwort ändern:
```bash
# Neues Hash generieren
php -r "echo password_hash('NeuesPasswort', PASSWORD_DEFAULT);"

# In config.php eintragen
```

---

## 🩺 Troubleshooting

### Cronjobs laufen nicht?
```bash
# Prüfen ob installiert
crontab -l

# Logs prüfen
tail -f backup-system/backups/logs/cron.log
```

### Permission Denied?
```bash
chmod -R 777 backup-system/backups
```

### Backup zu groß?
```php
// In config.php:
define('BACKUP_COMPRESS', true);  // Kompression aktivieren

$excludeDirectories = [
    '/backup-system',
    '/uploads/cache',  // Mehr excludieren
    // ...
];
```

### Login funktioniert nicht?
```bash
# Neues Passwort generieren
php -r "echo password_hash('NeuesPasswort', PASSWORD_DEFAULT);"

# Sessions löschen
rm -f /tmp/sess_*
```

---

## 📊 Monitoring

### Logs prüfen:
```bash
# Heutiges Log
cat backup-system/backups/logs/backup_$(date +%Y-%m-%d).log

# Cronjob-Log
tail -f backup-system/backups/logs/cron.log
```

### Speicherplatz prüfen:
```bash
du -sh backup-system/backups/database
du -sh backup-system/backups/files
```

### Backup-Anzahl:
```bash
ls -l backup-system/backups/database | wc -l
ls -l backup-system/backups/files | wc -l
```

---

## 📞 Wichtige Kommandos

```bash
# Installation
./quick-install.sh

# Cronjobs installieren
./install-cronjobs.sh

# System testen
./test.sh

# Manuelles Backup
php engine.php database

# Logs anzeigen
tail -f backups/logs/backup_$(date +%Y-%m-%d).log

# Alte Backups löschen (>7 Tage)
find backups/ -type f -mtime +7 -delete

# Alle Cronjobs anzeigen
crontab -l
```

---

## 💡 Best Practices

1. **Regelmäßig testen**: Führe monatlich eine Test-Wiederherstellung durch
2. **Mehrere Orte**: Nutze externe Speicherorte (FTP, lokale Festplatte)
3. **Überwachen**: Prüfe regelmäßig die Logs
4. **Alte Backups**: Lösche sehr alte Backups manuell bei Speichermangel
5. **Benachrichtigungen**: Aktiviere E-Mail-Benachrichtigungen bei Fehlern

---

## 📄 Dokumentation

**Vollständige Dokumentation:** `backup-system/README.md`

**Enthält:**
- Detaillierte Installationsanleitung
- Alle Konfigurationsoptionen
- Troubleshooting-Guide
- Sicherheitshinweise
- Wiederherstellungs-Guide

---

## ✅ System-Status prüfen

```bash
# Schneller Status-Check
./test.sh

# Oder manuell:
ls -lh backup-system/backups/database | tail -5  # Letzte 5 DB-Backups
crontab -l | grep backup-system                  # Cronjobs prüfen
tail -20 backup-system/backups/logs/backup_*.log # Logs prüfen
```

---

**Viel Erfolg mit deinem Backup-System! 🚀**

Bei Fragen oder Problemen: Siehe vollständige README.md

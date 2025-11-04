# 🚨 NOTFALL-WIEDERHERSTELLUNG - Emergency Recovery Guide

**Für absolute Notfälle: System kompromittiert, gehackt oder gecrasht**

---

## ⚡ Schnellzugriff

```
🌐 Admin-Interface: https://deine-domain.de/backup-system/admin.php
🔐 Standard-Login: admin / [dein-passwort]
📁 Backup-Verzeichnis: /backup-system/backups/
```

---

## 🚨 Szenario 1: HACKER-ANGRIFF ERKANNT

### Symptome:
- ❌ Admin-Passwort geändert
- ❌ Datenbank manipuliert
- ❌ Fremde Benutzer angelegt
- ❌ Unbekannte Dateien auf Server

### ✅ SOFORT-MASSNAHMEN (Schritt für Schritt):

#### Schritt 1: Admin-Interface aufrufen
```
https://deine-domain.de/backup-system/admin.php
```

#### Schritt 2: Einloggen
- Benutzername: `admin` (oder dein konfigurierter Name)
- Passwort: [dein Backup-System-Passwort]

**⚠️ Falls Login nicht funktioniert:**
```bash
# Via SSH zum Server verbinden, dann:
cd backup-system
php -r "echo password_hash('NeuesNotfallPasswort123', PASSWORD_DEFAULT);"

# Output in config.php eintragen:
nano config.php
# define('BACKUP_ADMIN_PASS', 'DEIN_NEUER_HASH');
```

#### Schritt 3: Notfall-Wiederherstellung starten
1. Klicke auf den großen roten Button: **"🚨 NOTFALL-WIEDERHERSTELLUNG STARTEN"**
2. Bestätige mit "OK"
3. Wähle: 
   - **"Ja"** = Datenbank + Dateien (vollständig, dauert länger)
   - **"Nein"** = Nur Datenbank (schneller)
4. Zweite Bestätigung: "JETZT STARTEN"
5. **WARTE!** Schließe die Seite NICHT!

#### Schritt 4: Ergebnis prüfen
Nach 30-120 Sekunden:
- ✅ "WIEDERHERSTELLUNG ABGESCHLOSSEN" → System ist sauber!
- ❌ Fehler? → Siehe unten "Plan B"

#### Schritt 5: System prüfen
```bash
# Hauptsystem testen
https://deine-domain.de/

# Admin-Dashboard testen
https://deine-domain.de/admin/dashboard.php

# Datenbank prüfen (via phpMyAdmin oder)
mysql -u user -p
USE deine_datenbank;
SHOW TABLES;
```

#### Schritt 6: Sicherheitsmaßnahmen
```bash
# 1. Alle Passwörter ändern
# 2. Logs prüfen
tail -f backup-system/backups/logs/security.log

# 3. Angreifer-IP identifizieren
grep "BRUTEFORCE\|BLOCKED" security.log

# 4. IP dauerhaft blockieren (Firewall)
sudo iptables -A INPUT -s 192.168.1.XXX -j DROP
```

---

## 🔥 Szenario 2: SERVER GECRASHT / DATENBANK KORRUPT

### Symptome:
- ❌ "Database connection error"
- ❌ "500 Internal Server Error"
- ❌ Weißer Bildschirm
- ❌ Tabellen fehlen

### ✅ SOFORT-MASSNAHMEN:

#### Methode A: Via Admin-Interface (wenn erreichbar)
```
1. https://deine-domain.de/backup-system/admin.php
2. Login
3. Tab: "Datenbank-Backups"
4. Neuestes Backup → "Wiederherstellen"
5. Bestätigen → Warten
```

#### Methode B: Via Kommandozeile (Server-Zugriff)
```bash
# 1. Zum Backup-Verzeichnis
cd /pfad/zu/backup-system

# 2. Neuestes Backup finden
ls -lt backups/database/ | head -5

# 3. Backup wiederherstellen
php restore.php database db_backup_2025-11-04_02-00-00.sql.gz

# 4. Ausgabe prüfen
# ✅ "success": true → Erfolgreich!
```

#### Methode C: Manuell via MySQL (falls PHP nicht funktioniert)
```bash
# 1. Backup herunterladen & entpacken
cd /tmp
cp /pfad/zu/backup-system/backups/database/db_backup_XXX.sql.gz .
gunzip db_backup_XXX.sql.gz

# 2. In Datenbank importieren
mysql -u root -p deine_datenbank < db_backup_XXX.sql

# 3. Testen
mysql -u root -p
USE deine_datenbank;
SHOW TABLES;
SELECT COUNT(*) FROM users;
```

---

## ⚡ Szenario 3: ALLE DATEIEN GELÖSCHT / ÜBERSCHRIEBEN

### Symptome:
- ❌ CSS/JS-Dateien fehlen
- ❌ Bilder verschwunden
- ❌ Config-Dateien weg
- ❌ "File not found" Fehler

### ✅ SOFORT-MASSNAHMEN:

#### Via Admin-Interface:
```
1. https://deine-domain.de/backup-system/admin.php
2. Login
3. Tab: "Datei-Backups"
4. Neuestes Backup → "Wiederherstellen"
5. Bestätigen → Warten (kann 2-5 Minuten dauern!)
```

#### Via Kommandozeile:
```bash
# 1. Zum Backup-Verzeichnis
cd /pfad/zu/backup-system

# 2. Neuestes Datei-Backup finden
ls -lt backups/files/ | head -3

# 3. Wiederherstellen
php restore.php files files_backup_2025-11-04_03-00-00.tar.gz

# 4. Oder manuell entpacken:
cd /tmp
cp /pfad/zu/backup-system/backups/files/files_backup_XXX.tar.gz .
tar -xzf files_backup_XXX.tar.gz
cp -r * /pfad/zu/deinem/projekt/
```

---

## 🔄 Szenario 4: WIEDERHERSTELLUNG WAR FALSCH / FEHLER

### Du hast versehentlich ein falsches Backup wiederhergestellt?

### ✅ ROLLBACK DURCHFÜHREN:

```
1. Admin-Interface öffnen
2. Tab: "Rollback-Punkte"
3. Neuesten Rollback-Punkt finden
   (z.B. "db_rollback_before_restore_2025-11-04_15-30-00.sql.gz")
4. Klick auf "↩️ Rollback"
5. Bestätigen
6. System ist wieder im ursprünglichen Zustand!
```

**Warum funktioniert das?**
- Vor JEDER Wiederherstellung erstellt das System automatisch einen Rollback-Punkt
- Du kannst jederzeit zu diesem Punkt zurückkehren
- Rollback-Punkte bleiben 30 Tage gespeichert

---

## 🆘 Szenario 5: BACKUP-SYSTEM SELBST NICHT ERREICHBAR

### Was tun wenn Admin-Interface down ist?

#### Plan A: Direkter Dateizugriff (FTP/SFTP)
```bash
# 1. Via FTP/SFTP zum Server verbinden
# 2. Verzeichnis: /backup-system/backups/database/
# 3. Neuestes Backup herunterladen
# 4. Lokal entpacken
# 5. Via phpMyAdmin oder MySQL importieren
```

#### Plan B: SSH-Zugriff
```bash
# 1. Via SSH einloggen
ssh dein-user@deine-domain.de

# 2. Zum Backup-Verzeichnis
cd /home/username/public_html/backup-system

# 3. Backup manuell wiederherstellen (siehe oben: Methode C)
```

#### Plan C: Hostinger File Manager
```
1. Hostinger Dashboard öffnen
2. File Manager
3. Navigiere zu /backup-system/backups/
4. Lade neuestes Backup herunter
5. Über phpMyAdmin importieren
```

---

## 📞 Notfall-Kontakte & Tools

### Benötigte Zugangsdaten (JETZT NOTIEREN!):
```
SSH-Zugang:
Host: ___________________________
User: ___________________________
Pass: ___________________________

MySQL-Zugang:
Host: ___________________________
User: ___________________________
Pass: ___________________________
Datenbank: ___________________________

FTP-Zugang:
Host: ___________________________
User: ___________________________
Pass: ___________________________

Backup-System Login:
URL: https://deine-domain.de/backup-system/admin.php
User: ___________________________
Pass: ___________________________
```

### Wichtige Pfade:
```
Projekt-Root: /home/username/public_html/
Backup-System: /home/username/public_html/backup-system/
Datenbank-Backups: /home/username/public_html/backup-system/backups/database/
Datei-Backups: /home/username/public_html/backup-system/backups/files/
Logs: /home/username/public_html/backup-system/backups/logs/
```

---

## 🧪 TESTEN VOR DEM NOTFALL!

**Führe JETZT einen Test durch:**

```bash
# Test 1: Backup erstellen
https://deine-domain.de/backup-system/admin.php
→ "💾 Datenbank-Backup" klicken
→ Prüfen ob erfolgreich

# Test 2: Notfall-Wiederherstellung testen
1. Irgendeine harmlose Änderung in der DB machen
2. "NOTFALL-WIEDERHERSTELLUNG" klicken
3. Prüfen ob Änderung weg ist
4. Rollback-Punkt wurde erstellt?

# Test 3: Rollback testen
1. Tab "Rollback-Punkte"
2. Neuesten Punkt auswählen
3. "Rollback" klicken
4. System im Ausgangszustand?
```

---

## ⏱️ Zeitplan für verschiedene Szenarien

| Szenario | Dauer | Komplexität |
|----------|-------|-------------|
| Nur Datenbank wiederherstellen | 30-60 Sek | ⭐ Einfach |
| DB + Kleine Dateien | 2-3 Min | ⭐⭐ Mittel |
| Vollständige Wiederherstellung | 5-10 Min | ⭐⭐⭐ Komplex |
| Rollback durchführen | 30-60 Sek | ⭐ Einfach |
| Manuelle Wiederherstellung | 10-20 Min | ⭐⭐⭐ Komplex |

---

## 🎯 Checkliste nach Notfall-Wiederherstellung

Nach erfolgreicher Wiederherstellung UNBEDINGT prüfen:

- [ ] Hauptsystem erreichbar?
- [ ] Admin-Dashboard funktioniert?
- [ ] Datenbank-Tabellen vollständig?
- [ ] Login funktioniert?
- [ ] Kritische Funktionen testen
- [ ] Logs auf Fehler prüfen
- [ ] Alle Passwörter ändern
- [ ] Firewall-Regeln aktualisieren
- [ ] Angreifer-IPs blockieren
- [ ] Security-Logs analysieren
- [ ] Neues Backup erstellen

---

## 🔒 Nach dem Notfall: Sicherheit erhöhen

```bash
# 1. Alle Passwörter ändern
# - Backup-System
# - Admin-Dashboard
# - Datenbank
# - FTP/SSH
# - Hostinger-Account

# 2. IP-Whitelist aktivieren (optional)
# In config.php:
$allowedIPs = ['deine-ip-adresse', '...'];

# 3. 2FA aktivieren (falls möglich)

# 4. Monitoring einrichten
tail -f backup-system/backups/logs/security.log

# 5. Externe Backups aktivieren
# → FTP-Upload konfigurieren
# → Lokale externe Festplatte
```

---

## 📋 Notfall-Checkliste (Zum Ausdrucken!)

```
☐ 1. Ruhe bewahren! System kann wiederhergestellt werden
☐ 2. Admin-Interface öffnen: backup-system/admin.php
☐ 3. Einloggen
☐ 4. "NOTFALL-WIEDERHERSTELLUNG" klicken
☐ 5. Bestätigen & Warten
☐ 6. System testen
☐ 7. Rollback-Punkt prüfen
☐ 8. Security-Logs analysieren
☐ 9. Angreifer-IP blockieren
☐ 10. Alle Passwörter ändern
☐ 11. Neues Backup erstellen
☐ 12. Monitoring aktivieren
```

---

## 🆘 Wenn GAR NICHTS funktioniert...

### Absoluter Notfall-Plan:

```bash
# 1. Neueste Backups von Server laden
scp user@server:/pfad/backup-system/backups/database/* ./local-backup/

# 2. Lokal entpacken
gunzip db_backup_XXX.sql.gz

# 3. Neue saubere Installation aufsetzen
# 4. Backup importieren
mysql -u root -p neue_datenbank < db_backup_XXX.sql

# 5. Dateien von Backup wiederherstellen
tar -xzf files_backup_XXX.tar.gz

# 6. Konfiguration anpassen
nano config/database.php
```

---

## 📞 Hilfe holen

Wenn du wirklich nicht weiterkommst:

1. **Hostinger Support** kontaktieren
2. **Backup-Dateien sichern** (herunterladen!)
3. **Screenshots von Fehlermeldungen** machen
4. **Logs bereitstellen**:
   - security.log
   - backup_XXX.log
   - restore_XXX.log

---

## ✅ Wichtigste Regel

**VOR dem Notfall vorbereitet sein:**
- ✅ Test-Wiederherstellung durchgeführt?
- ✅ Zugangsdaten notiert?
- ✅ Rollback-Funktion verstanden?
- ✅ Diese Anleitung ausgedruckt/gespeichert?

---

**Im Notfall: Schnell handeln, aber nicht panisch!**

**Dein System kann IMMER wiederhergestellt werden! 🛡️**

---

**Support-Info:**
```
Dokumentation: /backup-system/README.md
Security: /backup-system/SECURITY.md
Quickstart: /backup-system/QUICKSTART.md
Emergency: /backup-system/EMERGENCY.md (diese Datei)
```

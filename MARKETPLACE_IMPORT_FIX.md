# Fix: Marketplace Import Fehler

## Problem
Beim Import von Vendor-Belohnungen im Marktplatz-Tab "Belohnungen" tritt der Fehler auf:
```
Fehler: Datenbankfehler beim Import
```

## Ursache
Die Tabelle `reward_template_imports` existiert nicht in der Datenbank. Diese Tabelle wird benötigt, um zu tracken, welche Vendor-Templates bereits importiert wurden.

## Lösung

### Option 1: Über Browser (Empfohlen)
1. Öffne: `https://app.mehr-infos-jetzt.de/database/migrate_reward_template_imports.php`
2. Das Skript prüft automatisch, ob die Tabelle existiert und erstellt sie wenn nötig
3. Du siehst eine Erfolgsmeldung mit der Tabellenstruktur

### Option 2: Via FTP
1. Verbinde dich mit deinem Server via FTP
2. Navigiere zu: `/database/`
3. Öffne die Datei `migrate_reward_template_imports.php` im Browser

### Option 3: phpMyAdmin
1. Öffne phpMyAdmin
2. Wähle deine Datenbank aus
3. Gehe auf "SQL"
4. Kopiere den Inhalt aus `database/migrations/2025-11-20_create_reward_template_imports.sql`
5. Führe das SQL aus

## Was wurde geändert?

### 1. Migration erstellt
- `database/migrations/2025-11-20_create_reward_template_imports.sql`
- `database/migrate_reward_template_imports.php`

### 2. API verbessert
- `api/vendor/marketplace-import.php`
- Bessere Fehlerbehandlung
- Graceful handling wenn Tabelle fehlt
- Detailliertere Fehlermeldungen

## Nach der Migration

Nach erfolgreicher Migration kannst du:
1. Zurück zum Marktplatz gehen
2. Tab "Belohnungen" öffnen
3. Vendor-Belohnungen ohne Fehler importieren

## Weitere Verbesserungen

Die API wurde auch verbessert um:
- Fehlende Tabellen zu erkennen
- Hilfreiche Fehlermeldungen zu geben
- Im Notfall auch ohne Import-Tracking zu funktionieren (allerdings ohne Duplikat-Check)

## Backup

Eine Sicherung der Original-Dateien liegt im Deployment-Backup.

## Support

Bei Fragen oder Problemen:
- Prüfe die Error-Logs: `tail -f /var/log/php_errors.log`
- Kontaktiere Support

---

**Datum:** 2025-11-20  
**Version:** 1.0  
**Status:** Getestet und bereit für Deployment

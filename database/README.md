# 🗄️ Datenbank Migrationen

## Schnellstart

### Option 1: Via Kommandozeile (empfohlen)

```bash
cd /pfad/zu/deinem/projekt
php database/run-migrations.php
```

### Option 2: Via Browser

1. **WICHTIG**: Öffne zuerst `database/run-migrations.php` und ändere das Passwort:
   ```php
   $ADMIN_PASSWORD = 'dein-sicheres-passwort-2024'; // ÄNDERE DIES!
   ```

2. Rufe auf:
   ```
   https://app.mehr-infos-jetzt.de/database/run-migrations.php?password=DEIN_PASSWORT
   ```

## Was macht das Script?

Das Migrations-Script:
- ✅ Prüft die Datenbankverbindung
- ✅ Erstellt eine `migrations` Tracking-Tabelle
- ✅ Führt alle `.sql` Dateien aus `/database/migrations/` aus
- ✅ Überspringt bereits ausgeführte Migrationen
- ✅ Zeigt detaillierte Erfolgs- und Fehlermeldungen
- ✅ Verifiziert die Installation der `customer_tracking` Tabelle

## Ausgabe-Beispiel

```
🚀 Database Migration Runner
============================

✅ Datenbankverbindung erfolgreich

✅ Migrations-Tracking-Tabelle bereit

📂 Gefundene Migrationen:
   - 002_customer_tracking.sql

🔄 Führe aus: 002_customer_tracking.sql ... ✅ Erfolgreich

============================
📊 Zusammenfassung:
   ✅ Ausgeführt: 1
   ⏭️  Übersprungen: 0
   ❌ Fehler: 0

✅ Tracking-Tabelle 'customer_tracking' existiert

📋 Tabellen-Struktur:
   • id (int(11))
   • customer_id (int(11))
   • type (enum('page_view','click','event','time_spent'))
   • page (varchar(255))
   ...

🎉 Fertig!
```

## Sicherheit

- 🔒 **CLI-Modus**: Direkte Ausführung ohne Passwort
- 🔒 **Browser-Modus**: Passwort-geschützt
- 🔒 **Tracking**: Verhindert doppelte Ausführung von Migrationen
- ⚠️ **Wichtig**: Lösche oder schütze das Script nach Ausführung!

## Troubleshooting

### Fehler: "Datenbankverbindung fehlgeschlagen"
- Prüfe `/config/database.php`
- Stelle sicher, dass die DB-Credentials korrekt sind

### Fehler: "Table already exists"
- Normal wenn Migration bereits ausgeführt wurde
- Script überspringt automatisch bereits ausgeführte Migrationen

### Fehler: "Permission denied"
```bash
chmod +x database/run-migrations.php
```

## Neue Migration hinzufügen

1. Erstelle eine neue `.sql` Datei in `/database/migrations/`
2. Benenne sie mit Nummer-Präfix: `003_dein_feature.sql`
3. Führe `run-migrations.php` erneut aus

Beispiel:
```sql
-- 003_add_customer_notes.sql
CREATE TABLE customer_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

## Nach der Migration

1. ✅ Teste das Dashboard: `https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=overview`
2. ✅ Prüfe ob Tracking funktioniert
3. ✅ Schaue in die Browser-Konsole auf Fehler
4. ✅ **Lösche oder schütze das Migrations-Script!**

```bash
# Script löschen (optional)
rm database/run-migrations.php

# Oder schützen mit .htaccess
echo "Deny from all" > database/.htaccess
```

## Support

Bei Problemen:
1. Prüfe die PHP Error Logs
2. Teste die Datenbankverbindung
3. Stelle sicher, dass der DB-User die nötigen Rechte hat:
   ```sql
   GRANT ALL PRIVILEGES ON datenbankname.* TO 'username'@'localhost';
   FLUSH PRIVILEGES;
   ```

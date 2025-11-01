# Setup-Anleitung - Kundengesteuerte Freebie-Erstellung

## 🚀 Zwei Setup-Optionen

### Option 1: Automatisches PHP-Setup (EMPFOHLEN - am einfachsten!)

Perfekt wenn du:
- ✅ Direkten Zugriff auf deinen Webserver hast
- ✅ Die Datenbank-Einrichtung per Browser machen möchtest
- ✅ Keine SSH/Terminal-Kenntnisse brauchst

#### Schritte:

**1. Sicherheits-Token ändern**
Öffne `setup/setup-customer-freebies-auto.php` und ändere in Zeile 9:
```php
define('SETUP_TOKEN', 'dein-eigener-geheimer-token-12345');
```

**2. Skript im Browser aufrufen**
```
https://deine-domain.de/setup/setup-customer-freebies-auto.php?token=dein-eigener-geheimer-token-12345
```

**3. Setup durchführen**
- Das Skript erstellt automatisch alle Tabellen
- Fügt Beispiel-Konfigurationen ein
- Zeigt dir alle notwendigen nächsten Schritte
- Gibt dir die Webhook-URL für Digistore24

**4. Datei löschen (WICHTIG!)**
Nach erfolgreichem Setup SOFORT löschen:
```bash
rm setup/setup-customer-freebies-auto.php
```

---

### Option 2: Manuelles SQL-Setup

Perfekt wenn du:
- ✅ SSH-Zugriff hast
- ✅ Direkt mit der Datenbank arbeiten möchtest
- ✅ Mehr Kontrolle über den Prozess haben willst

#### Variante A: Via MySQL CLI

```bash
# Ins Setup-Verzeichnis wechseln
cd setup

# SQL-Datei ausführen
mysql -u dein_benutzer -p deine_datenbank < setup-customer-freebie-limits.sql

# Erfolgreich? Dann weitermachen mit Konfiguration
```

#### Variante B: Via phpMyAdmin

1. Öffne phpMyAdmin
2. Wähle deine Datenbank
3. Gehe zu "SQL"
4. Öffne `setup-customer-freebie-limits.sql` in einem Texteditor
5. Kopiere den Inhalt und füge ihn in phpMyAdmin ein
6. Klicke "Ausführen"

#### Variante C: Via Bash-Skript (automatisiert)

```bash
# Skript ausführbar machen
chmod +x install-customer-freebies.sh

# Skript starten
./install-customer-freebies.sh

# Folge den Anweisungen im Terminal
```

---

## 📋 Was wird eingerichtet?

### Neue Datenbank-Tabellen:

1. **`customer_freebie_limits`**
   - Speichert Freebie-Limits pro Kunde
   - Verknüpft mit Produkt-IDs
   - Automatisch aktualisiert via Webhook

2. **`product_freebie_config`**
   - Produkt-Konfigurationen (Digistore24 Produkt-IDs)
   - Freebie-Limits pro Produkt
   - Aktivierungsstatus

### Erweiterte Tabellen:

3. **`customer_freebies`** (neue Spalte)
   - `freebie_type` ENUM('template', 'custom')
   - Unterscheidet Template-basierte von eigenen Freebies
   - Neue Indizes für bessere Performance

### Beispiel-Daten:

4. **Standard-Produkt-Konfigurationen**
   - STARTER_001 → 5 Freebies
   - PROFESSIONAL_002 → 10 Freebies
   - ENTERPRISE_003 → 25 Freebies
   - UNLIMITED_004 → 999 Freebies

---

## ✅ Nach dem Setup

### 1. Admin-Panel öffnen
```
https://deine-domain.de/admin/freebie-limits.php
```

Hier kannst du:
- Produkt-Konfigurationen anpassen
- Neue Produkte hinzufügen
- Limits ändern
- Statistiken einsehen

### 2. Produkt-IDs anpassen

**WICHTIG:** Die Beispiel-Produkt-IDs (`STARTER_001`, etc.) müssen durch deine echten Digistore24-Produkt-IDs ersetzt werden!

Im Admin-Panel:
1. Klicke auf "Bearbeiten" bei jedem Produkt
2. Ändere die Produkt-ID zu deiner echten Digistore24-ID
3. Passe Name und Limit an
4. Speichern

### 3. Webhook in Digistore24 konfigurieren

**Webhook-URL:**
```
https://deine-domain.de/webhook/digistore24.php
```

**In Digistore24:**
1. Gehe zu Einstellungen → IPN/Webhook
2. Trage die Webhook-URL ein
3. Aktiviere diese Events:
   - ✅ `payment.success`
   - ✅ `subscription.created`
   - ✅ `refund.created`
4. Speichern

### 4. System testen

**Test-Kauf durchführen:**
1. Mache einen Test-Kauf über Digistore24
2. Prüfe Webhook-Logs: `webhook/webhook-logs.txt`
3. Prüfe Datenbank:
   ```sql
   SELECT * FROM customer_freebie_limits;
   ```

**Als Kunde testen:**
1. Logge dich als Test-Kunde ein
2. Gehe zu Dashboard → Freebies
3. Klicke "Eigenes Freebie erstellen"
4. Erstelle ein Test-Freebie
5. Prüfe ob Links generiert werden

---

## 🔍 Setup überprüfen

### Datenbank-Check
```sql
-- Prüfe ob alle Tabellen existieren
SHOW TABLES LIKE '%freebie%';

-- Sollte zeigen:
-- customer_freebies
-- customer_freebie_limits
-- product_freebie_config
-- freebies

-- Prüfe Produkt-Konfigurationen
SELECT * FROM product_freebie_config;

-- Prüfe Spalte freebie_type
DESCRIBE customer_freebies;
```

### Datei-Check
```bash
# Prüfe ob alle wichtigen Dateien existieren
ls -la ../webhook/digistore24.php
ls -la ../customer/custom-freebie-editor.php
ls -la ../api/save-custom-freebie.php
ls -la ../admin/freebie-limits.php
```

### Webhook-Test
```bash
# Prüfe ob Webhook erreichbar ist
curl -I https://deine-domain.de/webhook/digistore24.php

# Sollte 200 oder 400 zurückgeben (nicht 404 oder 500)
```

---

## ⚠️ Troubleshooting

### Problem: "Table already exists"
**Lösung:** Tabellen existieren bereits. Alles gut! Setup war schon erfolgreich.

### Problem: "Cannot add foreign key"
**Lösung:** 
```sql
-- Prüfe ob users-Tabelle existiert
SHOW TABLES LIKE 'users';

-- Wenn nicht, zuerst Haupt-Setup ausführen
```

### Problem: Setup-Skript zeigt Fehler
**Lösung:**
1. Prüfe Datenbank-Zugangsdaten in `config/database.php`
2. Prüfe ob PDO-Extension installiert ist: `php -m | grep PDO`
3. Prüfe Datei-Berechtigungen: `chmod 644 setup/*.php`

### Problem: "Access denied" bei Webhook
**Lösung:**
1. Prüfe Firewall-Einstellungen
2. Stelle sicher dass Digistore24 IPs nicht blockiert sind
3. Prüfe .htaccess Regeln

---

## 📊 Erwartete Ergebnisse

Nach erfolgreichem Setup solltest du haben:

✅ 3 neue/erweiterte Datenbank-Tabellen  
✅ 4 Standard-Produkt-Konfigurationen  
✅ Funktionierendes Admin-Panel  
✅ Funktionierenden Custom Freebie Editor  
✅ Webhook empfängt Digistore24 Events  
✅ Kunden können eigene Freebies erstellen  
✅ Limits werden automatisch gesetzt  

---

## 🆘 Hilfe & Support

### Logs überprüfen
```bash
# Webhook-Logs
tail -f webhook/webhook-logs.txt

# PHP Error-Log (Pfad kann variieren)
tail -f /var/log/apache2/error.log
```

### Datenbank-Diagnose
```sql
-- Zähle Custom Freebies
SELECT COUNT(*) FROM customer_freebies WHERE freebie_type = 'custom';

-- Zeige alle Kunden mit Limits
SELECT 
    u.name, 
    u.email, 
    cfl.freebie_limit, 
    cfl.product_name
FROM customer_freebie_limits cfl
JOIN users u ON cfl.customer_id = u.id;

-- Zeige Limit-Auslastung pro Kunde
SELECT 
    u.name,
    cfl.freebie_limit as 'Limit',
    COUNT(cf.id) as 'Erstellt',
    (cfl.freebie_limit - COUNT(cf.id)) as 'Verfügbar'
FROM customer_freebie_limits cfl
JOIN users u ON cfl.customer_id = u.id
LEFT JOIN customer_freebies cf ON cfl.customer_id = cf.customer_id 
    AND cf.freebie_type = 'custom'
GROUP BY cfl.customer_id;
```

### Support-Kontakt
Bei Problemen kontaktiere den Support mit:
- Screenshot des Fehlers
- Relevante Log-Einträge
- Datenbank-Status (siehe Diagnose-Queries oben)

---

## 📚 Weiterführende Dokumentation

- **Vollständige Dokumentation:** `../CUSTOMER_FREEBIES_README.md`
- **Quick-Start-Anleitung:** `../CUSTOMER_FREEBIES_QUICKSTART.md`
- **SQL-Schema:** `setup-customer-freebie-limits.sql`

---

**Version:** 1.0.0  
**Erstellt:** November 2025  
**Letzte Aktualisierung:** November 2025

# Dashboard Statistiken - Diagnose & Fix Guide

## 🚨 Problem
Im Customer Dashboard werden keine Werte für Freebies, Kurse, etc. angezeigt.

## 📋 Diagnose-Schritte

### Schritt 1: Debug-Script ausführen
1. Öffne: `https://app.mehr-infos-jetzt.de/customer/debug-overview-stats.php`
2. Logge dich als Kunde ein
3. Das Script zeigt dir:
   - ✅ Was funktioniert
   - ❌ Was fehlschlägt
   - 📊 Welche Tabellen existieren
   - 🔢 Wie viele Einträge vorhanden sind

### Schritt 2: Häufige Probleme identifizieren

#### Problem A: Tabellen fehlen
**Symptom:** Script zeigt "Tabelle nicht gefunden"

**Lösung:**
```sql
-- Prüfe welche Tabellen existieren
SHOW TABLES;

-- Wichtige Tabellen die existieren müssen:
- customer_freebies
- freebies
- course_access
- courses
- customer_tracking (optional)
- freebie_click_analytics (optional)
```

#### Problem B: Keine Daten vorhanden
**Symptom:** Tabellen existieren, aber COUNT() = 0

**Mögliche Ursachen:**
1. Customer hat noch keine Freebies freigeschaltet
2. Customer ID stimmt nicht
3. Daten wurden noch nicht angelegt

**Lösung:**
```sql
-- Prüfe ob Freebies existieren
SELECT * FROM freebies LIMIT 5;

-- Prüfe ob Customer freigeschaltete Freebies hat
SELECT * FROM customer_freebies WHERE customer_id = DEINE_ID;

-- Wenn leer: Erstelle Test-Freebies oder schalte welche frei
```

#### Problem C: Session/Customer ID fehlt
**Symptom:** Script zeigt "Customer ID: " (leer)

**Lösung:**
1. Prüfe ob Login funktioniert
2. Prüfe Session in `dashboard.php`:
```php
echo "Customer ID: " . ($customer_id ?? 'NICHT GESETZT');
echo "Session: ";
print_r($_SESSION);
```

#### Problem D: PDO Verbindung fehlt
**Symptom:** "PDO Verbindung nicht verfügbar"

**Lösung:**
1. Prüfe `config/database.php`
2. Teste Verbindung:
```php
require_once 'config/database.php';
echo "PDO Connected: " . ($pdo instanceof PDO ? 'YES' : 'NO');
```

## 🔧 Fixes

### Fix 1: Debug-Modus aktivieren (EMPFOHLEN)
Verwende die neue `overview-debug.php`:

1. **Backup erstellen:**
```bash
cp customer/sections/overview.php customer/sections/overview-backup.php
```

2. **Debug-Version aktivieren:**
```bash
cp customer/sections/overview-debug.php customer/sections/overview.php
```

3. **Dashboard aufrufen:**
   - Rechts unten erscheint eine grüne Debug-Box
   - Zeigt alle Query-Ergebnisse und Fehler

4. **Nach dem Fix:**
   - Setze `$debug_mode = false;` in Zeile 17

### Fix 2: Manuelle Fehlerbehebung

#### Wenn Freebies fehlen:
```sql
-- Schalte Test-Freebies frei
INSERT INTO customer_freebies (customer_id, freebie_id, unlocked_at)
SELECT 1, id, NOW() FROM freebies LIMIT 3;
-- Ersetze "1" mit deiner Customer ID
```

#### Wenn Kurse fehlen:
```sql
-- Gib Zugriff auf Test-Kurse
INSERT INTO course_access (user_id, course_id, granted_at)
SELECT 1, id, NOW() FROM courses WHERE is_active = 1 LIMIT 2;
-- Ersetze "1" mit deiner Customer ID
```

#### Wenn Tracking-Tabellen fehlen:
```sql
-- Erstelle customer_tracking Tabelle
CREATE TABLE IF NOT EXISTS customer_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    page VARCHAR(255),
    duration INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_id, type),
    INDEX idx_created_at (created_at)
);

-- Erstelle freebie_click_analytics Tabelle
CREATE TABLE IF NOT EXISTS freebie_click_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    freebie_id INT NOT NULL,
    click_date DATE NOT NULL,
    click_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_clicks (customer_id, freebie_id, click_date)
);
```

## ⚡ Quick Fix Cheat Sheet

### Problem: Alle Werte sind 0
```bash
# 1. Debug-Script aufrufen
https://app.mehr-infos-jetzt.de/customer/debug-overview-stats.php

# 2. Fehler identifizieren (siehe Output)

# 3. Je nach Fehler:
#    - Tabellen fehlen? → SQL Scripts ausführen (siehe oben)
#    - Daten fehlen? → Test-Daten anlegen (siehe oben)
#    - PDO fehlt? → database.php prüfen
#    - Session fehlt? → Login prüfen
```

### Problem: Nur bestimmte Werte sind 0
```bash
# Einzelne Queries testen:

# Test Freebies:
SELECT COUNT(*) FROM customer_freebies cf
INNER JOIN freebies f ON cf.freebie_id = f.id
WHERE cf.customer_id = DEINE_ID;

# Test Kurse:
SELECT COUNT(*) FROM course_access WHERE user_id = DEINE_ID;

# Test Klicks:
SELECT COALESCE(SUM(click_count), 0) 
FROM freebie_click_analytics 
WHERE customer_id = DEINE_ID;
```

## 🎯 Finaler Check

Nach dem Fix:
1. ✅ Dashboard aufrufen: `https://app.mehr-infos-jetzt.de/customer/dashboard.php`
2. ✅ Werte werden angezeigt?
3. ✅ Keine Fehler in der Debug-Box?
4. ✅ Debug-Modus deaktivieren (`$debug_mode = false;`)
5. ✅ Backup löschen

## 📞 Wenn nichts funktioniert

Sende mir den Output von:
1. `debug-overview-stats.php` (Screenshot)
2. Debug-Box Content (Screenshot)
3. Browser Console Errors (F12 → Console)

## 🔐 Sicherheit

**WICHTIG:** Nach dem Fix:
- Debug-Script löschen oder umbenennen
- Debug-Modus in overview.php deaktivieren
- Niemals Credentials in Screenshots teilen!
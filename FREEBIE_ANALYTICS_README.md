# 📊 Freebie Click Analytics System

## Übersicht

Ein vollständiges historisches Click-Tracking-System mit **echten Analytics-Daten** für KI Leadsystem. Das System trackt jeden Seitenaufruf, speichert historische Daten und bietet umfassende Performance-Analytics.

## ✨ Features

### 1. **Automatisches Click-Tracking**
- ✅ Jeder Freebie-Aufruf wird automatisch getrackt
- ✅ Session-basiertes Unique-Tracking (Cookie)
- ✅ IP-Anonymisierung (DSGVO-konform)
- ✅ User-Agent & Referrer-Tracking
- ✅ Real-time Updates

### 2. **Historische Datenbank**
- 📅 Tägliche Aggregation der Klicks
- 📊 30-Tage-Verlauf für Charts
- 🔍 Detaillierte Logs für Analyse
- 🗄️ Automatische Bereinigung alter Logs (90 Tage)

### 3. **Performance-Analytics**
- 📈 Echte Line-Charts mit Chart.js
- 🎯 Top-Performer-Ranking
- 💡 Durchschnitts-Berechnungen
- 🏆 Achievement-System

### 4. **DSGVO-Konform**
- 🔒 IP-Anonymisierung
- 🍪 Cookie-Banner Integration
- 📝 Automatische Daten-Löschung
- ✅ Opt-in Tracking

## 🗄️ Datenbankstruktur

### Tabellen

#### `freebie_click_analytics`
Haupttabelle für tägliche Aggregation:
```sql
- id (Primary Key)
- customer_id (Foreign Key → users)
- freebie_id (Foreign Key → customer_freebies)
- click_date (DATE)
- click_count (INT)
- unique_clicks (INT)
- conversion_count (INT)
- created_at, updated_at
```

**Indizes:**
- `idx_customer_date` (customer_id, click_date)
- `idx_freebie_date` (freebie_id, click_date)
- `unique_freebie_date` (freebie_id, click_date) - UNIQUE

#### `freebie_click_logs`
Detaillierte Logs für erweiterte Analyse:
```sql
- id (Primary Key)
- freebie_id (Foreign Key)
- customer_id (Foreign Key)
- ip_address (VARCHAR(45)) - anonymisiert
- user_agent (VARCHAR(255))
- referrer (VARCHAR(500))
- click_timestamp (TIMESTAMP)
- session_id (VARCHAR(100))
- is_unique (TINYINT)
- converted (TINYINT)
```

**Indizes:**
- `idx_freebie_timestamp` (freebie_id, click_timestamp)
- `idx_customer_timestamp` (customer_id, click_timestamp)
- `idx_session` (session_id)

### View

#### `v_freebie_analytics_summary`
Zusammenfassende Statistiken pro Freebie:
```sql
SELECT 
    customer_id,
    freebie_id,
    freebie_name,
    total_clicks,
    total_unique_clicks,
    total_conversions,
    first_click_date,
    last_click_date,
    active_days,
    avg_clicks_per_day
FROM v_freebie_analytics_summary;
```

### Stored Procedure

#### `sp_track_freebie_click`
Zentrale Tracking-Funktion:
```sql
CALL sp_track_freebie_click(
    freebie_id,
    customer_id,
    is_unique,
    ip_address,
    user_agent,
    referrer,
    session_id
);
```

**Funktionen:**
1. Inkrementiert täglichen Counter in `freebie_click_analytics`
2. Updated Gesamt-Counter in `customer_freebies`
3. Speichert detaillierten Log in `freebie_click_logs`
4. Transaktional sicher

## 🚀 Installation

### Schritt 1: Setup ausführen

Das Setup-Script erstellt alle Tabellen, Views, Procedures und migriert bestehende Daten:

```bash
# Via Browser (empfohlen)
https://app.mehr-infos-jetzt.de/setup/setup-freebie-analytics.php

# Oder via CLI
php setup/setup-freebie-analytics.php
```

**Was das Script macht:**
1. ✅ Erstellt Analytics-Tabellen
2. ✅ Erstellt Views und Procedures
3. ✅ Migriert bestehende Klick-Daten
4. ✅ Richtet automatische Bereinigung ein

### Schritt 2: Tracking verifizieren

1. Öffne ein Freebie in einem Browser
2. Prüfe Browser-Console: `Tracking: ✓ Tracked`
3. Gehe zu Dashboard → Fortschritt
4. Chart sollte Daten anzeigen

### Schritt 3: Events aktivieren (falls nötig)

```sql
SET GLOBAL event_scheduler = ON;
```

## 📊 Verwendung

### Im Frontend

Das Tracking passiert **automatisch** beim Laden eines Freebies:

```javascript
// Wird automatisch in freebie/index.php ausgeführt
fetch('/api/track-freebie-click.php', {
    method: 'POST',
    body: new URLSearchParams({
        freebie_id: 123,
        customer_id: 456
    })
});
```

### Fortschritt-Dashboard

URL: `customer/dashboard.php?page=fortschritt`

**Zeigt:**
- 📊 Performance Chart (30 Tage)
- 🏆 Top 5 Performer
- 🎯 Achievement-System
- 📈 Kurs-Fortschritt
- ⏱️ Aktivitäts-Timeline

### Abfragen

```sql
-- Klicks der letzten 30 Tage für einen Kunden
SELECT click_date, SUM(click_count) as clicks
FROM freebie_click_analytics
WHERE customer_id = 123
AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY click_date
ORDER BY click_date;

-- Top 10 Freebies
SELECT * FROM v_freebie_analytics_summary
ORDER BY total_clicks DESC
LIMIT 10;

-- Conversion Rate
SELECT 
    freebie_id,
    SUM(click_count) as total_clicks,
    SUM(conversion_count) as conversions,
    ROUND((SUM(conversion_count) / SUM(click_count)) * 100, 2) as conversion_rate
FROM freebie_click_analytics
GROUP BY freebie_id;
```

## 🔧 API-Endpunkte

### POST `/api/track-freebie-click.php`

Trackt einen Freebie-Click:

**Request:**
```
POST /api/track-freebie-click.php
Content-Type: application/x-www-form-urlencoded

freebie_id=123&customer_id=456
```

**Response:**
```json
{
    "success": true,
    "tracked": true,
    "unique": true,
    "timestamp": "2025-11-02 15:30:00"
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Missing required parameters"
}
```

## 🔐 Datenschutz & Sicherheit

### DSGVO-Compliance

1. **IP-Anonymisierung:**
   ```php
   // Letztes Oktett wird entfernt
   192.168.1.123 → 192.168.1.0
   ```

2. **Cookie-Banner:**
   - Tracking nur nach Zustimmung
   - Opt-out möglich
   - LocalStorage für Präferenzen

3. **Automatische Löschung:**
   - Detaillierte Logs: 90 Tage
   - Aggregierte Daten: unbegrenzt

4. **Session-Tracking:**
   - Cookie-basiert
   - 30-Tage-Gültigkeit
   - Keine personenbezogenen Daten

### Sicherheit

- ✅ Prepared Statements (SQL-Injection-Schutz)
- ✅ XSS-Protection via `htmlspecialchars()`
- ✅ Rate-Limiting (via Session)
- ✅ Foreign Key Constraints
- ✅ Transaktionale Integrität

## 📈 Performance

### Optimierungen

1. **Indizes:**
   - Composite-Index für Datums-Range-Queries
   - Session-ID Index für Unique-Check

2. **Aggregation:**
   - Tägliche Zusammenfassung statt Einzelzeilen
   - Batch-Updates via Stored Procedure

3. **Automatische Bereinigung:**
   - Event-Scheduler löscht alte Logs
   - Reduziert Tabellengröße

### Erwartete Performance

- **Tracking-Request:** < 50ms
- **Chart-Abfrage:** < 100ms (30 Tage)
- **Dashboard-Ladezeit:** < 500ms

### Skalierung

Bei hohem Traffic:
1. Redis-Cache für häufige Queries
2. Async-Tracking via Queue
3. Separate Analytics-Datenbank
4. CDN für Chart.js

## 🐛 Troubleshooting

### Problem: Chart zeigt keine Daten

**Lösung:**
```sql
-- Prüfen ob Tabelle existiert
SELECT COUNT(*) FROM freebie_click_analytics;

-- Prüfen ob Daten vorhanden
SELECT * FROM freebie_click_analytics 
WHERE customer_id = YOUR_ID
LIMIT 10;
```

### Problem: Tracking funktioniert nicht

**Checks:**
1. Browser Console → Network Tab
2. `track-freebie-click.php` Status 200?
3. Response JSON enthält `"success": true`?
4. PHP Error Logs prüfen

### Problem: "Beta" Badge im Chart

**Bedeutung:** Analytics-Tabelle existiert nicht

**Lösung:**
```bash
php setup/setup-freebie-analytics.php
```

### Problem: Events laufen nicht

**Lösung:**
```sql
SET GLOBAL event_scheduler = ON;
SHOW EVENTS;
```

## 🔮 Zukünftige Erweiterungen

### Geplante Features

1. **Erweiterte Metriken:**
   - Verweildauer
   - Bounce Rate
   - Traffic-Quellen
   - Device-Types
   - Geo-Location (Land/Stadt)

2. **A/B-Testing:**
   - Template-Vergleiche
   - CTA-Optimierung
   - Headline-Tests

3. **Conversion-Tracking:**
   - Lead-Qualität
   - Email-Öffnungsraten
   - Download-Rates

4. **Export-Funktionen:**
   - CSV-Export
   - PDF-Reports
   - Scheduled Reports per Email

5. **Heatmaps:**
   - Click-Heatmaps
   - Scroll-Depth
   - Hover-Tracking

6. **Realtime-Dashboard:**
   - Live-Klicks
   - Aktive Besucher
   - WebSocket-Updates

## 📝 Maintenance

### Regelmäßige Tasks

1. **Wöchentlich:**
   - Performance-Metriken überprüfen
   - Disk-Space monitoren

2. **Monatlich:**
   - Alte Analytics-Daten archivieren
   - Index-Optimierung

3. **Jährlich:**
   - DSGVO-Compliance Review
   - Security-Audit

### Backup

```bash
# Analytics-Daten sichern
mysqldump -u user -p database \
    freebie_click_analytics \
    freebie_click_logs > analytics_backup.sql
```

### Restore

```bash
mysql -u user -p database < analytics_backup.sql
```

## 📞 Support

### Logs

```bash
# PHP Error Log
tail -f /var/log/php-errors.log

# MySQL Query Log
tail -f /var/log/mysql/query.log
```

### Debug-Mode

```php
// In track-freebie-click.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Kontakt

Bei Problemen oder Fragen:
- 📧 GitHub Issues
- 📚 Dokumentation: `/docs`
- 🐛 Bug Reports: GitHub

---

## 📊 Changelog

### Version 1.0.0 (2025-11-02)
- ✅ Initial Release
- ✅ Basic Click Tracking
- ✅ Historical Data Storage
- ✅ Performance Charts
- ✅ Achievement System
- ✅ DSGVO Compliance
- ✅ Auto-Migration

---

**Version**: 1.0.0  
**Status**: ✅ Production Ready  
**Last Updated**: 2025-11-02  
**Dependencies**: Chart.js 4.x, PHP 7.4+, MySQL 5.7+

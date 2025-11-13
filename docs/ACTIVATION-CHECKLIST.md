# ✅ Aktivierungs-Checkliste: Vereintes Lead-Dashboard

## 📋 Vor der Aktivierung

### Backup erstellen
- [ ] Vollständiges Datenbank-Backup erstellt
- [ ] Alle Dateien gesichert (`/freebie/*`, `/customer/*`)
- [ ] Backup-Location dokumentiert
- [ ] Restore-Prozess getestet

### Systemvoraussetzungen prüfen
- [ ] PHP 7.4+ installiert
- [ ] MySQL 5.7+ läuft
- [ ] PDO Extension aktiviert
- [ ] Sessions funktionieren
- [ ] Schreibrechte auf `/tmp` oder Session-Ordner
- [ ] `.htaccess` erlaubt (falls Apache)

### Bestehende Daten prüfen
- [ ] Customer-Freebies sind angelegt
- [ ] Freebie-Courses sind verknüpft
- [ ] Empfehlungsprogramm ist konfiguriert (falls gewünscht)
- [ ] Belohnungsstufen sind definiert (falls Empfehlungsprogramm)
- [ ] Kunden haben `ref_code` gesetzt (falls Empfehlungsprogramm)

## 🚀 Installation

### Schritt 1: Dateien hochladen
- [ ] `freebie/thankyou-new.php` hochgeladen
- [ ] `lead-dashboard-unified.php` hochgeladen
- [ ] `migrations/unified-lead-dashboard.php` hochgeladen
- [ ] `docs/` Ordner hochgeladen (optional)
- [ ] `setup-unified-dashboard.html` hochgeladen (optional)

### Schritt 2: Datenbank-Migration
```bash
# Im Browser öffnen
https://app.mehr-infos-jetzt.de/migrations/unified-lead-dashboard.php
```

- [ ] Migration erfolgreich ausgeführt
- [ ] Alle Tabellen erstellt:
  - [ ] `lead_login_tokens`
  - [ ] `lead_users` (Spalten geprüft)
  - [ ] `lead_referrals`
  - [ ] `reward_definitions`
  - [ ] `referral_claimed_rewards`
- [ ] Keine Fehlermeldungen in Migration
- [ ] Log-Output gespeichert

### Schritt 3: Alte Dateien sichern
```bash
# Via SSH/FTP
cd /path/to/project
mv freebie/thankyou.php freebie/thankyou-old-backup.php
```

- [ ] Alte `thankyou.php` umbenannt zu `thankyou-old-backup.php`
- [ ] Backup-Datei existiert und ist vollständig
- [ ] Permissions auf Backup-Datei gesetzt (644)

### Schritt 4: Neue Dateien aktivieren
```bash
# Via SSH/FTP
mv freebie/thankyou-new.php freebie/thankyou.php
```

- [ ] Neue `thankyou.php` ist aktiv
- [ ] Permissions korrekt (644)
- [ ] Datei ist vollständig (kein Upload-Fehler)

## 🧪 Testing

### Funktionale Tests

#### Test 1: One-Click-Login
1. [ ] Neue Freebie-Anmeldung durchführen
2. [ ] Danke-Seite lädt korrekt
3. [ ] Nur "Zum Dashboard" Button sichtbar
4. [ ] Button führt zum Dashboard
5. [ ] Automatischer Login funktioniert
6. [ ] Keine Fehler in Browser-Console
7. [ ] Session wird korrekt gesetzt

```sql
-- Token in DB prüfen
SELECT * FROM lead_login_tokens 
WHERE email = 'test@example.com' 
ORDER BY created_at DESC 
LIMIT 1;

-- Lead-User prüfen
SELECT * FROM lead_users 
WHERE email = 'test@example.com';
```

#### Test 2: Dashboard-Anzeige
- [ ] Dashboard lädt vollständig
- [ ] Header zeigt Logo und User-Info
- [ ] Stats werden angezeigt (wenn Empfehlungsprogramm)
- [ ] Kurse werden mit Mockups angezeigt
- [ ] "Kurs starten" Buttons funktionieren
- [ ] Keine JavaScript-Fehler
- [ ] Keine PHP-Fehler

#### Test 3: Videoplayer
- [ ] Klick auf "Kurs starten" öffnet Player
- [ ] Video lädt und spielt ab
- [ ] Drip-Content funktioniert (wenn konfiguriert)
- [ ] Fortschritt wird getrackt
- [ ] "Als abgeschlossen markieren" funktioniert
- [ ] Sidebar zeigt alle Lektionen
- [ ] Gesperrte Lektionen sind markiert

#### Test 4: Empfehlungsprogramm (falls aktiviert)
- [ ] Empfehlungslink wird angezeigt
- [ ] Copy-to-Clipboard funktioniert
- [ ] Belohnungsstufen werden angezeigt
- [ ] Progress-Bars zeigen korrekten Fortschritt
- [ ] Empfehlungen-Liste zeigt korrekte Daten
- [ ] Status-Badges korrekt

#### Test 5: Responsive Design
- [ ] Desktop (> 1024px): 3-Spalten-Layout
- [ ] Tablet (768px - 1024px): 2-Spalten-Layout
- [ ] Mobile (< 768px): 1-Spalte
- [ ] Alle Buttons klickbar auf Mobile
- [ ] Keine horizontalen Scrollbars
- [ ] Touch-Gesten funktionieren

### Performance-Tests
- [ ] Seite lädt in < 2 Sekunden
- [ ] Bilder sind optimiert
- [ ] Keine übermäßigen DB-Queries
- [ ] Browser-Cache funktioniert
- [ ] CDN-Links funktionieren (Font Awesome, Google Fonts)

### Sicherheits-Tests
- [ ] Token ist nach Verwendung markiert (used_at)
- [ ] Abgelaufene Tokens werden abgelehnt
- [ ] SQL-Injection nicht möglich (Prepared Statements)
- [ ] XSS nicht möglich (htmlspecialchars)
- [ ] Session Hijacking geschützt
- [ ] HTTPS erzwungen (falls möglich)

### Browser-Kompatibilität
- [ ] Chrome/Edge (neueste Version)
- [ ] Firefox (neueste Version)
- [ ] Safari (macOS & iOS)
- [ ] Mobile Browser (Android)

## 🐛 Troubleshooting

### Häufige Probleme

#### Problem: "Token nicht gefunden"
**Lösung:**
```sql
-- Token prüfen
SELECT * FROM lead_login_tokens WHERE token = 'DEIN_TOKEN_HIER';

-- Falls nicht vorhanden: Neue Anmeldung
-- Falls abgelaufen: expires_at prüfen
-- Falls verwendet: used_at prüfen
```
- [ ] Problem gelöst
- [ ] Ursache dokumentiert

#### Problem: "Kurse werden nicht angezeigt"
**Lösung:**
```sql
-- Freebies prüfen
SELECT cf.*, fc.id as course_id
FROM customer_freebies cf
LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id
WHERE cf.customer_id = CUSTOMER_ID;
```
- [ ] Problem gelöst
- [ ] Fehlende Verknüpfungen erstellt

#### Problem: "Empfehlungsprogramm fehlt"
**Lösung:**
```sql
-- Customer prüfen
SELECT id, referral_enabled, ref_code 
FROM users 
WHERE id = CUSTOMER_ID;

-- Falls ref_code fehlt:
UPDATE users 
SET ref_code = 'UNIQUE_CODE', referral_enabled = 1 
WHERE id = CUSTOMER_ID;
```
- [ ] Problem gelöst
- [ ] ref_code generiert

#### Problem: "Session-Fehler"
**Lösung:**
- Prüfe Session-Ordner Permissions
- Prüfe `session.save_path` in php.ini
- Prüfe Disk Space
- Teste Session-Start manuell:
```php
<?php
session_start();
$_SESSION['test'] = 'works';
echo session_id();
var_dump($_SESSION);
?>
```
- [ ] Problem gelöst
- [ ] Session funktioniert

## 📊 Monitoring

### Nach Go-Live überwachen

#### Erste Stunde
- [ ] Keine PHP-Errors im Error-Log
- [ ] Neue Lead-Anmeldungen funktionieren
- [ ] Token-Generierung läuft
- [ ] Automatischer Login funktioniert
- [ ] Dashboard lädt korrekt

#### Erster Tag
- [ ] Conversion-Rate normal
- [ ] Keine Beschwerden von Users
- [ ] Performance akzeptabel
- [ ] Keine DB-Probleme
- [ ] Speicherplatz ausreichend

#### Erste Woche
- [ ] User-Feedback sammeln
- [ ] Metriken analysieren:
  - Anzahl neuer Leads
  - Login-Erfolgsrate
  - Kurs-Completion-Rate
  - Empfehlungs-Rate (falls aktiviert)
- [ ] Optimierungen identifizieren

### Metriken-Tracking (optional)
```sql
-- Neue Leads (heute)
SELECT COUNT(*) FROM lead_users 
WHERE DATE(created_at) = CURDATE();

-- Token-Verwendungsrate
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as used,
    SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as valid
FROM lead_login_tokens
WHERE DATE(created_at) = CURDATE();

-- Empfehlungs-Performance
SELECT 
    COUNT(*) as total_referrals,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
FROM lead_referrals
WHERE DATE(invited_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY);
```

## 🎉 Go-Live

### Final Check vor Go-Live
- [ ] Alle Tests bestanden
- [ ] Backup erstellt
- [ ] Team informiert
- [ ] Support vorbereitet
- [ ] Rollback-Plan dokumentiert
- [ ] Monitoring aktiv

### Go-Live Durchführung
```bash
# Datum/Zeit: __________________
# Durchgeführt von: ____________
```

- [ ] Alte thankyou.php deaktiviert
- [ ] Neue thankyou.php aktiviert
- [ ] System-Check durchgeführt
- [ ] Erste Test-Anmeldung erfolgreich
- [ ] No-Go Kriterien geprüft
  - [ ] Keine kritischen Errors
  - [ ] Performance OK
  - [ ] DB-Verbindung stabil

### Nach Go-Live
- [ ] Erste 10 Anmeldungen überwacht
- [ ] Error-Log geprüft (keine neuen Errors)
- [ ] Performance-Metrics OK
- [ ] User-Feedback positiv
- [ ] Dokumentation aktualisiert

## 📝 Notizen & Anmerkungen

### Probleme während Installation:
```
[Platz für Notizen]







```

### Anpassungen/Konfiguration:
```
[Platz für Notizen]







```

### Offene Punkte:
```
[Platz für Notizen]







```

## ✅ Sign-Off

**Installation abgeschlossen von:**
- Name: _______________________
- Datum: ______________________
- Unterschrift: _______________

**Go-Live freigegeben von:**
- Name: _______________________
- Datum: ______________________
- Unterschrift: _______________

---

**Status:** [ ] Testing | [ ] Staging | [ ] Production

**Nächste Schritte:**
1. _________________________________
2. _________________________________
3. _________________________________
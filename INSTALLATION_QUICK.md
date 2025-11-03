# 🎯 REFERRAL-SYSTEM - SCHNELLINSTALLATION

## ⚡ One-Click Installation (EMPFOHLEN)

```bash
# 1. SSH-Verbindung zu Server
ssh lumisaas@mehr-infos-jetzt.de

# 2. Ins Verzeichnis wechseln
cd /home/lumisaas/public_html

# 3. Installer ausführbar machen
chmod +x install-referral.sh

# 4. Installer starten
./install-referral.sh
```

Das war's! Das Skript macht alles automatisch:
- ✅ Erstellt Logs-Ordner
- ✅ Richtet Cron-Job ein
- ✅ Prüft Datenbank-Tabellen
- ✅ Setzt Berechtigungen
- ✅ Erstellt Test-Daten
- ✅ Validiert System

---

## 🔍 Problem: "Keine Änderungen im Dashboard sichtbar"

### Mögliche Ursachen & Lösungen:

#### 1️⃣ Programm nicht aktiviert
```
Lösung:
1. Öffne: https://app.mehr-infos-jetzt.de/customer/dashboard.php
2. Gehe zu: Menü → "Empfehlungsprogramm"
3. Toggle oben rechts auf "Aktiviert" stellen
4. Firmendaten eintragen (Impressum!)
```

#### 2️⃣ Noch keine Tracking-Daten
```
Lösung:
1. Aktiviere Programm (siehe oben)
2. Rufe Test-Link auf:
   https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123
3. Öffne Browser-Console (F12)
4. Suche nach: "✓ Referral-Klick getrackt"
5. Navigiere zur Danke-Seite
6. Dashboard sollte jetzt Daten zeigen
```

#### 3️⃣ Navigation fehlt
```
Lösung:
Direktlink zum Admin-Monitoring verwenden:
https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php

Oder erweiterte Version:
https://app.mehr-infos-jetzt.de/admin/sections/referral-monitoring-extended.php
```

#### 4️⃣ Datenbank-Tabellen fehlen
```
Diagnose:
php scripts/test-referral-system.php

Falls Tabellen fehlen:
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas < database/migrations/004_referral_system.sql
```

#### 5️⃣ Cache-Problem
```
Lösung:
1. Browser-Cache leeren (Ctrl+Shift+Del)
2. Hard-Reload (Ctrl+F5)
3. Inkognito-Modus testen
```

---

## 🧪 System testen

### Test 1: Diagnose ausführen
```bash
cd /home/lumisaas/public_html
php scripts/test-referral-system.php
```

Zeigt:
- ✅ Datenbank-Verbindung
- ✅ Tabellen-Status
- ✅ API-Endpoints
- ✅ Aktive Programme
- ✅ Statistik-Daten
- ✅ Cron-Job-Status
- ✅ Logs & Berechtigungen

### Test 2: Tracking testen
```bash
# Browser öffnen:
https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123

# Browser-Console (F12) öffnen
# Sollte zeigen:
✓ Referral-Klick getrackt
```

### Test 3: Dashboard prüfen
```bash
# Admin-Dashboard:
https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php

# Customer-Dashboard:
https://app.mehr-infos-jetzt.de/customer/dashboard.php
→ Menü: "Empfehlungsprogramm"
```

---

## 📋 Manuelle Installation (wenn One-Click nicht funktioniert)

### Schritt 1: Logs-Ordner erstellen
```bash
mkdir -p /home/lumisaas/logs
chmod 755 /home/lumisaas/logs
```

### Schritt 2: Cron-Job einrichten
```bash
crontab -e

# Füge hinzu:
0 10 * * * php /home/lumisaas/public_html/scripts/send-reward-emails.php >> /home/lumisaas/logs/cron.log 2>&1

# Speichern: Strg+X, dann Y, dann Enter
```

### Schritt 3: Datenbank-Migration
```bash
cd /home/lumisaas/public_html
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas < database/migrations/004_referral_system.sql
```

### Schritt 4: Berechtigungen setzen
```bash
chmod -R 755 /home/lumisaas/public_html/api/referral
chmod 755 /home/lumisaas/public_html/scripts/send-reward-emails.php
chmod -R 755 /home/lumisaas/logs
```

### Schritt 5: Test-Daten erstellen
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
UPDATE customers 
SET 
    referral_enabled = 1,
    company_name = 'Test Firma GmbH',
    company_email = 'test@mehr-infos-jetzt.de',
    company_imprint_html = '<p>Test Firma GmbH<br>Teststraße 123<br>12345 Teststadt</p>'
WHERE id = 1;
EOF
```

---

## 🎯 Admin-Navigation hinzufügen (falls nicht sichtbar)

Falls das Empfehlungsprogramm im Admin-Menü nicht auftaucht:

### Option 1: Direktlink verwenden
```
Lesezeichen setzen:
https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php
```

### Option 2: Navigation manuell ergänzen
Öffne `admin/dashboard.php` und suche nach der Navigation.
Füge hinzu:
```php
<a href="?section=referral-overview" class="nav-item">
    🎁 Empfehlungsprogramm
</a>
```

---

## 📊 Dashboard zeigt noch keine Daten?

### Checkliste:
- [ ] Programm im Customer-Dashboard aktiviert?
- [ ] Firmendaten hinterlegt?
- [ ] Test-Link aufgerufen?
- [ ] Browser-Console prüft (F12)?
- [ ] Datenbank-Tabellen existieren?
- [ ] Cron-Job eingerichtet?

### Quick-Fix: Test-Daten erstellen
```bash
cd /home/lumisaas/public_html

# Test-Daten in Datenbank einfügen
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
-- Test-Klick
INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Test Browser', 'test_fp', NOW());

-- Test-Conversion
INSERT INTO referral_conversions (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Test Browser', 'test_fp', 'thankyou', NOW());

-- Stats aktualisieren
INSERT INTO referral_stats (customer_id, total_clicks, unique_clicks, total_conversions, conversion_rate)
VALUES (1, 1, 1, 1, 100.00)
ON DUPLICATE KEY UPDATE
    total_clicks = total_clicks + 1,
    unique_clicks = unique_clicks + 1,
    total_conversions = total_conversions + 1,
    conversion_rate = ROUND((total_conversions / total_clicks) * 100, 2),
    updated_at = NOW();

-- Prüfen
SELECT * FROM referral_stats WHERE customer_id = 1;
EOF
```

Nach diesem Befehl sollten im Dashboard sofort Daten sichtbar sein!

---

## 🔍 Logs überwachen

```bash
# Cron-Logs
tail -f /home/lumisaas/logs/cron.log

# Reward-E-Mails
tail -f /home/lumisaas/logs/reward-emails-$(date +%Y-%m-%d).log

# Apache Error-Log
tail -f /home/lumisaas/logs/error_log
```

---

## 🆘 Support

### Probleme melden
1. Führe Diagnose aus: `php scripts/test-referral-system.php`
2. Kopiere Output
3. Sende an: support@mehr-infos-jetzt.de

### Häufige Fragen

**Q: "Warum sehe ich keine Daten im Dashboard?"**
A: Programm muss aktiviert sein UND es muss mindestens 1 Klick/Conversion geben.

**Q: "Tracking funktioniert nicht?"**
A: Browser-Console prüfen (F12). JavaScript aktiviert? Ad-Blocker aus?

**Q: "E-Mails werden nicht versendet?"**
A: Cron-Job prüfen (`crontab -l`). Log-Datei checken.

**Q: "Conversions als 'verdächtig' markiert?"**
A: Normal bei < 5 Sekunden zwischen Klick und Conversion. Fraud-Log prüfen.

---

## ✅ Erfolgscheck

Nach Installation sollte Folgendes funktionieren:

1. ✅ Admin-Dashboard zeigt Übersicht
2. ✅ Customer kann Programm aktivieren
3. ✅ Test-Link trackt Klicks
4. ✅ Danke-Seite trackt Conversions
5. ✅ Dashboard zeigt Statistiken
6. ✅ Cron-Job läuft

---

**Bei Problemen: `./install-referral.sh` erneut ausführen!**

**Version**: 1.0  
**Support**: support@mehr-infos-jetzt.de

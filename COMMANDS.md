# 🚀 REFERRAL-SYSTEM - COMMAND CHEAT SHEET

## ⚡ INSTALLATION (ONE-COMMAND)

```bash
cd /home/lumisaas/public_html && chmod +x install-referral.sh && ./install-referral.sh
```

---

## 🔧 SETUP-BEFEHLE

### Logs-Ordner erstellen
```bash
mkdir -p /home/lumisaas/logs && chmod 755 /home/lumisaas/logs
```

### Cron-Job einrichten
```bash
(crontab -l 2>/dev/null; echo "0 10 * * * php /home/lumisaas/public_html/scripts/send-reward-emails.php >> /home/lumisaas/logs/cron.log 2>&1") | crontab -
```

### Datenbank-Migration
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas < database/migrations/004_referral_system.sql
```

### Berechtigungen setzen
```bash
chmod -R 755 /home/lumisaas/public_html/api/referral
chmod 755 /home/lumisaas/public_html/scripts/*.php
chmod -R 755 /home/lumisaas/logs
```

---

## 🧪 TEST-BEFEHLE

### System-Diagnose
```bash
php /home/lumisaas/public_html/scripts/test-referral-system.php
```

### Manuell E-Mail-Test
```bash
php /home/lumisaas/public_html/scripts/send-reward-emails.php
```

### Test-Daten erstellen
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Test', 'test_fp', NOW());

INSERT INTO referral_conversions (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Test', 'test_fp', 'thankyou', NOW());

INSERT INTO referral_stats (customer_id, total_clicks, unique_clicks, total_conversions, conversion_rate)
VALUES (1, 1, 1, 1, 100.00)
ON DUPLICATE KEY UPDATE total_clicks = 1, total_conversions = 1, conversion_rate = 100.00;
EOF
```

---

## 📊 DATENBANK-BEFEHLE

### MySQL-Login
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas
```

### Tabellen anzeigen
```sql
SHOW TABLES LIKE 'referral_%';
```

### Statistiken prüfen
```sql
SELECT 
    c.id,
    c.email,
    c.referral_enabled,
    rs.total_clicks,
    rs.total_conversions,
    rs.conversion_rate
FROM customers c
LEFT JOIN referral_stats rs ON c.id = rs.customer_id
WHERE c.referral_enabled = 1;
```

### Customer aktivieren
```sql
UPDATE customers 
SET 
    referral_enabled = 1,
    company_name = 'Ihre Firma',
    company_email = 'info@firma.de'
WHERE id = 1;
```

### Stats zurücksetzen (für Tests)
```sql
TRUNCATE TABLE referral_clicks;
TRUNCATE TABLE referral_conversions;
TRUNCATE TABLE referral_leads;
UPDATE referral_stats SET 
    total_clicks = 0, 
    unique_clicks = 0, 
    total_conversions = 0, 
    total_leads = 0, 
    conversion_rate = 0;
```

---

## 📝 LOG-BEFEHLE

### Cron-Log live verfolgen
```bash
tail -f /home/lumisaas/logs/cron.log
```

### Reward-E-Mails-Log
```bash
tail -f /home/lumisaas/logs/reward-emails-$(date +%Y-%m-%d).log
```

### Apache Error-Log
```bash
tail -f /home/lumisaas/logs/error_log
```

### Alle Logs der letzten 24h
```bash
find /home/lumisaas/logs -type f -mtime -1 -exec tail {} \;
```

---

## 🌐 WICHTIGE URLS

### Admin
```
Standard-Monitoring:
https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php

Erweiterte Analytics:
https://app.mehr-infos-jetzt.de/admin/sections/referral-monitoring-extended.php

Direct Section-Link:
https://app.mehr-infos-jetzt.de/admin/dashboard.php?section=referral-overview
```

### Customer
```
Dashboard:
https://app.mehr-infos-jetzt.de/customer/dashboard.php

Empfehlungsprogramm:
https://app.mehr-infos-jetzt.de/customer/sections/empfehlungsprogramm.php
```

### Test-Links
```
Freebie mit Ref:
https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123

Danke-Seite mit Ref:
https://app.mehr-infos-jetzt.de/thankyou.php?customer=1&ref=TEST123

Tracking-Pixel:
https://app.mehr-infos-jetzt.de/api/referral/track.php?customer=1&ref=TEST123
```

---

## 🔍 DIAGNOSE-BEFEHLE

### Cron-Job Status
```bash
crontab -l | grep send-reward-emails
```

### Tabellen-Status
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas -e "
SELECT 
    TABLE_NAME as 'Tabelle',
    TABLE_ROWS as 'Zeilen'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'lumisaas'
    AND TABLE_NAME LIKE 'referral_%'
ORDER BY TABLE_NAME;
"
```

### API-Endpoints prüfen
```bash
ls -lh /home/lumisaas/public_html/api/referral/
```

### Berechtigungen prüfen
```bash
ls -la /home/lumisaas/logs/
ls -la /home/lumisaas/public_html/api/referral/
```

---

## 🛠️ WARTUNG

### Alte Logs löschen (älter als 30 Tage)
```bash
find /home/lumisaas/logs -name "*.log" -type f -mtime +30 -delete
```

### Datenbank-Backup
```bash
mysqldump -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas \
    referral_clicks \
    referral_conversions \
    referral_leads \
    referral_stats \
    referral_rewards \
    referral_fraud_log \
    > backup_referral_$(date +%Y%m%d).sql
```

### Stats neu berechnen
```bash
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
UPDATE referral_stats rs
JOIN (
    SELECT 
        customer_id,
        COUNT(*) as clicks,
        COUNT(DISTINCT fingerprint) as unique_clicks
    FROM referral_clicks
    GROUP BY customer_id
) c ON rs.customer_id = c.customer_id
SET 
    rs.total_clicks = c.clicks,
    rs.unique_clicks = c.unique_clicks,
    rs.updated_at = NOW();
EOF
```

---

## 🚨 TROUBLESHOOTING

### Problem: Keine Daten im Dashboard
```bash
# 1. Prüfe ob Programm aktiviert
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas -e "
SELECT id, email, referral_enabled FROM customers WHERE id = 1;"

# 2. Prüfe Tracking-Daten
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas -e "
SELECT COUNT(*) FROM referral_clicks WHERE customer_id = 1;"

# 3. Erstelle Test-Daten (siehe oben unter Test-Befehle)
```

### Problem: Cron-Job läuft nicht
```bash
# 1. Prüfe Crontab
crontab -l

# 2. Test-Lauf
php /home/lumisaas/public_html/scripts/send-reward-emails.php

# 3. Cron-Log prüfen
tail -20 /home/lumisaas/logs/cron.log
```

### Problem: Tracking funktioniert nicht
```bash
# 1. Browser-Console öffnen (F12)
# 2. Freebie-Link mit ref aufrufen
# 3. Nach "Referral" suchen in Console

# Server-seitig prüfen:
tail -f /home/lumisaas/logs/error_log
```

### Problem: E-Mails kommen nicht an
```bash
# 1. Test-Mail senden
php /home/lumisaas/public_html/scripts/send-reward-emails.php

# 2. Mail-Log prüfen
tail -20 /home/lumisaas/logs/reward-emails-$(date +%Y-%m-%d).log

# 3. PHP mail() testen
php -r "mail('test@example.com', 'Test', 'Test message');"
```

---

## 📦 SCHNELL-INSTALLATION (Copy-Paste)

```bash
#!/bin/bash
# Komplett-Setup in einem Befehl

cd /home/lumisaas/public_html

# Setup ausführen
chmod +x install-referral.sh && ./install-referral.sh

# System testen
php scripts/test-referral-system.php

# Test-Daten erstellen
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
UPDATE customers SET referral_enabled = 1, company_name = 'Test GmbH', company_email = 'test@test.de' WHERE id = 1;
INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Test', 'test_fp', NOW());
INSERT INTO referral_stats (customer_id, total_clicks, unique_clicks, total_conversions)
VALUES (1, 1, 1, 0) ON DUPLICATE KEY UPDATE total_clicks = 1, unique_clicks = 1;
EOF

echo "✅ Setup abgeschlossen!"
echo "🌐 Öffne: https://app.mehr-infos-jetzt.de/admin/sections/referral-overview.php"
```

---

## 📚 DOKUMENTATION

- **Vollständig**: `REFERRAL_SYSTEM_COMPLETE.md`
- **Quickstart**: `REFERRAL_QUICKSTART_GUIDE.md`
- **Deployment**: `REFERRAL_DEPLOYMENT.md`
- **Installation**: `INSTALLATION_QUICK.md`
- **Commands**: `COMMANDS.md` (diese Datei)

---

**Support**: support@mehr-infos-jetzt.de  
**Version**: 1.0  
**Letzte Aktualisierung**: 03.11.2025

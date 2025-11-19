# 🎁 Reward Auto-Delivery System - Dokumentation

## Übersicht

Das **Reward Auto-Delivery System** liefert automatisch Belohnungen an Leads aus, wenn diese Belohnungsstufen durch erfolgreiche Empfehlungen erreichen. Die Auslieferung erfolgt über die vom Kunden konfigurierte Email-Marketing-API (Quentn, ActiveCampaign, Klick-Tipp, Brevo, GetResponse).

---

## 🎯 Funktionsweise

### 1. Customer richtet Empfehlungsprogramm ein

Der Customer konfiguriert auf `https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm`:

1. **Empfehlungsprogramm aktivieren** (Toggle)
2. **Email-Marketing-API einrichten**:
   - Provider auswählen (Quentn, ActiveCampaign, etc.)
   - API-Zugangsdaten eingeben
   - Listen/Tags konfigurieren
3. **Belohnungsstufen erstellen** unter "Belohnungsstufen"

### 2. Lead registriert sich und empfiehlt

- Lead meldet sich an über `lead_register.php`
- Lead empfiehlt Freunde über seinen Empfehlungslink
- System trackt `successful_referrals` in `lead_users` Tabelle

### 3. Automatische Belohnungsauslieferung

**Cronjob läuft alle 10 Minuten:**

```
*/10 * * * * php /path/to/api/rewards/auto-deliver-cron.php >> /path/to/logs/reward-delivery.log 2>&1
```

**Der Cronjob:**
1. Findet alle Leads, die Belohnungsstufen erreicht haben
2. Prüft, welche Belohnungen noch nicht ausgeliefert wurden
3. Versendet Email über Customer's Email-API
4. Tracked Auslieferung in `reward_deliveries` Tabelle

---

## 🔧 Installation

### Schritt 1: Automatische Installation (empfohlen)

```bash
# Cronjob automatisch einrichten
bash scripts/setup-reward-cronjob.sh
```

### Schritt 2: Manuelle Installation

Falls automatische Installation nicht funktioniert:

```bash
# Öffne Crontab
crontab -e

# Füge folgende Zeile hinzu (Pfade anpassen!)
*/10 * * * * /usr/bin/php /var/www/app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php >> /var/www/app.mehr-infos-jetzt.de/logs/reward-delivery.log 2>&1
```

### Schritt 3: Test

```bash
# Test im Terminal
php api/rewards/test-auto-delivery.php

# Oder im Browser
https://app.mehr-infos-jetzt.de/api/rewards/test-auto-delivery.php
```

---

## 📧 Email-Versand-Methoden

Das System unterstützt **2 Versandmethoden**, abhängig vom Email-Provider:

### Methode 1: Direkte Email (Brevo, GetResponse)

**Provider:** Brevo, GetResponse  
**Funktionsweise:** System versendet Email direkt über API

```php
// Automatisch:
$provider->sendEmail($email, $subject, $body);
```

**Vorteile:**
- ✅ Vollständig automatisch
- ✅ Keine Kampagnen-Konfiguration nötig
- ✅ Platzhalter werden automatisch ersetzt

**Email-Template:**
Das System verwendet ein Basis-Template mit Platzhaltern:
- `{{reward_title}}`
- `{{reward_description}}`
- `{{reward_warning}}`
- `{{successful_referrals}}`
- `{{current_points}}`
- `{{referral_code}}`
- `{{company_name}}`

### Methode 2: Tag-Trigger (Quentn, Klick-Tipp, ActiveCampaign)

**Provider:** Quentn, Klick-Tipp, ActiveCampaign  
**Funktionsweise:** System fügt Tag hinzu, triggert Kampagne

```php
// Automatisch:
$provider->addTag($email, 'reward_1_earned');
```

**Customer MUSS in seinem Email-System:**
1. **Kampagne erstellen**, die bei Tag `reward_X_earned` triggert
2. **Email-Template erstellen** mit Platzhaltern
3. **Custom Fields verwenden** für Platzhalter-Ersetzung

**Tag-Format:**
- `reward_1_earned` - Belohnung Stufe 1
- `reward_2_earned` - Belohnung Stufe 2
- `reward_3_earned` - Belohnung Stufe 3

**Verfügbare Custom Fields:**
Das System aktualisiert automatisch:
- `referral_code` (Text)
- `total_referrals` (Zahl)
- `successful_referrals` (Zahl)
- `rewards_earned` (Zahl)
- `last_reward` (Text)

---

## 📊 Datenbank-Struktur

### Tabelle: `reward_deliveries`

```sql
CREATE TABLE reward_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    reward_id INT NOT NULL,
    delivery_method VARCHAR(50) DEFAULT 'email',
    delivered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_details JSON NULL,
    
    UNIQUE KEY unique_delivery (lead_id, reward_id),
    FOREIGN KEY (lead_id) REFERENCES lead_users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES reward_definitions(id) ON DELETE CASCADE
);
```

**Verhindert Duplikate:** UNIQUE KEY stellt sicher, dass jede Belohnung nur 1x ausgeliefert wird.

### Neue Spalte: `lead_users.rewards_earned`

```sql
ALTER TABLE lead_users 
ADD COLUMN rewards_earned INT DEFAULT 0 AFTER successful_referrals;
```

Counter für erhaltene Belohnungen.

---

## 🎨 Email-Vorlagen für Customer

### Für Brevo / GetResponse (Direct Email)

Customer **braucht KEINE** Email-Vorlage - das System versendet automatisch.

Optional kann Customer das Basis-Template anpassen in:
`/api/rewards/email-delivery-service.php` → Methode `getEmailBody()`

### Für Quentn / Klick-Tipp / ActiveCampaign (Tag-Trigger)

Customer **MUSS** Email-Vorlagen in seinem System erstellen:

**Beispiel Email-Template:**

```html
Betreff: 🎉 Glückwunsch! Du hast eine Belohnung freigeschaltet

Hallo!

🎉 Herzlichen Glückwunsch!

Du hast es geschafft und eine neue Belohnung erreicht!

---

📊 Deine aktuelle Statistik:
✅ Erfolgreiche Empfehlungen: {successful_referrals}
⭐ Gesammelte Punkte: {successful_referrals}
🎁 Dein Empfehlungscode: {referral_code}

---

🎁 Deine freigeschaltete Belohnung:

{last_reward}

---

💪 Weiter so!

Du machst das großartig! Teile deinen Empfehlungscode weiterhin.

Viele Grüße
Dein Team
```

**Kampagnen-Setup:**
1. Neue Kampagne erstellen
2. Trigger: Tag `reward_1_earned` wird hinzugefügt
3. Aktion: Email senden mit obigem Template
4. Repeat für `reward_2_earned`, `reward_3_earned`, etc.

---

## 🔍 Monitoring & Logs

### Log-Dateien

```bash
# Live-Logs ansehen
tail -f logs/reward-delivery.log

# Letzte 100 Zeilen
tail -n 100 logs/reward-delivery.log

# Nach Fehlern suchen
grep "ERROR\|❌" logs/reward-delivery.log
```

### Manueller Test

```bash
# Im Terminal
php api/rewards/test-auto-delivery.php

# Im Browser (mit UI)
https://app.mehr-infos-jetzt.de/api/rewards/test-auto-delivery.php
```

### Cronjob-Status prüfen

```bash
# Aktive Cronjobs anzeigen
crontab -l | grep reward

# Letzte Cronjob-Ausführungen (wenn vorhanden)
grep CRON /var/log/syslog | grep reward
```

---

## 🐛 Troubleshooting

### Problem: Keine Belohnungen werden ausgeliefert

**Ursachen & Lösungen:**

1. **Cronjob läuft nicht**
   ```bash
   # Prüfen
   crontab -l | grep reward
   
   # Manuell ausführen
   php api/rewards/auto-deliver-cron.php
   ```

2. **Keine Email-API konfiguriert**
   - Customer muss unter "Empfehlungsprogramm" API einrichten
   - Prüfen: `SELECT * FROM customer_email_api_settings WHERE is_active = TRUE`

3. **Lead hat Stufe noch nicht erreicht**
   ```sql
   -- Prüfen
   SELECT 
       lu.email,
       lu.successful_referrals,
       rd.required_referrals,
       rd.reward_title
   FROM lead_users lu
   CROSS JOIN reward_definitions rd
   WHERE rd.user_id = lu.user_id
   AND lu.successful_referrals >= rd.required_referrals
   ```

4. **Belohnung bereits ausgeliefert**
   ```sql
   -- Prüfen
   SELECT * FROM reward_deliveries 
   WHERE lead_id = X AND reward_id = Y
   ```

### Problem: Email kommt nicht an

**Bei Direct Email (Brevo/GetResponse):**
- Prüfe API-Key in `customer_email_api_settings`
- Teste API mit: `php api/email-settings/test.php`
- Prüfe Spam-Ordner

**Bei Tag-Trigger (Quentn/Klick-Tipp/ActiveCampaign):**
- Prüfe ob Tag korrekt hinzugefügt wurde im Email-System
- Prüfe ob Kampagne für diesen Tag existiert
- Prüfe ob Kampagne aktiv ist
- Prüfe Custom Fields im Email-System

### Problem: Duplikate / Mehrfach-Auslieferung

**Verhindert durch:**
- `UNIQUE KEY unique_delivery (lead_id, reward_id)` in `reward_deliveries`
- System liefert jede Belohnung nur 1x pro Lead aus

Falls trotzdem Duplikate:
```sql
-- Duplikate finden
SELECT lead_id, reward_id, COUNT(*) as count
FROM reward_deliveries
GROUP BY lead_id, reward_id
HAVING count > 1;

-- Duplikate löschen (behält ältesten Eintrag)
DELETE rd1 FROM reward_deliveries rd1
INNER JOIN reward_deliveries rd2 
WHERE rd1.id > rd2.id
AND rd1.lead_id = rd2.lead_id
AND rd1.reward_id = rd2.reward_id;
```

---

## 📋 Checkliste für Customer

- [ ] **1. Empfehlungsprogramm aktiviert** auf Dashboard
- [ ] **2. Email-Marketing-API konfiguriert**
  - [ ] Provider ausgewählt
  - [ ] API-Key eingegeben
  - [ ] Verbindung getestet (✅ grüner Status)
- [ ] **3. Custom Fields im Email-System angelegt**
  - [ ] `referral_code` (Text)
  - [ ] `total_referrals` (Zahl)
  - [ ] `successful_referrals` (Zahl)
  - [ ] `rewards_earned` (Zahl)
  - [ ] `last_reward` (Text)
- [ ] **4. Belohnungsstufen erstellt** unter "Belohnungsstufen"
- [ ] **5. Bei Tag-Trigger-Providern:**
  - [ ] Kampagnen für Tags erstellt (`reward_1_earned`, etc.)
  - [ ] Email-Templates mit Platzhaltern erstellt
- [ ] **6. Cronjob installiert** (`bash scripts/setup-reward-cronjob.sh`)
- [ ] **7. Test durchgeführt** (`php api/rewards/test-auto-delivery.php`)

---

## 🔐 Sicherheit

- **API-Keys verschlüsselt:** Nicht im Code, nur in DB
- **Prepared Statements:** SQL-Injection-Schutz
- **Rate Limiting:** 0.5 Sekunden Pause zwischen Requests
- **Error Logging:** Keine sensiblen Daten in Logs
- **UNIQUE Constraint:** Verhindert Duplikate

---

## 📞 Support

Bei Problemen:

1. **Logs prüfen:** `tail -f logs/reward-delivery.log`
2. **Manuellen Test ausführen:** Browser-Test-UI verwenden
3. **Datenbank prüfen:** SQL-Queries in Troubleshooting-Sektion

---

## 🚀 Weiterentwicklung

**Geplante Features:**
- [ ] Dashboard für Delivery-Statistiken
- [ ] Email-Templates im Admin-Backend editierbar
- [ ] Webhook für externe Systeme
- [ ] SMS-Versand als Alternative
- [ ] A/B-Testing für Email-Templates

---

**Version:** 1.0.0  
**Letzte Aktualisierung:** 2025-01-19  
**Autor:** KI Leadsystem Team

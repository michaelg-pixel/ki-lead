# 🎁 Auto-Delivery System - Automatische Belohnungsauslieferung

## Übersicht

Das Auto-Delivery System liefert automatisch Belohnungen an Leads aus, wenn sie die erforderliche Anzahl von Empfehlungen erreicht haben. Es umfasst:

- ✨ **Automatische Prüfung** bei jeder Conversion
- 📧 **Email-Benachrichtigungen** mit vollständigen Details
- 📊 **Auslieferungs-Tracking** in dedizierter Datenbank
- 🎯 **Lead-Dashboard Integration** zur Anzeige erhaltener Belohnungen
- 🔧 **Admin-Dashboard** für Übersicht und Verwaltung
- 🔌 **API-Endpoints** für externe Integrationen

---

## 📦 Installation

### Schritt 1: Installation ausführen

Öffne im Browser:
```
https://app.mehr-infos-jetzt.de/install_auto_delivery.php
```

Klicke auf "Jetzt installieren" - das Skript:
- Erstellt die `reward_deliveries` Tabelle
- Fügt notwendige Spalten zu `reward_definitions` hinzu
- Erstellt alle benötigten Indizes

### Schritt 2: Installation verifizieren

Nach erfolgreicher Installation:
1. **Datei löschen**: `install_auto_delivery.php` aus Sicherheitsgründen entfernen
2. **Admin-Dashboard öffnen**: `/admin/reward_deliveries.php`
3. **Test durchführen**: Belohnungen konfigurieren und testen

---

## 🗄️ Datenbank-Struktur

### Neue Tabelle: `reward_deliveries`

Speichert alle ausgelieferten Belohnungen:

```sql
CREATE TABLE `reward_deliveries` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `lead_id` INT NOT NULL,                    -- Empfänger (Lead)
  `reward_id` INT NOT NULL,                  -- Belohnungsdefinition
  `user_id` INT NOT NULL,                    -- Freebie-Ersteller/Kunde
  `reward_type` VARCHAR(50),                 -- download, code, link, custom
  `reward_title` VARCHAR(255) NOT NULL,      -- Titel der Belohnung
  `reward_value` TEXT,                       -- Wert/Beschreibung
  `delivery_url` TEXT,                       -- Download-Link
  `access_code` VARCHAR(255),                -- Zugriffscode
  `delivery_instructions` TEXT,              -- Einlöse-Anweisungen
  `delivered_at` DATETIME NOT NULL,          -- Auslieferungszeitpunkt
  `delivery_status` ENUM('delivered','claimed','expired'),
  `email_sent` TINYINT(1) DEFAULT 0,        -- Email-Benachrichtigung gesendet
  `email_sent_at` DATETIME,
  `claimed_at` DATETIME,                     -- Eingelöst am
  `notes` TEXT,                              -- Admin-Notizen
  UNIQUE KEY `unique_delivery` (`lead_id`, `reward_id`)
);
```

### Erweiterte Tabelle: `reward_definitions`

Neue Spalten für Auslieferungsdetails:

```sql
ALTER TABLE reward_definitions ADD COLUMN:
- auto_deliver TINYINT(1) DEFAULT 1          -- Automatische Auslieferung aktiv
- delivery_url TEXT                           -- Download-Link
- access_code VARCHAR(255)                    -- Zugriffscode
- delivery_instructions TEXT                  -- Einlöse-Anweisungen
```

---

## 🚀 Verwendung

### Für Kunden/Freebie-Ersteller

#### 1. Belohnungen mit Auslieferungsdetails konfigurieren

Im Admin-Bereich unter "Empfehlungsprogramm" → "Belohnungen verwalten":

```php
// Beispiel: Belohnung mit Download-Link
[
  'tier_level' => 1,
  'tier_name' => 'Bronze',
  'reward_title' => 'E-Book "Lead Generation Secrets"',
  'required_referrals' => 3,
  'delivery_url' => 'https://example.com/download/ebook.pdf',
  'delivery_instructions' => 'Klicke auf den Download-Link und speichere das E-Book.'
]

// Beispiel: Belohnung mit Zugriffscode
[
  'tier_level' => 2,
  'tier_name' => 'Silber',
  'reward_title' => 'Premium-Kurs Zugang',
  'required_referrals' => 5,
  'access_code' => 'PREMIUM2024',
  'delivery_url' => 'https://kurs-plattform.de/login',
  'delivery_instructions' => 'Registriere dich auf der Plattform und gib den Code ein.'
]
```

#### 2. Automatische Auslieferung

Wenn ein Lead die erforderliche Anzahl erreicht:
1. **Automatische Prüfung** nach jeder Conversion
2. **Sofortige Auslieferung** in `reward_deliveries`
3. **Email-Benachrichtigung** an Lead mit:
   - Download-Link (falls vorhanden)
   - Zugriffscode (falls vorhanden)
   - Einlöse-Anweisungen
4. **Anzeige im Lead-Dashboard**

### Für Leads

Leads sehen ihre Belohnungen unter:
```
https://app.mehr-infos-jetzt.de/lead_dashboard.php
```

Im Bereich "Meine Belohnungen" werden angezeigt:
- ✅ Alle erhaltenen Belohnungen
- 🔗 Download-Links (anklickbar)
- 🔑 Zugriffscodes (kopierbar)
- 📋 Einlöse-Anweisungen
- 📅 Auslieferungszeitpunkt

---

## 🔌 API-Dokumentation

### 1. Belohnungen prüfen und ausliefern

**Endpoint:** `POST /api/reward_delivery.php`

#### Automatische Prüfung für Lead

```json
{
  "action": "check_and_deliver",
  "lead_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "lead_id": 123,
  "referral_count": 5,
  "rewards_delivered": 2,
  "rewards": [
    {
      "reward_id": 45,
      "reward_title": "E-Book Download",
      "tier_level": 1,
      "delivery_id": 789
    },
    {
      "reward_id": 46,
      "reward_title": "Premium Zugang",
      "tier_level": 2,
      "delivery_id": 790
    }
  ]
}
```

#### Ausgelieferte Belohnungen abrufen

```json
{
  "action": "get_delivered_rewards",
  "lead_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "rewards": [
    {
      "id": 789,
      "reward_title": "E-Book Download",
      "delivery_url": "https://...",
      "access_code": null,
      "delivery_instructions": "...",
      "delivered_at": "2024-11-17 10:30:00",
      "email_sent": 1
    }
  ]
}
```

#### Manuelle Auslieferung (Admin)

```json
{
  "action": "manual_delivery",
  "lead_id": 123,
  "reward_id": 45
}
```

### 2. Conversion-Webhook

**Endpoint:** `POST /webhook/referral_conversion.php`

Wird aufgerufen wenn ein empfohlener Lead konvertiert:

```json
{
  "email": "lead@example.com",
  "freebie_id": 33,
  "status": "converted"
}
```

**Verhalten:**
1. Aktualisiert Conversion-Status
2. Prüft automatisch Belohnungen für Referrer
3. Liefert erreichte Belohnungen aus
4. Sendet Email-Benachrichtigungen

**Response:**
```json
{
  "status": "success",
  "message": "Conversion recorded and rewards checked",
  "lead_id": 456,
  "referrer_id": 123,
  "rewards_delivered": 1,
  "reward_details": [...]
}
```

---

## 📧 Email-Benachrichtigungen

### Automatische Email bei Belohnungsauslieferung

**An:** Lead-Email  
**Betreff:** 🎁 Du hast eine Belohnung freigeschaltet!

**Inhalt:**
- Glückwunsch-Header
- Belohnungsdetails (Titel, Beschreibung, Wert)
- Download-Link (anklickbar, falls vorhanden)
- Zugriffscode (mit Copy-Button, falls vorhanden)
- Einlöse-Anweisungen (falls vorhanden)
- Link zum Dashboard

**Email wird gesendet wenn:**
- `delivery_url` vorhanden ist ODER
- `access_code` vorhanden ist ODER
- `delivery_instructions` vorhanden sind

---

## 🎯 Integration ins Lead-Dashboard

### Automatische Anzeige

Die Belohnungssektion wird automatisch im Lead-Dashboard angezeigt:

```php
// In lead_dashboard.php einbinden:
require_once __DIR__ . '/includes/lead_rewards_section.php';

// Sektion rendern:
echo renderMyRewardsSection($pdo, $lead_id);
```

### Features der Lead-Ansicht

- 📦 Grid-Layout aller Belohnungen
- 🆕 "NEU" Badge für Belohnungen < 24h alt
- 🎨 Farbcodierte Tier-Badges
- 🔗 Anklickbare Download-Buttons
- 🔑 Kopierbare Zugriffscodes
- 📋 Formatierte Anweisungen
- 📱 Responsive Design

---

## 🔧 Admin-Dashboard

### Übersicht

**URL:** `/admin/reward_deliveries.php`

### Features

1. **Statistiken-Dashboard**
   - Gesamt Auslieferungen
   - Einzigartige Leads
   - Emails versendet
   - Eingelöste Belohnungen
   - Heute ausgeliefert

2. **Filter-Optionen**
   - Nach Status (Ausgeliefert, Eingelöst, Abgelaufen)
   - Nach Kunde/Freebie-Ersteller
   - Textsuche (Lead-Name, Email, Belohnungstitel)

3. **Auslieferungs-Tabelle**
   - Lead-Info
   - Belohnungsdetails
   - Status-Badges
   - Email-Status
   - Auslieferungszeitpunkt
   - Detail-Ansicht (geplant)

4. **Export-Funktionen** (geplant)
   - CSV-Export
   - PDF-Berichte

---

## 🔄 Workflow-Übersicht

### Automatischer Ablauf

```
1. Lead wird empfohlen
   └─> Referrer-ID wird gespeichert in lead_users

2. Lead konvertiert
   └─> Webhook /webhook/referral_conversion.php wird aufgerufen
       └─> Status in lead_referrals aktualisiert
       └─> checkAndDeliverRewards() prüft Belohnungen
           └─> Zählt erfolgreiche Referrals
           └─> Findet erreichte Belohnungen
           └─> Für jede erreichte Belohnung:
               ├─> Eintrag in reward_deliveries
               ├─> Email-Benachrichtigung an Lead
               └─> Status-Update

3. Lead öffnet Dashboard
   └─> Sieht alle erhaltenen Belohnungen
       └─> Kann Download-Links anklicken
       └─> Kann Codes kopieren
       └─> Sieht Einlöse-Anweisungen
```

### Manueller Ablauf (Admin)

```
1. Admin öffnet /admin/reward_deliveries.php
   └─> Sieht alle Auslieferungen

2. Admin kann manuell ausliefern via API:
   POST /api/reward_delivery.php
   {
     "action": "manual_delivery",
     "lead_id": 123,
     "reward_id": 45
   }
```

---

## ⚙️ Konfiguration

### Automatische Auslieferung aktivieren/deaktivieren

Für jede Belohnung in `reward_definitions`:

```php
// Aktiviert (Standard)
'auto_deliver' => 1

// Deaktiviert (nur manuelle Auslieferung)
'auto_deliver' => 0
```

### Email-Absender anpassen

In `/api/reward_delivery.php`, Funktion `sendRewardNotificationEmail()`:

```php
$headers .= "From: " . ($lead['company_name'] ?? 'KI Leadsystem') . 
            " <noreply@mehr-infos-jetzt.de>\r\n";
```

### Belohnungstypen

Unterstützte `reward_type` Werte:
- `download` - Datei-Download
- `code` - Zugriffscode
- `link` - Externer Link
- `custom` - Benutzerdefiniert

---

## 🐛 Troubleshooting

### Problem: Belohnungen werden nicht ausgeliefert

**Lösung 1:** Prüfe ob `auto_deliver = 1` in `reward_definitions`

**Lösung 2:** Prüfe Logs:
```
/webhook/conversion-logs.txt
```

**Lösung 3:** Manuelle Prüfung via API:
```bash
curl -X POST https://app.mehr-infos-jetzt.de/api/reward_delivery.php \
  -H "Content-Type: application/json" \
  -d '{"action":"check_and_deliver","lead_id":123}'
```

### Problem: Emails werden nicht gesendet

**Lösung 1:** SMTP-Konfiguration prüfen

**Lösung 2:** `email_sent` Flag in Datenbank überprüfen:
```sql
SELECT * FROM reward_deliveries WHERE email_sent = 0;
```

**Lösung 3:** PHP mail() Funktion testen

### Problem: Doppelte Auslieferungen

**Geschützt durch:** UNIQUE KEY `unique_delivery` (`lead_id`, `reward_id`)

Wenn Belohnung bereits ausgeliefert wurde, wird sie übersprungen.

---

## 🔐 Sicherheit

### Best Practices

1. **Installation-Script löschen**
   - `install_auto_delivery.php` nach Installation entfernen

2. **API-Zugriff beschränken**
   - Optional: API-Key-Authentifizierung hinzufügen
   - IP-Whitelist für Webhooks

3. **Admin-Zugriff**
   - Nur authentifizierte Admins: `$_SESSION['role'] === 'admin'`

4. **SQL-Injection Schutz**
   - Alle Queries verwenden Prepared Statements

5. **XSS-Schutz**
   - Alle Outputs verwenden `htmlspecialchars()`

---

## 📊 Monitoring & Analytics

### Wichtige Metriken

```sql
-- Auslieferungsrate pro Tag
SELECT 
  DATE(delivered_at) as date,
  COUNT(*) as deliveries
FROM reward_deliveries
GROUP BY DATE(delivered_at)
ORDER BY date DESC;

-- Email-Erfolgsrate
SELECT 
  (COUNT(CASE WHEN email_sent = 1 THEN 1 END) * 100.0 / COUNT(*)) as success_rate
FROM reward_deliveries;

-- Top-Belohnungen
SELECT 
  reward_title,
  COUNT(*) as times_delivered
FROM reward_deliveries
GROUP BY reward_title
ORDER BY times_delivered DESC;

-- Conversion nach Auslieferung
SELECT 
  TIMESTAMPDIFF(HOUR, delivered_at, claimed_at) as hours_to_claim
FROM reward_deliveries
WHERE claimed_at IS NOT NULL;
```

---

## 🚀 Erweiterungen (Roadmap)

### Geplante Features

- [ ] **Ablaufdatum für Belohnungen**
  - Automatisches Setzen auf `expired` nach X Tagen

- [ ] **Mehrfach-Auslieferung**
  - Gleiche Belohnung mehrmals ausliefern können

- [ ] **Webhook-Callbacks**
  - Externe URLs bei Auslieferung benachrichtigen

- [ ] **Admin-Benachrichtigungen**
  - Email an Admin bei neuen Auslieferungen

- [ ] **Gamification**
  - Punkte-System
  - Fortschrittsbalken
  - Achievements

- [ ] **Analytics-Dashboard**
  - Grafische Statistiken
  - Trend-Analysen
  - Export-Funktionen

---

## 📞 Support

Bei Fragen oder Problemen:

1. **Dokumentation prüfen** - Siehe dieses README
2. **Logs überprüfen** - `/webhook/conversion-logs.txt`
3. **Datenbank-Status** - Via `/admin/reward_deliveries.php`
4. **API testen** - Mit curl oder Postman

---

## 📝 Changelog

### Version 1.0 (2024-11-17)

**Initial Release**
- ✨ Automatische Belohnungsprüfung
- 📧 Email-Benachrichtigungssystem
- 📊 Auslieferungs-Tracking
- 🎯 Lead-Dashboard Integration
- 🔧 Admin-Dashboard
- 🔌 REST-API
- 🔄 Webhook-Integration

---

## 📄 Lizenz

Proprietary - KI Leadsystem
© 2024 Michael G.

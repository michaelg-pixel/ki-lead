# 🎯 Reward-System mit Quentn-Integration

## Übersicht

Das Reward-System liefert automatisch Belohnungen an Leads aus, wenn diese eine bestimmte Anzahl erfolgreicher Empfehlungen erreichen.

### Flow

```
Lead erreicht Belohnungsstufe
    ↓
Cronjob läuft alle 5 Minuten (auto-deliver-cron.php)
    ↓
System prüft: Welche Belohnungen sind fällig?
    ↓
System updated Custom Fields in Quentn mit allen Reward-Daten
    ↓
System setzt Tag (z.B. "Optinpilot-Belohnung")
    ↓
Quentn-Kampagne wird durch Tag getriggert
    ↓
E-Mail mit Platzhaltern wird an Lead gesendet
```

## 🔧 Setup

### 1. Migration ausführen

Rufe auf: `https://app.mehr-infos-jetzt.de/test-reward-tag.php?run_migration=1`

Dies fügt das `reward_tag` Feld zu folgenden Tabellen hinzu:
- `reward_definitions` (pro Belohnung)
- `customer_email_api_settings` (global für alle Belohnungen)

### 2. Tag konfigurieren

**Option A - Global für alle Belohnungen:**
```sql
UPDATE customer_email_api_settings 
SET reward_tag = 'Optinpilot-Belohnung'
WHERE customer_id = DEINE_USER_ID;
```

**Option B - Pro Belohnung:**
```sql
UPDATE reward_definitions 
SET reward_tag = 'Optinpilot-Belohnung'
WHERE user_id = DEINE_USER_ID AND tier_level = 1;
```

Oder nutze: `https://app.mehr-infos-jetzt.de/test-reward-tag.php?set_tag=1&user_id=DEINE_ID&tag=Optinpilot-Belohnung`

### 3. Quentn-Kampagne einrichten

#### A. Kampagne erstellen
1. In Quentn neue Kampagne erstellen
2. **Start-Trigger:** Tag "Optinpilot-Belohnung"
3. E-Mail-Element hinzufügen

#### B. Custom Fields in Quentn anlegen

Folgende Custom Fields werden automatisch vom System gesetzt:

| Field Name | Datentyp | Beschreibung |
|------------|----------|--------------|
| `successful_referrals` | Zahl | Anzahl erfolgreicher Empfehlungen |
| `total_referrals` | Zahl | Gesamte Empfehlungen |
| `referral_code` | Text | Empfehlungscode des Leads |
| `rewards_earned` | Zahl | Anzahl erhaltener Belohnungen |
| `last_reward` | Text | Titel der letzten Belohnung |
| `reward_title` | Text | Aktueller Reward-Titel |
| `reward_description` | Text | Aktueller Reward-Beschreibung |
| `reward_warning` | Text | Wichtige Hinweise zur Belohnung |
| `current_points` | Zahl | Aktuelle Punkte (= successful_referrals) |

#### C. E-Mail-Template mit Platzhaltern

In deiner Quentn-E-Mail kannst du folgende Platzhalter verwenden:

```html
Hallo {{first_name}},

glückwunsch! Durch deine {{successful_referrals}} erfolgreichen 
Empfehlungen hast du folgende Belohnung freigeschaltet:

🎁 {{reward_title}}
📝 {{reward_description}}

⚠️ Wichtig: {{reward_warning}}

Dein Empfehlungscode: {{referral_code}}
Deine Punkte: {{current_points}}

Empfiehl weiter und schalte noch mehr Belohnungen frei!
```

### 4. Cronjob einrichten

Der Cronjob ist bereits in CloudPanel angelegt (siehe Bild 1):

**URL:** `https://31.97.39.234:8443/site/app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php`

**Zeitplan:** Alle 5 Minuten (`*/5 * * * *`)

Manuell testen: `https://31.97.39.234:8443/site/app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php`

## 🧪 Testing

### Test-Ablauf

1. **Lead mit ausreichend Referrals erstellen:**
```sql
-- Beispiel: Lead mit 3 erfolgreichen Empfehlungen
UPDATE lead_users 
SET successful_referrals = 3
WHERE id = LEAD_ID;
```

2. **Cronjob manuell aufrufen:**
```bash
curl https://31.97.39.234:8443/site/app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php
```

3. **In Quentn prüfen:**
- Wurde der Tag "Optinpilot-Belohnung" gesetzt?
- Wurden die Custom Fields aktualisiert?
- Wurde die Kampagne getriggert?
- Wurde die E-Mail versendet?

4. **In Datenbank prüfen:**
```sql
-- Ausgelieferte Belohnungen prüfen
SELECT * FROM reward_deliveries 
WHERE lead_id = LEAD_ID 
ORDER BY delivered_at DESC;

-- Lead-Status prüfen
SELECT id, email, successful_referrals, rewards_earned 
FROM lead_users 
WHERE id = LEAD_ID;
```

## 📊 Monitoring

### Logs prüfen

Der Cronjob gibt detaillierte Logs aus:

```
[2025-11-20 12:00:00] [REWARD-AUTO-DELIVERY] START - Reward Auto-Delivery Cronjob
[2025-11-20 12:00:01] [REWARD-AUTO-DELIVERY] Gefunden: 3 ausstehende Belohnungen
[2025-11-20 12:00:02] [REWARD-AUTO-DELIVERY] ✅ Belohnung ausgeliefert: Lead #123 - Bronze Stufe
[2025-11-20 12:00:03] [REWARD-AUTO-DELIVERY] ENDE - Ausgeliefert: 3 | Fehlgeschlagen: 0
```

### Fehlersuche

**Problem: Keine Belohnungen werden ausgeliefert**
- Prüfe ob `is_active = 1` in `reward_definitions`
- Prüfe ob `successful_referrals >= required_referrals`
- Prüfe ob Email-API-Settings korrekt konfiguriert sind

**Problem: Tag wird gesetzt aber E-Mail kommt nicht**
- Prüfe Tag-Name in Quentn-Kampagne (muss exakt übereinstimmen!)
- Prüfe ob Kampagne aktiv ist
- Prüfe ob Lead in Quentn existiert
- Prüfe Custom Fields in Quentn

**Problem: Custom Fields werden nicht aktualisiert**
- Prüfe Quentn API-Key
- Prüfe ob Custom Fields in Quentn angelegt sind
- Logs in `error_log` prüfen

## 🔍 Tag-Priorität

Das System wählt den Tag in folgender Reihenfolge:

1. **reward_tag aus reward_definitions** (spezifisch pro Belohnung)
   ```sql
   SELECT reward_tag FROM reward_definitions WHERE id = X;
   ```

2. **reward_tag aus customer_email_api_settings** (global für alle Belohnungen)
   ```sql
   SELECT reward_tag FROM customer_email_api_settings WHERE customer_id = X;
   ```

3. **Fallback:** `reward_X_earned` (dynamisch mit tier_level)
   - Tier 1 → `reward_1_earned`
   - Tier 2 → `reward_2_earned`
   - etc.

## 💡 Best Practices

### Empfohlene Quentn-Kampagnen-Struktur

```
Start: Tag "Optinpilot-Belohnung"
  ↓
Warte 1 Minute (damit Custom Fields synchronisiert sind)
  ↓
E-Mail: Glückwunsch zur Belohnung
  ↓
Warte 3 Tage
  ↓
E-Mail: Reminder zum Einlösen
  ↓
Tag entfernen: "Optinpilot-Belohnung"
```

### Mehrere Belohnungsstufen

Wenn du verschiedene E-Mails für verschiedene Stufen willst:

**Option 1 - Verschiedene Tags:**
```sql
-- Bronze
UPDATE reward_definitions SET reward_tag = 'Belohnung-Bronze' WHERE tier_level = 1;

-- Silber  
UPDATE reward_definitions SET reward_tag = 'Belohnung-Silber' WHERE tier_level = 2;

-- Gold
UPDATE reward_definitions SET reward_tag = 'Belohnung-Gold' WHERE tier_level = 3;
```

**Option 2 - Bedingungen in Quentn:**
```
Start: Tag "Optinpilot-Belohnung"
  ↓
Bedingung: current_points = 1 → E-Mail Bronze
Bedingung: current_points = 3 → E-Mail Silber
Bedingung: current_points = 5 → E-Mail Gold
```

## 🚀 Deployment

Die Änderungen werden automatisch deployed über GitHub Actions:

```bash
git add api/rewards/email-delivery-service.php
git add migrations/add_reward_tag_field.php
git add test-reward-tag.php
git commit -m "feat: Konfigurierbare Tags für Quentn-Kampagnen"
git push origin main
```

## 📞 Support

Bei Problemen:
1. `test-reward-tag.php` aufrufen für Status-Check
2. Cronjob manuell testen
3. Quentn-Logs prüfen
4. Server error_log prüfen: `/var/www/app.mehr-infos-jetzt.de/error_log`

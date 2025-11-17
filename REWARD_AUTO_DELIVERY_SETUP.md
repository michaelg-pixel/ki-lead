# 🎁 Reward Auto-Delivery System - Setup & Test-Anleitung

## 📋 Übersicht

Das Reward Auto-Delivery System liefert Belohnungen automatisch an Leads aus wenn diese die erforderliche Anzahl an Empfehlungen erreichen.

## 🚀 Installation (3 Schritte)

### Schritt 1: Installation ausführen

Öffne im Browser:
```
https://app.mehr-infos-jetzt.de/install_reward_auto_delivery.php
```

Das Skript erstellt automatisch:
- ✅ `reward_deliveries` Tabelle
- ✅ Neue Spalten in `reward_definitions`
- ✅ Autoresponder-Spalten in `users` Tabelle

### Schritt 2: Belohnung konfigurieren

1. Gehe zu: **Dashboard → Empfehlungsprogramm → Belohnungsstufen**
2. Erstelle oder bearbeite eine Belohnungsstufe
3. Fülle die Auto-Delivery Felder aus:

#### Pflichtfelder:
- ✅ Stufen-Level (z.B. 1, 2, 3...)
- ✅ Stufen-Name (z.B. "Bronze", "Silber", "Gold")
- ✅ Erforderliche Empfehlungen (z.B. 3)
- ✅ Belohnungs-Titel (z.B. "Kostenloses E-Book")

#### Optional - Auto-Delivery Felder:
- 🔗 **Download-URL** - Link zum Download der Belohnung
- 🔑 **Zugriffscode** - Code für Zugriff auf geschützte Inhalte
- 📋 **Einlöse-Anweisungen** - Text-Anleitung wie die Belohnung eingelöst wird

#### Wichtig:
- ☑️ **"Auto-Zusendung" Checkbox aktivieren!**

### Schritt 3: Fertig! 🎉

Das war's! Das System ist jetzt einsatzbereit.

---

## 🧪 Testen (3 Methoden)

### Methode 1: Manueller Referral-Eintrag

1. Gehe zu deiner Datenbank (phpMyAdmin)
2. Öffne Tabelle `lead_referrals`
3. Füge Einträge für einen Test-Lead hinzu:

```sql
-- Lead-ID von deinem Test-Lead herausfinden
SELECT id, email, name FROM lead_users WHERE email = 'deine-test-email@example.com';

-- Referrals hinzufügen (Beispiel für Lead-ID 5)
INSERT INTO lead_referrals (referrer_id, referred_email, referred_name, status, invited_at)
VALUES 
(5, 'referral1@test.de', 'Test Person 1', 'active', NOW()),
(5, 'referral2@test.de', 'Test Person 2', 'active', NOW()),
(5, 'referral3@test.de', 'Test Person 3', 'active', NOW());

-- Dann triggere das System:
-- Öffne das Lead-Dashboard als dieser User und die Belohnung sollte automatisch ausgeliefert werden
```

### Methode 2: Echter Test mit echten Referral-Links

1. Logge dich als Lead ein: `https://app.mehr-infos-jetzt.de/lead_dashboard.php`
2. Kopiere deinen Referral-Link
3. Öffne den Link in einem Inkognito-Fenster
4. Trage eine neue Email ein → Lead wird als Referral registriert
5. Wiederhole bis erforderliche Anzahl erreicht
6. Prüfe Lead-Dashboard → "Meine Belohnungen" Sektion

### Methode 3: API-Test über Postman/Insomnia

Erstelle einen POST-Request an:
```
https://app.mehr-infos-jetzt.de/test_reward_trigger.php
```

Mit folgendem Body:
```json
{
  "lead_id": 5,
  "customer_id": 1,
  "simulate_referrals": 3
}
```

---

## 📧 Email-Versand Konfiguration

### Option A: Normale Email (Fallback)

Keine Konfiguration nötig - nutzt PHP's `mail()` Funktion.

### Option B: Customer's Autoresponder-API (Empfohlen)

1. Gehe zu: **Dashboard → Einstellungen → API-Konfiguration**
2. Trage ein:
   - Webhook-URL deines Autoresponders
   - API-Key (falls benötigt)
   - Provider-Name (optional)

#### Unterstützte Webhook-Payload:

```json
{
  "event": "reward_delivery",
  "lead": {
    "email": "lead@example.com",
    "name": "Max Mustermann",
    "id": 123
  },
  "reward": {
    "title": "Kostenloses E-Book",
    "description": "Dein Geschenk für 3 Empfehlungen",
    "type": "ebook",
    "value": "19,99€",
    "download_url": "https://...",
    "access_code": "ABC123",
    "instructions": "1. Klicke auf den Link..."
  },
  "timestamp": "2025-01-15T10:30:00+00:00"
}
```

#### Workflow:
1. Lead erreicht Empfehlungsziel
2. System prüft ob Autoresponder konfiguriert ist
3. **Falls JA**: Sendet POST-Request an Autoresponder-Webhook
4. **Falls NEIN** oder **Fehler**: Fallback auf normale Email
5. Lead sieht Belohnung im Dashboard unter "Meine Belohnungen"

---

## 🎯 Was Leads sehen

### Im Lead-Dashboard (`/lead_dashboard.php`):

#### Neue Sektion: "Meine Belohnungen"
- 🎁 Card-Layout für jede erhaltene Belohnung
- 🔗 Download-Buttons (anklickbar)
- 🔑 Zugriffscodes mit Copy-Button
- 📋 Formatierte Einlöse-Anweisungen
- ✨ "NEU" Badge für Belohnungen < 24h alt
- 📅 Auslieferungsdatum

#### Email-Benachrichtigung
Leads bekommen automatisch eine Email mit:
- Titel & Beschreibung der Belohnung
- Download-Link (falls vorhanden)
- Zugriffscode (falls vorhanden)
- Einlöse-Anweisungen (falls vorhanden)
- Link zum Dashboard

---

## 🔍 Troubleshooting

### "Ich sehe keine Belohnungen im Dashboard"

**Mögliche Ursachen:**
1. Installation nicht durchgeführt → Run `install_reward_auto_delivery.php`
2. Keine Belohnungen konfiguriert → Erstelle Belohnungsstufen
3. "Auto-Zusendung" nicht aktiviert → Checkbox in Belohnungsstufe aktivieren
4. Lead hat noch keine Empfehlungsziele erreicht → Prüfe `lead_referrals` Tabelle

**Debug-Steps:**
```sql
-- 1. Prüfe ob Tabelle existiert
SHOW TABLES LIKE 'reward_deliveries';

-- 2. Prüfe ob Belohnungen existieren
SELECT * FROM reward_definitions WHERE user_id = 1 AND is_active = 1;

-- 3. Prüfe Referrals
SELECT COUNT(*) FROM lead_referrals WHERE referrer_id = 5 AND status = 'active';

-- 4. Prüfe ausgelieferte Belohnungen
SELECT * FROM reward_deliveries WHERE lead_id = 5;
```

### "Emails werden nicht versendet"

**Mögliche Ursachen:**
1. `auto_deliver` Checkbox nicht aktiviert
2. Email-Server Problem (prüfe Server-Logs)
3. Autoresponder-API falsch konfiguriert

**Debug:**
```sql
-- Prüfe ob auto_deliver aktiv ist
SELECT id, tier_name, auto_deliver FROM reward_definitions;

-- Prüfe Email-Status
SELECT 
    rd.*,
    rdef.tier_name,
    rdef.auto_deliver
FROM reward_deliveries rd
LEFT JOIN reward_definitions rdef ON rd.reward_id = rdef.id
WHERE rd.lead_id = 5;
```

**Server-Logs prüfen:**
```bash
tail -f /var/log/apache2/error.log
# oder
tail -f /var/log/php-fpm/error.log
```

### "Autoresponder-Integration funktioniert nicht"

**Prüfschritte:**
1. API-URL korrekt? → Prüfe `users.autoresponder_webhook_url`
2. API-Key korrekt? → Prüfe `users.autoresponder_api_key`
3. Webhook erreichbar? → Teste mit cURL:

```bash
curl -X POST https://deine-autoresponder-url.com/webhook \
  -H "Content-Type: application/json" \
  -H "X-API-Key: DEIN-API-KEY" \
  -d '{
    "event": "reward_delivery",
    "lead": {"email": "test@example.com", "name": "Test"},
    "reward": {"title": "Test Belohnung"}
  }'
```

---

## 📊 Datenbank-Schema

### `reward_deliveries` Tabelle

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primary Key |
| `lead_id` | INT | Lead der die Belohnung erhält |
| `reward_id` | INT | ID der Belohnungsdefinition |
| `user_id` | INT | Customer/Freebie-Ersteller |
| `reward_type` | VARCHAR | Typ (ebook, pdf, etc.) |
| `reward_title` | VARCHAR | Titel der Belohnung |
| `reward_value` | VARCHAR | Wert (z.B. "19,99€") |
| `delivery_url` | TEXT | Download-URL |
| `access_code` | VARCHAR | Zugriffscode |
| `delivery_instructions` | TEXT | Einlöse-Anweisungen |
| `delivered_at` | DATETIME | Auslieferungszeitpunkt |
| `delivery_status` | ENUM | pending/delivered/failed |
| `email_sent` | TINYINT | Email versendet? (0/1) |
| `email_sent_at` | DATETIME | Email-Versandzeitpunkt |

### `reward_definitions` - Neue Spalten

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `reward_download_url` | TEXT | Download-URL |
| `reward_access_code` | VARCHAR | Zugriffscode |
| `reward_instructions` | TEXT | Einlöse-Anweisungen |
| `auto_deliver` | TINYINT | Auto-Zusendung aktiv? |

### `users` - Neue Spalten

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `autoresponder_webhook_url` | TEXT | Webhook-URL |
| `autoresponder_api_key` | VARCHAR | API-Key |
| `autoresponder_provider` | VARCHAR | Provider-Name |

---

## 🎓 Best Practices

### 1. Belohnungen richtig strukturieren

```
Stufe 1: 3 Empfehlungen   → Einfaches Dankeschön (PDF)
Stufe 2: 5 Empfehlungen   → Wertvolles E-Book
Stufe 3: 10 Empfehlungen  → Beratungsgespräch
Stufe 4: 20 Empfehlungen  → Exklusiver Kurs-Zugang
```

### 2. Klare Einlöse-Anweisungen

✅ **GUT:**
```
1. Klicke auf den Download-Button
2. Gib den Zugriffscode "ABC123" ein
3. Lade dein E-Book herunter
4. Bei Fragen: support@example.com
```

❌ **SCHLECHT:**
```
Lade es einfach runter
```

### 3. Auto-Delivery richtig einsetzen

- ✅ Aktiviere für digitale Produkte (E-Books, PDFs, Codes)
- ✅ Aktiviere für automatisierbare Belohnungen
- ❌ Deaktiviere für manuelle Belohnungen (persönliche Beratung)
- ❌ Deaktiviere wenn du jeden Versand prüfen willst

---

## 📞 Support & Fragen

Bei Problemen oder Fragen:
1. Prüfe diese Anleitung
2. Prüfe Server-Logs
3. Prüfe Datenbank-Einträge
4. Teste mit einfachen Testdaten

---

## 🚀 Next Level Features (Optional)

### Conversion-Tracking

Momentan werden Referrals als "erfolgreich" gezählt sobald sie sich registrieren.

**Für echte Conversion-Tracking:**
1. Webhook von Digistore24/CopeCart integrieren
2. Bei Kauf: Status in `lead_referrals` auf `converted` setzen
3. Nur `converted` Referrals zählen für Belohnungen

### Multi-Language Support

Belohnungs-Emails in verschiedenen Sprachen:
1. Spalte `language` in `lead_users` hinzufügen
2. Email-Templates für jede Sprache erstellen
3. In `sendRewardDeliveryEmail()` entsprechendes Template wählen

### Benachrichtigungen

Benachrichtige Customers wenn Belohnungen ausgeliefert wurden:
1. Admin-Email bei jeder Auslieferung
2. Dashboard-Statistik über ausgelieferte Belohnungen
3. Wöchentlicher Report

---

**Version:** 1.0
**Letzte Aktualisierung:** 15.01.2025
**System:** KI Leadsystem - Reward Auto-Delivery
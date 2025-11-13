# 🚀 Quick Start: Empfehlungsprogramm API-Integration

Schnelleinstieg in 5 Minuten - Von Null zur ersten automatischen Belohnungs-Email!

---

## ✅ Checkliste

- [ ] Datenbank-Migration durchführen
- [ ] Email-Marketing-Anbieter auswählen
- [ ] API-Zugangsdaten eintragen
- [ ] Verbindung testen
- [ ] Erste Belohnungsstufe erstellen
- [ ] Testen mit Test-Lead

---

## 📝 Schritt-für-Schritt

### 1️⃣ Datenbank-Migration (2 Minuten)

```
1. Browser öffnen
2. Zu https://app.mehr-infos-jetzt.de/customer/migrations/migrate-referral-api-settings.html
3. Button "Migration starten" klicken
4. Warten bis alle 3 Schritte ✅ grün sind
```

✅ **Fertig!** Die Datenbank ist bereit.

---

### 2️⃣ API-Einstellungen (2 Minuten)

**Dashboard öffnen:**
```
Dashboard → Empfehlungsprogramm → API-Einstellungen
```

**Anbieter wählen (Beispiel: Brevo):**

1. **Klick auf Brevo-Karte**
2. **API-Key eintragen:**
   - Zu Brevo gehen → SMTP & API
   - API-Key kopieren
   - Einfügen

3. **Sender-Daten:**
   ```
   Sender-Email: info@deine-domain.de (muss verifiziert sein!)
   Sender-Name: Deine Firma
   ```

4. **Listen-ID** (optional):
   ```
   Listen-ID: 12 (deine Brevo Listen-ID)
   ```

5. **Start-Tag** (optional):
   ```
   Start-Tag: lead_empfehlung
   ```

6. **Double Opt-in:**
   ```
   [✓] Double Opt-in aktivieren
   ```

7. **Button klicken:** "Speichern & Testen"

✅ **Ergebnis:** "Verbindung zu Brevo erfolgreich"

---

### 3️⃣ Erste Belohnungsstufe erstellen (1 Minute)

**Zu Belohnungen:**
```
Dashboard → Empfehlungsprogramm → Freebie wählen → "Belohnungen verwalten"
```

**Neue Belohnungsstufe:**

```
Stufen-Level: 1
Stufen-Name: Bronze-Belohnung
Erforderliche Empfehlungen: 3

Belohnungs-Titel: Kostenloses E-Book "10 Marketing-Tipps"
Belohnungs-Beschreibung: Dein exklusives E-Book als Dankeschön!
Wert: 29€

✅ Aktivieren Sie: "Auto-Zusendung"
✅ Aktivieren Sie: "Über API versenden"
```

**Email-Text (optional):**
```
Betreff: 🎉 Glückwunsch - Du hast die Bronze-Belohnung erreicht!

Text:
Hallo {{name}},

herzlichen Glückwunsch! Du hast 3 erfolgreiche Empfehlungen gemacht 
und dir die Bronze-Belohnung verdient! 🏆

**Deine Belohnung:**
{{reward_title}}

{{reward_description}}

Viel Erfolg weiterhin!

Dein Team von {{company_name}}
```

**Speichern!**

✅ **Fertig!** Erste Belohnung ist aktiv.

---

### 4️⃣ Test durchführen (< 1 Minute)

**Option A: Mit echtem Test-Lead**

1. Gehe zu deinem Freebie
2. Registriere einen Test-Lead
3. Lass den Test-Lead 3 weitere Personen empfehlen
4. Nach der 3. Empfehlung: **Automatische Email!** 📧

**Option B: Manueller Trigger (Entwickler)**

```bash
# Terminal / SSH
cd /pfad/zum/projekt
php api/rewards/reward-email-service.php check 123

# 123 = Lead-ID eines Test-Leads mit 3+ Empfehlungen
```

**Ergebnis prüfen:**

```sql
SELECT * FROM lead_reward_emails
WHERE lead_id = 123
ORDER BY created_at DESC;
```

✅ **Status sollte sein:** `sent`

---

## 🎯 Was passiert automatisch?

```
Lead macht Empfehlung #3
    ↓
Webhook: /api/webhooks/referral-success.php
    ↓
System prüft: Hat Lead >= 3 Empfehlungen?
    ↓
    JA → Belohnung triggern
    ↓
Email-Template vorbereiten (Platzhalter ersetzen)
    ↓
Via Brevo API versenden
    ↓
Status speichern: ✅ sent
    ↓
Tag "bronze_belohnung" zu Lead hinzufügen
    ↓
Lead erhält Email mit Belohnung! 🎉
```

---

## 🔧 Webhook einrichten (Fortgeschritten)

**In deinem Lead-Registration-Code:**

```php
// Nach erfolgreicher Empfehlung
if ($new_referral_registered) {
    
    // Empfehlungs-Counter erhöhen
    $stmt = $pdo->prepare("
        UPDATE lead_users 
        SET successful_referrals = successful_referrals + 1
        WHERE id = ?
    ");
    $stmt->execute([$referrer_lead_id]);
    
    // Webhook triggern für Belohnungs-Check
    $ch = curl_init('https://app.mehr-infos-jetzt.de/api/webhooks/referral-success.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Webhook-Secret: ' . getenv('WEBHOOK_SECRET')
        ],
        CURLOPT_POSTFIELDS => json_encode(['lead_id' => $referrer_lead_id]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        error_log("Reward check triggered for lead {$referrer_lead_id}");
    } else {
        error_log("Reward check failed: " . $response);
    }
}
```

---

## 📊 Monitoring & Debugging

### Status-Dashboard (SQL-Queries)

**Alle versendeten Belohnungs-Emails heute:**
```sql
SELECT 
    lre.*,
    lu.name as lead_name,
    lu.email as lead_email,
    rd.tier_name,
    rd.reward_title
FROM lead_reward_emails lre
INNER JOIN lead_users lu ON lre.lead_id = lu.id
INNER JOIN reward_definitions rd ON lre.reward_id = rd.id
WHERE lre.sent_at >= CURDATE()
ORDER BY lre.sent_at DESC;
```

**Fehlgeschlagene Emails (Retry nötig):**
```sql
SELECT * FROM lead_reward_emails
WHERE send_status = 'failed'
    AND retry_count < max_retries
ORDER BY created_at DESC;
```

**API-Fehler der letzten Stunde:**
```sql
SELECT * FROM email_api_logs
WHERE success = FALSE
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;
```

---

## 🆘 Häufige Probleme

### Email wird nicht versendet

```bash
# Check 1: API-Einstellungen verifiziert?
SELECT is_verified FROM customer_email_api_settings 
WHERE customer_id = DEINE_ID;
# Muss: 1 sein

# Check 2: Belohnung korrekt konfiguriert?
SELECT auto_send_email, send_via_api, is_active 
FROM reward_definitions 
WHERE id = REWARD_ID;
# Alle müssen: 1 sein

# Check 3: Email-Log prüfen
SELECT send_status, error_message 
FROM lead_reward_emails 
WHERE reward_id = REWARD_ID AND lead_id = LEAD_ID;
# send_status sollte 'sent' sein, nicht 'failed'
```

### Brevo sagt "Sender not verified"

```
1. Login bei Brevo
2. Settings → Senders & IP
3. Email hinzufügen
4. Bestätigungs-Email erhalten
5. Verifizieren
6. Nochmal testen in Dashboard
```

### GetResponse sagt "Campaign required"

```
GetResponse MUSS eine Kampagnen-ID haben!

1. Login bei GetResponse
2. Kontakte → Listen
3. Liste auswählen
4. ID aus URL kopieren: .../campaigns/DIESE_ZAHL
5. In API-Einstellungen eintragen
```

---

## 🎓 Nächste Schritte

### Level 2: Mehrere Belohnungsstufen

```
Bronze:   3 Empfehlungen → E-Book
Silber:   5 Empfehlungen → Video-Kurs
Gold:    10 Empfehlungen → 1:1 Beratung
Platin:  20 Empfehlungen → VIP-Zugang
Diamant: 50 Empfehlungen → Jahres-Abo
```

### Level 3: Erweiterte Email-Templates

```html
<!-- Custom HTML mit Branding -->
<div style="font-family: Arial; max-width: 600px;">
  <img src="https://deine-domain.de/logo.png" style="width: 200px;">
  
  <h1 style="color: #667eea;">Glückwunsch {{name}}!</h1>
  
  <div style="background: #f0f4ff; padding: 20px; border-radius: 10px;">
    <h2>{{reward_title}}</h2>
    <p>{{reward_description}}</p>
    
    <a href="{{download_url}}" 
       style="background: #667eea; color: white; padding: 15px 30px; 
              text-decoration: none; border-radius: 5px; display: inline-block;">
      Jetzt herunterladen
    </a>
  </div>
  
  <p style="color: #666; font-size: 12px; margin-top: 30px;">
    {{company_name}} | Abmelden: <a href="#">hier klicken</a>
  </p>
</div>
```

### Level 4: A/B-Testing

```php
// Verschiedene Email-Versionen testen
$templates = [
    'v1' => 'Klassisches Design',
    'v2' => 'Minimalistisch',
    'v3' => 'Mit Video'
];

$version = ['v1', 'v2', 'v3'][array_rand(['v1', 'v2', 'v3'])];
$emailBody = getTemplate($version, $reward);

// Tracking welche Version besser performed
```

### Level 5: Gamification

```php
// Fortschrittsbalken in Email
$progress = ($current_referrals / $next_tier_required) * 100;

echo "
  <div style='background: #eee; height: 20px; border-radius: 10px;'>
    <div style='background: #667eea; width: {$progress}%; height: 20px; 
                border-radius: 10px; transition: width 0.3s;'></div>
  </div>
  <p>Noch {$needed} Empfehlungen bis zur nächsten Belohnung!</p>
";
```

---

## 🏆 Pro-Tipps

### 1. Schrittweise starten
```
Woche 1: 1 Belohnungsstufe, 10 Test-Leads
Woche 2: 3 Belohnungsstufen, 50 Leads
Woche 3: 5 Belohnungsstufen, 200 Leads
Woche 4: Full rollout
```

### 2. Email-Zeiten optimieren
```php
// Beste Versandzeiten: Di-Do, 10-11 Uhr oder 14-15 Uhr
if (date('N') >= 2 && date('N') <= 4) {  // Dienstag-Donnerstag
    if ((date('H') >= 10 && date('H') < 11) || 
        (date('H') >= 14 && date('H') < 15)) {
        sendEmail();
    } else {
        scheduleEmail();  // Für später einplanen
    }
}
```

### 3. Personalisierung maximieren
```
Schlecht:  "Hallo, du hast eine Belohnung!"
Besser:    "Hallo {{name}}, du hast die Bronze-Belohnung erreicht!"
Am besten: "🎉 {{name}}, du bist jetzt in den Top 10% unserer Empfehler!"
```

### 4. Dringlichkeit erzeugen
```
"⏰ Deine Belohnung ist 7 Tage gültig!"
"🔥 Nur noch 3 Plätze für die Bonus-Beratung!"
"💎 Limited Edition - nur für die ersten 50 Empfehler!"
```

### 5. Social Proof einbauen
```
"Über 500 Teilnehmer haben diese Belohnung bereits erhalten!"
"Marie aus München hat durch 10 Empfehlungen den Gold-Status erreicht!"
"Werde Teil unserer Elite-Empfehler-Community!"
```

---

## 📈 KPIs tracken

```sql
-- Durchschnittliche Empfehlungen pro Lead
SELECT AVG(successful_referrals) FROM lead_users;

-- Conversion Rate Belohnungen
SELECT 
    COUNT(DISTINCT lead_id) as leads_with_rewards,
    (SELECT COUNT(*) FROM lead_users) as total_leads,
    (COUNT(DISTINCT lead_id) * 100.0 / (SELECT COUNT(*) FROM lead_users)) as conversion_rate
FROM lead_reward_emails
WHERE send_status = 'sent';

-- Beliebteste Belohnungen
SELECT 
    rd.tier_name,
    rd.reward_title,
    COUNT(*) as times_earned
FROM lead_reward_emails lre
INNER JOIN reward_definitions rd ON lre.reward_id = rd.id
WHERE lre.send_status = 'sent'
GROUP BY rd.id
ORDER BY times_earned DESC;

-- Email Open Rate (falls Provider unterstützt)
SELECT 
    COUNT(*) as total_sent,
    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
    (SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as open_rate
FROM lead_reward_emails
WHERE send_status = 'sent';
```

---

## ✅ Fertig!

Du bist jetzt bereit für automatische Belohnungs-Emails! 🎉

**Vollständige Dokumentation:** `/customer/docs/REFERRAL-API-INTEGRATION.md`

**Support:** Bei Problemen die Logs prüfen:
- `email_api_logs`
- `lead_reward_emails`

**Happy Rewarding!** 🚀
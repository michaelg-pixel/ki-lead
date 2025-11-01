# 🛒 Digistore24 Integration - Vollständige Anleitung

## 📋 Überblick

Diese Integration ermöglicht:
- ✅ Automatische Kurs-Freischaltung nach Kauf
- ✅ Automatische Account-Erstellung für neue Kunden
- ✅ Kauflinks auf gesperrten Premium-Kursen
- ✅ Automatische E-Mail-Benachrichtigung
- ✅ Refund-Handling (Zugang wird entzogen)

---

## 🚀 Setup-Anleitung (5 Schritte)

### **Schritt 1: Datenbank vorbereiten**

Führe das Update-Script aus:
```
https://app.mehr-infos-jetzt.de/setup/update-course-access-table.php
```

**Was passiert:**
- ✅ Spalte `granted_at` wird hinzugefügt (falls nicht vorhanden)
- ✅ Spalte `expires_at` wird hinzugefügt (falls nicht vorhanden)
- ✅ Tabelle ist bereit für automatische Freischaltung

---

### **Schritt 2: Kurs erstellen & Produkt-ID hinterlegen**

1. **Gehe zum Admin:**
   ```
   https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=templates
   ```

2. **Erstelle einen neuen Kurs:**
   - Titel: "Mein Premium Videokurs"
   - Typ: Video
   - ❌ **"Kostenlos" NICHT aktivieren** (für Premium-Kurs)
   - Beschreibung ausfüllen
   - Mockup hochladen

3. **Wichtig: Digistore24 Produkt-ID eintragen:**
   ```
   Digistore24 Produkt-ID: 12345
   ```
   ⚠️ Diese ID ist der Schlüssel für die automatische Zuordnung!

4. **Kurs speichern**

---

### **Schritt 3: Module & Lektionen hinzufügen**

1. **Kurs bearbeiten** (✏️ Button)
2. **Module erstellen:**
   - Modul 1: "Einführung"
   - Modul 2: "Fortgeschritten"
   - etc.

3. **Lektionen hinzufügen:**
   - Titel eingeben
   - Video-URL (YouTube/Vimeo)
   - Beschreibung
   - Optional: PDF-Anhang

---

### **Schritt 4: Digistore24 konfigurieren**

1. **Login bei Digistore24**
   ```
   https://www.digistore24.com
   ```

2. **Gehe zu deinem Produkt**
   - Menü → Produkte → [Dein Produkt]

3. **IPN-URL eintragen:**
   ```
   https://app.ki-leadsystem.com/webhook/digistore24.php
   ```
   
   **Wo:** Einstellungen → IPN/API → IPN URL

4. **Produkt-ID notieren:**
   - Diese ID muss mit der ID im Admin übereinstimmen!
   - Format: Meist eine Zahl wie `12345` oder `ABC123`

5. **IPN-Events aktivieren:**
   - ✅ payment.success
   - ✅ subscription.created
   - ✅ purchase
   - ✅ refund.created

---

### **Schritt 5: Testen**

#### **Option A: Testkauf durchführen**
1. Testkauf über Digistore24 mit Test-Kreditkarte
2. Prüfe die Logs:
   ```
   webhook/webhook-logs.txt
   ```
3. Login mit Test-Account:
   ```
   https://app.mehr-infos-jetzt.de/public/login.php
   ```
4. Überprüfe: Kurs sollte jetzt sichtbar sein unter "Meine Kurse"

#### **Option B: Manuell testen**
1. Erstelle Test-User im Admin
2. Füge manuell Zugang hinzu via SQL:
   ```sql
   INSERT INTO course_access (user_id, course_id, access_source)
   VALUES (123, 456, 'admin')
   ```

---

## 🔄 Workflow - So funktioniert es

### **1. Kunde kauft Kurs bei Digistore24**
```
Kunde → Kauft Produkt #12345 bei Digistore24
```

### **2. Digistore24 sendet Webhook**
```json
{
  "event": "payment.success",
  "product_id": "12345",
  "buyer": {
    "email": "kunde@example.com",
    "first_name": "Max",
    "last_name": "Mustermann"
  },
  "order_id": "DS24-ABC123"
}
```

### **3. Webhook verarbeitet Kauf**

**A) Neuer Kunde:**
```php
// 1. Account erstellen
$userId = createNewUser($email, $name);

// 2. Willkommens-E-Mail senden
sendWelcomeEmail($email, $password, $rawCode);

// 3. Kurs freischalten
grantCourseAccess($userId, $productId);

// 4. Kurs-Zugang-E-Mail senden
sendCourseAccessEmail($email, $courseTitle);
```

**B) Bestehender Kunde:**
```php
// 1. User-ID holen
$userId = getUserIdByEmail($email);

// 2. Kurs freischalten
grantCourseAccess($userId, $productId);

// 3. Kurs-Zugang-E-Mail senden
sendCourseAccessEmail($email, $courseTitle);
```

### **4. Kunde erhält E-Mails**

**E-Mail 1: Willkommen (nur bei neuen Kunden)**
```
Betreff: Willkommen beim KI Leadsystem!

Deine Zugangsdaten:
- E-Mail: kunde@example.com
- Passwort: xyz123abc
- RAW-Code: RAW-2025-123

[Jetzt einloggen]
```

**E-Mail 2: Kurs freigeschaltet**
```
Betreff: Dein Kurs ist jetzt freigeschaltet!

Du hast jetzt Zugang zu:
📚 Mein Premium Videokurs

[Zum Kurs]
```

### **5. Kunde sieht Kurs im Dashboard**
```
https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=kurse

✅ Verfügbare Kurse
   [Mein Premium Videokurs]
   🚀 Kurs starten
```

---

## 🎯 Customer Journey

### **Bevor der Kauf:**
```
Customer Dashboard → Meine Kurse
├── ✅ Verfügbare Kurse (Freebies)
└── 🔒 Weitere Premium-Kurse
    └── [Mein Premium Videokurs]
        └── 🛒 Jetzt kaufen → Digistore24
```

### **Nach dem Kauf:**
```
Customer Dashboard → Meine Kurse
├── ✅ Verfügbare Kurse
│   ├── [Freebie-Kurs]
│   └── [Mein Premium Videokurs] ← NEU!
│       └── 🚀 Kurs starten
└── 🔒 Weitere Premium-Kurse
    └── [Andere Kurse...]
```

---

## 🔍 Debugging & Logs

### **Webhook-Logs überprüfen:**
```bash
# Via SSH
tail -f webhook/webhook-logs.txt

# Oder im Browser (falls öffentlich)
https://app.mehr-infos-jetzt.de/webhook/webhook-logs.txt
```

### **Log-Beispiel bei erfolgreichem Kauf:**
```json
[2025-11-01 10:30:00] [received] {
  "raw_input": "{\"event\":\"payment.success\", ...}"
}

[2025-11-01 10:30:01] [parsed] {
  "event": "payment.success",
  "product_id": "12345",
  "buyer": {...}
}

[2025-11-01 10:30:02] [success] {
  "message": "New customer created",
  "user_id": 789,
  "email": "kunde@example.com"
}

[2025-11-01 10:30:03] [success] {
  "success": "Course access granted",
  "user_id": 789,
  "course_id": 456,
  "course_title": "Mein Premium Videokurs"
}
```

### **Datenbank manuell prüfen:**
```sql
-- Alle Kurs-Zugriffe anzeigen
SELECT 
  u.name,
  u.email,
  c.title,
  ca.access_source,
  ca.granted_at
FROM course_access ca
JOIN users u ON ca.user_id = u.id
JOIN courses c ON ca.course_id = c.id
ORDER BY ca.granted_at DESC;

-- Kurse mit Digistore-ID
SELECT id, title, digistore_product_id 
FROM courses 
WHERE digistore_product_id IS NOT NULL;
```

---

## ❌ Refund-Handling

### **Was passiert bei Rückerstattung:**

1. **Digistore24 sendet Webhook:**
   ```json
   {
     "event": "refund.created",
     "product_id": "12345",
     "buyer": {
       "email": "kunde@example.com"
     }
   }
   ```

2. **Webhook verarbeitet Refund:**
   ```php
   // 1. User deaktivieren
   UPDATE users SET is_active = 0 WHERE email = ?
   
   // 2. Kurs-Zugang entfernen
   DELETE FROM course_access 
   WHERE user_id = ? AND course_id = ?
   ```

3. **Kunde kann nicht mehr auf Kurs zugreifen**

---

## 🛠️ Troubleshooting

### **Problem: Kurs wird nicht freigeschaltet**

**Mögliche Ursachen:**

1. **Produkt-ID stimmt nicht überein**
   ```sql
   -- Prüfen:
   SELECT id, title, digistore_product_id FROM courses;
   ```
   → Muss exakt mit Digistore24 übereinstimmen!

2. **Webhook erreicht Server nicht**
   - Prüfe Firewall-Regeln
   - Teste IPN-URL in Digistore24
   - Schaue in `webhook-logs.txt`

3. **Webhook-Fehler**
   ```bash
   # Logs prüfen:
   tail -50 webhook/webhook-logs.txt | grep error
   ```

### **Problem: Kunde sieht Kurs nicht**

**Checkliste:**

- [ ] Kurs ist `is_active = 1`?
- [ ] Eintrag in `course_access` vorhanden?
- [ ] User ist eingeloggt als `role = 'customer'`?
- [ ] Cache geleert? (Strg+F5)

**SQL-Check:**
```sql
-- Hat User Zugang zu Kurs?
SELECT * FROM course_access 
WHERE user_id = 123 AND course_id = 456;

-- Alle Zugriffe eines Users:
SELECT c.title, ca.access_source, ca.granted_at
FROM course_access ca
JOIN courses c ON ca.course_id = c.id
WHERE ca.user_id = 123;
```

### **Problem: Webhook-Logs leer**

**Lösung:**
1. Ordner-Berechtigung prüfen:
   ```bash
   chmod 755 webhook/
   chmod 666 webhook/webhook-logs.txt
   ```

2. Log-Datei erstellen:
   ```bash
   touch webhook/webhook-logs.txt
   chmod 666 webhook/webhook-logs.txt
   ```

---

## 📧 E-Mail-Konfiguration

### **SMTP-Einstellungen (Optional):**

Falls Standard-PHP-Mail nicht funktioniert, kann PHPMailer verwendet werden:

```php
// In webhook/digistore24.php ersetzen:
use PHPMailer\PHPMailer\PHPMailer;

function sendWelcomeEmail($email, $name, $password, $rawCode) {
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'deine-email@gmail.com';
    $mail->Password = 'dein-app-passwort';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom('noreply@mehr-infos-jetzt.de', 'KI Leadsystem');
    $mail->addAddress($email, $name);
    $mail->Subject = 'Willkommen beim KI Leadsystem!';
    $mail->isHTML(true);
    $mail->Body = "..."; // HTML aus Original-Funktion
    
    $mail->send();
}
```

---

## 📊 Statistiken & Analytics

### **Wichtige Metriken:**

```sql
-- Anzahl Kurs-Zugriffe nach Quelle
SELECT access_source, COUNT(*) as total
FROM course_access
GROUP BY access_source;

-- Umsatz-Kurse (über Digistore24 verkauft)
SELECT c.title, COUNT(*) as verkauft
FROM course_access ca
JOIN courses c ON ca.course_id = c.id
WHERE ca.access_source = 'digistore24'
GROUP BY c.title
ORDER BY verkauft DESC;

-- Neue Kunden heute
SELECT COUNT(*) 
FROM users 
WHERE DATE(created_at) = CURDATE()
AND source = 'digistore24';
```

---

## ✅ Checkliste für Go-Live

- [ ] Datenbank-Update ausgeführt
- [ ] Kurse erstellt mit Digistore-Produkt-ID
- [ ] IPN-URL in Digistore24 konfiguriert
- [ ] Testkauf durchgeführt
- [ ] Webhook-Logs überprüft
- [ ] E-Mails funktionieren
- [ ] Customer kann Kurs sehen und starten
- [ ] Refund getestet (optional)
- [ ] Backup erstellt

---

## 📞 Support

Bei Problemen:
1. Webhook-Logs prüfen: `webhook/webhook-logs.txt`
2. Datenbank-Einträge prüfen (SQL oben)
3. Browser-Console auf Fehler prüfen (F12)
4. Digistore24 IPN-Logs prüfen

---

**Viel Erfolg mit deiner automatisierten Kurs-Verkaufsmaschine! 🚀**
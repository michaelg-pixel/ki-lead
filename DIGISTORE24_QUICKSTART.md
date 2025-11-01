# ✅ Digistore24 Integration - Schnellstart

## 🎯 In 3 Minuten startklar!

### 1️⃣ **Datenbank aktualisieren** (30 Sekunden)
```
https://app.mehr-infos-jetzt.de/setup/update-course-access-table.php
```
✅ Führt alle notwendigen Updates automatisch durch

---

### 2️⃣ **Kurs mit Produkt-ID erstellen** (1 Minute)

**Admin-Bereich:**
```
https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=templates
```

**Wichtig beim Erstellen:**
- ❌ **"Kostenlos" NICHT aktivieren** (für Premium)
- ✏️ **Digistore24 Produkt-ID eintragen**: z.B. `12345`
- 💾 **Speichern**

---

### 3️⃣ **Digistore24 konfigurieren** (1 Minute)

**In Digistore24:**
1. Gehe zu: Produkt → Einstellungen → IPN/API
2. Trage IPN-URL ein:
   ```
   https://app.ki-leadsystem.com/webhook/digistore24.php
   ```
3. Aktiviere Events:
   - ✅ payment.success
   - ✅ subscription.created
   - ✅ refund.created

---

## 🎉 Fertig! So funktioniert es jetzt:

### **Für den Kunden:**

#### **Vor dem Kauf:**
```
Customer Dashboard → Meine Kurse
└── 🔒 Premium-Kurs
    └── [🛒 Jetzt kaufen] ← Klick führt zu Digistore24
```

#### **Nach dem Kauf:**
```
✅ Automatische Account-Erstellung (falls neu)
✅ E-Mail mit Login-Daten
✅ Sofortige Kurs-Freischaltung
✅ E-Mail mit Kurs-Zugang

Customer Dashboard → Meine Kurse
└── ✅ Premium-Kurs
    └── [🚀 Kurs starten] ← JETZT VERFÜGBAR!
```

---

## 🔥 Was automatisch passiert:

### **Bei Kauf:**
1. Kunde kauft bei Digistore24
2. Webhook empfängt Benachrichtigung
3. **Neuer Kunde?**
   - Account wird erstellt
   - Passwort generiert
   - Willkommens-E-Mail versendet
4. **Kurs wird freigeschaltet**
   - Zugang in Datenbank
   - Kurs-E-Mail versendet
5. Kunde loggt sich ein
6. **Kurs ist sofort verfügbar!** ✅

### **Bei Refund:**
1. Kunde fordert Rückerstattung
2. Webhook empfängt Benachrichtigung
3. **Account wird deaktiviert**
4. **Kurs-Zugang wird entfernt**

---

## 🧪 Testen:

### **Option 1: Testkauf**
1. Testkauf bei Digistore24
2. Warte 1-2 Minuten
3. Login mit Test-Account
4. Kurs sollte unter "Meine Kurse" erscheinen

### **Option 2: Webhook-Test**
Sende Test-Webhook mit cURL:
```bash
curl -X POST https://app.ki-leadsystem.com/webhook/digistore24.php \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.success",
    "product_id": "12345",
    "buyer": {
      "email": "test@example.com",
      "first_name": "Test",
      "last_name": "User"
    },
    "order_id": "TEST-123"
  }'
```

### **Logs überprüfen:**
```
webhook/webhook-logs.txt
```

---

## 📱 Customer-Ansicht

### **Verfügbare Kurse (Freebie + Gekaufte):**
![Verfügbare Kurse](https://via.placeholder.com/800x300/a855f7/ffffff?text=Verfügbare+Kurse)
```
✅ Fortschrittsbalken
✅ "Kurs starten" Button
✅ Anzahl Module/Lektionen
```

### **Premium-Kurse (Noch nicht gekauft):**
![Premium Kurse](https://via.placeholder.com/800x300/fb7185/ffffff?text=Premium+Kurse)
```
🔒 Lock-Overlay beim Hover
🛒 "Jetzt kaufen" Button
📊 Kurs-Informationen sichtbar
```

---

## 💡 Pro-Tipps:

### **Mehrere Kurse verkaufen:**
Erstelle für jeden Kurs eine eigene Digistore24-Produkt-ID:
```
Kurs 1 → Produkt-ID: 12345
Kurs 2 → Produkt-ID: 12346
Kurs 3 → Produkt-ID: 12347
```

### **Bundle-Angebote:**
Erstelle Bundle mit mehreren Produkt-IDs:
```php
// In webhook/digistore24.php anpassen:
$productIds = explode(',', $data['product_id']); // "12345,12346"
foreach ($productIds as $pid) {
    grantCourseAccess($userId, $pid);
}
```

### **Zeitlich begrenzte Kurse:**
Nutze `expires_at` Spalte:
```sql
UPDATE course_access 
SET expires_at = DATE_ADD(NOW(), INTERVAL 90 DAY)
WHERE user_id = 123 AND course_id = 456;
```

---

## ⚠️ Wichtige Hinweise:

### **Produkt-ID muss EXAKT übereinstimmen:**
```
❌ Falsch: "12345" vs "12345 "  (Leerzeichen)
❌ Falsch: "12345" vs "ABC-12345"
✅ Richtig: "12345" === "12345"
```

### **IPN-Events aktivieren:**
In Digistore24 MÜSSEN diese Events aktiviert sein:
- ✅ payment.success
- ✅ subscription.created
- ✅ purchase

Ohne diese Events funktioniert die automatische Freischaltung NICHT!

### **Webhook-Logs beachten:**
Jeder eingehende Webhook wird geloggt in:
```
webhook/webhook-logs.txt
```
Bei Problemen zuerst hier nachschauen!

---

## 📊 Status prüfen:

### **SQL-Abfragen:**

**Alle Kurs-Zugriffe:**
```sql
SELECT u.email, c.title, ca.access_source 
FROM course_access ca
JOIN users u ON ca.user_id = u.id
JOIN courses c ON ca.course_id = c.id;
```

**Verkaufte Kurse heute:**
```sql
SELECT c.title, COUNT(*) as verkauft
FROM course_access ca
JOIN courses c ON ca.course_id = c.id
WHERE DATE(ca.granted_at) = CURDATE()
AND ca.access_source = 'digistore24'
GROUP BY c.title;
```

**User mit Zugriff:**
```sql
SELECT u.name, u.email, COUNT(ca.id) as kurse
FROM users u
LEFT JOIN course_access ca ON u.id = ca.user_id
WHERE u.role = 'customer'
GROUP BY u.id;
```

---

## 🚀 Go-Live Checkliste:

- [ ] ✅ Datenbank aktualisiert
- [ ] ✅ Kurs erstellt mit Digistore-ID
- [ ] ✅ Module & Lektionen hinzugefügt
- [ ] ✅ IPN-URL in Digistore24 konfiguriert
- [ ] ✅ IPN-Events aktiviert
- [ ] ✅ Testkauf durchgeführt
- [ ] ✅ Webhook-Logs überprüft
- [ ] ✅ E-Mails kommen an
- [ ] ✅ Kurs im Customer-Dashboard sichtbar
- [ ] ✅ Video spielt ab
- [ ] ✅ Fortschritt wird gespeichert

---

## 📞 Support & Hilfe:

**Dokumentation:**
- 📘 Vollständige Anleitung: `DIGISTORE24_INTEGRATION.md`
- 🚀 Schnellstart (diese Datei): `DIGISTORE24_QUICKSTART.md`
- 🎓 Videokurs-System: `VIDEOKURS_SYSTEM_README.md`

**Bei Problemen:**
1. Webhook-Logs prüfen
2. Datenbank-Abfragen ausführen
3. Browser-Console checken (F12)
4. Vollständige Anleitung lesen

---

## 🎉 Geschafft!

Dein automatisches Kurs-Verkaufssystem ist jetzt einsatzbereit!

**Der Ablauf:**
```
Kunde kauft → Webhook → Auto-Account → Kurs freigeschaltet → E-Mail → Kunde lernt! 🚀
```

**Viel Erfolg beim Verkaufen! 💰**
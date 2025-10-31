# 🎯 Kundenverwaltung mit Digistore24 Integration

## 📋 Übersicht

Vollständiges Kundenverwaltungssystem mit automatischer Digistore24-Integration:

✅ **Automatische Kunden-Registrierung** via Digistore24 Webhook  
✅ **Manuelle Kunden-Verwaltung** im Admin-Dashboard  
✅ **Freebie-Zuweisung** per Klick  
✅ **Kunden sperren/aktivieren**  
✅ **RAW-Code System** für eindeutige Identifikation  
✅ **Modernes Dark-Purple Design** wie im Screenshot  

---

## 🚀 Installation

### 1. Datenbank-Setup

Führe das SQL-Setup-Skript aus:

```bash
mysql -u dein_user -p deine_datenbank < setup/customer-management-setup.sql
```

Oder in CloudPanel/PhpMyAdmin:
1. Öffne PhpMyAdmin
2. Wähle deine Datenbank aus
3. Gehe zu "SQL"
4. Kopiere den Inhalt von `setup/customer-management-setup.sql`
5. Klicke "Ausführen"

### 2. Digistore24 Webhook einrichten

**In Digistore24:**

1. Gehe zu **Produkt → IPN/Webhook**
2. Trage folgende URL ein:
   ```
   https://app.mehr-infos-jetzt.de/webhook/digistore24.php
   ```
3. Aktiviere folgende Events:
   - ✅ `payment.success`
   - ✅ `subscription.created`
   - ✅ `refund.created`
4. Speichern!

**Was passiert automatisch?**
- Bei Kauf wird automatisch ein Kunden-Account erstellt
- Kunde erhält E-Mail mit:
  - Login-Daten (E-Mail + Passwort)
  - RAW-Code
  - Login-Link
- Bei Rückerstattung wird der Account automatisch gesperrt

---

## 📱 Features im Admin-Dashboard

### Kundenverwaltung (Admin-Seite)

**URL:** `https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=users`

#### 🔍 Suche & Filter
- Suche nach Name, E-Mail oder RAW-Code
- Status-Filter: Alle / Aktiv / Inaktiv

#### ➕ Kunden hinzufügen
1. Klicke "Neuen Kunden hinzufügen"
2. Fülle aus:
   - Name
   - E-Mail
   - Passwort (mind. 8 Zeichen)
3. Kunde erhält automatisch:
   - RAW-Code
   - Willkommens-E-Mail mit Login-Daten

#### 📋 Aktionen pro Kunde

| Icon | Aktion | Beschreibung |
|------|--------|--------------|
| 👁️ | Ansehen | Details anzeigen |
| ➕ | Freebie zuweisen | Template auswählen und zuweisen |
| ✏️ | Bearbeiten | Kundendaten ändern |
| 🔒/🔓 | Sperren/Aktivieren | Account-Zugang steuern |
| 🗑️ | Löschen | Permanent löschen (mit Bestätigung) |

#### 🎁 Freebie-Zuweisung
1. Klicke auf ➕ beim Kunden
2. Wähle Freebie-Template aus
3. Kunde erhält E-Mail-Benachrichtigung
4. Freebie erscheint in seinem Dashboard

---

## 🎨 Design-Anpassungen

Das Dashboard nutzt ein modernes **Dark-Purple Theme**:

### Farben
```css
/* Hauptfarben */
--primary: #a855f7;        /* Lila */
--primary-dark: #9333ea;   /* Dunkel-Lila */
--background: #0f0f1e;     /* Dunkel-Blau */
--surface: #1e1e3f;        /* Card-Hintergrund */
--border: rgba(168, 85, 247, 0.3);

/* Status */
--success: #4ade80;        /* Grün (Aktiv) */
--danger: #ef4444;         /* Rot (Inaktiv/Löschen) */
```

### Responsive
- Mobile-optimiert
- Tablet-freundlich
- Desktop-ready

---

## 🔐 Sicherheit

### Admin-Zugriff
```php
// Alle API-Endpunkte prüfen:
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Keine Berechtigung');
}
```

### Webhook-Sicherheit
- Logging aller Webhook-Aufrufe in `webhook/webhook-logs.txt`
- JSON-Validierung
- Exception-Handling

### Passwort-Handling
- Automatische Generierung sicherer Passwörter (16 Zeichen)
- Password-Hashing mit `password_hash()`
- Passwort-Änderung nach erstem Login empfohlen

---

## 📊 Datenbank-Struktur

### `users` Tabelle (erweitert)
```sql
- id (Primary Key)
- name
- email
- password (hashed)
- role (customer/admin)
- is_active (1/0)
- raw_code (RAW-2025-001)
- digistore_order_id
- digistore_product_id
- digistore_product_name
- source (digistore24/manual)
- refund_date
- created_at
- updated_at
```

### `user_freebies` Tabelle (neu)
```sql
- id
- user_id (FK zu users)
- freebie_id (FK zu freebie_templates)
- assigned_at
- assigned_by (Admin User ID)
- completed (0/1)
- completed_at
```

### `user_progress` Tabelle (neu)
```sql
- id
- user_id
- content_type (course/tutorial/freebie)
- content_id
- progress (0-100%)
- last_accessed
- completed
- completed_at
```

---

## 🔧 API-Endpunkte

### POST `/api/customer-add.php`
**Beschreibung:** Neuen Kunden manuell hinzufügen

**Body:**
```json
{
  "name": "Max Mustermann",
  "email": "max@example.com",
  "password": "Sicher123!"
}
```

**Response:**
```json
{
  "success": true,
  "user_id": 42,
  "raw_code": "RAW-2025-123"
}
```

### POST `/api/customer-assign-freebie.php`
**Beschreibung:** Freebie-Template zuweisen

**Body:**
```json
{
  "user_id": 42,
  "freebie_id": 7
}
```

### POST `/api/customer-toggle-status.php`
**Beschreibung:** Status ändern (sperren/aktivieren)

**Body:**
```json
{
  "user_id": 42
}
```

### POST `/api/customer-delete.php`
**Beschreibung:** Kunden permanent löschen

**Body:**
```json
{
  "user_id": 42
}
```

---

## 📧 E-Mail-Templates

### Willkommens-E-Mail (Automatisch)
- **Auslöser:** Neuer Kunde (Digistore24 oder manuell)
- **Inhalt:**
  - Begrüßung
  - Login-Daten (E-Mail + Passwort)
  - RAW-Code
  - Login-Button

### Freebie-Zuweisung (Automatisch)
- **Auslöser:** Admin weist Freebie zu
- **Inhalt:**
  - Benachrichtigung über neue Zuweisung
  - Template-Name
  - Link zum Freebie-Bereich

### Sperrung/Aktivierung (Automatisch)
- **Auslöser:** Admin ändert Status
- **Inhalt:**
  - Info über Änderung
  - Support-Kontakt (bei Sperrung)
  - Login-Link (bei Aktivierung)

---

## 🐛 Debugging

### Webhook-Logs prüfen
```bash
tail -f webhook/webhook-logs.txt
```

**Format:**
```
[2025-10-31 10:30:15] [received] {"raw_input": "..."}
[2025-10-31 10:30:15] [parsed] {"event": "payment.success", ...}
[2025-10-31 10:30:16] [success] {"message": "New customer created", "user_id": 42}
```

### Fehlersuche

**Problem:** Webhook funktioniert nicht
```bash
# Prüfe Log-Datei
cat webhook/webhook-logs.txt

# Prüfe Berechtigungen
chmod 644 webhook/digistore24.php
chmod 666 webhook/webhook-logs.txt
```

**Problem:** E-Mails kommen nicht an
```php
// Test Mail-Funktion
mail('test@example.com', 'Test', 'Test-Nachricht');
```

**Problem:** Datenbank-Fehler
```sql
-- Prüfe Tabellen
SHOW TABLES LIKE 'user%';

-- Prüfe Spalten
DESCRIBE users;
DESCRIBE user_freebies;
```

---

## ✅ Testing Checklist

### Digistore24 Integration
- [ ] Webhook-URL in Digistore24 eingetragen
- [ ] Test-Kauf durchgeführt
- [ ] Kunde in Datenbank angelegt
- [ ] E-Mail empfangen
- [ ] RAW-Code generiert
- [ ] Login funktioniert

### Manuelle Kundenverwaltung
- [ ] Kunde hinzufügen funktioniert
- [ ] Freebie zuweisen funktioniert
- [ ] Status ändern funktioniert
- [ ] Kunde löschen funktioniert
- [ ] Suche funktioniert
- [ ] Filter funktionieren

### Design
- [ ] Mobile responsive
- [ ] Alle Icons sichtbar
- [ ] Farben korrekt (Lila-Theme)
- [ ] Modals funktionieren
- [ ] Animationen smooth

---

## 🎯 Nächste Schritte

1. **SQL-Setup ausführen**
2. **Digistore24 konfigurieren**
3. **Test-Kunden anlegen**
4. **Webhook testen**
5. **Design prüfen**

---

## 📞 Support

Bei Fragen oder Problemen:
- GitHub Issues erstellen
- Logs prüfen (`webhook/webhook-logs.txt`)
- Datenbank-Status prüfen

---

**Viel Erfolg! 🚀**

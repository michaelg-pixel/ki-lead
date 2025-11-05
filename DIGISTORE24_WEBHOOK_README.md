# 🛒 Digistore24 Webhook-Zentrale - Dokumentation

## 📋 Übersicht

Das Digistore24 Webhook-System ermöglicht die zentrale Verwaltung von Produkten und deren automatische Verarbeitung bei Käufen über Digistore24.

### Funktionen

✅ **Zentrale Produktverwaltung** - Admin trägt nur die Produkt-ID ein  
✅ **Automatische Kundenanlage** - Neuer Account wird erstellt  
✅ **Freebie-Limits** - Automatische Zuweisung basierend auf Produkt  
✅ **Empfehlungsprogramm** - Slots werden automatisch vergeben  
✅ **Launch-Paket** - Fertige Freebies werden direkt zugewiesen  
✅ **Kurs-Zugang** - Optional Kurse freischalten  
✅ **Rückerstattungen** - Automatische Deaktivierung bei Refund  
✅ **Abo-Verwaltung** - Downgrade bei Abo-Ende

---

## 🚀 Einrichtung (3 Schritte)

### Schritt 1: Datenbank einrichten

Rufe folgende URL auf:
```
https://app.mehr-infos-jetzt.de/database/setup-digistore-products.php
```

Das Script:
- Erstellt die Tabelle `digistore_products`
- Legt die 4 Standard-Produktvarianten an:
  - Launch Angebot (497€)
  - Starter Abo (49€/Monat)
  - Pro Abo (99€/Monat)
  - Business Abo (199€/Monat)

### Schritt 2: Produkt-IDs eintragen

1. Gehe zu **Admin-Dashboard → Digistore24**
2. Trage bei jedem Produkt die Digistore24 Produkt-ID ein
3. Aktiviere das Produkt mit dem Schalter
4. Klicke auf **"Speichern"**

### Schritt 3: Webhook in Digistore24 einrichten

1. Logge dich in Digistore24 ein
2. Gehe zu deinem Produkt
3. Klicke auf **"IPN Settings"**
4. Trage folgende URL ein:
   ```
   https://app.mehr-infos-jetzt.de/webhook/digistore24.php
   ```
5. Speichern!

---

## 📦 Produktvarianten

### Launch Angebot (497€ einmalig)
- **4 fertige Freebies** (sofort verfügbar)
- **4 eigene Freebies** (selbst erstellen)
- **1 Empfehlungsprogramm-Slot**

### Starter Abo (49€/Monat)
- **4 eigene Freebies**
- **1 Empfehlungsprogramm-Slot**

### Pro Abo (99€/Monat)
- **8 eigene Freebies**
- **3 Empfehlungsprogramm-Slots**

### Business Abo (199€/Monat)
- **20 eigene Freebies**
- **10 Empfehlungsprogramm-Slots**

---

## 🔄 Webhook-Ablauf

### Bei Kauf (payment.success)

1. **Kunde anlegen**
   - E-Mail, Name aus Digistore24
   - Passwort wird generiert
   - RAW-Code wird erstellt

2. **Produkt-Konfiguration laden**
   - System prüft welches Produkt gekauft wurde
   - Lädt die Einstellungen aus `digistore_products`

3. **Features zuweisen**
   - Freebie-Limit setzen
   - Empfehlungs-Slots vergeben
   - Fertige Freebies zuweisen (Launch)
   - Kurs-Zugang gewähren (optional)

4. **E-Mails versenden**
   - Willkommens-E-Mail mit Zugangsdaten
   - Kurs-Freischaltungs-E-Mail (falls Kurs)

### Bei Rückerstattung (refund.created)

- Kunde wird deaktiviert
- Freebie-Limit → 0
- Empfehlungs-Slots → 0
- Kurs-Zugang entfernt

### Bei Abo-Ende (subscription.cancelled)

- Downgrade auf Freemium (2 Freebies)
- Empfehlungs-Slots → 0

---

## 🧪 Testen

### Webhook testen

1. Gehe zu **Admin-Dashboard → Digistore24**
2. Klicke bei einem aktiven Produkt auf **"Webhook testen"**
3. Das System erstellt einen Test-User
4. Prüfe das Ergebnis und die Logs

### Logs prüfen

Alle Webhook-Aktivitäten werden geloggt in:
```
/webhook/webhook-logs.txt
```

---

## 🗂️ Datenbank-Struktur

### Tabelle: `digistore_products`

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `product_id` | VARCHAR(100) | Digistore24 Produkt-ID |
| `product_name` | VARCHAR(255) | Name des Produkts |
| `product_type` | ENUM | launch, starter, pro, business, custom |
| `price` | DECIMAL(10,2) | Preis in Euro |
| `billing_type` | ENUM | one_time, monthly, yearly |
| `own_freebies_limit` | INT | Anzahl eigener Freebies |
| `ready_freebies_count` | INT | Anzahl fertiger Freebies |
| `referral_program_slots` | INT | Empfehlungsprogramm Slots |
| `is_active` | TINYINT(1) | Produkt aktiv? |

---

## 📁 Dateien

```
📦 Digistore24 System
├── 📄 database/setup-digistore-products.php   # Setup-Script
├── 📄 admin/sections/digistore.php            # Admin-Oberfläche
├── 📄 api/digistore-update.php                # Update-API
├── 📄 webhook/digistore24.php                 # Webhook-Handler
├── 📄 webhook/test-digistore.php              # Test-Tool
└── 📄 DIGISTORE24_WEBHOOK_README.md           # Diese Datei
```

---

## 🎯 Erweiterungen

### Eigene Produktvarianten

Du kannst eigene Produktvarianten hinzufügen:

1. Gehe in die Datenbank
2. Füge einen neuen Eintrag in `digistore_products` ein:
   ```sql
   INSERT INTO digistore_products (
       product_id, product_name, product_type, price, billing_type,
       own_freebies_limit, ready_freebies_count, referral_program_slots
   ) VALUES (
       '', 'VIP Paket', 'custom', 499.00, 'one_time',
       50, 10, 20
   );
   ```
3. Trage die Digistore-ID im Admin ein

### Kurse verknüpfen

Um einen Kurs automatisch freizuschalten:

1. Gehe zu **Admin → Kurse**
2. Bearbeite den Kurs
3. Trage die Digistore24 Produkt-ID ein
4. Speichern!

Der Webhook schaltet den Kurs automatisch frei wenn das Produkt gekauft wird.

---

## ❓ Häufige Fragen

### Der Webhook wird nicht ausgelöst?

1. **Prüfe die IPN-URL in Digistore24**
   - Muss exakt sein: `https://app.mehr-infos-jetzt.de/webhook/digistore24.php`
   - Keine Leerzeichen oder zusätzliche Zeichen

2. **Prüfe ob Produkt aktiv ist**
   - Admin-Dashboard → Digistore24
   - Produkt muss grünes "✅ Aktiv" haben

3. **Schaue in die Logs**
   - `/webhook/webhook-logs.txt`
   - Zeigt alle eingehenden Requests

### Kunde bekommt zu wenig/zu viele Freebies?

1. **Prüfe die Produkt-Konfiguration**
   - Admin-Dashboard → Digistore24
   - Ist das richtige Limit eingetragen?

2. **Prüfe die Datenbank**
   ```sql
   SELECT * FROM customer_freebie_limits WHERE customer_id = XXX;
   ```

### Fertige Freebies werden nicht zugewiesen?

Die Tabelle `freebies` braucht Templates mit `is_template = 1`:

```sql
UPDATE freebies SET is_template = 1 WHERE id IN (1,2,3,4);
```

---

## 📞 Support

Bei Fragen oder Problemen:

1. Prüfe die **Logs** (`/webhook/webhook-logs.txt`)
2. Nutze das **Test-Tool** (`/webhook/test-digistore.php`)
3. Schaue in die **Datenbank** (Admin-Tools)

---

## 🔐 Sicherheit

- ✅ Webhook-URL ist öffentlich zugänglich (muss für Digistore24)
- ✅ Keine kritischen Aktionen ohne Validierung
- ✅ Alle Datenbank-Operationen nutzen Prepared Statements
- ✅ Logging für Nachvollziehbarkeit
- ✅ E-Mail-Versand nur an verifizierte Käufer

---

**Stand:** November 2025  
**Version:** 2.0  
**Autor:** KI Leadsystem Team

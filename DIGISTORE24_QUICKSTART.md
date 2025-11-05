# 🚀 Digistore24 Webhook - Schnellstart

## ⚡ Installation in 5 Minuten

### 1️⃣ Datenbank einrichten

```
https://app.ki-leadsystem.com/database/setup-digistore-products.php
```
✅ Erstellt `digistore_products` Tabelle  
✅ Legt 4 Standard-Produkte an

---

### 2️⃣ Zusätzliche Tabellen erstellen

```
https://app.ki-leadsystem.com/database/migrate-referral-slots.php
```
✅ Erstellt `customer_referral_slots`  
✅ Erstellt `customer_freebies`  
✅ Fügt `is_template` Spalte hinzu

---

### 3️⃣ Produkt-IDs eintragen

1. **Admin-Dashboard öffnen:**
   ```
   https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=digistore
   ```

2. **Bei jedem Produkt:**
   - Digistore24 Produkt-ID eintragen
   - Produkt aktivieren ✅
   - Speichern 💾

---

### 4️⃣ Webhook in Digistore24 einrichten

1. Digistore24 Login
2. Produkt → IPN Settings
3. URL eintragen:
   ```
   https://app.ki-leadsystem.com/webhook/digistore24.php
   ```
4. Speichern!

---

### 5️⃣ Testen

**Webhook testen:**
```
Admin-Dashboard → Digistore24 → "Webhook testen" Button
```

**Logs prüfen:**
```
/webhook/webhook-logs.txt
```

---

## 📦 Die 4 Produkte

| Produkt | Preis | Eigene Freebies | Fertige Freebies | Empf.-Slots |
|---------|-------|-----------------|------------------|-------------|
| **Launch** | 497€ einmalig | 4 | 4 | 1 |
| **Starter** | 49€/Monat | 4 | - | 1 |
| **Pro** | 99€/Monat | 8 | - | 3 |
| **Business** | 199€/Monat | 20 | - | 10 |

---

## 🎯 Was passiert beim Kauf?

1. ✅ Kunde wird automatisch angelegt
2. ✅ Passwort & RAW-Code generiert
3. ✅ Freebie-Limits werden gesetzt
4. ✅ Empfehlungs-Slots vergeben
5. ✅ Fertige Freebies zugewiesen (Launch)
6. ✅ Kurse freigeschaltet (optional)
7. ✅ Willkommens-E-Mail verschickt

---

## 📖 Fertige Freebies markieren (Launch-Paket)

Damit das Launch-Paket die 4 fertigen Freebies zuweist:

```sql
UPDATE freebies 
SET is_template = 1 
WHERE id IN (1,2,3,4);
```

Ersetze `1,2,3,4` mit deinen Template-IDs.

---

## 🔍 Troubleshooting

### Webhook funktioniert nicht?

1. ✅ Produkt-ID korrekt eingetragen?
2. ✅ Produkt aktiviert? (grüner Haken)
3. ✅ IPN-URL in Digistore24 korrekt?
4. ✅ Logs prüfen: `/webhook/webhook-logs.txt`

### Zu wenig/viele Freebies?

Prüfe in der Datenbank:
```sql
SELECT * FROM customer_freebie_limits WHERE customer_id = XXX;
```

### Test-Tool nutzen

```
https://app.ki-leadsystem.com/webhook/test-digistore.php?product_id=DEINE_ID
```

---

## 📚 Vollständige Dokumentation

Siehe: [DIGISTORE24_WEBHOOK_README.md](DIGISTORE24_WEBHOOK_README.md)

---

**Fertig!** 🎉  
Das System ist jetzt einsatzbereit und verarbeitet automatisch alle Käufe über Digistore24.

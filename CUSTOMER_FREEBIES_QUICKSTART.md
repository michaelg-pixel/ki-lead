# 🚀 Quick Start - Kundengesteuerte Freebie-Erstellung

## Setup in 5 Minuten

### Option 1: Automatisches Setup (empfohlen)

```bash
cd setup
chmod +x install-customer-freebies.sh
./install-customer-freebies.sh
```

Das Skript führt automatisch alle Schritte aus und gibt dir die Webhook-URL für Digistore24.

---

### Option 2: Manuelles Setup

#### Schritt 1: Datenbank einrichten

```bash
mysql -u dein_user -p deine_datenbank < setup/setup-customer-freebie-limits.sql
```

#### Schritt 2: Webhook in Digistore24 konfigurieren

1. Gehe zu Digistore24 → Einstellungen → IPN/Webhook
2. Trage ein: `https://deine-domain.de/webhook/digistore24.php`
3. Aktiviere Events:
   - ✅ `payment.success`
   - ✅ `subscription.created`
   - ✅ `refund.created`

#### Schritt 3: Produkt-Konfiguration

1. Öffne: `https://deine-domain.de/admin/freebie-limits.php`
2. Erstelle Konfigurationen für deine Produkte:

| Produkt-ID | Produkt-Name | Freebie-Limit |
|-----------|-------------|---------------|
| DEIN_STARTER_ID | Starter | 5 |
| DEIN_PRO_ID | Professional | 10 |
| DEIN_ENTERPRISE_ID | Enterprise | 25 |

#### Schritt 4: Testen

1. Test-Kauf über Digistore24 durchführen
2. Webhook-Logs prüfen: `webhook/webhook-logs.txt`
3. Datenbank prüfen:
   ```sql
   SELECT * FROM customer_freebie_limits;
   ```

---

## Für Kunden

### Eigenes Freebie erstellen

1. **Login** → Dashboard → Freebies
2. Klicke auf **"✨ Eigenes Freebie erstellen"**
3. Fülle das Formular aus:
   - Name (intern)
   - Überschriften
   - Button-Text
   - E-Mail Optin-Code
   - Farben
   - Layout
4. Klicke **"💾 Speichern"**
5. **Kopiere die Links** und nutze sie in deinem Marketing!

### Links verwenden

Nach dem Speichern erhältst du:

**Freebie-Link:**
```
https://deine-domain.de/freebie/index.php?id=custom-abc123
```
→ Verwende diesen Link in deinen Marketing-Kampagnen

**Danke-Seiten-Link:**
```
https://deine-domain.de/freebie/thankyou.php?id=456&customer=123
```
→ Verwende diesen Link in deinem E-Mail-Provider als Weiterleitungs-URL

---

## Admin-Features

### Produkt-Konfiguration verwalten

```
https://deine-domain.de/admin/freebie-limits.php
```

Hier kannst du:
- ✅ Neue Produkte hinzufügen
- ✅ Limits anpassen
- ✅ Produkte aktivieren/deaktivieren
- ✅ Statistiken einsehen

### Kunden-Limits manuell anpassen

```sql
-- Limit für Kunde erhöhen
UPDATE customer_freebie_limits 
SET freebie_limit = 20 
WHERE customer_id = 123;

-- Limit für alle Kunden eines Produkts erhöhen
UPDATE customer_freebie_limits 
SET freebie_limit = 15 
WHERE product_id = 'STARTER_001';
```

---

## Beispiel-Workflow

### 1. Kunde kauft "Professional Paket"
- Digistore24 sendet Webhook
- System erstellt Account
- System setzt Freebie-Limit auf 10

### 2. Kunde erstellt Freebie
- Kunde loggt sich ein
- Klickt "Eigenes Freebie erstellen"
- Füllt Formular aus und speichert
- Erhält sofort die Marketing-Links

### 3. Kunde nutzt Freebie
- Teilt Freebie-Link auf Social Media
- Interessenten füllen E-Mail-Formular aus
- Werden automatisch weitergeleitet zur Danke-Seite
- Kunde kann Conversions tracken

---

## Häufige Probleme

### Problem: "Limit erreicht"

**Lösung:**
```sql
-- Aktuelles Limit prüfen
SELECT * FROM customer_freebie_limits WHERE customer_id = 123;

-- Anzahl Custom Freebies prüfen
SELECT COUNT(*) FROM customer_freebies 
WHERE customer_id = 123 AND freebie_type = 'custom';

-- Limit manuell erhöhen
UPDATE customer_freebie_limits 
SET freebie_limit = 20 
WHERE customer_id = 123;
```

### Problem: Webhook funktioniert nicht

**Lösung:**
1. Prüfe Webhook-Logs: `webhook/webhook-logs.txt`
2. Teste URL manuell: `curl https://deine-domain.de/webhook/digistore24.php`
3. Prüfe Firewall-Einstellungen
4. Stelle sicher, dass Digistore24 Zugriff hat

### Problem: Links funktionieren nicht

**Lösung:**
1. Prüfe ob `unique_id` gesetzt ist:
   ```sql
   SELECT unique_id FROM customer_freebies WHERE id = 123;
   ```
2. Stelle sicher dass Freebie gespeichert wurde
3. Prüfe `.htaccess` Konfiguration

---

## Support-Checkliste

Vor Support-Anfrage prüfen:

- [ ] Datenbank-Tabellen existieren
- [ ] Webhook-URL korrekt in Digistore24 eingetragen
- [ ] Webhook-Logs zeigen erfolgreiche Requests
- [ ] Produkt-Konfiguration existiert für Produkt-ID
- [ ] PHP-Extensions (PDO, JSON, Session) installiert
- [ ] Datei-Berechtigungen korrekt

---

## Weitere Ressourcen

📖 **Vollständige Dokumentation:**
`CUSTOMER_FREEBIES_README.md`

🎬 **Video-Tutorial:**
(Coming Soon)

💬 **Support:**
support@deine-domain.de

---

**Version:** 1.0.0  
**Letzte Aktualisierung:** November 2025

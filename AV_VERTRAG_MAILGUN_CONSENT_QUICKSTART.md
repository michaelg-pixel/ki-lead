# 🚀 QUICKSTART: AVV Mailgun Consent

## ⚡ Installation in 3 Schritten

### Schritt 1: Datenbank-Migration ausführen ✅

**Browser-Migration (empfohlen):**

1. Öffne: https://app.mehr-infos-jetzt.de/database/migrations/browser/migrate-mailgun-consent-type.html
2. Klicke auf **"Migration jetzt ausführen"**
3. Warte auf ✅ **"Migration erfolgreich!"**

**Oder per SQL:**

```bash
cd database/migrations/
mysql -u root -p ki_leadsystem < 2025-11-22_add_mailgun_consent_type.sql
```

---

### Schritt 2: Testen als Kunde 🧪

1. **Login als Testkunde:**
   ```
   E-Mail: mailtest2_michael.gluska@gmail.com
   ```

2. **Öffne Empfehlungsprogramm:**
   ```
   https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm
   ```

3. **Erwartetes Verhalten:**
   - ⚠️ **Transparenz-Banner** wird angezeigt
   - 🔒 **Toggle ist gesperrt**
   - ✅ **"Ich verstehe und stimme zu" Button** sichtbar

---

### Schritt 3: Zustimmung testen ✅

1. **Klicke auf:** "Ich verstehe und stimme zu"
2. **Modal öffnet sich** mit Zustimmungspunkten
3. **Checkbox aktivieren**
4. **Klicke:** "Zustimmung speichern"
5. **Seite lädt neu** → Toggle ist jetzt nutzbar!

---

## 🎯 Was passiert jetzt?

### Für NEUE Kunden (ohne Zustimmung):

```
Empfehlungsprogramm öffnen
    ↓
🔒 Toggle GESPERRT
⚠️ Transparenz-Banner wird angezeigt
    ↓
"Ich verstehe und stimme zu" klicken
    ↓
📋 Modal mit Details öffnet sich
    ↓
☑️ Checkbox aktivieren → Speichern
    ↓
✅ Zustimmung in DB gespeichert
    ↓
🔄 Seite lädt neu
    ↓
✅ Toggle AKTIV → Programm nutzbar!
```

### Für Kunden MIT Zustimmung:

```
Empfehlungsprogramm öffnen
    ↓
✅ Toggle SOFORT nutzbar
✅ Keine Banner
✅ Freebies & Belohnungen verfügbar
```

---

## 📊 Belohnungsstufen-Sperre

**Ohne aktiviertes Programm:**

1. Öffne: `?page=belohnungsstufen`
2. **Sperrbildschirm** wird angezeigt mit:
   - 🔒 Lock-Icon
   - 📝 4-Schritt-Anleitung
   - ✅ "Jetzt Empfehlungsprogramm aktivieren" Button

**Nach Aktivierung:**

1. Öffne: `?page=belohnungsstufen`
2. ✅ **Normale Ansicht** zum Erstellen von Belohnungen

---

## 🗄️ Datenbank-Check

### Prüfe ob Migration erfolgreich:

```sql
-- Prüfe ENUM-Werte
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'av_contract_acceptances' 
AND COLUMN_NAME = 'acceptance_type';
```

**Erwartete Ausgabe:**
```
enum('registration','update','renewal','mailgun_consent')
```

### Prüfe Zustimmungen:

```sql
-- Alle Mailgun-Zustimmungen anzeigen
SELECT 
    u.email,
    u.company_name,
    a.accepted_at,
    a.acceptance_type
FROM av_contract_acceptances a
JOIN users u ON a.user_id = u.id
WHERE a.acceptance_type = 'mailgun_consent'
ORDER BY a.accepted_at DESC;
```

---

## ✅ Checkliste

- [ ] **Migration ausgeführt** (Browser oder SQL)
- [ ] **ENUM enthält `mailgun_consent`** (DB-Check)
- [ ] **Als Testkunde einloggen**
- [ ] **Empfehlungsprogramm öffnen**
- [ ] **Transparenz-Banner sichtbar**
- [ ] **Modal funktioniert**
- [ ] **Zustimmung speichern klappt**
- [ ] **Toggle wird aktivierbar**
- [ ] **Belohnungsstufen-Sperre funktioniert**

---

## 🐛 Häufige Probleme

### Problem: "Column 'acceptance_type' cannot be null"

**Ursache:** ENUM wurde noch nicht erweitert

**Lösung:**
```sql
ALTER TABLE av_contract_acceptances 
MODIFY COLUMN acceptance_type 
ENUM('registration','update','renewal','mailgun_consent');
```

### Problem: Toggle bleibt gesperrt

**Prüfe Zustimmung:**
```sql
SELECT * FROM av_contract_acceptances 
WHERE user_id = [DEINE_USER_ID] 
AND acceptance_type = 'mailgun_consent';
```

**Wenn leer:** Zustimmung manuell hinzufügen:
```sql
INSERT INTO av_contract_acceptances (
    user_id, 
    acceptance_type, 
    av_contract_version, 
    ip_address, 
    user_agent
) VALUES (
    [DEINE_USER_ID], 
    'mailgun_consent', 
    'Mailgun_AVV_2025_v1', 
    '127.0.0.1', 
    'Manual Insert'
);
```

### Problem: Modal öffnet nicht

**Prüfe JavaScript-Konsole:**
- F12 → Console
- Fehler sichtbar?

**Prüfe API:**
```bash
curl -X POST https://app.mehr-infos-jetzt.de/api/mailgun/consent.php \
  -H "Content-Type: application/json" \
  -d '{"consent_given": true}'
```

---

## 📁 Wichtige Dateien

```
customer/sections/
├── empfehlungsprogramm.php          # Hauptseite
├── belohnungsstufen.php              # Belohnungen
└── belohnungsstufen-lock-check.php  # Sperre

api/mailgun/
└── consent.php                       # Zustimmung speichern

database/migrations/
├── 2025-11-22_add_mailgun_consent_type.sql
└── browser/
    ├── migrate-mailgun-consent-type.html
    └── execute-mailgun-consent-migration.php
```

---

## 📞 Support

Bei Problemen:
1. Prüfe Error-Log: `/var/log/php_errors.log`
2. Prüfe Browser-Konsole (F12)
3. Prüfe DB-Verbindung

---

**Fertig! 🎉**

Das AVV Mailgun Consent System ist jetzt einsatzbereit!

---

**Version:** 1.0  
**Datum:** 22. November 2025  
**Autor:** Michael Gluska

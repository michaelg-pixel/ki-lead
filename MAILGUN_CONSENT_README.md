# Mailgun-Transparenz & AVV-Zustimmung - Empfehlungsprogramm

## 🎯 Übersicht

Das Empfehlungsprogramm wurde komplett überarbeitet, um **rechtliche Transparenz ZUERST** zu gewährleisten.

**Alte API-Integration (Quentn, ActiveCampaign, etc.) wurde entfernt** - Mailgun versendet jetzt direkt die Belohnungs-Mails.

---

## ✅ Was wurde implementiert?

### 1. **Überarbeitete Empfehlungsprogramm-Seite**
   - **Datei:** `customer/sections/empfehlungsprogramm.php`
   - **URL:** https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm
   
   **Neue Struktur:**
   - ❌ Toggle deaktiviert bis Mailgun-Zustimmung erfolgt
   - 📢 Transparenz-Banner prominent platziert (Mailgun EU-Server, Datenschutz)
   - ✅ Zustimmungs-Modal für Mailgun + AVV
   - 📊 Nach Zustimmung: normale Ansicht (Statistiken, Freebies)

### 2. **API-Endpoint für Mailgun-Zustimmung**
   - **Datei:** `api/mailgun/consent.php`
   
   **Funktionen:**
   - Speichert Zustimmung in bestehender Tabelle `av_contract_acceptances`
   - `acceptance_type = 'mailgun_consent'`
   - `av_contract_version = 'Mailgun_AVV_2025_v1'`
   - Erfasst IP-Adresse, User-Agent, Timestamp
   - Duplikat-Prüfung
   - Admin-Logging

### 3. **Erweiterte Admin-Übersicht**
   - **Datei:** `admin/av-contract-acceptances.php`
   - **URL:** https://app.mehr-infos-jetzt.de/admin/av-contract-acceptances.php
   
   **Erweiterungen:**
   - Neuer Filter: "Mailgun + AVV"
   - Statistik-Karte für Mailgun-Zustimmungen
   - Badge-Styling: Rosa Badge für `mailgun_consent`

---

## 🗄️ Datenbank-Struktur

**Bestehende Tabelle wird genutzt:** `av_contract_acceptances`

```sql
-- Beispiel-Eintrag für Mailgun-Zustimmung
INSERT INTO av_contract_acceptances (
    user_id,
    accepted_at,
    ip_address,
    user_agent,
    av_contract_version,
    acceptance_type,
    created_at
) VALUES (
    123,                           -- customer_id
    NOW(),
    '87.123.45.67',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)...',
    'Mailgun_AVV_2025_v1',
    'mailgun_consent',             -- NEUER TYPE
    NOW()
);
```

**Keine Datenbank-Migration erforderlich** - die Tabelle existiert bereits!

---

## 🧪 Testing-Anleitung

### **Test 1: Empfehlungsprogramm-Seite aufrufen**

1. Als Kunde einloggen:
   - URL: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm

2. **Erwartetes Ergebnis (wenn noch keine Zustimmung):**
   - Toggle ist **deaktiviert** (grau, nicht klickbar)
   - Großer **gelber Transparenz-Banner** wird angezeigt
   - Button: "Ich verstehe und stimme zu"

3. **Erwartetes Ergebnis (wenn Zustimmung bereits erfolgt):**
   - Toggle ist **aktivierbar**
   - Normale Ansicht: Statistiken, Freebies, Links

---

### **Test 2: Mailgun-Zustimmung geben**

1. Auf Empfehlungsprogramm-Seite:
   - Klicke auf **"Ich verstehe und stimme zu"** Button

2. **Modal öffnet sich:**
   - Titel: "Zustimmung erforderlich"
   - 2 Sections: "Mailgun E-Mail-Versand" + "AVV"
   - Checkbox: "Ja, ich stimme zu"

3. **Zustimmung geben:**
   - Klicke auf Checkbox
   - Button "Zustimmung speichern" wird aktiviert (grün)
   - Klicke auf "Zustimmung speichern"

4. **Erwartetes Ergebnis:**
   - ✅ Notification: "Zustimmung gespeichert! Seite wird neu geladen..."
   - Seite lädt neu
   - Toggle ist jetzt **aktivierbar**
   - Banner ist **verschwunden**

---

### **Test 3: Datenbank prüfen**

```sql
-- Mailgun-Zustimmungen anzeigen
SELECT 
    u.name,
    u.email,
    a.accepted_at,
    a.ip_address,
    a.av_contract_version,
    a.acceptance_type
FROM av_contract_acceptances a
JOIN users u ON a.user_id = u.id
WHERE a.acceptance_type = 'mailgun_consent'
ORDER BY a.accepted_at DESC;
```

**Erwartete Ausgabe:**
```
| name         | email             | accepted_at          | ip_address    | av_contract_version   | acceptance_type   |
|--------------|-------------------|----------------------|---------------|-----------------------|-------------------|
| Max Kunde    | max@example.com   | 2025-01-22 14:30:15  | 87.123.45.67  | Mailgun_AVV_2025_v1   | mailgun_consent   |
```

---

### **Test 4: Admin-Übersicht prüfen**

1. Als Admin einloggen:
   - URL: https://app.mehr-infos-jetzt.de/admin/av-contract-acceptances.php

2. **Erwartetes Ergebnis:**
   - Neue Statistik-Karte: **"Mailgun + AVV"** mit Anzahl
   - Filter-Dropdown: Option **"Mailgun + AVV"** vorhanden
   - Tabelle zeigt Mailgun-Zustimmungen mit **rosa Badge**

3. **Filter testen:**
   - Wähle Filter: "Mailgun + AVV"
   - Nur Mailgun-Zustimmungen werden angezeigt

---

### **Test 5: Duplikat-Prüfung**

1. Als Kunde einloggen (der bereits zugestimmt hat)
2. Rufe Empfehlungsprogramm-Seite auf
3. **Erwartetes Ergebnis:**
   - Banner wird **NICHT angezeigt** (da Zustimmung vorhanden)
   - Toggle ist aktivierbar

---

## 🛠️ Fehlersuche

### Problem: Toggle bleibt deaktiviert nach Zustimmung

**Ursache:** Zustimmung wurde nicht korrekt gespeichert

**Lösung:**
```sql
-- Manuell Zustimmung prüfen
SELECT * FROM av_contract_acceptances 
WHERE user_id = <CUSTOMER_ID> AND acceptance_type = 'mailgun_consent';

-- Falls leer: API-Endpoint prüfen oder manuell einfügen
INSERT INTO av_contract_acceptances (
    user_id, accepted_at, ip_address, user_agent, 
    av_contract_version, acceptance_type, created_at
) VALUES (
    <CUSTOMER_ID>, NOW(), '127.0.0.1', 'Manual Insert', 
    'Mailgun_AVV_2025_v1', 'mailgun_consent', NOW()
);
```

---

### Problem: Modal öffnet nicht

**Ursache:** JavaScript-Fehler

**Lösung:**
1. Browser-Console öffnen (F12)
2. Fehler prüfen
3. Seite neu laden mit STRG+SHIFT+R (Hard Reload)

---

### Problem: API gibt Fehler zurück

**Ursache:** Session oder Berechtigungen

**Prüfen:**
```bash
# PHP Error Log
tail -f /path/to/error.log

# Suche nach:
# "❌ MAILGUN CONSENT ERROR"
```

**Häufige Fehler:**
- `Nicht autorisiert` → Session abgelaufen
- `Datenbankfehler` → Tabelle av_contract_acceptances fehlt
- `Zustimmung wurde nicht erteilt` → Request-Body falsch

---

## 📋 Checkliste Abhaken

- [ ] **Test 1:** Empfehlungsprogramm-Seite zeigt Banner (ohne Zustimmung)
- [ ] **Test 2:** Zustimmungs-Modal funktioniert
- [ ] **Test 3:** Zustimmung wird in Datenbank gespeichert
- [ ] **Test 4:** Admin-Übersicht zeigt Mailgun-Zustimmungen
- [ ] **Test 5:** Duplikat-Prüfung verhindert doppelte Einträge
- [ ] **Test 6:** Toggle wird aktivierbar nach Zustimmung

---

## 🚀 Deployment-Status

| Datei | Status | Commit |
|-------|--------|--------|
| `customer/sections/empfehlungsprogramm.php` | ✅ Deployed | `21bc7ab` |
| `api/mailgun/consent.php` | ✅ Deployed | `208e870` |
| `admin/av-contract-acceptances.php` | ✅ Deployed | `0e7664a` |

---

## 🔐 Rechtliche Compliance

### DSGVO-Konformität:
✅ **Art. 28 DSGVO** - Auftragsverarbeitungsvertrag (AVV) mit Mailgun
✅ **Transparenz** - Vollständige Information über Datenverarbeitung
✅ **Einwilligung** - Explizite Zustimmung vor Nutzung
✅ **Nachweispflicht** - Tracking in `av_contract_acceptances` Tabelle
✅ **EU-Server** - Mailgun nutzt europäische Server

### Tracking-Informationen:
- **IP-Adresse** - Für Audit-Zwecke
- **User-Agent** - Browser/Geräte-Info
- **Timestamp** - Exakter Zeitpunkt der Zustimmung
- **Version** - `Mailgun_AVV_2025_v1`

---

## 📞 Support

Bei Problemen oder Fragen:
- **Admin-Log:** `/var/log/php_errors.log`
- **Datenbank:** `av_contract_acceptances` Tabelle prüfen
- **Browser-Console:** F12 → Console für JavaScript-Fehler

---

## 🎉 Fertig!

Das System ist jetzt bereit für den produktiven Einsatz. Alle rechtlichen Anforderungen sind erfüllt, und Kunden können transparent über die Mailgun-Nutzung informiert werden.
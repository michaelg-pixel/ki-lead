# AVV Mailgun Consent - Empfehlungsprogramm

## 📋 Übersicht

Dieses System implementiert eine **DSGVO-konforme Zustimmung** für die Nutzung von Mailgun im Empfehlungsprogramm. Kunden müssen erst die Nutzungsbedingungen akzeptieren, bevor sie das Empfehlungsprogramm aktivieren können.

## 🎯 User Flow

### Für NEUE Kunden (ohne AVV-Zustimmung):

1. **Empfehlungsprogramm-Seite öffnen** (`?page=empfehlungsprogramm`)
   - ❌ Keine AVV-Zustimmung vorhanden
   - ✅ Transparenz-Banner wird angezeigt

2. **Transparenz-Informationen lesen**
   - 📧 Mailgun E-Mail-Versand (EU-Server, DSGVO)
   - 🗄️ Welche Daten werden verarbeitet
   - 📜 Auftragsverarbeitungsvertrag (AVV)

3. **"Ich verstehe und stimme zu" klicken**
   - ✅ Modal öffnet sich
   - ✅ Detaillierte Zustimmungspunkte

4. **Im Modal zustimmen**
   - ✅ Checkbox aktivieren
   - ✅ "Zustimmung speichern" Button wird aktiv
   - ✅ Zustimmung wird in DB gespeichert (`av_contract_acceptances`)

5. **Toggle wird aktivierbar**
   - ✅ Seite lädt neu
   - ✅ Toggle ist jetzt nutzbar
   - ✅ Kunde kann Empfehlungsprogramm aktivieren

6. **Freebies & Belohnungen nutzen**
   - ✅ Empfehlungslinks generieren
   - ✅ Belohnungsstufen erstellen

### Für Kunden MIT AVV-Zustimmung:

1. **Empfehlungsprogramm-Seite öffnen**
   - ✅ Zustimmung bereits vorhanden
   - ✅ Transparenz-Banner wird NICHT angezeigt
   - ✅ Toggle ist sofort nutzbar
   - ✅ Freebies & Statistiken werden angezeigt

## 🗄️ Datenbankstruktur

### Tabelle: `av_contract_acceptances`

```sql
CREATE TABLE IF NOT EXISTS `av_contract_acceptances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `accepted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `av_contract_version` varchar(20) DEFAULT '1.0',
  `acceptance_type` ENUM('registration','update','renewal','mailgun_consent') NOT NULL DEFAULT 'registration',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_av_acceptance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### ENUM-Werte für `acceptance_type`:

- `registration` - Zustimmung bei Registrierung
- `update` - Aktualisierung der Zustimmung
- `renewal` - Erneuerung der Zustimmung
- **`mailgun_consent`** - **Spezifische Mailgun-Zustimmung für Empfehlungsprogramm**

## 📁 Dateien

### Frontend

- **`customer/sections/empfehlungsprogramm.php`**
  - Hauptseite mit Transparenz-Banner
  - Modal für Zustimmung
  - Toggle für Empfehlungsprogramm-Aktivierung
  - Freebies-Liste & Statistiken

- **`customer/sections/belohnungsstufen.php`**
  - Belohnungsstufen-Verwaltung
  - Sperrbildschirm wenn Programm nicht aktiviert
  - 4-Schritt-Anleitung zur Aktivierung

- **`customer/sections/belohnungsstufen-lock-check.php`**
  - Prüft ob Empfehlungsprogramm aktiviert ist
  - Zeigt Sperrbildschirm wenn nicht aktiviert
  - Enthält komplette 4-Schritt-Anleitung

### Backend / API

- **`api/mailgun/consent.php`**
  - Speichert Zustimmung in Datenbank
  - Prüft ob Zustimmung bereits existiert
  - Logged IP-Adresse & User-Agent (DSGVO-konform)
  - Speichert `acceptance_type = 'mailgun_consent'`

### Migration

- **`database/migrations/2025-11-22_add_mailgun_consent_type.sql`**
  - SQL-Migration für ENUM-Erweiterung
  
- **`database/migrations/browser/migrate-mailgun-consent-type.html`**
  - Browser-basierte Migration (Frontend)
  
- **`database/migrations/browser/execute-mailgun-consent-migration.php`**
  - PHP-Backend für Migration
  - Prüft aktuelle ENUM-Werte
  - Erweitert ENUM um `mailgun_consent`
  - Verifiziert erfolgreiche Migration

## 🚀 Installation

### Schritt 1: Datenbank-Migration ausführen

**Option A: Direkt per SQL**

```bash
cd database/migrations/
mysql -u [user] -p [database] < 2025-11-22_add_mailgun_consent_type.sql
```

**Option B: Browser-Migration**

1. Öffne: `https://app.mehr-infos-jetzt.de/database/migrations/browser/migrate-mailgun-consent-type.html`
2. Klicke auf "Migration jetzt ausführen"
3. Warte auf Erfolgsbestätigung

### Schritt 2: Dateien sind bereits deployed

Alle Dateien wurden via GitHub committed und werden automatisch deployed:
- ✅ `customer/sections/empfehlungsprogramm.php`
- ✅ `api/mailgun/consent.php`
- ✅ `customer/sections/belohnungsstufen-lock-check.php`

### Schritt 3: Testen

1. Öffne als Testkunde: `https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm`
2. Prüfe ob Transparenz-Banner angezeigt wird
3. Klicke auf "Ich verstehe und stimme zu"
4. Akzeptiere im Modal
5. Prüfe ob Toggle aktivierbar wird
6. Teste Belohnungsstufen-Sperre: `?page=belohnungsstufen`

## 🔒 DSGVO-Konformität

### Was wird gespeichert?

- ✅ User-ID (Foreign Key zu `users` Tabelle)
- ✅ Zeitstempel der Zustimmung
- ✅ IP-Adresse (Nachweis der Zustimmung)
- ✅ User-Agent (Browser-Information)
- ✅ AVV-Version: `Mailgun_AVV_2025_v1`
- ✅ Typ: `mailgun_consent`

### Rechtliche Basis

- **Art. 28 DSGVO** - Auftragsverarbeitungsvertrag
- **Transparenzpflicht** - Vollständige Information über Datenverarbeitung
- **Einwilligung** - Freiwillige, informierte Zustimmung
- **EU-Server** - Datenverarbeitung bleibt in Europa

## 🔄 Workflow-Logik

```
Kunde öffnet Empfehlungsprogramm
    ↓
    └─→ Prüfe: mailgun_consent in av_contract_acceptances?
         ├─→ JA: Zeige normales Interface
         │        └─→ Toggle nutzbar
         │             └─→ Freebies & Belohnungen verfügbar
         │
         └─→ NEIN: Zeige Transparenz-Banner
                   └─→ "Ich verstehe und stimme zu" Button
                        └─→ Modal öffnet sich
                             └─→ Checkbox aktivieren
                                  └─→ Zustimmung speichern
                                       └─→ Reload → Interface verfügbar
```

## ⚡ Features

### Transparenz-Banner

- 🛡️ Shield-Icon & ansprechendes Design
- 📧 Mailgun-Info (EU-Server, DSGVO)
- 🗄️ Datenverarbeitungs-Details
- 📜 AVV-Informationen
- ✅ Call-to-Action Button

### Consent Modal

- 📋 Detaillierte Zustimmungspunkte
- ☑️ Interaktive Checkbox
- 🚫 Bestätigung erst aktiv nach Checkbox
- ✅ Speichern mit Loading-State
- 🔄 Auto-Reload nach Erfolg

### Belohnungsstufen-Sperre

- 🔒 Sperrbildschirm wenn nicht aktiviert
- 📝 4-Schritt-Anleitung
- 🎯 Direkter Link zur Aktivierung
- 🎁 Feature-Box mit Vorteilen

## 🐛 Troubleshooting

### Problem: ENUM enthält kein `mailgun_consent`

**Lösung:** Migration ausführen:
```sql
ALTER TABLE `av_contract_acceptances` 
MODIFY COLUMN `acceptance_type` 
ENUM('registration','update','renewal','mailgun_consent') 
NOT NULL DEFAULT 'registration';
```

### Problem: Toggle bleibt gesperrt

**Prüfung:**
```sql
SELECT * FROM av_contract_acceptances 
WHERE user_id = [USER_ID] 
AND acceptance_type = 'mailgun_consent';
```

Wenn leer → Kunde muss Zustimmung geben

### Problem: Modal öffnet nicht

**Prüfe:**
- JavaScript-Konsole auf Fehler
- API-Endpoint `/api/mailgun/consent.php` erreichbar?
- Session aktiv?

## 📊 Monitoring

### Zustimmungen prüfen

```sql
-- Alle Mailgun-Zustimmungen
SELECT 
    u.email,
    u.company_name,
    a.accepted_at,
    a.ip_address
FROM av_contract_acceptances a
JOIN users u ON a.user_id = u.id
WHERE a.acceptance_type = 'mailgun_consent'
ORDER BY a.accepted_at DESC;
```

### Statistiken

```sql
-- Anzahl Zustimmungen pro Typ
SELECT 
    acceptance_type,
    COUNT(*) as total,
    DATE(accepted_at) as date
FROM av_contract_acceptances
GROUP BY acceptance_type, DATE(accepted_at)
ORDER BY date DESC;
```

## 🎨 Design-System

### Farben

- **Transparenz-Banner:** `#fbbf24` → `#f59e0b` (Gold-Gradient)
- **Modal Header:** `#667eea` → `#764ba2` (Lila-Gradient)
- **Success Button:** `#10b981` → `#059669` (Grün-Gradient)
- **Warning:** `#ef4444` (Rot)
- **Background:** `#1f2937` → `#111827` (Dark)

### Icons (Font Awesome 6.4.0)

- 🛡️ `fa-shield-alt` - Sicherheit
- 📧 `fa-envelope` - E-Mail
- 🗄️ `fa-database` - Datenbank
- 📜 `fa-file-contract` - AVV
- ✅ `fa-check-circle` - Bestätigung
- 🔒 `fa-lock` - Sperre

## 👨‍💻 Entwickler-Notizen

- **Session-Handling:** Nutzt `config/security.php` für sichere Sessions
- **CSRF-Schutz:** Nicht implementiert (TODO für Production)
- **Rate-Limiting:** Nicht implementiert auf `/api/mailgun/consent.php`
- **Audit-Log:** Automatisches Logging via `error_log()`

## 📝 Changelog

### 2025-11-22
- ✅ Initiale Implementierung
- ✅ ENUM-Erweiterung um `mailgun_consent`
- ✅ Transparenz-Banner Design
- ✅ Modal-System
- ✅ API-Endpoint
- ✅ Belohnungsstufen-Sperre
- ✅ Dokumentation

---

**Autor:** Michael Gluska  
**Projekt:** KI Leadsystem - Empfehlungsprogramm  
**Version:** 1.0  
**Datum:** 22. November 2025

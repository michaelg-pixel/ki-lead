# AV-Vertrags-Zustimmungen Tracking System

## 📋 Übersicht

DSGVO-konformes System zur Speicherung und Nachweispflicht von AV-Vertrags-Zustimmungen (Auftragsverarbeitungsvertrag) bei der Benutzerregistrierung.

## 🎯 Zweck

Gemäß Art. 28 DSGVO muss bei der Auftragsverarbeitung ein schriftlicher Vertrag geschlossen werden. Dieses System dokumentiert:
- **Wann** der Kunde zugestimmt hat (Zeitstempel)
- **Wo** die Zustimmung erfolgte (IP-Adresse)
- **Womit** die Zustimmung erfolgte (User-Agent/Browser)
- **Welche Version** des AV-Vertrags akzeptiert wurde

## 📁 Dateien

### Migrations
- `migrations/create_av_contract_acceptances.sql` - SQL-Schema für Datenbanktabelle
- `migrations/migrate_av_contract_acceptances.php` - PHP-Script zur Migration

### Code
- `includes/av_contract_helpers.php` - Helper-Funktionen für AV-Zustimmungen
- `public/register.php` - Registrierungsseite (erweitert)

## 🚀 Installation

### 1. Migration ausführen

```bash
php migrations/migrate_av_contract_acceptances.php
```

Oder manuell via phpMyAdmin:
```sql
source migrations/create_av_contract_acceptances.sql
```

### 2. Funktionsweise

Nach erfolgreicher Registrierung wird automatisch ein Eintrag erstellt mit:

```php
saveAvContractAcceptance($pdo, $user_id, 'registration', '1.0');
```

## 📊 Datenbankstruktur

```sql
CREATE TABLE av_contract_acceptances (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  accepted_at DATETIME NOT NULL,
  ip_address VARCHAR(45) NOT NULL,      -- IPv4/IPv6
  user_agent TEXT NOT NULL,              -- Browser-Info
  av_contract_version VARCHAR(20),       -- z.B. '1.0'
  acceptance_type ENUM(...),             -- registration/update/renewal
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🔍 Verwendung

### Zustimmung speichern

```php
require_once '../includes/av_contract_helpers.php';

// Bei Registrierung
$success = saveAvContractAcceptance($pdo, $user_id, 'registration', '1.0');

// Bei Update
$success = saveAvContractAcceptance($pdo, $user_id, 'update', '2.0');
```

### Zustimmungen abrufen

```php
// Letzte Zustimmung
$acceptance = getLatestAvContractAcceptance($pdo, $user_id);

// Alle Zustimmungen (Historie)
$all_acceptances = getAllAvContractAcceptances($pdo, $user_id);

// Prüfen ob Zustimmung existiert
$has_accepted = hasAvContractAcceptance($pdo, $user_id);
```

### Anzeige formatieren

```php
$acceptance = getLatestAvContractAcceptance($pdo, $user_id);
echo formatAvAcceptanceDisplay($acceptance);
```

## 🔒 Sicherheit

### IP-Adresse Ermittlung
Die Funktion `getClientIpAddress()` berücksichtigt:
- CloudFlare (`HTTP_CF_CONNECTING_IP`)
- Nginx Proxy (`HTTP_X_REAL_IP`)
- Standard Proxy (`HTTP_X_FORWARDED_FOR`)
- Direkte Verbindung (`REMOTE_ADDR`)

### User-Agent
- Begrenzt auf 1000 Zeichen
- Sanitized gespeichert
- Vollständige Browser-Info für Nachweiszwecke

### Transaktionen
Die Speicherung erfolgt innerhalb einer Datenbank-Transaktion:
```php
$pdo->beginTransaction();
// ... User erstellen ...
// ... AV-Zustimmung speichern ...
$pdo->commit();
```

Bei Fehler wird alles zurückgerollt (Rollback).

## 📈 DSGVO-Konformität

✅ **Zweckbindung**: Daten nur für Nachweis der AV-Vertrags-Zustimmung
✅ **Datensparsamkeit**: Nur notwendige Daten (IP, Zeit, User-Agent)
✅ **Integrität**: Timestamps unveränderbar
✅ **Transparenz**: Kunde sieht eigene Zustimmung im Dashboard
✅ **Löschung**: CASCADE bei User-Löschung

## 🛠 Wartung

### Alte Einträge löschen (nach Löschung des Users)
```sql
-- Automatisch durch CASCADE Foreign Key
DELETE FROM users WHERE id = 123;
-- Löscht auch av_contract_acceptances Einträge
```

### Statistiken
```sql
-- Anzahl Zustimmungen pro Typ
SELECT acceptance_type, COUNT(*) as count 
FROM av_contract_acceptances 
GROUP BY acceptance_type;

-- Zustimmungen der letzten 30 Tage
SELECT COUNT(*) 
FROM av_contract_acceptances 
WHERE accepted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 📝 Rechtliche Hinweise

- IP-Adressen sind personenbezogene Daten (Art. 4 Nr. 1 DSGVO)
- Speicherung nur für Nachweispflicht gem. Art. 28 DSGVO
- Aufbewahrungsfrist: Dauer des Vertragsverhältnisses + Verjährungsfristen
- Informationspflicht: In Datenschutzerklärung aufnehmen

## 🔄 Versionshistorie

### Version 1.0 (2025-01-20)
- Initiale Implementation
- Speicherung bei Registrierung
- IP-Adresse, User-Agent, Timestamp
- Helper-Funktionen
- DSGVO-konforme Struktur

## 💡 Erweiterungsmöglichkeiten

1. **Dashboard-Ansicht**: Kunde sieht eigene Zustimmungen
2. **Admin-Interface**: Übersicht aller Zustimmungen
3. **Export-Funktion**: CSV/PDF-Export für Audits
4. **E-Mail-Bestätigung**: Optional E-Mail mit Zustimmungsdetails
5. **Checksum**: Hash über Zustimmungsdaten zur Integritätsprüfung

## 📞 Support

Bei Fragen oder Problemen:
- GitHub Issues: [Repository URL]
- E-Mail: support@mehr-infos-jetzt.de

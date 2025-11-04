# 📄 AV-Vertrag System - Dokumentation

## Übersicht

Das AV-Vertrag System ermöglicht es Kunden, ihre Firmendaten zu hinterlegen und einen personalisierten Auftragsverarbeitungsvertrag (AV-Vertrag) gemäß Art. 28 DSGVO herunterzuladen.

## ✨ Features

- ✅ Firmendaten-Formular im Customer Dashboard
- ✅ Sichere Speicherung in der Datenbank
- ✅ Personalisierter AV-Vertrag zum Download
- ✅ Drucken / PDF-Export Funktion
- ✅ Vollständig responsive (Mobile, Tablet, Desktop)
- ✅ Dashboard-konformes Design
- ✅ API-basierte Datenverwaltung
- ✅ Einfache Browser-Installation

## 📦 Installierte Komponenten

### 1. Datenbank

**Datei:** `database/migrations/2025-11-04_av_vertrag_company_data.sql`

**Tabelle:** `user_company_data`

Felder:
- `id` - Primary Key
- `user_id` - Foreign Key zu users-Tabelle
- `company_name` - Firmenname
- `company_address` - Straße und Hausnummer
- `company_zip` - Postleitzahl
- `company_city` - Stadt
- `company_country` - Land (Standard: Deutschland)
- `contact_person` - Ansprechpartner (optional)
- `contact_email` - Kontakt-E-Mail (optional)
- `contact_phone` - Telefonnummer (optional)
- `created_at` - Erstellungsdatum
- `updated_at` - Aktualisierungsdatum

### 2. Backend APIs

#### a) Daten speichern
**Datei:** `customer/api/save-company-data.php`
- **Methode:** POST
- **Authentifizierung:** Session (customer role)
- **Funktion:** Speichert oder aktualisiert Firmendaten
- **Response:** JSON mit success/error

#### b) Daten abrufen
**Datei:** `customer/api/get-company-data.php`
- **Methode:** GET
- **Authentifizierung:** Session (customer role)
- **Funktion:** Lädt gespeicherte Firmendaten
- **Response:** JSON mit Firmendaten

### 3. Frontend

#### a) Einstellungsseite
**Datei:** `customer/sections/einstellungen.php`
- Erweitert um AV-Vertrag Sektion
- Formular für Firmendaten
- AJAX-basiertes Speichern
- Download-Button für AV-Vertrag

#### b) AV-Vertrag Download
**Datei:** `customer/av-vertrag-download.php`
- Personalisierter AV-Vertrag mit Firmendaten
- Drucken/PDF-Export Funktion
- Professionelles Layout
- Alle relevanten DSGVO-Informationen

### 4. Installation

**Datei:** `install-av-vertrag.php`
- Browser-basierte Installation
- System-Checks vor Installation
- Sichere Tabellenerstellung
- Keine Passwort-Eingabe erforderlich

## 🚀 Installation

### Schritt 1: Installation ausführen

1. Öffne im Browser: `https://app.mehr-infos-jetzt.de/install-av-vertrag.php`
2. Klicke auf "Installation starten"
3. Warte auf die Bestätigung
4. **Wichtig:** Lösche die Datei nach erfolgreicher Installation!

### Schritt 2: Testen

1. Melde dich als Customer an
2. Gehe zu "Einstellungen"
3. Scrolle zur "AV-Vertrag" Sektion
4. Fülle das Formular aus
5. Klicke auf "Firmendaten speichern"
6. Klicke auf "AV-Vertrag herunterladen"

## 📱 Nutzung

### Für Kunden (Customer Dashboard)

1. **Firmendaten hinterlegen:**
   - Dashboard → Einstellungen
   - Scrolle zu "Auftragsverarbeitungsvertrag (AV-Vertrag)"
   - Fülle alle Pflichtfelder aus:
     - Firmenname *
     - Straße und Hausnummer *
     - PLZ *
     - Stadt *
   - Optionale Felder:
     - Land (Standard: Deutschland)
     - Ansprechpartner
     - Kontakt-E-Mail
     - Telefon
   - Klicke "Firmendaten speichern"

2. **AV-Vertrag herunterladen:**
   - Nach dem Speichern erscheint der Button "AV-Vertrag herunterladen"
   - Klicke auf den Button
   - Der personalisierte Vertrag öffnet sich in einem neuen Tab
   - Nutze die Drucken-Funktion des Browsers oder "Drucken als PDF"

### Für Admins

- Keine spezielle Admin-Funktion erforderlich
- Alle Kunden können ihre eigenen Daten verwalten
- Daten sind user-spezifisch (via user_id Foreign Key)

## 🔒 Sicherheit

### Implementierte Sicherheitsmaßnahmen:

1. **Session-basierte Authentifizierung**
   - Nur eingeloggte Customer haben Zugriff
   - User ID aus Session für alle Operationen

2. **SQL Injection Schutz**
   - Prepared Statements für alle DB-Queries
   - PDO mit Exception Mode

3. **XSS Schutz**
   - htmlspecialchars() für alle Ausgaben
   - Keine direkte Ausgabe von User-Input

4. **CSRF Schutz**
   - Session-Validierung bei allen API-Calls
   - POST-only für Daten-Änderungen

5. **Foreign Key Constraints**
   - ON DELETE CASCADE verhindert Waisen-Datensätze
   - Referentielle Integrität gewährleistet

6. **Input-Validierung**
   - Required-Fields in HTML und PHP
   - E-Mail-Validierung (filter_var)
   - Length-Limits in DB-Schema

## 🎨 Design

Das AV-Vertrag System folgt dem bestehenden Dashboard-Design:

- **Farbschema:** Violett-Gradient (#667eea - #764ba2)
- **Dark Theme:** Konsistent mit restlichem Dashboard
- **Responsive:** Optimiert für alle Geräte
- **Typography:** System-Schriften für optimale Performance

### Breakpoints:

- Desktop: > 1024px
- Tablet: 768px - 1024px
- Mobile Landscape: 480px - 768px
- Mobile Portrait: < 480px

## 🔧 Technische Details

### Stack:
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** Vanilla JavaScript (keine Dependencies)
- **Styling:** CSS3 mit CSS Grid & Flexbox

### Browser-Kompatibilität:
- Chrome/Edge: 90+
- Firefox: 88+
- Safari: 14+
- Mobile Browsers: iOS 14+, Android 10+

## 📄 Dateistruktur

```
.
├── database/
│   └── migrations/
│       └── 2025-11-04_av_vertrag_company_data.sql
├── customer/
│   ├── api/
│   │   ├── save-company-data.php
│   │   └── get-company-data.php
│   ├── sections/
│   │   └── einstellungen.php (erweitert)
│   └── av-vertrag-download.php
└── install-av-vertrag.php
```

## 🐛 Troubleshooting

### Problem: Tabelle existiert nicht
**Lösung:** Führe `install-av-vertrag.php` aus

### Problem: "Nicht autorisiert" Fehler
**Lösung:** 
- Prüfe ob User eingeloggt ist
- Prüfe Session-Variable 'user_id'
- Prüfe Role = 'customer'

### Problem: Daten werden nicht gespeichert
**Lösung:**
- Prüfe DB-Verbindung in `config/database.php`
- Prüfe ob Tabelle existiert
- Prüfe Browser-Konsole auf JavaScript-Fehler

### Problem: AV-Vertrag zeigt keine Daten
**Lösung:**
- Prüfe ob Firmendaten gespeichert wurden
- Prüfe Foreign Key: user_id muss in users existieren
- Prüfe Browser-Konsole auf PHP-Fehler

### Problem: Download-Button erscheint nicht
**Lösung:**
- Speichere erst die Firmendaten
- Lade die Seite neu (F5)
- Prüfe ob $company_data geladen wird

## 📚 API-Dokumentation

### POST /customer/api/save-company-data.php

**Request:**
```
company_name: string (required)
company_address: string (required)
company_zip: string (required)
company_city: string (required)
company_country: string (optional, default: "Deutschland")
contact_person: string (optional)
contact_email: string (optional, email format)
contact_phone: string (optional)
```

**Response Success:**
```json
{
  "success": true,
  "message": "Firmendaten erfolgreich gespeichert",
  "action": "created" | "updated"
}
```

**Response Error:**
```json
{
  "success": false,
  "message": "Fehler-Beschreibung"
}
```

### GET /customer/api/get-company-data.php

**Response Success:**
```json
{
  "success": true,
  "data": {
    "company_name": "Musterfirma GmbH",
    "company_address": "Musterstraße 123",
    "company_zip": "12345",
    "company_city": "Berlin",
    "company_country": "Deutschland",
    "contact_person": "Max Mustermann",
    "contact_email": "max@example.com",
    "contact_phone": "+49 123 456789",
    "created_at": "2025-11-04 12:00:00",
    "updated_at": "2025-11-04 12:00:00"
  }
}
```

**Response No Data:**
```json
{
  "success": true,
  "data": null,
  "message": "Noch keine Firmendaten hinterlegt"
}
```

## 🎯 Roadmap / Zukünftige Features

- [ ] E-Mail-Versand des AV-Vertrags
- [ ] Versionshistorie der Firmendaten
- [ ] Digitale Signatur
- [ ] Mehrere Ansprechpartner pro Firma
- [ ] Export in verschiedene Formate (DOCX, etc.)
- [ ] Admin-Dashboard für alle Verträge
- [ ] Automatische Erinnerung bei Ablauf

## 📞 Support

Bei Fragen oder Problemen:
1. Prüfe diese Dokumentation
2. Prüfe die Browser-Konsole auf Fehler
3. Prüfe die PHP-Error-Logs
4. Kontaktiere den Entwickler

## ✅ Changelog

### Version 1.0.0 (2025-11-04)
- ✅ Initiale Implementierung
- ✅ Datenbank-Schema erstellt
- ✅ API-Endpunkte implementiert
- ✅ Frontend-Integration
- ✅ Browser-Installation
- ✅ Responsive Design
- ✅ Dokumentation

## 📝 Lizenz

Proprietäres System für KI-Lead-System
© 2025 Henry Landmann

# KI Lead System - Backup-Index
Erstellt am: 03. November 2025, 15:33 Uhr

## Gesicherte Dateien

### ✅ Konfigurationsdateien
- config/database.php - Datenbank-Verbindung und Zugangsdaten
- config/settings.php - System-Einstellungen, URLs, SMTP-Konfiguration
- .htaccess - Server-Routing-Regeln

### ✅ Kern-Dateien
- index.php - Haupt-Einstiegspunkt
- README.md - Projekt-Übersicht und Dokumentation

### ✅ Authentifizierung & Sicherheit
- includes/auth.php - Login, Logout, Session-Management, CSRF-Schutz

### ✅ Datenbank
- database/migrations/004_referral_system.sql - DSGVO-konformes Referral-System

## Wichtige Komponenten im Repository

### 📁 Admin-Bereich (/admin)
- Dashboard, Kursverwaltung, Freebie-Management
- Benutzer- und Tutorial-Verwaltung
- API-Endpunkte für CRUD-Operationen

### 📁 Customer-Bereich (/customer)
- Dashboard, Kurse, Freebies
- Fortschritt-Tracking, Einstellungen
- Empfehlungsprogramm

### 📁 API (/api)
- Referral-Tracking und -Statistiken
- Customer-Management
- Freebie-Generierung

### 📁 Public (/public)
- Login/Logout/Register
- Thankyou-Pages

### 📁 Database (/database)
- Migrationen für alle Systeme
- Setup-Skripte

### 📁 Setup (/setup)
- Installations- und Konfigurations-Skripte
- System-Setup-Tools

## Kritische Systeme

1. **Referral-System**: DSGVO-konform mit IP-Hashing
2. **Kurs-System**: Video-Kurse mit Fortschritt-Tracking
3. **Freebie-Generator**: Lead-Magneten-Erstellung
4. **Tutorial-System**: Video-Tutorials mit Kategorien
5. **Customer-Management**: Benutzerverwaltung und -tracking

## Wiederherstellung

Bei Bedarf können alle Dateien aus diesem GitHub Repository wiederhergestellt werden:

```bash
git clone https://github.com/michaelg-pixel/ki-lead.git
cd ki-lead
```

Die Datenbank-Migrationen ermöglichen ein vollständiges Neuaufsetzen der Struktur.

## Hinweise

- **Datenbank-Credentials** müssen in config/database.php neu eingetragen werden
- **HTTPS** ist Voraussetzung (siehe .htaccess)
- **PHP 8.0+** und **MySQL 8.0+** erforderlich

## Sicherheitshinweis

⚠️ **WICHTIG**: Sensitive Daten wie Datenbank-Passwörter werden NICHT in diesem öffentlichen Repository gespeichert!
Erstelle eine lokale Kopie von config/database.php mit deinen echten Zugangsdaten.

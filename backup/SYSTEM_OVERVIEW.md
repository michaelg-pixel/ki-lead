# KI Lead System - System-Übersicht

## 📊 Gesicherte Hauptkomponenten

### 1. Konfiguration & Kern
- **database.php**: Datenbank-Verbindung mit PDO, MySQL-Zugangsdaten
- **settings.php**: System-Einstellungen, URLs, SMTP, Session-Konfiguration
- **auth.php**: Authentifizierung, Login/Logout, Session-Management, CSRF-Schutz
- **.htaccess**: URL-Routing und öffentliche Bereiche
- **index.php**: Haupt-Einstiegspunkt mit Session-Check

### 2. Datenbank-Migrationen
- **004_referral_system.sql**: Vollständiges DSGVO-konformes Referral-System
  - Tracking mit IP-Hashing
  - Anti-Fraud-Mechanismen
  - Lead-Verwaltung mit Double-Opt-In
  - Belohnungs-System

### 3. Webhooks & Integration
- **webhook/digistore24.php**: Automatische Kunden-Anlage von Digistore24
  - Kurs-Freischaltung
  - Freebie-Limits
  - Willkommens-E-Mails
  - Rückerstattungs-Handling

### 4. Customer Dashboard
- Responsive Design (Mobile-first)
- Seiten: Übersicht, Kurse, Freebies, Fortschritt, Einstellungen, Empfehlungen
- API-Endpunkte für Tracking und Checklisten

### 5. Admin-Panel
- Kurs-Verwaltung (Module, Lektionen, Videos)
- Tutorial-System
- Freebie-Generator
- Benutzer-Verwaltung
- Referral-Monitoring

## 🔐 Sicherheitsfeatures

1. **Session-Management**: Sichere Sessions mit HTTPS-Only Cookies
2. **CSRF-Schutz**: Token-basierte Absicherung
3. **Password-Hashing**: BCrypt für Passwort-Speicherung
4. **DSGVO-Konformität**: IP-Hashing, Einwilligungen mit Zeitstempel
5. **Input-Validation**: PDO Prepared Statements gegen SQL-Injection

## 🗄️ Datenbank-Struktur

### Haupttabellen:
- **users**: Benutzer mit Rollen (admin/customer)
- **courses**: Kurse mit Modulen und Lektionen
- **course_access**: Kurs-Zugriffsrechte
- **customer_freebies**: Freebie-Generierung
- **customer_freebie_limits**: Freebie-Kontingente pro Kunde
- **referral_***: Vollständiges Referral-System (7 Tabellen)
- **tutorials**: Video-Tutorials mit Kategorien

## 🚀 Deployment-Informationen

- **Live-URL**: https://app.mehr-infos-jetzt.de
- **Server**: Hostinger CloudPanel
- **PHP**: 8.0+
- **MySQL**: 8.0+
- **Deployment**: GitHub Actions + SSH

## 📚 Dokumentationen im Backup

1. README.md - Projekt-Übersicht
2. DEPLOYMENT_GUIDE.md - Deployment & Troubleshooting
3. BACKUP_INDEX.md - Backup-Übersicht
4. SYSTEM_OVERVIEW.md - Diese Datei

## 🔄 Wiederherstellung

Bei kompletter Wiederherstellung:
1. Repository klonen: `git clone https://github.com/michaelg-pixel/ki-lead.git`
2. Datenbank-Credentials in config/database.php anpassen
3. URLs in config/settings.php anpassen
4. Datenbank-Migrationen ausführen
5. .htaccess konfigurieren
6. Upload-Verzeichnisse erstellen und Rechte setzen

## 🔑 Wichtige Dateien für Wiederherstellung

**Minimum für Wiederherstellung:**
- config/database.php (mit echten Credentials)
- config/settings.php (mit echten URLs)
- .htaccess
- database/migrations/*.sql (alle Migrationen)
- webhook/digistore24.php

**Diese Dateien enthalten alle kritischen Konfigurationen und ermöglichen einen Neustart des Systems.**

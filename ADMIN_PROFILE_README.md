# 👤 Admin-Profil System - Dokumentation

## 📋 Übersicht

Das Admin-Profil-System bietet umfassende Verwaltungs- und Überwachungsfunktionen für Administratoren. Es besteht aus 4 Hauptbereichen:

1. **Profil bearbeiten** - Persönliche Daten und Profilbild verwalten
2. **Sicherheit & Sessions** - Login-Aktivitäten überwachen
3. **Aktivitätsprotokoll** - Admin-Aktionen nachverfolgen
4. **Präferenzen** - Einstellungen und Benachrichtigungen anpassen

---

## 🚀 Installation

### Schritt 1: Setup-Script ausführen

Rufen Sie folgende URL in Ihrem Browser auf:

```
https://app.mehr-infos-jetzt.de/setup/setup-admin-profile.php
```

Das Script erstellt automatisch:
- ✅ Datenbank-Tabellen (admin_activity_log, login_sessions, admin_preferences)
- ✅ Upload-Verzeichnis für Profilbilder
- ✅ Standard-Einstellungen für alle Admins
- ✅ Beispiel-Aktivitäten zur Demo

### Schritt 2: Profilseite aufrufen

Nach erfolgreichem Setup können Sie die Profilseite aufrufen:

```
https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=profile
```

---

## 🎯 Features im Detail

### 1. 👤 Profil bearbeiten

#### Profilbild hochladen
- Unterstützte Formate: JPG, PNG, GIF, WEBP
- Maximale Größe: 5MB
- Automatische Anzeige im Dashboard und in der Sidebar

#### Profildaten ändern
- Name ändern
- E-Mail-Adresse ändern
- Sofortige Aktualisierung in der Session

#### Statistiken
- Tage als Admin aktiv
- Anzahl durchgeführter Aktionen
- Letzte Aktivität

---

### 2. 🔐 Sicherheit & Sessions

#### Aktive Sessions anzeigen
Zeigt alle aktiven Login-Sessions mit:
- Gerät und Browser
- IP-Adresse
- Standort (wenn verfügbar)
- Letzte Aktivität

#### Session-Management
- **Einzelne Session beenden** - Bestimmte Geräte abmelden
- **Alle anderen Sessions beenden** - Alle außer der aktuellen Session abmelden

#### Letzte Login-Aktivitäten
Übersicht über die letzten 10 Logins mit:
- Datum und Uhrzeit
- Geräte-Informationen
- IP-Adresse und Standort

---

### 3. 📊 Aktivitätsprotokoll

Protokolliert automatisch alle wichtigen Admin-Aktionen:

#### Erfasste Aktionen
- ✅ System-Anmeldungen
- ✅ Profil-Änderungen
- ✅ Passwort-Änderungen
- ✅ Benutzer-Verwaltung (Erstellen, Bearbeiten, Löschen)
- ✅ Kurs-Verwaltung (Erstellen, Bearbeiten, Löschen)
- ✅ Freebie-Verwaltung (Erstellen, Bearbeiten, Löschen)
- ✅ Einstellungs-Änderungen

#### Anzeige
- Letzte 20 Aktivitäten
- Icon und Beschreibung für jede Aktion
- Zeitstempel und IP-Adresse
- Automatische Kategorisierung

---

### 4. ⚙️ Präferenzen & Einstellungen

#### E-Mail-Benachrichtigungen
Toggle-Schalter für:
- ✉️ Neue Benutzer-Registrierungen
- 💳 Kurskäufe über Digistore24
- 📈 Wöchentliche Zusammenfassung

#### Interface-Einstellungen
- 🎨 **Theme**: Dark/Light Mode (Coming Soon)
- 🌍 **Sprache**: Deutsch/Englisch
- 🕐 **Zeitzone**: Anpassbar
- 📅 **Datumsformat**: Anpassbar

---

## 📁 Dateistruktur

```
admin/
├── sections/
│   └── profile.php           # Haupt-Profilseite
├── api/
│   ├── update-profile.php    # API für Profil-Update
│   ├── upload-profile-image.php  # API für Bild-Upload
│   ├── session-management.php    # API für Sessions
│   ├── preferences.php       # API für Präferenzen
│   └── activity-log.php      # API für Aktivitäten

setup/
├── setup-admin-profile.php   # Setup-Script
└── admin-profile-setup.sql   # SQL-Datei

uploads/
└── profile-images/           # Profilbilder
    └── .htaccess
```

---

## 🗄️ Datenbank-Struktur

### Tabelle: `admin_activity_log`
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- action_type (VARCHAR)
- action_description (TEXT)
- ip_address (VARCHAR)
- user_agent (TEXT)
- created_at (TIMESTAMP)
```

### Tabelle: `login_sessions`
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- session_token (VARCHAR)
- ip_address (VARCHAR)
- user_agent (TEXT)
- browser (VARCHAR)
- device (VARCHAR)
- location (VARCHAR)
- last_activity (TIMESTAMP)
- created_at (TIMESTAMP)
- is_active (BOOLEAN)
```

### Tabelle: `admin_preferences`
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, UNIQUE, FOREIGN KEY)
- notifications_new_users (BOOLEAN)
- notifications_course_purchases (BOOLEAN)
- notifications_weekly_summary (BOOLEAN)
- theme (VARCHAR)
- language (VARCHAR)
- timezone (VARCHAR)
- date_format (VARCHAR)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Spalte: `users.profile_image`
```sql
- profile_image (VARCHAR, NULL)
```

---

## 🔧 API-Endpunkte

### Profil aktualisieren
```
POST /admin/api/update-profile.php
Body: { "name": "...", "email": "..." }
```

### Profilbild hochladen
```
POST /admin/api/upload-profile-image.php
Content-Type: multipart/form-data
Body: profile_image (File)
```

### Sessions verwalten
```
GET  /admin/api/session-management.php?action=get_sessions
GET  /admin/api/session-management.php?action=get_last_logins
POST /admin/api/session-management.php?action=terminate_session
POST /admin/api/session-management.php?action=terminate_all_sessions
```

### Präferenzen verwalten
```
GET  /admin/api/preferences.php?action=get
POST /admin/api/preferences.php?action=update
```

### Aktivitäten abrufen
```
GET /admin/api/activity-log.php?limit=20
```

---

## 🔒 Sicherheit

### Zugriffskontrolle
- Alle API-Endpunkte prüfen auf Admin-Session
- `$_SESSION['user_id']` und `$_SESSION['role'] === 'admin'` erforderlich

### Datei-Upload
- Nur Bilddateien erlaubt (JPG, PNG, GIF, WEBP)
- Maximale Größe: 5MB
- Validierung von MIME-Type
- Sichere Dateinamen-Generierung

### Aktivitätsprotokoll
- Automatische IP-Adressen-Erfassung
- User-Agent-Tracking
- Keine persönlichen Passwörter im Log

---

## 📱 Mobile Optimierung

Das Admin-Profil ist vollständig responsive:
- ✅ Angepasste Layouts für Tablets
- ✅ Touch-optimierte Bedienung für Mobile
- ✅ Stapelbare Cards auf kleinen Bildschirmen
- ✅ Horizontales Scrollen bei Überlauf

---

## 🎨 Design-System

### Farben
- **Primary**: `#a855f7` (Violett)
- **Success**: `#4ade80` (Grün)
- **Danger**: `#fb7185` (Rot)
- **Background**: `#0a0a16` (Dunkelblau)

### Komponenten
- Toggle-Switches für Einstellungen
- Loading-Spinner bei API-Aufrufen
- Toast-Benachrichtigungen für Feedback
- Card-basiertes Layout

---

## 🚧 Zukünftige Features

- [ ] 2-Faktor-Authentifizierung
- [ ] Dark/Light Mode Toggle (funktional)
- [ ] Erweiterte Benachrichtigungs-Optionen
- [ ] Export von Aktivitätsprotokollen
- [ ] Geolocation für Login-Standorte
- [ ] Session-Details mit Browser-Fingerprinting

---

## 🐛 Troubleshooting

### Profilbild wird nicht angezeigt
1. Prüfen Sie, ob das Verzeichnis `/uploads/profile-images/` existiert
2. Prüfen Sie die Berechtigungen (755)
3. Prüfen Sie die `.htaccess` im Upload-Verzeichnis

### Aktivitäten werden nicht protokolliert
1. Prüfen Sie, ob die Tabelle `admin_activity_log` existiert
2. Prüfen Sie die Datenbank-Verbindung
3. Führen Sie das Setup-Script erneut aus

### Sessions werden nicht angezeigt
1. Prüfen Sie, ob die Tabelle `login_sessions` existiert
2. Session-Tracking muss beim Login implementiert sein
3. Prüfen Sie die Fremdschlüssel-Beziehungen

---

## 📞 Support

Bei Fragen oder Problemen:
1. Überprüfen Sie diese Dokumentation
2. Schauen Sie in die Error-Logs: `/error_log`
3. Führen Sie das Setup-Script erneut aus

---

## 📄 Lizenz

Dieses System ist Teil des KI-Lead-Systems.
© 2024 - Alle Rechte vorbehalten.

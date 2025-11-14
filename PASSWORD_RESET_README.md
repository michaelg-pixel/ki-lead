# 🔐 Passwort-Vergessen-Funktion

## ✅ Installation abgeschlossen!

Die komplette Passwort-Reset-Funktion wurde erfolgreich implementiert mit Quentn E-Mail-Integration.

---

## 📁 Erstellte Dateien

### 1. **Config & API**
- `config/quentn_config.php` - Quentn API Konfiguration
- `includes/quentn_api.php` - E-Mail-Versand Funktionen

### 2. **Frontend-Seiten**
- `public/password-reset-request.php` - E-Mail eingeben & Token anfordern
- `public/password-reset.php` - Neues Passwort setzen
- `public/login.php` - **AKTUALISIERT** mit "Passwort vergessen?" Link

### 3. **Datenbank**
- `database/migrations/add_password_reset_columns.php` - Migration Script

---

## 🚀 Installation durchführen

### Schritt 1: Datenbank-Migration ausführen

Rufe folgende URL im Browser auf:
```
https://app.mehr-infos-jetzt.de/database/migrations/add_password_reset_columns.php
```

**Was passiert:**
- Fügt `password_reset_token` Spalte zur `users` Tabelle hinzu
- Fügt `password_reset_expires` Spalte zur `users` Tabelle hinzu  
- Erstellt Index auf `password_reset_token`

**Erwartetes Ergebnis:**
```
✅ Migration erfolgreich!

Hinzugefügte Spalten:
  ✓ password_reset_token (VARCHAR 64)
  ✓ password_reset_expires (DATETIME)
  ✓ Index auf password_reset_token

✅ Verifizierung: Alle Spalten korrekt angelegt!
```

---

## 🔧 Quentn API Konfiguration

Die API ist bereits konfiguriert in `config/quentn_config.php`:

```php
define('QUENTN_API_BASE_URL', 'https://pk1bh1.eu-1.quentn.com/public/api/v1/');
define('QUENTN_API_KEY', 'm-gkCLAXFVewwguCP1ZCm9zFFi_bauieZPl21EkGUqo');
```

### E-Mail-Template in Quentn

Du musst eventuell noch ein E-Mail-Template in Quentn erstellen oder eine Campaign für Passwort-Reset E-Mails.

**Verfügbare Platzhalter:**
- `{{first_name}}` - Vorname des Users
- `{{reset_link}}` - Der Reset-Link

---

## 📱 Funktionsweise

### User-Flow

```
1. User klickt auf "Passwort vergessen?" auf Login-Seite
   ↓
2. Gibt E-Mail-Adresse ein
   ↓
3. System generiert sicheren Token (64 Zeichen)
   ↓
4. Token wird in DB gespeichert (1 Stunde gültig)
   ↓
5. E-Mail wird über Quentn API versendet
   ↓
6. User klickt auf Reset-Link in E-Mail
   ↓
7. Kommt zu password-reset.php?token=XXX
   ↓
8. System validiert Token (existiert + nicht abgelaufen)
   ↓
9. User gibt neues Passwort ein (min. 8 Zeichen)
   ↓
10. Passwort wird gehashed und gespeichert
    ↓
11. Token wird gelöscht
    ↓
12. Auto-Redirect zum Login nach 3 Sekunden
```

---

## 🔒 Sicherheitsfeatures

✅ **Kryptographisch sichere Tokens**  
Tokens werden mit `bin2hex(random_bytes(32))` generiert

✅ **Zeitlimit**  
Reset-Links sind nur 1 Stunde gültig

✅ **Rate-Limiting**  
Max. 3 Anfragen pro E-Mail pro Stunde

✅ **Keine User-Enumeration**  
System zeigt immer gleiche Meldung, egal ob E-Mail existiert

✅ **Token wird gelöscht**  
Nach erfolgreicher Verwendung wird Token sofort entfernt

✅ **Password-Hashing**  
Passwörter werden mit `password_hash()` gesichert

✅ **HTTPS erforderlich**  
Sollte über .htaccess erzwungen werden

---

## 🧪 Testing

### 1. Migration testen
```
URL: https://app.mehr-infos-jetzt.de/database/migrations/add_password_reset_columns.php
Erwartung: ✅ Erfolgsmeldung
```

### 2. Passwort-Reset-Anfrage testen
```
URL: https://app.mehr-infos-jetzt.de/public/password-reset-request.php
Aktion: E-Mail eingeben
Erwartung: Erfolgsmeldung + E-Mail erhalten
```

### 3. Reset-Link testen
```
Aktion: Auf Link in E-Mail klicken
Erwartung: Formular für neues Passwort
```

### 4. Neues Passwort setzen
```
Aktion: Neues Passwort eingeben (2x)
Erwartung: Erfolgsmeldung + Auto-Redirect zum Login
```

### 5. Mit neuem Passwort einloggen
```
URL: https://app.mehr-infos-jetzt.de/public/login.php
Erwartung: Erfolgreicher Login
```

---

## 📧 E-Mail-Template

Die E-Mail wird automatisch mit folgendem Design versendet:

- **Header:** Gradient-Design (Purple/Blue)
- **Inhalt:** Personalisierte Anrede mit Vornamen
- **Button:** "Passwort jetzt zurücksetzen"
- **Info-Box:** Hinweis auf 1-Stunde-Gültigkeit
- **Footer:** Optinpilot Branding

Das Template ist in `includes/quentn_api.php` definiert und kann angepasst werden.

---

## 🔍 Debugging

### Logs prüfen

Alle Fehler werden in PHP Error-Log geschrieben:

```bash
# Server Error-Log checken
tail -f /var/log/apache2/error.log
# oder
tail -f /var/log/php/error.log
```

### Häufige Probleme

**Problem:** E-Mail kommt nicht an  
**Lösung:** 
- Quentn API Key prüfen
- Quentn Domain-Verifizierung prüfen
- Error-Log checken

**Problem:** Token ungültig  
**Lösung:**
- Prüfen ob Token in DB existiert
- Prüfen ob `password_reset_expires` nicht abgelaufen ist

**Problem:** Migration schlägt fehl  
**Lösung:**
- DB-Berechtigungen prüfen
- Prüfen ob Spalten schon existieren

---

## 🎨 UI/UX

### Login-Seite
- Neuer Link "🔐 Passwort vergessen?" rechts unter Passwort-Feld
- Dezentes Design, fügt sich nahtlos ein

### Request-Seite  
- Gradient-Background (Purple/Blue)
- White Card mit Icon 🔐
- Info-Box mit Hinweis auf 1-Stunden-Gültigkeit
- "Zurück zum Login" Link

### Reset-Seite
- Gradient-Background (Purple/Blue)  
- White Card mit Icon 🔑
- Zeigt User-E-Mail an
- Passwort-Anforderungen Box
- 2 Passwort-Felder (Eingabe + Bestätigung)
- Auto-Redirect nach Erfolg

### E-Mail
- Professionelles HTML-Design
- Responsive (Mobile-friendly)
- Gradient-Header
- Call-to-Action Button
- Info-Box mit Ablaufzeit

---

## ⚙️ Konfiguration anpassen

### E-Mail-Absender ändern

In `config/quentn_config.php`:

```php
define('QUENTN_FROM_EMAIL', 'noreply@mehr-infos-jetzt.de');
define('QUENTN_FROM_NAME', 'Optinpilot');
```

### Token-Gültigkeit ändern

In `public/password-reset-request.php`, Zeile ~45:

```php
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
// Ändern zu z.B.:
$expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
```

### Rate-Limit ändern

In `includes/quentn_api.php`, Funktion `checkPasswordResetRateLimit()`:

```php
return ($result['count'] < 3); // Max 3 Anfragen
// Ändern zu z.B.:
return ($result['count'] < 5); // Max 5 Anfragen
```

---

## 📋 Checkliste

- [x] Quentn API Config erstellt
- [x] API Helper-Funktionen erstellt
- [x] Datenbank-Migration erstellt
- [x] Request-Seite erstellt
- [x] Reset-Seite erstellt
- [x] Login-Seite aktualisiert (Link hinzugefügt)
- [ ] **Migration ausführen** (URL aufrufen)
- [ ] Quentn Domain verifizieren
- [ ] Test durchführen
- [ ] Produktiv nutzen

---

## 🆘 Support

Bei Problemen:

1. **Error-Logs checken**
2. **Quentn Dashboard prüfen** (Domain-Verifizierung, API-Status)
3. **Datenbank prüfen** (Spalten vorhanden?)
4. **Browser Console checken** (JavaScript-Fehler?)

---

## ✨ Features

✅ Sichere Token-Generierung  
✅ E-Mail-Versand über Quentn  
✅ Responsive Design  
✅ Rate-Limiting  
✅ User-Enumeration-Schutz  
✅ Auto-Cleanup abgelaufener Tokens  
✅ Schönes E-Mail-Template  
✅ DSGVO-konform  

---

**Status:** ✅ Bereit zum Testen nach Migration!

**Nächster Schritt:** Migration ausführen und testen!

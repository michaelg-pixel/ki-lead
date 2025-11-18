# Sichere Session-Verwaltung mit 90-Tage Laufzeit

## 📋 Übersicht

Diese Implementierung ermöglicht es Kunden, 90 Tage lang eingeloggt zu bleiben, ohne sich jeden Tag neu anmelden zu müssen. Die Lösung ist sicher und folgt Best Practices für Session-Management.

## 🔒 Sicherheits-Features

- **90-Tage Session-Laufzeit**: Kunden bleiben automatisch 90 Tage eingeloggt
- **Sichere Cookies**: HttpOnly und SameSite-Flags zum Schutz vor XSS und CSRF
- **Automatische Session-Regeneration**: Alle 24 Stunden neue Session-ID zur Sicherheit
- **Session-Validierung**: Prüfung auf Ablauf und gültige Session-Daten
- **Brute-Force-Schutz**: 1 Sekunde Delay bei fehlgeschlagenem Login
- **HTTPS-Support**: Sichere Cookies wenn HTTPS verfügbar ist

## 🚀 Verwendung in bestehenden Dateien

### In geschützten Seiten (Customer/Admin Dashboards)

Ersetze den Standard `session_start()` Code mit:

```php
<?php
// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../config/security.php';

// Starte sichere Session
startSecureSession();

// Prüfe Login und leite um falls nicht eingeloggt
requireLogin('/public/login.php');

// Ab hier ist der User garantiert eingeloggt
// Deine Seiten-Logik hier...
?>
```

### Beispiel für Customer Dashboard

```php
<?php
require_once __DIR__ . '/../config/security.php';
startSecureSession();
requireLogin('/public/login.php');

// Jetzt kannst du sicher auf User-Daten zugreifen
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$userEmail = $_SESSION['email'];
?>
```

### Beispiel für Admin Dashboard

```php
<?php
require_once __DIR__ . '/../config/security.php';
startSecureSession();
requireLogin('/public/login.php');

// Prüfe Admin-Rechte
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

// Admin-spezifische Logik...
?>
```

## 📊 Session-Informationen anzeigen (Debug)

Für Debugging oder Admin-Bereiche kannst du Session-Informationen anzeigen:

```php
<?php
require_once __DIR__ . '/../config/security.php';
startSecureSession();

// Session-Info abrufen
$sessionInfo = getSessionInfo();

echo "<pre>";
print_r($sessionInfo);
echo "</pre>";
?>
```

Ausgabe:
```
Array
(
    [session_id] => abc123...
    [created_at] => 18.11.2025 10:30:00
    [age_days] => 5
    [remaining_days] => 85
    [last_activity] => 18.11.2025 14:22:15
    [last_regeneration] => 18.11.2025 10:30:00
    [is_logged_in] => 1
    [user_id] => 123
    [user_email] => kunde@example.com
)
```

## 🔧 Verfügbare Funktionen

### `startSecureSession()`
Startet eine sichere Session mit 90-Tage Konfiguration und Validierung.

### `isLoggedIn()`
Prüft ob ein Benutzer eingeloggt ist.

```php
if (isLoggedIn()) {
    echo "Willkommen zurück!";
} else {
    echo "Bitte logge dich ein.";
}
```

### `requireLogin($loginUrl)`
Erzwingt Login - leitet automatisch zur Login-Seite um wenn nicht eingeloggt.

```php
requireLogin('/public/login.php'); // Leitet um wenn nicht eingeloggt
// Code nach dieser Zeile wird nur ausgeführt wenn User eingeloggt ist
```

### `destroySecureSession()`
Beendet die Session sicher (wird in logout.php verwendet).

### `getSessionInfo()`
Gibt detaillierte Session-Informationen zurück (für Debugging).

## ⚙️ Konfiguration anpassen

Die Konfiguration befindet sich in `config/security.php`:

```php
// Session-Dauer ändern (aktuell 90 Tage)
define('SESSION_LIFETIME_DAYS', 90);
```

Für kürzere oder längere Session-Dauer einfach die Anzahl der Tage ändern:
- 7 Tage: `define('SESSION_LIFETIME_DAYS', 7);`
- 30 Tage: `define('SESSION_LIFETIME_DAYS', 30);`
- 180 Tage: `define('SESSION_LIFETIME_DAYS', 180);`

## 🛡️ Optionale Sicherheits-Features

### IP-Validierung aktivieren

In `config/security.php` die auskommentierte IP-Prüfung aktivieren:

```php
// Aktuell deaktiviert wegen mobilen Nutzern
// Entferne die Kommentare um IP-Validierung zu aktivieren:
if (isset($_SESSION['user_ip'])) {
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ($_SESSION['user_ip'] !== $current_ip) {
        destroySecureSession();
        return false;
    }
}
```

**Hinweis**: IP-Validierung kann bei mobilen Nutzern (wechselnde IPs) problematisch sein.

## 📝 Migration bestehender Dateien

### Schritt 1: Finde alle Dateien mit session_start()

```bash
grep -r "session_start()" .
```

### Schritt 2: Ersetze in jeder Datei

**Vorher:**
```php
<?php
session_start();
require_once '../config/database.php';
```

**Nachher:**
```php
<?php
require_once __DIR__ . '/../config/security.php';
startSecureSession();
require_once __DIR__ . '/../config/database.php';
```

### Schritt 3: Füge Login-Prüfung hinzu (falls nötig)

In geschützten Bereichen:
```php
requireLogin('/public/login.php');
```

## 🔍 Wichtige Dateien die aktualisiert wurden

- ✅ `config/security.php` - Neue Sicherheitskonfiguration
- ✅ `public/login.php` - Nutzt neue Session-Verwaltung
- ✅ `public/logout.php` - Nutzt sichere Session-Zerstörung

## 📌 Noch zu aktualisieren

Diese Dateien sollten noch aktualisiert werden, um die neue Session-Verwaltung zu nutzen:

1. `customer/dashboard.php`
2. `customer/*.php` (alle Customer-Seiten)
3. `admin/dashboard.php`
4. `admin/*.php` (alle Admin-Seiten)
5. Alle anderen Dateien die `session_start()` verwenden

## 🧪 Testen

### Test 1: Normale Anmeldung
1. Gehe zu `/public/login.php`
2. Melde dich an
3. Schließe den Browser
4. Öffne den Browser erneut nach einigen Stunden
5. Gehe zu `/customer/dashboard.php`
6. Du solltest immer noch eingeloggt sein

### Test 2: Session-Ablauf
1. Ändere `SESSION_LIFETIME_DAYS` auf 0 (für sofortigen Ablauf)
2. Melde dich an
3. Aktualisiere die Seite
4. Du solltest ausgeloggt werden

### Test 3: Logout
1. Melde dich an
2. Klicke auf Logout
3. Versuche zurückzunavigieren
4. Du solltest zur Login-Seite weitergeleitet werden

## 💡 Best Practices

1. **Verwende immer `startSecureSession()` statt `session_start()`**
2. **Verwende `requireLogin()` in geschützten Bereichen**
3. **Verwende `isLoggedIn()` für bedingte Anzeigen**
4. **Verwende `destroySecureSession()` beim Logout**
5. **Teste die Session-Dauer regelmäßig**

## ⚠️ Wichtige Hinweise

- Die Session-Konfiguration muss **VOR** `session_start()` geladen werden
- Die `config/security.php` muss in jeder Datei eingebunden werden, die Sessions nutzt
- Bei Problemen prüfe die Server-Logs: `error_log()`
- Sessions werden im Server-Speicher gehalten - bei Shared-Hosting kann die Dauer limitiert sein
- Die tatsächliche Session-Dauer hängt auch vom `session.gc_maxlifetime` des Servers ab

## 🆘 Problembehandlung

### Problem: Sessions laufen trotzdem nach 24 Stunden ab

**Lösung**: Prüfe ob dein Hosting-Provider die `session.gc_maxlifetime` limitiert.

Kontaktiere den Support oder füge in `.htaccess` hinzu:
```apache
php_value session.gc_maxlifetime 7776000
php_value session.cookie_lifetime 7776000
```

### Problem: "Session already started" Fehler

**Lösung**: Stelle sicher dass `session_start()` nur einmal aufgerufen wird.
Die Funktion `startSecureSession()` prüft bereits ob eine Session aktiv ist.

### Problem: Nutzer wird bei jedem Browser-Neustart ausgeloggt

**Lösung**: Prüfe ob Cookies gespeichert werden:
1. Browser-Einstellungen → Cookies aktiviert?
2. Browser → Entwicklertools → Application → Cookies
3. Suche nach Cookie mit Name `PHPSESSID`
4. Prüfe "Expires" Datum (sollte ~90 Tage in der Zukunft sein)

## 📚 Weitere Ressourcen

- [PHP Session Security](https://www.php.net/manual/de/features.session.security.php)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [Cookie Security Best Practices](https://owasp.org/www-community/controls/SecureCookieAttribute)

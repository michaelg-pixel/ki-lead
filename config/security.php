<?php
/**
 * Sicherheitskonfiguration für Session-Management
 * 
 * Diese Datei muss VOR session_start() eingebunden werden
 * und konfiguriert sichere Sessions mit verlängerter Dauer
 */

// Verhindere direkten Zugriff
if (!defined('SESSION_CONFIG_INCLUDED')) {
    define('SESSION_CONFIG_INCLUDED', true);
}

// Session-Konfiguration
define('SESSION_LIFETIME_DAYS', 90); // Session-Dauer in Tagen
define('SESSION_LIFETIME_SECONDS', SESSION_LIFETIME_DAYS * 24 * 60 * 60); // 90 Tage in Sekunden

/**
 * Konfiguriert sichere Session-Parameter
 * MUSS vor session_start() aufgerufen werden
 */
function configureSecureSession() {
    // Setze Session Cookie Parameter für 90 Tage
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME_SECONDS,  // 90 Tage
        'path' => '/',
        'domain' => '',  // Automatisch die aktuelle Domain
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',  // Nur über HTTPS senden wenn verfügbar
        'httponly' => true,  // Kein Zugriff via JavaScript (XSS-Schutz)
        'samesite' => 'Lax'  // CSRF-Schutz
    ]);
    
    // Setze Session-Speicher-Parameter
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME_SECONDS);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME_SECONDS);
    
    // Zusätzliche Sicherheitseinstellungen
    ini_set('session.use_strict_mode', 1);  // Akzeptiere nur selbst erstellte Session-IDs
    ini_set('session.use_only_cookies', 1);  // Verwende nur Cookies, keine URL-Parameter
    ini_set('session.cookie_httponly', 1);  // JavaScript-Zugriff verhindern
    
    // Nutze PHP's Session ID Regeneration
    ini_set('session.use_trans_sid', 0);  // Keine Session-ID in URLs
}

/**
 * Startet eine sichere Session
 * 
 * @return bool True wenn erfolgreich, False bei Fehler
 */
function startSecureSession() {
    // Konfiguriere Session-Parameter
    configureSecureSession();
    
    // Starte Session wenn noch nicht gestartet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Prüfe ob Session initialisiert werden muss
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);  // Neue Session-ID zur Sicherheit
        $_SESSION['initiated'] = true;
        $_SESSION['created_at'] = time();
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    // Session-Validierung: Prüfe ob Session noch gültig ist
    if (!validateSession()) {
        return false;
    }
    
    // Session-Erneuerung nach 24 Stunden für zusätzliche Sicherheit
    if (isset($_SESSION['last_regeneration'])) {
        if (time() - $_SESSION['last_regeneration'] > 86400) { // 24 Stunden
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
    
    // Aktualisiere Last-Activity Timestamp
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Validiert die aktuelle Session
 * 
 * @return bool True wenn Session gültig, False wenn ungültig
 */
function validateSession() {
    // Prüfe ob Session abgelaufen ist (90 Tage)
    if (isset($_SESSION['created_at'])) {
        if (time() - $_SESSION['created_at'] > SESSION_LIFETIME_SECONDS) {
            destroySecureSession();
            return false;
        }
    }
    
    // Optional: Prüfe IP-Adresse (kann bei mobilen Nutzern problematisch sein)
    // Kommentiere diese Zeilen aus wenn du IP-Validierung haben möchtest:
    /*
    if (isset($_SESSION['user_ip'])) {
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($_SESSION['user_ip'] !== $current_ip) {
            destroySecureSession();
            return false;
        }
    }
    */
    
    // Optional: Prüfe User-Agent (grundlegender Bot-Schutz)
    if (isset($_SESSION['user_agent'])) {
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        if ($_SESSION['user_agent'] !== $current_ua) {
            // Bei User-Agent Änderung nur warnen, nicht automatisch ausloggen
            // da Browser-Updates den User-Agent ändern können
            error_log('Session Warning: User-Agent changed for session ' . session_id());
        }
    }
    
    return true;
}

/**
 * Zerstört die aktuelle Session sicher
 */
function destroySecureSession() {
    // Session-Variablen löschen
    $_SESSION = array();
    
    // Session-Cookie löschen
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Session zerstören
    session_destroy();
}

/**
 * Prüft ob ein Benutzer eingeloggt ist
 * 
 * @return bool True wenn eingeloggt, False wenn nicht
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true && 
           isset($_SESSION['user_id']);
}

/**
 * Erzwingt Login - leitet zu Login-Seite um wenn nicht eingeloggt
 * 
 * @param string $loginUrl URL zur Login-Seite
 */
function requireLogin($loginUrl = '/public/login.php') {
    if (!isLoggedIn()) {
        // Speichere aktuelle URL für Redirect nach Login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Gibt Session-Informationen zurück (für Debugging)
 * 
 * @return array Session-Infos
 */
function getSessionInfo() {
    if (!isset($_SESSION['initiated'])) {
        return ['status' => 'Keine aktive Session'];
    }
    
    $created = $_SESSION['created_at'] ?? 0;
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $lastRegeneration = $_SESSION['last_regeneration'] ?? 0;
    
    $ageSeconds = time() - $created;
    $ageDays = floor($ageSeconds / 86400);
    $remainingSeconds = SESSION_LIFETIME_SECONDS - $ageSeconds;
    $remainingDays = floor($remainingSeconds / 86400);
    
    return [
        'session_id' => session_id(),
        'created_at' => date('d.m.Y H:i:s', $created),
        'age_days' => $ageDays,
        'remaining_days' => max(0, $remainingDays),
        'last_activity' => date('d.m.Y H:i:s', $lastActivity),
        'last_regeneration' => date('d.m.Y H:i:s', $lastRegeneration),
        'is_logged_in' => isLoggedIn(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_email' => $_SESSION['email'] ?? null
    ];
}

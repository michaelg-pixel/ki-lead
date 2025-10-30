<?php
/**
 * Authentifizierungs-System
 * 
 * Funktionen für Login, Logout, Session-Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

/**
 * Session sicher starten
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1); // Nur bei HTTPS!
        
        session_name('LUMI_SESSION');
        session_start();
        
        // Session-Fixation verhindern
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        // Session-Timeout prüfen
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            session_unset();
            session_destroy();
            header('Location: ' . PUBLIC_URL . '/login.php?timeout=1');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Benutzer einloggen
 * 
 * @param string $email
 * @param string $password
 * @return array|false User-Daten bei Erfolg, false bei Fehler
 */
function login($email, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT id, email, password, name, role, is_active 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Account aktiv?
    if (!$user['is_active']) {
        return false;
    }
    
    // Passwort prüfen
    if (!password_verify($password, $user['password'])) {
        return false;
    }
    
    // Session setzen
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Login-Zeit speichern
    $stmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    return $user;
}

/**
 * Benutzer ausloggen
 */
function logout() {
    session_unset();
    session_destroy();
    
    // Cookie löschen
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

/**
 * Prüfen ob Benutzer eingeloggt ist
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Prüfen ob Benutzer Admin ist
 * 
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Prüfen ob Benutzer Customer ist
 * 
 * @return bool
 */
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * Aktuellen Benutzer abrufen
 * 
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Admin-Zugriff erzwingen
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ' . PUBLIC_URL . '/admin-login.php');
        exit;
    }
}

/**
 * Customer-Zugriff erzwingen
 */
function requireCustomer() {
    if (!isLoggedIn() || !isCustomer()) {
        header('Location: ' . PUBLIC_URL . '/login.php');
        exit;
    }
}

/**
 * Login-Zugriff erzwingen (egal welche Rolle)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . PUBLIC_URL . '/login.php');
        exit;
    }
}

/**
 * Benutzer registrieren
 * 
 * @param string $email
 * @param string $password
 * @param string $name
 * @param string $role
 * @return int|false User-ID bei Erfolg, false bei Fehler
 */
function register($email, $password, $name = '', $role = 'customer') {
    $conn = getDBConnection();
    
    // E-Mail bereits vorhanden?
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false;
    }
    
    // Passwort hashen
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Benutzer anlegen
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, name, role) 
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$email, $hashed_password, $name, $role])) {
        return $conn->lastInsertId();
    }
    
    return false;
}

/**
 * CSRF-Token generieren
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 * 
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Passwort zurücksetzen
 */
function resetPassword($user_id, $new_password) {
    $conn = getDBConnection();
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed_password, $user_id]);
}

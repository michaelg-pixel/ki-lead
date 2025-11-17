<?php
/**
 * Haupt-Konfigurationsdatei
 * Lädt alle anderen Configs und definiert MySQLi Konstanten
 */

// Error Reporting für Entwicklung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// DATENBANK-KONSTANTEN (MySQLi)
// ========================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'lumisaas');
define('DB_USER', 'lumisaas52');
define('DB_PASS', 'I1zx1XdL1hrWd75yu57e');

// ========================================
// SITE-KONFIGURATION
// ========================================

define('SITE_URL', 'https://app.mehr-infos-jetzt.de');
define('SITE_NAME', 'KI Leadsystem');

// ========================================
// SESSION-KONFIGURATION
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// PDO-VERBINDUNG (optional, für Kompatibilität)
// ========================================

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ));
    
} catch (PDOException $e) {
    // Stille Fehlerbehandlung
    $pdo = null;
}

// ========================================
// HELPER-FUNKTIONEN
// ========================================

/**
 * Gibt die PDO-Datenbankverbindung zurück
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Erstellt eine MySQLi-Verbindung
 */
function getMySQLiConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Verbindung fehlgeschlagen: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Prüft ob Referral-System aktiviert ist
 */
function isReferralEnabled() {
    if (file_exists(__DIR__ . '/referral_config.php')) {
        require_once __DIR__ . '/referral_config.php';
        return defined('REFERRAL_SYSTEM_ENABLED') && REFERRAL_SYSTEM_ENABLED;
    }
    return false;
}
?>

<?php
/**
 * Datenbank-Konfiguration - KI Leadsystem
 * Unterstützt BEIDE Methoden:
 * 1. Direkte $pdo Variable (für bestehende Dateien)
 * 2. getDBConnection() Funktion (für neue Dateien)
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// DATENBANK-ZUGANGSDATEN
// ========================================

$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

// ========================================
// GLOBALE PDO-VERBINDUNG ($pdo)
// Für bestehende Dateien die direkt $pdo nutzen
// ========================================

$pdo = null;

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ));
    
    // Erfolg! Stille Verbindung
    
} catch (PDOException $e) {
    // Fehlerausgabe nur wenn nicht in API-Call
    if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
        echo "<!DOCTYPE html>";
        echo "<html><head><meta charset='UTF-8'><title>Datenbankfehler</title>";
        echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
        echo ".error{background:#fee2e2;border-left:4px solid #ef4444;padding:20px;border-radius:8px;}</style></head><body>";
        echo "<div class='error'>";
        echo "<h1>❌ Datenbankverbindung fehlgeschlagen!</h1>";
        echo "<p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
        echo "<hr>";
        echo "<h3>Überprüfe folgendes:</h3>";
        echo "<ul>";
        echo "<li>Datenbank-Name: <code>" . htmlspecialchars($database) . "</code></li>";
        echo "<li>Username: <code>" . htmlspecialchars($username) . "</code></li>";
        echo "<li>Passwort: <em>(verborgen)</em></li>";
        echo "<li>Host: <code>" . htmlspecialchars($host) . "</code></li>";
        echo "</ul>";
        echo "<hr>";
        echo "<p><strong>Wo findest du die richtigen Daten?</strong></p>";
        echo "<ol><li>Öffne CloudPanel</li>";
        echo "<li>Gehe zu <strong>Databases</strong></li>";
        echo "<li>Klicke auf <strong>app.mehr-infos-jetzt.de</strong></li>";
        echo "<li>Klicke auf <strong>'Show Credentials'</strong></li>";
        echo "<li>Kopiere: Database Name, Username, Password</li></ol>";
        echo "</div></body></html>";
    }
    exit;
}

// ========================================
// FUNKTION: getDBConnection()
// Für neue Dateien (z.B. login.php)
// ========================================

/**
 * Gibt die PDO-Datenbankverbindung zurück
 * 
 * @return PDO Die Datenbankverbindung
 * @throws Exception Wenn keine Verbindung besteht
 */
function getDBConnection() {
    global $pdo;
    
    if ($pdo === null) {
        throw new Exception("Keine Datenbankverbindung verfügbar!");
    }
    
    return $pdo;
}

// ========================================
// HELPER-FUNKTIONEN
// ========================================

/**
 * Prüft ob eine Tabelle existiert
 * 
 * @param string $tableName Name der Tabelle
 * @return bool True wenn Tabelle existiert
 */
function tableExists($tableName) {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Gibt Datenbankinfo zurück
 * 
 * @return array Array mit DB-Informationen
 */
function getDatabaseInfo() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        return [
            'connected' => true,
            'database' => $result['db_name'],
            'charset' => 'utf8mb4'
        ];
    } catch (PDOException $e) {
        return [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ========================================
// AUTO-TEST (Optional - für Debugging)
// ========================================

// Uncomment für Debug-Ausgabe:
// if (php_sapi_name() === 'cli') {
//     echo "✅ Datenbankverbindung erfolgreich!\n";
//     echo "   Database: " . $database . "\n";
//     echo "   Host: " . $host . "\n";
// }
?>
<?php
/**
 * Vendor System Migration Executor
 * Führt SQL-Befehle für die Vendor System Migration aus
 */

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbank-Verbindung
require_once __DIR__ . '/../../config/database.php';

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST-Requests erlaubt'
    ]);
    exit;
}

// JSON-Input parsen
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['sql'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Kein SQL-Statement übergeben'
    ]);
    exit;
}

$sql = $data['sql'];

try {
    // SQL ausführen
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    // Erfolg
    echo json_encode([
        'success' => true,
        'message' => 'SQL erfolgreich ausgeführt',
        'affected_rows' => $stmt->rowCount()
    ]);
    
} catch (PDOException $e) {
    // Fehler loggen
    error_log('Migration Error: ' . $e->getMessage());
    
    // Check if error is about column already existing
    if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
        strpos($e->getMessage(), 'already exists') !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Spalte existiert bereits (ignoriert)',
            'warning' => $e->getMessage()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }
}
?>
<?php
/**
 * API: SQL Migration ausführen
 * Führt SQL-Statements für Datenbank-Migrationen aus
 */

// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keine HTML-Fehler ausgeben

// JSON Header setzen - IMMER als erstes!
header('Content-Type: application/json');

// Session starten
session_start();

try {
    // Auth-Check
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Nicht authentifiziert. Bitte einloggen.'
        ]);
        exit;
    }

    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Keine Berechtigung. Nur Admins können Migrationen ausführen.'
        ]);
        exit;
    }

    // Input validieren
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['sql'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'SQL parameter fehlt'
        ]);
        exit;
    }
    
    $sql = trim($input['sql']);
    
    if (empty($sql)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'SQL darf nicht leer sein'
        ]);
        exit;
    }

    // Datenbankverbindung
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    // SQL ausführen
    $stmt = $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Migration erfolgreich ausgeführt',
        'affected_rows' => $stmt
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'sql_state' => $e->getCode()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

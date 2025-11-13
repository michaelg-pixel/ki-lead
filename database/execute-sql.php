<?php
/**
 * SQL Execution Endpoint for Browser Migrations
 * Führt SQL-Befehle sicher aus für Browser-basierte Migrationen
 */

// Nur während Entwicklung erlaubt - IN PRODUKTION ENTFERNEN!
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Prüfe Request-Methode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

// Lese JSON-Body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['sql'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Kein SQL-Befehl übermittelt']);
    exit;
}

$sql = trim($data['sql']);
$description = $data['description'] ?? 'SQL Execution';

try {
    $pdo = getDBConnection();
    
    // Erlaube nur ALTER TABLE und CREATE TABLE Befehle für Sicherheit
    if (!preg_match('/^(ALTER TABLE|CREATE TABLE)/i', $sql)) {
        throw new Exception('Nur ALTER TABLE und CREATE TABLE Befehle sind erlaubt');
    }
    
    // Führe SQL aus
    $pdo->exec($sql);
    
    // Prüfe ob Spalte wirklich existiert (für ALTER TABLE ADD COLUMN)
    if (preg_match('/ALTER TABLE\s+(\w+)\s+ADD COLUMN.*?(\w+)/i', $sql, $matches)) {
        $tableName = $matches[1];
        $columnName = $matches[2];
        
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if ($stmt->rowCount() > 0) {
            $message = "Spalte '$columnName' erfolgreich hinzugefügt";
        } else {
            $message = "Spalte existierte bereits oder konnte nicht hinzugefügt werden";
        }
    } else {
        $message = "Erfolgreich ausgeführt";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'description' => $description
    ]);
    
} catch (PDOException $e) {
    // Prüfe ob es sich um "Spalte existiert bereits" Fehler handelt
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Spalte existiert bereits (übersprungen)',
            'description' => $description
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'description' => $description
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'description' => $description
    ]);
}
?>
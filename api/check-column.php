<?php
/**
 * API: Prüfen ob eine Spalte in einer Tabelle existiert
 */

header('Content-Type: application/json');
session_start();

// Nur für Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $table = $input['table'] ?? '';
    $column = $input['column'] ?? '';
    
    if (empty($table) || empty($column)) {
        throw new Exception('Table and column parameters required');
    }
    
    $pdo = getDBConnection();
    
    // Prüfe ob Spalte existiert
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'exists' => $result['count'] > 0,
        'table' => $table,
        'column' => $column
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

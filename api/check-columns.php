<?php
// DEBUG-Version zum Prüfen der RICHTIGEN Tabelle
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // Admin-Check
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Keine Berechtigung');
    }
    
    // Datenbankverbindung
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Alle Tabellen anzeigen die mit "free" beginnen
    $stmt = $pdo->query("SHOW TABLES LIKE '%free%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'tables_found' => $tables,
        'table_structures' => []
    ];
    
    // Für jede gefundene Tabelle die Struktur anzeigen
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['table_structures'][$table] = array_column($columns, 'Field');
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
exit;
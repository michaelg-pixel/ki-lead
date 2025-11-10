<?php
/**
 * API: SQL Migration ausführen
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
    $sql = $input['sql'] ?? '';
    
    if (empty($sql)) {
        throw new Exception('SQL parameter required');
    }
    
    $pdo = getDBConnection();
    
    // SQL ausführen
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Migration executed successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

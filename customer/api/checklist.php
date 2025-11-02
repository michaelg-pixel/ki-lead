<?php
/**
 * Checklist API - Speichert Checklist-Fortschritt in der Datenbank
 * Jeder Kunde hat seinen eigenen Fortschritt
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/database.php';

// User ID aus Session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fortschritt abrufen
        $stmt = $pdo->prepare("
            SELECT task_id, completed, completed_at 
            FROM customer_checklist 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // In einfaches Array umwandeln
        $progress = [];
        foreach ($tasks as $task) {
            $progress[$task['task_id']] = (bool)$task['completed'];
        }
        
        echo json_encode([
            'success' => true,
            'progress' => $progress
        ]);
        
    } elseif ($method === 'POST') {
        // Fortschritt speichern
        $input = json_decode(file_get_contents('php://input'), true);
        $task_id = $input['task_id'] ?? null;
        $completed = $input['completed'] ?? false;
        
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'task_id required']);
            exit;
        }
        
        // Upsert (Insert oder Update)
        $stmt = $pdo->prepare("
            INSERT INTO customer_checklist (user_id, task_id, completed, completed_at) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                completed = VALUES(completed),
                completed_at = VALUES(completed_at),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $completed_at = $completed ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$user_id, $task_id, $completed ? 1 : 0, $completed_at]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Progress saved'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    error_log("Checklist API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

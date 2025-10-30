<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
$pdo = getDBConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['course_id'])) {
    echo json_encode(['success' => false, 'error' => 'Course ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$input['course_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Course deleted']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
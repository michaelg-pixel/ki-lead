<?php
/**
 * Webhook Configuration API - Delete
 * Löscht Webhook-Konfigurationen
 */

require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $webhookId = $_POST['webhook_id'] ?? null;
    
    if (!$webhookId) {
        throw new Exception('Webhook-ID fehlt');
    }
    
    // Webhook löschen (CASCADE löscht automatisch alle Verknüpfungen)
    $stmt = $pdo->prepare("DELETE FROM webhook_configurations WHERE id = ?");
    $stmt->execute([$webhookId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Webhook nicht gefunden');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Webhook erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

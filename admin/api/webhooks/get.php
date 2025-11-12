<?php
/**
 * Webhook Configuration API - Get
 * Lädt Webhook-Daten zum Bearbeiten
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
    
    $webhookId = $_GET['webhook_id'] ?? null;
    
    if (!$webhookId) {
        throw new Exception('Webhook-ID fehlt');
    }
    
    // Webhook laden
    $stmt = $pdo->prepare("SELECT * FROM webhook_configurations WHERE id = ?");
    $stmt->execute([$webhookId]);
    $webhook = $stmt->fetch();
    
    if (!$webhook) {
        throw new Exception('Webhook nicht gefunden');
    }
    
    // Produkt-IDs laden
    $stmt = $pdo->prepare("SELECT product_id FROM webhook_product_ids WHERE webhook_id = ?");
    $stmt->execute([$webhookId]);
    $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Kurse laden
    $stmt = $pdo->prepare("SELECT course_id FROM webhook_course_access WHERE webhook_id = ?");
    $stmt->execute([$webhookId]);
    $courseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'webhook' => $webhook,
        'product_ids' => $productIds,
        'course_ids' => $courseIds
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

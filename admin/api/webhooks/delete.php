<?php
/**
 * Webhook Configuration API - Delete
 * Löscht Webhook-Konfigurationen
 */

require_once '../../../config/database.php';
session_start();

// Für AJAX-Requests JSON zurückgeben
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    } else {
        header('Location: /admin/dashboard.php?page=webhooks&error=' . urlencode('Keine Berechtigung'));
    }
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
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Webhook erfolgreich gelöscht'
        ]);
    } else {
        header('Location: /admin/dashboard.php?page=webhooks&success=' . urlencode('Webhook erfolgreich gelöscht'));
    }
    exit;
    
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        header('Location: /admin/dashboard.php?page=webhooks&error=' . urlencode($e->getMessage()));
    }
    exit;
}

<?php
/**
 * Webhook Configuration API - Save
 * Erstellt oder aktualisiert Webhook-Konfigurationen
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
    $pdo->beginTransaction();
    
    $webhookId = $_POST['webhook_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Ressourcen
    $ownFreebiesLimit = intval($_POST['own_freebies_limit'] ?? 0);
    $readyFreebiesCount = intval($_POST['ready_freebies_count'] ?? 0);
    $referralSlots = intval($_POST['referral_slots'] ?? 0);
    
    // Upsell
    $isUpsell = isset($_POST['is_upsell']) ? 1 : 0;
    $upsellBehavior = $_POST['upsell_behavior'] ?? 'add';
    
    // Produkt-IDs
    $productIdsString = $_POST['product_ids'] ?? '';
    $productIds = array_filter(array_map('trim', explode(',', $productIdsString)));
    
    // Kurse
    $courseIds = $_POST['courses'] ?? [];
    
    // Validierung
    if (empty($name)) {
        throw new Exception('Name ist erforderlich');
    }
    
    if (empty($productIds)) {
        throw new Exception('Mindestens eine Produkt-ID ist erforderlich');
    }
    
    if ($webhookId) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE webhook_configurations SET
                name = ?,
                description = ?,
                is_active = ?,
                own_freebies_limit = ?,
                ready_freebies_count = ?,
                referral_slots = ?,
                is_upsell = ?,
                upsell_behavior = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name,
            $description,
            $isActive,
            $ownFreebiesLimit,
            $readyFreebiesCount,
            $referralSlots,
            $isUpsell,
            $upsellBehavior,
            $webhookId
        ]);
        
        // Bestehende Verknüpfungen löschen
        $pdo->prepare("DELETE FROM webhook_product_ids WHERE webhook_id = ?")->execute([$webhookId]);
        $pdo->prepare("DELETE FROM webhook_course_access WHERE webhook_id = ?")->execute([$webhookId]);
        
    } else {
        // CREATE
        $stmt = $pdo->prepare("
            INSERT INTO webhook_configurations (
                name, description, is_active,
                own_freebies_limit, ready_freebies_count, referral_slots,
                is_upsell, upsell_behavior, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name,
            $description,
            $isActive,
            $ownFreebiesLimit,
            $readyFreebiesCount,
            $referralSlots,
            $isUpsell,
            $upsellBehavior,
            $_SESSION['user_id']
        ]);
        
        $webhookId = $pdo->lastInsertId();
    }
    
    // Produkt-IDs verknüpfen
    $stmt = $pdo->prepare("INSERT INTO webhook_product_ids (webhook_id, product_id) VALUES (?, ?)");
    foreach ($productIds as $productId) {
        $stmt->execute([$webhookId, $productId]);
    }
    
    // Kurse verknüpfen
    if (!empty($courseIds)) {
        $stmt = $pdo->prepare("INSERT INTO webhook_course_access (webhook_id, course_id) VALUES (?, ?)");
        foreach ($courseIds as $courseId) {
            $stmt->execute([$webhookId, intval($courseId)]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'webhook_id' => $webhookId,
        'message' => 'Webhook erfolgreich gespeichert'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

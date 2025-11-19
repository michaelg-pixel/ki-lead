<?php
/**
 * API: Freebie & Template Unlock Status prüfen
 * Prüft ob Templates/Freebies durch Webhook-Produktkauf freigeschaltet sind
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';

// Nur für eingeloggte Kunden
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$pdo = getDBConnection();

try {
    $statusMap = [];
    
    // 1. Alle Templates holen
    $stmt = $pdo->query("SELECT id, name FROM freebies ORDER BY created_at DESC");
    $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Prüfe welche Templates freigeschaltet sind
    $stmt = $pdo->prepare("
        SELECT DISTINCT f.id as template_id
        FROM freebies f
        INNER JOIN courses c ON c.is_active = 1
        INNER JOIN webhook_course_access wca ON c.id = wca.course_id
        INNER JOIN webhook_configurations wc ON wca.webhook_id = wc.id AND wc.is_active = 1
        INNER JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
        WHERE EXISTS (
            -- Kunde hat das Produkt gekauft
            SELECT 1 FROM customer_freebie_limits cfl 
            WHERE cfl.customer_id = :customer_id 
            AND cfl.product_id = wpi.product_id
        )
    ");
    
    $stmt->execute(['customer_id' => $customer_id]);
    $unlockedTemplates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 3. Status für alle Templates setzen
    foreach ($allTemplates as $template) {
        $isUnlocked = in_array($template['id'], $unlockedTemplates);
        $statusMap['template_' . $template['id']] = [
            'unlock_status' => $isUnlocked ? 'unlocked' : 'locked',
            'name' => $template['name']
        ];
    }
    
    // 4. Status für bereits genutzte customer_freebies prüfen
    $stmt = $pdo->prepare("
        SELECT 
            cf.id as freebie_id,
            cf.template_id,
            cf.has_course,
            cf.headline,
            CASE 
                WHEN cf.has_course = 0 THEN 'no_course'
                WHEN wca.webhook_id IS NOT NULL THEN 'unlocked'
                ELSE 'locked'
            END as unlock_status
        FROM customer_freebies cf
        LEFT JOIN (
            -- Prüfe ob Kunde Produkt gekauft hat und Webhook Kurszugang gewährt
            SELECT DISTINCT cf2.id as customer_freebie_id, wca2.webhook_id
            FROM customer_freebies cf2
            INNER JOIN customer_course_instances cci ON cf2.id = cci.customer_freebie_id
            INNER JOIN webhook_course_access wca2 ON cci.course_id = wca2.course_id
            INNER JOIN webhook_configurations wc ON wca2.webhook_id = wc.id AND wc.is_active = 1
            INNER JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
            WHERE cf2.customer_id = :customer_id
            AND EXISTS (
                SELECT 1 FROM customer_freebie_limits cfl 
                WHERE cfl.customer_id = :customer_id 
                AND cfl.product_id = wpi.product_id
            )
        ) wca ON cf.id = wca.customer_freebie_id
        WHERE cf.customer_id = :customer_id
    ");
    
    $stmt->execute(['customer_id' => $customer_id]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // customer_freebies Status speichern
    foreach ($freebies as $freebie) {
        $statusMap['freebie_' . $freebie['freebie_id']] = [
            'unlock_status' => $freebie['unlock_status'],
            'has_course' => (bool)$freebie['has_course'],
            'headline' => $freebie['headline']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'customer_id' => $customer_id,
        'total_templates' => count($allTemplates),
        'unlocked_count' => count($unlockedTemplates),
        'statuses' => $statusMap,
        'unlocked_template_ids' => $unlockedTemplates
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler',
        'message' => $e->getMessage()
    ]);
}
?>
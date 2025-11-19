<?php
/**
 * API: Freebie Unlock Status prüfen
 * Prüft ob ein Freebie durch Webhook-Produktkauf freigeschaltet ist
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
    // Alle customer_freebies des Kunden laden mit Unlock-Status
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
                -- Kunde hat das Produkt gekauft (über customer_freebie_limits)
                SELECT 1 FROM customer_freebie_limits cfl 
                WHERE cfl.customer_id = :customer_id 
                AND cfl.product_id = wpi.product_id
            )
        ) wca ON cf.id = wca.customer_freebie_id
        WHERE cf.customer_id = :customer_id
    ");
    
    $stmt->execute(['customer_id' => $customer_id]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // In Array umwandeln für einfachen Zugriff
    $statusMap = [];
    foreach ($freebies as $freebie) {
        $statusMap[$freebie['freebie_id']] = [
            'unlock_status' => $freebie['unlock_status'],
            'has_course' => (bool)$freebie['has_course'],
            'headline' => $freebie['headline']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'statuses' => $statusMap
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Datenbankfehler',
        'message' => $e->getMessage()
    ]);
}
?>
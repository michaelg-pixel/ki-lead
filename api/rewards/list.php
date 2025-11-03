<?php
/**
 * API: Alle Belohnungsstufen eines Users abrufen
 * GET /api/rewards/list.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Auth prüfen
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Prüfen ob Tabelle existiert
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'reward_definitions'");
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'count' => 0,
                'message' => 'Tabelle reward_definitions existiert noch nicht. Bitte Setup ausführen.'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Datenbankprüfung fehlgeschlagen: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Alle Belohnungsstufen des Users laden
    $stmt = $pdo->prepare("
        SELECT 
            rd.*,
            COALESCE(achieved.leads_count, 0) as leads_achieved,
            COALESCE(claimed.claims_count, 0) as times_claimed
        FROM reward_definitions rd
        LEFT JOIN (
            SELECT tier_id, COUNT(DISTINCT lead_id) as leads_count
            FROM referral_reward_tiers
            GROUP BY tier_id
        ) achieved ON rd.tier_level = achieved.tier_id
        LEFT JOIN (
            SELECT reward_id, COUNT(*) as claims_count
            FROM referral_claimed_rewards
            GROUP BY reward_id
        ) claimed ON rd.id = claimed.reward_id
        WHERE rd.user_id = ?
        ORDER BY rd.tier_level ASC, rd.sort_order ASC
    ");
    
    $stmt->execute([$user_id]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $rewards,
        'count' => count($rewards)
    ]);
    
} catch (PDOException $e) {
    error_log("Reward List Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'sql_error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("Reward List Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Serverfehler: ' . $e->getMessage()
    ]);
}

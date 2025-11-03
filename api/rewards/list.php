<?php
/**
 * API: Alle Belohnungsstufen eines Users abrufen - VEREINFACHT
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
    
    // Einfache Abfrage ohne komplexe JOINs
    // (Statistiken werden später hinzugefügt wenn das Lead-System vollständig ist)
    $stmt = $pdo->prepare("
        SELECT 
            rd.*,
            0 as leads_achieved,
            0 as times_claimed
        FROM reward_definitions rd
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

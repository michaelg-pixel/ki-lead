<?php
/**
 * Marketplace Import API - FIXED VERSION
 * Importiert ein Template in die eigenen reward_definitions
 * FIX: delivery_type Spalte entfernt (existiert nicht in Tabelle)
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['template_id'])) {
        throw new Exception('Template-ID fehlt');
    }
    
    $template_id = (int)$input['template_id'];
    $tier_level = isset($input['tier_level']) ? (int)$input['tier_level'] : null;
    $required_referrals = isset($input['required_referrals']) ? (int)$input['required_referrals'] : null;
    $freebie_id = isset($input['freebie_id']) ? (int)$input['freebie_id'] : null;
    
    // Prüfe ob bereits importiert
    $stmt = $pdo->prepare("
        SELECT id FROM reward_template_imports 
        WHERE template_id = ? AND customer_id = ?
    ");
    $stmt->execute([$template_id, $customer_id]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Sie haben dieses Template bereits importiert'
        ]);
        exit;
    }
    
    // Template laden
    $stmt = $pdo->prepare("
        SELECT * FROM vendor_reward_templates 
        WHERE id = ? AND is_published = 1
    ");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Template nicht gefunden oder nicht veröffentlicht']);
        exit;
    }
    
    // Transaction starten
    $pdo->beginTransaction();
    
    try {
        // Template in reward_definitions kopieren
        // WICHTIG: delivery_type wurde entfernt - existiert nicht in Tabelle
        $stmt = $pdo->prepare("
            INSERT INTO reward_definitions (
                user_id,
                imported_from_template_id,
                is_imported,
                tier_level,
                tier_name,
                required_referrals,
                reward_type,
                reward_title,
                reward_description,
                reward_value,
                reward_icon,
                reward_color,
                download_url,
                access_instructions,
                freebie_id,
                is_active,
                sort_order,
                created_at
            ) VALUES (
                ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, NOW()
            )
        ");
        
        $stmt->execute([
            $customer_id,
            $template_id,
            $tier_level ?? $template['suggested_tier_level'],
            $template['template_name'], // tier_name
            $required_referrals ?? $template['suggested_referrals_required'],
            $template['reward_type'],
            $template['reward_title'],
            $template['reward_description'],
            $template['reward_value'],
            $template['reward_icon'],
            $template['reward_color'],
            $template['reward_download_url'],
            $template['reward_instructions'],
            $freebie_id
        ]);
        
        $reward_definition_id = $pdo->lastInsertId();
        
        // Import loggen
        $stmt = $pdo->prepare("
            INSERT INTO reward_template_imports (
                template_id,
                customer_id,
                reward_definition_id,
                import_source,
                purchase_price
            ) VALUES (?, ?, ?, 'marketplace', ?)
        ");
        
        $stmt->execute([
            $template_id,
            $customer_id,
            $reward_definition_id,
            $template['marketplace_price']
        ]);
        
        // Template Import-Counter erhöhen
        $stmt = $pdo->prepare("
            UPDATE vendor_reward_templates 
            SET times_imported = times_imported + 1 
            WHERE id = ?
        ");
        $stmt->execute([$template_id]);
        
        // Commit
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Template erfolgreich importiert',
            'reward_definition_id' => $reward_definition_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Marketplace Import Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
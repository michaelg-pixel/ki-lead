<?php
/**
 * Marketplace Import API
 * Importiert ein Vendor-Template in die eigenen Belohnungsstufen
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

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

$user_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validierung
    if (empty($input['template_id'])) {
        throw new Exception('template_id erforderlich');
    }
    
    $template_id = (int)$input['template_id'];
    $freebie_id = !empty($input['freebie_id']) ? (int)$input['freebie_id'] : null;
    
    // Transaction starten
    $pdo->beginTransaction();
    
    try {
        // 1. Prüfe ob Template existiert und veröffentlicht ist
        $stmt = $pdo->prepare("
            SELECT vrt.*, u.email as vendor_email, u.first_name as vendor_first_name 
            FROM vendor_reward_templates vrt
            JOIN users u ON vrt.vendor_id = u.id
            WHERE vrt.id = ? AND vrt.is_published = 1
        ");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception('Template nicht gefunden oder nicht veröffentlicht');
        }
        
        // 2. Prüfe ob bereits importiert für diese Kombination
        $checkStmt = $pdo->prepare("
            SELECT id FROM reward_template_imports 
            WHERE template_id = ? AND customer_id = ? AND freebie_id <=> ?
        ");
        $checkStmt->execute([$template_id, $user_id, $freebie_id]);
        if ($checkStmt->fetch()) {
            throw new Exception('Template wurde bereits für dieses Freebie importiert');
        }
        
        // 3. Kopiere Template in reward_definitions
        $insertStmt = $pdo->prepare("
            INSERT INTO reward_definitions (
                user_id,
                freebie_id,
                imported_from_template_id,
                is_imported,
                tier_level,
                tier_name,
                tier_description,
                required_referrals,
                reward_type,
                reward_title,
                reward_description,
                reward_value,
                reward_delivery_type,
                reward_instructions,
                reward_download_url,
                reward_icon,
                reward_color,
                is_active,
                is_featured,
                auto_deliver,
                created_at
            ) VALUES (
                ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, NOW()
            )
        ");
        
        $success = $insertStmt->execute([
            $user_id,
            $freebie_id,
            $template_id,
            $template['suggested_tier_level'] ?? 1,
            $template['template_name'],
            $template['template_description'],
            $template['suggested_referrals_required'] ?? 3,
            $template['reward_type'],
            $template['reward_title'],
            $template['reward_description'],
            $template['reward_value'],
            $template['reward_delivery_type'] ?? 'manual',
            $template['reward_instructions'],
            $template['reward_download_url'],
            $template['reward_icon'] ?? '🎁',
            $template['reward_color'] ?? '#667eea'
        ]);
        
        if (!$success) {
            $errorInfo = $insertStmt->errorInfo();
            throw new Exception('Fehler beim Einfügen: ' . ($errorInfo[2] ?? 'Unbekannter Fehler'));
        }
        
        $reward_definition_id = $pdo->lastInsertId();
        
        if (!$reward_definition_id) {
            throw new Exception('Keine ID nach Insert erhalten');
        }
        
        // 4. Log Import in reward_template_imports
        $importLogStmt = $pdo->prepare("
            INSERT INTO reward_template_imports (
                template_id,
                customer_id,
                reward_definition_id,
                import_date,
                import_source
            ) VALUES (?, ?, ?, NOW(), 'marketplace')
        ");
        
        $importLogStmt->execute([$template_id, $user_id, $reward_definition_id]);
        
        // 5. Update Counter in vendor_reward_templates
        $updateStmt = $pdo->prepare("
            UPDATE vendor_reward_templates 
            SET times_imported = times_imported + 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$template_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Belohnung erfolgreich importiert',
            'reward_definition_id' => $reward_definition_id,
            'freebie_id' => $freebie_id,
            'template_name' => $template['template_name']
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Marketplace Import PDO Error: ' . $e->getMessage());
    error_log('Error Code: ' . $e->getCode());
    error_log('SQL State: ' . ($e->errorInfo[0] ?? 'N/A'));
    
    http_response_code(500);
    
    // Gib mehr Details im Entwicklungsmodus
    $errorDetails = 'Datenbankfehler';
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $errorDetails = 'Fehlende Datenbank-Spalte. Bitte führe die Migration aus: /database/migrations/browser/migrate-vendor-reward-import.html';
    } elseif (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        $errorDetails = 'Fehlende Datenbank-Tabelle. Bitte führe die Migration aus: /database/migrations/browser/migrate-vendor-reward-import.html';
    }
    
    echo json_encode([
        'success' => false, 
        'error' => $errorDetails,
        'debug_info' => $_SESSION['role'] === 'admin' ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    error_log('Marketplace Import Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

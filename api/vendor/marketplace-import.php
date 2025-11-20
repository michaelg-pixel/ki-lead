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
    
    // Log für Debugging
    error_log("Import Request - User: $user_id, Template: $template_id, Freebie: " . ($freebie_id ?? 'NULL'));
    
    // Transaction starten
    $pdo->beginTransaction();
    
    try {
        // 1. Prüfe ob Template existiert und veröffentlicht ist
        $stmt = $pdo->prepare("
            SELECT vrt.*, u.email as vendor_email 
            FROM vendor_reward_templates vrt
            JOIN users u ON vrt.vendor_id = u.id
            WHERE vrt.id = ? AND vrt.is_published = 1
        ");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception('Template nicht gefunden oder nicht veröffentlicht');
        }
        
        error_log("Template gefunden: " . $template['template_name']);
        
        // 2. Prüfe ob bereits importiert - SAFE VERSION mit Tabellen-Check
        try {
            $checkStmt = $pdo->prepare("
                SELECT id FROM reward_template_imports 
                WHERE template_id = ? AND customer_id = ?
            ");
            $checkStmt->execute([$template_id, $user_id]);
            if ($checkStmt->fetch()) {
                throw new Exception('Template wurde bereits importiert');
            }
        } catch (PDOException $e) {
            // Wenn Tabelle nicht existiert, ignorieren wir das hier
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                throw $e;
            }
            error_log("reward_template_imports Tabelle existiert nicht - wird übersprungen");
        }
        
        // 3. Bereite Daten vor
        $insertData = [
            'user_id' => $user_id,
            'freebie_id' => $freebie_id,
            'imported_from_template_id' => $template_id,
            'is_imported' => 1,
            'tier_level' => $template['suggested_tier_level'] ?? 1,
            'tier_name' => $template['template_name'] ?? 'Importierte Belohnung',
            'tier_description' => $template['template_description'] ?? null,
            'required_referrals' => $template['suggested_referrals_required'] ?? 3,
            'reward_type' => $template['reward_type'] ?? 'other',
            'reward_title' => $template['reward_title'] ?? $template['template_name'],
            'reward_description' => $template['reward_description'] ?? null,
            'reward_value' => $template['reward_value'] ?? null,
            'reward_delivery_type' => $template['reward_delivery_type'] ?? 'manual',
            'reward_instructions' => $template['reward_instructions'] ?? null,
            'reward_download_url' => $template['reward_download_url'] ?? null,
            'reward_icon' => $template['reward_icon'] ?? '🎁',
            'reward_color' => $template['reward_color'] ?? '#667eea'
        ];
        
        error_log("Insert Data prepared: " . json_encode($insertData));
        
        // 4. Kopiere Template in reward_definitions
        $sql = "INSERT INTO reward_definitions (
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
            created_at
        ) VALUES (
            :user_id,
            :freebie_id,
            :imported_from_template_id,
            :is_imported,
            :tier_level,
            :tier_name,
            :tier_description,
            :required_referrals,
            :reward_type,
            :reward_title,
            :reward_description,
            :reward_value,
            :reward_delivery_type,
            :reward_instructions,
            :reward_download_url,
            :reward_icon,
            :reward_color,
            1,
            NOW()
        )";
        
        $insertStmt = $pdo->prepare($sql);
        
        // Bind Parameters
        foreach ($insertData as $key => $value) {
            $insertStmt->bindValue(":$key", $value);
        }
        
        $success = $insertStmt->execute();
        
        if (!$success) {
            $errorInfo = $insertStmt->errorInfo();
            error_log("Insert Error: " . json_encode($errorInfo));
            throw new Exception('Fehler beim Einfügen: ' . ($errorInfo[2] ?? 'Unbekannter Fehler'));
        }
        
        $reward_definition_id = $pdo->lastInsertId();
        
        if (!$reward_definition_id) {
            throw new Exception('Keine ID nach Insert erhalten');
        }
        
        error_log("Reward Definition created with ID: $reward_definition_id");
        
        // 5. Log Import in reward_template_imports (SAFE mit Try-Catch)
        try {
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
            error_log("Import logged successfully");
        } catch (PDOException $e) {
            // Wenn Tabelle nicht existiert, loggen aber nicht abbrechen
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log("WARNUNG: reward_template_imports Tabelle existiert nicht. Migration erforderlich!");
                error_log("Bitte führe aus: php database/migrate_reward_template_imports.php");
            } else {
                throw $e; // Andere Fehler weiterwerfen
            }
        }
        
        // 6. Update Counter in vendor_reward_templates
        $updateStmt = $pdo->prepare("
            UPDATE vendor_reward_templates 
            SET times_imported = times_imported + 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$template_id]);
        
        error_log("Template counter updated");
        
        $pdo->commit();
        
        error_log("Transaction committed successfully");
        
        echo json_encode([
            'success' => true,
            'message' => 'Belohnung erfolgreich importiert',
            'reward_definition_id' => $reward_definition_id,
            'freebie_id' => $freebie_id,
            'template_name' => $template['template_name']
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Marketplace Import PDO Error: ' . $e->getMessage());
    error_log('Error Code: ' . $e->getCode());
    error_log('SQL State: ' . ($e->errorInfo[0] ?? 'N/A'));
    error_log('Stack Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    
    // Gib detaillierte Fehlermeldung
    $errorDetails = 'Datenbankfehler beim Import';
    $debugInfo = null;
    
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $matches);
        $missingColumn = $matches[1] ?? 'unbekannt';
        $errorDetails = "Fehlende Datenbank-Spalte: '$missingColumn'";
        $debugInfo = $e->getMessage();
    } elseif (strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
        preg_match("/Table '([^']+)'/", $e->getMessage(), $matches);
        $missingTable = $matches[1] ?? 'unbekannt';
        $errorDetails = "Fehlende Datenbank-Tabelle: '$missingTable'";
        
        if (strpos($missingTable, 'reward_template_imports') !== false) {
            $errorDetails .= " - Bitte Migration ausführen: php database/migrate_reward_template_imports.php";
        }
        
        $debugInfo = $e->getMessage();
    } else {
        $debugInfo = $e->getMessage();
    }
    
    echo json_encode([
        'success' => false, 
        'error' => $errorDetails,
        'debug_info' => $_SESSION['role'] === 'admin' ? $debugInfo : null,
        'sql_state' => $e->errorInfo[0] ?? null,
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log('Marketplace Import Error: ' . $e->getMessage());
    error_log('Stack Trace: ' . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'trace' => $_SESSION['role'] === 'admin' ? $e->getTraceAsString() : null
    ]);
}

<?php
/**
 * Migration Executor: Fix reward_delivery_type Column
 * Ändert die Spalte von ENUM zu VARCHAR(50)
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $output = [];
    
    $output[] = "Starting migration: Fix reward_delivery_type column...";
    $output[] = "";
    
    // Check current column type
    $stmt = $pdo->query("SHOW COLUMNS FROM vendor_reward_templates LIKE 'reward_delivery_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $output[] = "Current column type: {$column['Type']}";
    } else {
        throw new Exception("Column 'reward_delivery_type' not found!");
    }
    
    // Alter column to VARCHAR(50) to allow all values
    $sql = "ALTER TABLE vendor_reward_templates 
            MODIFY COLUMN reward_delivery_type VARCHAR(50) DEFAULT 'manual'";
    
    $pdo->exec($sql);
    
    $output[] = "✓ Column reward_delivery_type changed to VARCHAR(50)";
    $output[] = "";
    
    // Verify change
    $stmt = $pdo->query("SHOW COLUMNS FROM vendor_reward_templates LIKE 'reward_delivery_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $output[] = "New column type: {$column['Type']}";
    $output[] = "";
    $output[] = "✓ Migration completed successfully!";
    $output[] = "";
    $output[] = "You can now update templates without the 'Data truncated' error.";
    
    echo json_encode([
        'success' => true,
        'output' => implode("\n", $output)
    ]);
    
} catch (PDOException $e) {
    error_log('Migration Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Migration Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
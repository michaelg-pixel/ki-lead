<?php
/**
 * CHECK TABLE STRUCTURE
 */
require_once '../config/database.php';

echo "=== TABLE STRUCTURE CHECK ===\n\n";

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total Columns: " . count($columns) . "\n\n";
    
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

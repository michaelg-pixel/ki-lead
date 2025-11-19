<?php
require_once '../config/database.php';

echo "=== CHECK ENUM VALUES ===\n\n";

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field = 'freebie_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($col) {
        echo "Column: freebie_type\n";
        echo "Type: " . $col['Type'] . "\n\n";
        
        // Parse ENUM values
        preg_match("/^enum\((.+)\)$/", $col['Type'], $matches);
        if (!empty($matches[1])) {
            $values = str_getcsv($matches[1], ',', "'");
            echo "Allowed values:\n";
            foreach ($values as $value) {
                echo "  - '$value'\n";
            }
        }
    }
    
    // Check what the source freebie has
    echo "\n=== SOURCE FREEBIE (ID: 7) ===\n";
    $stmt = $pdo->prepare("SELECT id, freebie_type FROM customer_freebies WHERE id = 7");
    $stmt->execute();
    $source = $stmt->fetch();
    
    if ($source) {
        echo "freebie_type: '" . $source['freebie_type'] . "'\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

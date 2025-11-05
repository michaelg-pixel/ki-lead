<?php
/**
 * Migration Script: Add Font Fields to customer_freebies Table
 * 
 * This script adds the following fields to the customer_freebies table:
 * - font_heading: Stores the heading font (websafe or Google Font)
 * - font_body: Stores the body font (websafe or Google Font)
 * - font_size: Stores the font size preference (small, medium, large)
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🚀 Starting migration: Add font fields to customer_freebies\n";
    echo "================================================\n\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'font_heading'");
    $fontHeadingExists = $stmt->fetch();
    
    if ($fontHeadingExists) {
        echo "⚠️ Font fields already exist. Skipping migration.\n";
        exit(0);
    }
    
    // Add font_heading column
    echo "➡️ Adding font_heading column...\n";
    $pdo->exec("
        ALTER TABLE customer_freebies 
        ADD COLUMN font_heading VARCHAR(100) DEFAULT 'Inter' AFTER cta_animation
    ");
    echo "✅ font_heading column added successfully\n\n";
    
    // Add font_body column
    echo "➡️ Adding font_body column...\n";
    $pdo->exec("
        ALTER TABLE customer_freebies 
        ADD COLUMN font_body VARCHAR(100) DEFAULT 'Inter' AFTER font_heading
    ");
    echo "✅ font_body column added successfully\n\n";
    
    // Add font_size column
    echo "➡️ Adding font_size column...\n";
    $pdo->exec("
        ALTER TABLE customer_freebies 
        ADD COLUMN font_size VARCHAR(20) DEFAULT 'medium' AFTER font_body
    ");
    echo "✅ font_size column added successfully\n\n";
    
    echo "================================================\n";
    echo "✨ Migration completed successfully!\n\n";
    
    echo "📊 Summary:\n";
    echo "  - font_heading: VARCHAR(100) - Default 'Inter'\n";
    echo "  - font_body: VARCHAR(100) - Default 'Inter'\n";
    echo "  - font_size: VARCHAR(20) - Default 'medium'\n\n";
    
    echo "🎉 You can now use custom fonts in the customer freebie editor!\n";
    
} catch (PDOException $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>

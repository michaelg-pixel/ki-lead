<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = getDBConnection();

// Check freebies table structure
echo "=== FREEBIES TABLE STRUCTURE ===\n\n";
$stmt = $pdo->query("DESCRIBE freebies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Check actual data for template ID 22
echo "\n\n=== TEMPLATE ID 22 DATA ===\n\n";
$stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = 22");
$stmt->execute();
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if ($template) {
    echo "ID: " . $template['id'] . "\n";
    echo "Name: " . ($template['name'] ?? 'NULL') . "\n";
    echo "Headline: " . ($template['headline'] ?? 'NULL') . "\n";
    echo "Subheadline: " . ($template['subheadline'] ?? 'NULL') . "\n";
    echo "Preheadline: " . ($template['preheadline'] ?? 'NULL') . "\n";
    echo "Bullet Points: " . ($template['bullet_points'] ?? 'NULL') . "\n";
    echo "CTA Text: " . ($template['cta_text'] ?? 'NULL') . "\n";
    echo "Mockup URL: " . ($template['mockup_image_url'] ?? 'NULL') . "\n";
    echo "Layout: " . ($template['layout'] ?? 'NULL') . "\n";
    echo "Primary Color: " . ($template['primary_color'] ?? 'NULL') . "\n";
    echo "Background Color: " . ($template['background_color'] ?? 'NULL') . "\n";
    
    echo "\n=== FONT SETTINGS ===\n";
    echo "Headline Font: " . ($template['headline_font'] ?? 'NULL') . "\n";
    echo "Headline Size: " . ($template['headline_size'] ?? 'NULL') . "\n";
    echo "Preheadline Font: " . ($template['preheadline_font'] ?? 'NULL') . "\n";
    echo "Preheadline Size: " . ($template['preheadline_size'] ?? 'NULL') . "\n";
    echo "Subheadline Font: " . ($template['subheadline_font'] ?? 'NULL') . "\n";
    echo "Subheadline Size: " . ($template['subheadline_size'] ?? 'NULL') . "\n";
    echo "Bulletpoints Font: " . ($template['bulletpoints_font'] ?? 'NULL') . "\n";
    echo "Bulletpoints Size: " . ($template['bulletpoints_size'] ?? 'NULL') . "\n";
} else {
    echo "Template with ID 22 not found\n";
}

// Check if customer freebie exists
echo "\n\n=== CUSTOMER FREEBIE DATA ===\n\n";
$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE template_id = 22");
$stmt->execute();
$customer_freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if ($customer_freebie) {
    echo "Customer Freebie exists for template 22\n";
    echo "Customer ID: " . $customer_freebie['customer_id'] . "\n";
    echo "Headline: " . ($customer_freebie['headline'] ?? 'NULL') . "\n";
} else {
    echo "No customer freebie found for template 22\n";
}

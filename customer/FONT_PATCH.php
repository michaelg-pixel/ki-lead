<?php
/**
 * PATCH: Customer Freebie Editor - Font-Einstellungen korrekt speichern
 * 
 * Diese Datei enthält die korrigierten SQL-Queries für customer/freebie-editor.php
 * Ersetze die bestehenden UPDATE und INSERT Queries mit diesen Versionen.
 */

// ===== KORRIGIERTE UPDATE QUERY =====
// Ersetze die UPDATE Query in Zeile ~55-65 mit dieser Version:

$stmt = $pdo->prepare("
    UPDATE customer_freebies SET
        headline = ?, 
        subheadline = ?, 
        preheadline = ?,
        bullet_points = ?, 
        cta_text = ?, 
        layout = ?,
        background_color = ?, 
        primary_color = ?, 
        raw_code = ?,
        mockup_image_url = ?,
        preheadline_font = ?,
        preheadline_size = ?,
        headline_font = ?,
        headline_size = ?,
        subheadline_font = ?,
        subheadline_size = ?,
        bulletpoints_font = ?,
        bulletpoints_size = ?,
        updated_at = NOW()
    WHERE id = ?
");

$stmt->execute([
    $headline, 
    $subheadline, 
    $preheadline,
    $bullet_points, 
    $cta_text, 
    $layout,
    $background_color, 
    $primary_color, 
    $raw_code,
    $mockup_image_url,
    // Font-Einstellungen aus Template (bleiben für Customer-Freebie gleich)
    $template['preheadline_font'] ?? 'Poppins',
    $template['preheadline_size'] ?? 14,
    $template['headline_font'] ?? 'Poppins',
    $template['headline_size'] ?? 48,
    $template['subheadline_font'] ?? 'Poppins',
    $template['subheadline_size'] ?? 20,
    $template['bulletpoints_font'] ?? 'Poppins',
    $template['bulletpoints_size'] ?? 16,
    $customer_freebie['id']
]);

// ===== KORRIGIERTE INSERT QUERY =====
// Ersetze die INSERT Query in Zeile ~70-85 mit dieser Version:

$stmt = $pdo->prepare("
    INSERT INTO customer_freebies (
        customer_id, 
        template_id, 
        headline, 
        subheadline, 
        preheadline,
        bullet_points, 
        cta_text, 
        layout, 
        background_color, 
        primary_color,
        raw_code, 
        unique_id, 
        url_slug, 
        mockup_image_url, 
        preheadline_font,
        preheadline_size,
        headline_font,
        headline_size,
        subheadline_font,
        subheadline_size,
        bulletpoints_font,
        bulletpoints_size,
        freebie_type, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'template', NOW())
");

$stmt->execute([
    $customer_id, 
    $template_id, 
    $headline, 
    $subheadline, 
    $preheadline,
    $bullet_points, 
    $cta_text, 
    $layout, 
    $background_color, 
    $primary_color,
    $raw_code, 
    $unique_id, 
    $url_slug, 
    $template['mockup_image_url'],
    // Font-Einstellungen aus Template kopieren
    $template['preheadline_font'] ?? 'Poppins',
    $template['preheadline_size'] ?? 14,
    $template['headline_font'] ?? 'Poppins',
    $template['headline_size'] ?? 48,
    $template['subheadline_font'] ?? 'Poppins',
    $template['subheadline_size'] ?? 20,
    $template['bulletpoints_font'] ?? 'Poppins',
    $template['bulletpoints_size'] ?? 16
]);

?>

<?php
session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $customer_id = $_SESSION['user_id'];
    
    // POST-Daten empfangen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['freebie_id'])) {
        throw new Exception('Freebie-ID fehlt');
    }
    
    $source_freebie_id = (int)$input['freebie_id'];
    
    // Original-Freebie laden
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND marketplace_enabled = 1
    ");
    $stmt->execute([$source_freebie_id]);
    $source_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source_freebie) {
        throw new Exception('Freebie nicht gefunden oder nicht im Marktplatz verfügbar');
    }
    
    // Prüfen, ob es das eigene Freebie ist
    if ($source_freebie['customer_id'] == $customer_id) {
        throw new Exception('Du kannst dein eigenes Freebie nicht kaufen');
    }
    
    // Prüfen, ob bereits gekauft
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE customer_id = ? AND copied_from_freebie_id = ?
    ");
    $stmt->execute([$customer_id, $source_freebie_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Du hast dieses Freebie bereits gekauft');
    }
    
    // Neues unique_id generieren
    $unique_id = bin2hex(random_bytes(16));
    
    // Domain für Links
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $domain = $_SERVER['HTTP_HOST'];
    
    // Freebie kopieren
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id,
            template_id,
            freebie_type,
            headline,
            subheadline,
            preheadline,
            mockup_image_url,
            background_color,
            primary_color,
            cta_text,
            bullet_points,
            layout,
            email_field_text,
            button_text,
            privacy_checkbox_text,
            thank_you_headline,
            thank_you_message,
            email_provider,
            email_api_key,
            email_list_id,
            course_id,
            unique_id,
            niche,
            original_creator_id,
            copied_from_freebie_id,
            marketplace_enabled
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $customer_id,
        $source_freebie['template_id'],
        'purchased', // Markieren als gekauft
        $source_freebie['headline'],
        $source_freebie['subheadline'],
        $source_freebie['preheadline'],
        $source_freebie['mockup_image_url'],
        $source_freebie['background_color'],
        $source_freebie['primary_color'],
        $source_freebie['cta_text'],
        $source_freebie['bullet_points'],
        $source_freebie['layout'],
        $source_freebie['email_field_text'],
        $source_freebie['button_text'],
        $source_freebie['privacy_checkbox_text'],
        $source_freebie['thank_you_headline'],
        $source_freebie['thank_you_message'],
        null, // Email-Provider zurücksetzen (muss neu konfiguriert werden)
        null, // API-Key zurücksetzen
        null, // List-ID zurücksetzen
        $source_freebie['course_id'],
        $unique_id,
        $source_freebie['niche'],
        $source_freebie['customer_id'], // Original-Ersteller
        $source_freebie_id, // Original-Freebie
        0 // Nicht automatisch im Marktplatz aktivieren
    ]);
    
    $new_freebie_id = $pdo->lastInsertId();
    
    // Verkaufszähler beim Original erhöhen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_sales_count = marketplace_sales_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$source_freebie_id]);
    
    // Neue Links generieren
    $freebie_link = $protocol . '://' . $domain . '/freebie/index.php?id=' . $unique_id;
    $thankyou_link = $protocol . '://' . $domain . '/freebie/thankyou.php?id=' . $new_freebie_id . '&customer=' . $customer_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Freebie erfolgreich kopiert',
        'freebie_id' => $new_freebie_id,
        'freebie_link' => $freebie_link,
        'thankyou_link' => $thankyou_link
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
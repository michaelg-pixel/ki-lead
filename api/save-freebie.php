<?php
// KEINE Leerzeichen vor diesem <?php Tag!
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Setze Header SOFORT
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'error' => 'Unbekannter Fehler'];

try {
    // Admin-Check
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Keine Berechtigung');
    }
    
    // JSON-Daten einlesen
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Ungültige JSON-Daten: ' . json_last_error_msg());
    }
    
    // Datenbankverbindung
    $db_path = __DIR__ . '/../config/database.php';
    if (!file_exists($db_path)) {
        throw new Exception('Datenbank-Konfiguration nicht gefunden');
    }
    
    require_once $db_path;
    
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Validierung
    if (empty($data['name'])) {
        throw new Exception('Template-Name ist erforderlich');
    }
    
    if (empty($data['headline'])) {
        throw new Exception('Headline ist erforderlich');
    }
    
    // Basis-Werte
    $name = trim($data['name']);
    $headline = trim($data['headline']);
    $subheadline = trim($data['subheadline'] ?? '');
    $preheadline = trim($data['preheadline'] ?? '');
    $description = trim($data['description'] ?? '');
    
    // Layout-Mapping
    $layoutMapping = [
        'hybrid' => 'layout1',
        'centered' => 'layout2',
        'sidebar' => 'layout3'
    ];
    $layout = $data['layout'] ?? 'hybrid';
    $layout = $layoutMapping[$layout] ?? 'layout1';
    
    // Bulletpoints und CTA
    $bullet_points = trim($data['bulletpoints'] ?? '');
    $cta_text = trim($data['cta_button_text'] ?? 'Jetzt kostenlos sichern');
    
    // Farben
    $primary_color = $data['primary_color'] ?? '#7C3AED';
    $secondary_color = $data['secondary_color'] ?? '#EC4899';
    $background_color = $data['background_color'] ?? '#FFFFFF';
    $text_color = $data['text_color'] ?? '#1F2937';
    $cta_button_color = $data['cta_button_color'] ?? '#5B8DEF';
    
    // Weitere Felder
    $mockup_image_url = trim($data['mockup_image_url'] ?? '');
    $url_slug = !empty($data['url_slug']) ? trim($data['url_slug']) : generateSlug($name);
    $raw_code = trim($data['custom_raw_code'] ?? '');
    $custom_css = trim($data['custom_css'] ?? '');
    
    // WICHTIG: course_id als NULL wenn nicht ausgewählt (wegen Foreign Key)
    $course_id = (!empty($data['course_id']) && (int)$data['course_id'] > 0) ? (int)$data['course_id'] : null;
    $linked_course_id = $course_id; // Beide Felder gleich setzen
    
    // customer_id als NULL für Master-Templates
    $customer_id = null;
    $unique_id = 'master_' . uniqid();
    
    // Fonts
    $heading_font = $data['heading_font'] ?? 'Inter';
    $body_font = $data['body_font'] ?? 'Inter';
    
    // Optional Felder
    $pixel_code = trim($data['pixel_code'] ?? '');
    $optin_placeholder_email = $data['optin_placeholder_email'] ?? 'Deine E-Mail-Adresse';
    $optin_button_text = $data['optin_button_text'] ?? 'KOSTENLOS DOWNLOADEN';
    $optin_privacy_text = trim($data['optin_privacy_text'] ?? '');
    $show_footer = isset($data['show_footer']) ? (int)$data['show_footer'] : 1;
    $footer_links = trim($data['footer_links'] ?? '');
    $allow_customer_image = isset($data['allow_customer_image']) ? (int)$data['allow_customer_image'] : 1;
    
    // Template ID für Update
    $template_id = !empty($data['template_id']) ? (int)$data['template_id'] : null;
    
    if ($template_id) {
        // UPDATE
        $sql = "UPDATE freebies SET
            name = ?,
            headline = ?,
            subheadline = ?,
            preheadline = ?,
            description = ?,
            bullet_points = ?,
            cta_text = ?,
            layout = ?,
            primary_color = ?,
            secondary_color = ?,
            background_color = ?,
            text_color = ?,
            cta_button_color = ?,
            mockup_image_url = ?,
            url_slug = ?,
            raw_code = ?,
            custom_css = ?,
            course_id = ?,
            linked_course_id = ?,
            heading_font = ?,
            body_font = ?,
            pixel_code = ?,
            optin_placeholder_email = ?,
            optin_button_text = ?,
            optin_privacy_text = ?,
            show_footer = ?,
            footer_links = ?,
            allow_customer_image = ?,
            updated_at = NOW()
        WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $headline,
            $subheadline,
            $preheadline,
            $description,
            $bullet_points,
            $cta_text,
            $layout,
            $primary_color,
            $secondary_color,
            $background_color,
            $text_color,
            $cta_button_color,
            $mockup_image_url,
            $url_slug,
            $raw_code,
            $custom_css,
            $course_id,
            $linked_course_id,
            $heading_font,
            $body_font,
            $pixel_code,
            $optin_placeholder_email,
            $optin_button_text,
            $optin_privacy_text,
            $show_footer,
            $footer_links,
            $allow_customer_image,
            $template_id
        ]);
        
        $response = [
            'success' => true,
            'message' => 'Template erfolgreich aktualisiert',
            'template_id' => $template_id
        ];
        
    } else {
        // INSERT
        $sql = "INSERT INTO freebies (
            customer_id,
            course_id,
            unique_id,
            name,
            headline,
            subheadline,
            preheadline,
            description,
            bullet_points,
            cta_text,
            layout,
            primary_color,
            secondary_color,
            background_color,
            text_color,
            cta_button_color,
            mockup_image_url,
            url_slug,
            raw_code,
            custom_css,
            linked_course_id,
            heading_font,
            body_font,
            pixel_code,
            optin_placeholder_email,
            optin_button_text,
            optin_privacy_text,
            show_footer,
            footer_links,
            allow_customer_image,
            usage_count,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $customer_id,
            $course_id,
            $unique_id,
            $name,
            $headline,
            $subheadline,
            $preheadline,
            $description,
            $bullet_points,
            $cta_text,
            $layout,
            $primary_color,
            $secondary_color,
            $background_color,
            $text_color,
            $cta_button_color,
            $mockup_image_url,
            $url_slug,
            $raw_code,
            $custom_css,
            $linked_course_id,
            $heading_font,
            $body_font,
            $pixel_code,
            $optin_placeholder_email,
            $optin_button_text,
            $optin_privacy_text,
            $show_footer,
            $footer_links,
            $allow_customer_image
        ]);
        
        $template_id = $pdo->lastInsertId();
        
        $response = [
            'success' => true,
            'message' => 'Template erfolgreich erstellt',
            'template_id' => $template_id,
            'unique_id' => $unique_id
        ];
    }
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// JSON ausgeben - NICHTS danach!
echo json_encode($response);
exit;

// Hilfsfunktion: Slug generieren
function generateSlug($text) {
    $search = ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'];
    $replace = ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'];
    $text = str_replace($search, $replace, $text);
    
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}
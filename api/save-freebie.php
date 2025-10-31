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
    
    // Daten verarbeiten - JSON oder FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $data = [];
    
    if (strpos($contentType, 'application/json') !== false) {
        $raw_input = file_get_contents('php://input');
        if (empty($raw_input)) {
            throw new Exception('Keine Daten empfangen');
        }
        
        $data = json_decode($raw_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ungültige JSON-Daten: ' . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            throw new Exception('JSON-Daten sind kein Array');
        }
    } else {
        $data = $_POST;
    }
    
    // Validierung
    if (!isset($data['name']) || trim($data['name']) === '') {
        throw new Exception('Template-Name ist erforderlich');
    }
    
    if (!isset($data['headline']) || trim($data['headline']) === '') {
        throw new Exception('Headline ist erforderlich');
    }
    
    // Mockup-Image verarbeiten
    $mockup_image_url = $data['mockup_image_url'] ?? '';
    
    // Base64-Upload verarbeiten
    if (!empty($data['mockup_image_base64'])) {
        $base64_data = $data['mockup_image_base64'];
        
        if (strpos($base64_data, 'base64,') !== false) {
            $base64_data = explode('base64,', $base64_data)[1];
        }
        
        $image_data = base64_decode($base64_data);
        
        if ($image_data === false) {
            throw new Exception('Ungültige Base64-Daten');
        }
        
        $upload_dir = __DIR__ . '/../uploads/freebies';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = 'png';
        if (preg_match('/data:image\/(\w+);base64/', $data['mockup_image_base64'], $matches)) {
            $file_extension = $matches[1];
            if ($file_extension === 'jpeg') $file_extension = 'jpg';
        }
        
        $new_filename = 'mockup_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . '/' . $new_filename;
        
        if (file_put_contents($target_path, $image_data) === false) {
            throw new Exception('Fehler beim Speichern der Datei');
        }
        
        $mockup_image_url = '/uploads/freebies/' . $new_filename;
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
    
    // Basis-Werte extrahieren
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
    
    // Typografie - Neue Felder
    $headline_font = $data['headline_font'] ?? 'Inter';
    $headline_size = (int)($data['headline_size'] ?? 48);
    $headline_size_mobile = (int)($data['headline_size_mobile'] ?? 32);
    $body_font = $data['body_font'] ?? 'Inter';
    $body_size = (int)($data['body_size'] ?? 16);
    $body_size_mobile = (int)($data['body_size_mobile'] ?? 14);
    
    // Legacy Font-Einstellungen (für Kompatibilität)
    $preheadline_font = $data['preheadline_font'] ?? $headline_font;
    $preheadline_size = (int)($data['preheadline_size'] ?? 14);
    $subheadline_font = $data['subheadline_font'] ?? $body_font;
    $subheadline_size = (int)($data['subheadline_size'] ?? 20);
    $bulletpoints_font = $data['bulletpoints_font'] ?? $body_font;
    $bulletpoints_size = (int)($data['bulletpoints_size'] ?? $body_size);
    
    // Weitere Felder
    $url_slug = !empty($data['url_slug']) ? trim($data['url_slug']) : generateSlug($name);
    $raw_code = trim($data['custom_raw_code'] ?? '');
    $custom_css = trim($data['custom_css'] ?? '');
    $email_optin_code = trim($data['email_optin_code'] ?? '');
    
    $course_id = (!empty($data['course_id']) && (int)$data['course_id'] > 0) ? (int)$data['course_id'] : null;
    $linked_course_id = $course_id;
    
    $customer_id = null;
    $unique_id = 'master_' . uniqid();
    
    $heading_font = $headline_font; // Alias für Kompatibilität
    
    $pixel_code = trim($data['pixel_code'] ?? '');
    $optin_placeholder_email = $data['optin_placeholder_email'] ?? 'Deine E-Mail-Adresse';
    $optin_button_text = $data['optin_button_text'] ?? 'KOSTENLOS DOWNLOADEN';
    $optin_privacy_text = trim($data['optin_privacy_text'] ?? '');
    
    $show_mockup = isset($data['show_mockup']) ? (int)$data['show_mockup'] : 1;
    $show_footer = isset($data['show_footer']) ? (int)$data['show_footer'] : 1;
    $footer_links = trim($data['footer_links'] ?? '');
    $allow_customer_image = isset($data['allow_customer_image']) ? (int)$data['allow_customer_image'] : 1;
    $is_master_template = isset($data['is_master_template']) ? (int)$data['is_master_template'] : 1;
    
    $template_id = !empty($data['template_id']) ? (int)$data['template_id'] : null;
    
    if ($template_id) {
        // UPDATE
        if (empty($mockup_image_url)) {
            $stmt = $pdo->prepare("SELECT mockup_image_url FROM freebies WHERE id = ?");
            $stmt->execute([$template_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $mockup_image_url = $existing['mockup_image_url'] ?? '';
        } else {
            $stmt = $pdo->prepare("SELECT mockup_image_url FROM freebies WHERE id = ?");
            $stmt->execute([$template_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($existing['mockup_image_url'])) {
                $old_file = __DIR__ . '/..' . $existing['mockup_image_url'];
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
            }
        }
        
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
            headline_font = ?,
            headline_size = ?,
            headline_size_mobile = ?,
            body_size = ?,
            body_size_mobile = ?,
            preheadline_font = ?,
            preheadline_size = ?,
            subheadline_font = ?,
            subheadline_size = ?,
            bulletpoints_font = ?,
            bulletpoints_size = ?,
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
            $headline_font,
            $headline_size,
            $headline_size_mobile,
            $body_size,
            $body_size_mobile,
            $preheadline_font,
            $preheadline_size,
            $subheadline_font,
            $subheadline_size,
            $bulletpoints_font,
            $bulletpoints_size,
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
            'template_id' => $template_id,
            'mockup_image_url' => $mockup_image_url
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
            headline_font,
            headline_size,
            headline_size_mobile,
            body_size,
            body_size_mobile,
            preheadline_font,
            preheadline_size,
            subheadline_font,
            subheadline_size,
            bulletpoints_font,
            bulletpoints_size,
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())";
        
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
            $headline_font,
            $headline_size,
            $headline_size_mobile,
            $body_size,
            $body_size_mobile,
            $preheadline_font,
            $preheadline_size,
            $subheadline_font,
            $subheadline_size,
            $bulletpoints_font,
            $bulletpoints_size,
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
            'unique_id' => $unique_id,
            'mockup_image_url' => $mockup_image_url
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

echo json_encode($response);
exit;

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

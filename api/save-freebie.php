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
    
    // DEBUG: Rohe Eingabedaten loggen
    $raw_input = file_get_contents('php://input');
    error_log("RAW INPUT: " . $raw_input);
    error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log("POST DATA: " . print_r($_POST, true));
    
    // Daten verarbeiten - JSON oder FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $data = [];
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON-Daten
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
        
        error_log("DECODED JSON DATA: " . print_r($data, true));
    } else {
        // FormData
        $data = $_POST;
        error_log("USING POST DATA");
    }
    
    // DEBUG: Prüfe ob 'name' vorhanden ist
    error_log("DATA['name'] exists: " . (isset($data['name']) ? 'YES' : 'NO'));
    error_log("DATA['name'] value: " . ($data['name'] ?? 'NOT SET'));
    error_log("DATA keys: " . implode(', ', array_keys($data)));
    
    // Validierung
    if (!isset($data['name']) || trim($data['name']) === '') {
        throw new Exception('Template-Name ist erforderlich (Feld ist leer oder nicht vorhanden)');
    }
    
    if (!isset($data['headline']) || trim($data['headline']) === '') {
        throw new Exception('Headline ist erforderlich (Feld ist leer oder nicht vorhanden)');
    }
    
    // Mockup-Image verarbeiten
    $mockup_image_url = $data['mockup_image_url'] ?? '';
    
    // Base64-Upload verarbeiten
    if (!empty($data['mockup_image_base64'])) {
        $base64_data = $data['mockup_image_base64'];
        
        // Base64-Header entfernen (data:image/png;base64,...)
        if (strpos($base64_data, 'base64,') !== false) {
            $base64_data = explode('base64,', $base64_data)[1];
        }
        
        // Dekodieren
        $image_data = base64_decode($base64_data);
        
        if ($image_data === false) {
            throw new Exception('Ungültige Base64-Daten');
        }
        
        // Upload-Verzeichnis erstellen
        $upload_dir = __DIR__ . '/../uploads/freebies';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Dateityp aus Base64-Header ermitteln oder aus Image-Data
        $file_extension = 'png'; // Default
        if (preg_match('/data:image\/(\w+);base64/', $data['mockup_image_base64'], $matches)) {
            $file_extension = $matches[1];
            if ($file_extension === 'jpeg') $file_extension = 'jpg';
        } else {
            // Versuche Typ aus Binärdaten zu erkennen
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->buffer($image_data);
            $ext_map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $file_extension = $ext_map[$mime_type] ?? 'png';
        }
        
        // Eindeutigen Dateinamen generieren
        $new_filename = 'mockup_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . '/' . $new_filename;
        
        // Datei speichern
        if (file_put_contents($target_path, $image_data) === false) {
            throw new Exception('Fehler beim Speichern der Datei');
        }
        
        // URL für Datenbank
        $mockup_image_url = '/uploads/freebies/' . $new_filename;
    }
    // Fallback: File-Upload über $_FILES (FormData)
    elseif (isset($_FILES['mockup_image']) && $_FILES['mockup_image']['error'] === UPLOAD_ERR_OK) {
        // Upload-Verzeichnis erstellen falls nicht vorhanden
        $upload_dir = __DIR__ . '/../uploads/freebies';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Datei-Informationen
        $file = $_FILES['mockup_image'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validierung
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Ungültiges Dateiformat. Erlaubt: JPG, PNG, GIF, WEBP');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('Datei zu groß. Maximal 5MB erlaubt.');
        }
        
        // Eindeutigen Dateinamen generieren
        $new_filename = 'mockup_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . '/' . $new_filename;
        
        // Datei verschieben
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // URL für Datenbank
            $mockup_image_url = '/uploads/freebies/' . $new_filename;
        } else {
            throw new Exception('Fehler beim Hochladen der Datei');
        }
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
    
    error_log("EXTRACTED NAME: " . $name);
    error_log("EXTRACTED HEADLINE: " . $headline);
    
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
    $url_slug = !empty($data['url_slug']) ? trim($data['url_slug']) : generateSlug($name);
    $raw_code = trim($data['custom_raw_code'] ?? '');
    $custom_css = trim($data['custom_css'] ?? '');
    $email_optin_code = trim($data['email_optin_code'] ?? '');
    
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
    
    // Checkbox-Werte richtig verarbeiten
    $show_mockup = isset($data['show_mockup']) ? (int)$data['show_mockup'] : 1;
    $show_footer = isset($data['show_footer']) ? (int)$data['show_footer'] : 1;
    $footer_links = trim($data['footer_links'] ?? '');
    $allow_customer_image = isset($data['allow_customer_image']) ? (int)$data['allow_customer_image'] : 1;
    $is_master_template = isset($data['is_master_template']) ? (int)$data['is_master_template'] : 1;
    
    // Template ID für Update
    $template_id = !empty($data['template_id']) ? (int)$data['template_id'] : null;
    
    if ($template_id) {
        // UPDATE
        error_log("UPDATING TEMPLATE ID: " . $template_id);
        
        // Wenn kein neues Bild hochgeladen wurde, altes behalten
        if (empty($mockup_image_url)) {
            $stmt = $pdo->prepare("SELECT mockup_image_url FROM freebies WHERE id = ?");
            $stmt->execute([$template_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $mockup_image_url = $existing['mockup_image_url'] ?? '';
        } else {
            // Altes Bild löschen wenn neues hochgeladen wurde
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
            'template_id' => $template_id,
            'mockup_image_url' => $mockup_image_url
        ];
        
    } else {
        // INSERT
        error_log("INSERTING NEW TEMPLATE");
        
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
        
        error_log("INSERTED TEMPLATE WITH ID: " . $template_id);
        
        $response = [
            'success' => true,
            'message' => 'Template erfolgreich erstellt',
            'template_id' => $template_id,
            'unique_id' => $unique_id,
            'mockup_image_url' => $mockup_image_url
        ];
    }
    
} catch (PDOException $e) {
    error_log("DATABASE ERROR: " . $e->getMessage());
    $response = [
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ];
} catch (Exception $e) {
    error_log("GENERAL ERROR: " . $e->getMessage());
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

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
$pdo = getDBConnection();

// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../config/fonts.php';

$customer_id = $_SESSION['user_id'];

// JSON-Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Daten']);
    exit;
}

try {
    // Validierung
    $name = $input['name'] ?? '';
    $headline = $input['headline'] ?? '';
    $id = $input['id'] ?? null;
    
    if (empty($name) || empty($headline)) {
        throw new Exception('Name und Headline sind erforderlich');
    }
    
    // Bei neuem Freebie: Limit prüfen
    if (empty($id)) {
        $stmt = $pdo->prepare("SELECT freebie_limit FROM customer_freebie_limits WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $limitData = $stmt->fetch(PDO::FETCH_ASSOC);
        $freebieLimit = $limitData['freebie_limit'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
        $stmt->execute([$customer_id]);
        $customCount = $stmt->fetchColumn();
        
        if ($customCount >= $freebieLimit) {
            throw new Exception('Freebie-Limit erreicht');
        }
    }
    
    // Bullet Points als JSON speichern
    $bulletPoints = $input['bullet_points'] ?? [];
    $bulletPointsJson = json_encode($bulletPoints);
    
    // Font-Einstellungen mit Fallbacks
    $preheadlineFont = $input['preheadline_font'] ?? $fontConfig['defaults']['preheadline_font'];
    $preheadlineSize = (int)($input['preheadline_size'] ?? $fontConfig['defaults']['preheadline_size']);
    $headlineFont = $input['headline_font'] ?? $fontConfig['defaults']['headline_font'];
    $headlineSize = (int)($input['headline_size'] ?? $fontConfig['defaults']['headline_size']);
    $subheadlineFont = $input['subheadline_font'] ?? $fontConfig['defaults']['subheadline_font'];
    $subheadlineSize = (int)($input['subheadline_size'] ?? $fontConfig['defaults']['subheadline_size']);
    $bulletpointsFont = $input['bulletpoints_font'] ?? $fontConfig['defaults']['bulletpoints_font'];
    $bulletpointsSize = (int)($input['bulletpoints_size'] ?? $fontConfig['defaults']['bulletpoints_size']);
    
    // Unique ID generieren (falls neu)
    $uniqueId = $id ? null : 'custom-' . uniqid() . '-' . time();
    
    if ($id) {
        // Bestehendes Freebie aktualisieren
        $stmt = $pdo->prepare("
            UPDATE customer_freebies SET
                name = ?,
                headline = ?,
                subheadline = ?,
                preheadline = ?,
                cta_text = ?,
                email_optin_code = ?,
                thankyou_message = ?,
                background_color = ?,
                primary_color = ?,
                layout = ?,
                bullet_points = ?,
                course_id = ?,
                preheadline_font = ?,
                preheadline_size = ?,
                headline_font = ?,
                headline_size = ?,
                subheadline_font = ?,
                subheadline_size = ?,
                bulletpoints_font = ?,
                bulletpoints_size = ?,
                updated_at = NOW()
            WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
        ");
        
        $stmt->execute([
            $name,
            $headline,
            $input['subheadline'] ?? '',
            $input['preheadline'] ?? '',
            $input['cta_text'] ?? 'Jetzt kostenlos sichern',
            $input['email_optin_code'] ?? '',
            $input['thankyou_message'] ?? '',
            $input['background_color'] ?? '#667eea',
            $input['primary_color'] ?? '#667eea',
            $input['layout'] ?? 'centered',
            $bulletPointsJson,
            !empty($input['course_id']) ? $input['course_id'] : null,
            $preheadlineFont,
            $preheadlineSize,
            $headlineFont,
            $headlineSize,
            $subheadlineFont,
            $subheadlineSize,
            $bulletpointsFont,
            $bulletpointsSize,
            $id,
            $customer_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Freebie aktualisiert',
            'id' => $id
        ]);
        
    } else {
        // Neues Freebie erstellen
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id,
                template_id,
                freebie_type,
                name,
                headline,
                subheadline,
                preheadline,
                cta_text,
                email_optin_code,
                thankyou_message,
                background_color,
                primary_color,
                layout,
                bullet_points,
                course_id,
                preheadline_font,
                preheadline_size,
                headline_font,
                headline_size,
                subheadline_font,
                subheadline_size,
                bulletpoints_font,
                bulletpoints_size,
                unique_id,
                is_active,
                created_at,
                updated_at
            ) VALUES (?, NULL, 'custom', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        
        $stmt->execute([
            $customer_id,
            $name,
            $headline,
            $input['subheadline'] ?? '',
            $input['preheadline'] ?? '',
            $input['cta_text'] ?? 'Jetzt kostenlos sichern',
            $input['email_optin_code'] ?? '',
            $input['thankyou_message'] ?? '',
            $input['background_color'] ?? '#667eea',
            $input['primary_color'] ?? '#667eea',
            $input['layout'] ?? 'centered',
            $bulletPointsJson,
            !empty($input['course_id']) ? $input['course_id'] : null,
            $preheadlineFont,
            $preheadlineSize,
            $headlineFont,
            $headlineSize,
            $subheadlineFont,
            $subheadlineSize,
            $bulletpointsFont,
            $bulletpointsSize,
            $uniqueId
        ]);
        
        $newId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Freebie erstellt',
            'id' => $newId,
            'unique_id' => $uniqueId
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

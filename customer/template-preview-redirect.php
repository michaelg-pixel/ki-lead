<?php
/**
 * Template Vorschau Redirect
 * Erstellt automatisch einen customer_freebies Eintrag für die Vorschau
 * und leitet zum finalen Freebie-Link weiter
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Authentifizierung prüfen
if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

$customer_id = $_SESSION['user_id'];
$template_id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;

if (!$template_id) {
    die('Template ID fehlt');
}

try {
    $pdo = getDBConnection();
    
    // Prüfen ob bereits ein Eintrag existiert
    $stmt = $pdo->prepare("
        SELECT id, unique_id 
        FROM customer_freebies 
        WHERE customer_id = ? AND template_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$customer_id, $template_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Eintrag existiert bereits - direkt weiterleiten
        $unique_id = $existing['unique_id'];
    } else {
        // Noch kein Eintrag - Template-Daten laden
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            die('Template nicht gefunden');
        }
        
        // Neuen Eintrag erstellen
        $unique_id = md5(uniqid($customer_id . '_' . $template_id . '_', true));
        
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id, 
                template_id, 
                unique_id,
                headline,
                subheadline,
                preheadline,
                bullet_points,
                cta_text,
                background_color,
                primary_color,
                layout,
                mockup_image_url,
                freebie_type,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'template', NOW(), NOW())
        ");
        
        $stmt->execute([
            $customer_id,
            $template_id,
            $unique_id,
            $template['headline'] ?? '',
            $template['subheadline'] ?? '',
            $template['preheadline'] ?? '',
            $template['bullet_points'] ?? '',
            $template['cta_text'] ?? 'JETZT KOSTENLOS SICHERN',
            $template['background_color'] ?? '#FFFFFF',
            $template['primary_color'] ?? '#8B5CF6',
            $template['layout'] ?? 'centered',
            $template['mockup_image_url'] ?? ''
        ]);
    }
    
    // Zum finalen Freebie-Link weiterleiten
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $domain = $_SERVER['HTTP_HOST'];
    $freebie_url = $protocol . '://' . $domain . '/freebie/index.php?id=' . $unique_id;
    
    header('Location: ' . $freebie_url);
    exit;
    
} catch (PDOException $e) {
    error_log('Template Preview Redirect Error: ' . $e->getMessage());
    die('Fehler beim Erstellen der Vorschau: ' . $e->getMessage());
}

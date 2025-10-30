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
    
    // Template-ID aus GET
    if (empty($_GET['id'])) {
        throw new Exception('Template-ID fehlt');
    }
    
    $template_id = (int)$_GET['id'];
    
    // Datenbankverbindung
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Template laden
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template nicht gefunden');
    }
    
    // Layout zurück-mappen (layout1 → hybrid etc.)
    $layoutMapping = [
        'layout1' => 'hybrid',
        'layout2' => 'centered',
        'layout3' => 'sidebar'
    ];
    
    if (isset($template['layout']) && isset($layoutMapping[$template['layout']])) {
        $template['layout_display'] = $layoutMapping[$template['layout']];
    }
    
    $response = [
        'success' => true,
        'template' => $template
    ];
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// JSON ausgeben
echo json_encode($response);
exit;
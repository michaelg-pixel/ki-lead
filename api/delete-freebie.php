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
    
    // Template-ID aus POST oder GET
    $template_id = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $template_id = $data['id'] ?? null;
    } elseif (isset($_GET['id'])) {
        $template_id = (int)$_GET['id'];
    }
    
    if (empty($template_id)) {
        throw new Exception('Template-ID fehlt');
    }
    
    $template_id = (int)$template_id;
    
    // Datenbankverbindung - funktioniert mit beiden Varianten
    require_once __DIR__ . '/../config/database.php';
    
    // Prüfe ob getDBConnection() Funktion existiert
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
    } elseif (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Prüfen ob Template existiert
    $stmt = $pdo->prepare("SELECT id, name FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template nicht gefunden');
    }
    
    // Template löschen
    $stmt = $pdo->prepare("DELETE FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    
    $response = [
        'success' => true,
        'message' => 'Template erfolgreich gelöscht',
        'deleted_id' => $template_id,
        'deleted_name' => $template['name']
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
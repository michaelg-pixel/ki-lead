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
    // Login-Check (Admin oder Customer)
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Nicht eingeloggt');
    }
    
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // ID aus GET
    if (empty($_GET['id'])) {
        throw new Exception('ID fehlt');
    }
    
    $id = (int)$_GET['id'];
    
    // Datenbankverbindung
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Unterscheiden zwischen Admin (freebies) und Customer (customer_freebies)
    if ($user_role === 'admin') {
        // Admin lädt aus freebies Tabelle
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
        $stmt->execute([$id]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$freebie) {
            throw new Exception('Template nicht gefunden');
        }
        
        // Layout zurück-mappen (layout1 → hybrid etc.)
        $layoutMapping = [
            'layout1' => 'hybrid',
            'layout2' => 'centered',
            'layout3' => 'sidebar'
        ];
        
        if (isset($freebie['layout']) && isset($layoutMapping[$freebie['layout']])) {
            $freebie['layout_display'] = $layoutMapping[$freebie['layout']];
        }
        
        $response = [
            'success' => true,
            'template' => $freebie
        ];
        
    } elseif ($user_role === 'customer') {
        // Customer lädt aus customer_freebies Tabelle
        $stmt = $pdo->prepare("
            SELECT * FROM customer_freebies 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$freebie) {
            throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
        }
        
        $response = [
            'success' => true,
            'freebie' => $freebie
        ];
        
    } else {
        throw new Exception('Keine Berechtigung');
    }
    
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
<?php
/**
 * API: Link-Kürzung für Freebies
 * Erstellt einen kurzen Link für Freebie oder Danke-Seite
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$response = ['success' => false];

try {
    // Admin-Check
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Keine Berechtigung');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['freebie_id']) || !isset($input['type'])) {
        throw new Exception('Freebie-ID und Typ erforderlich');
    }
    
    $freebie_id = (int)$input['freebie_id'];
    $type = $input['type']; // 'freebie' oder 'thankyou'
    
    if (!in_array($type, ['freebie', 'thankyou'])) {
        throw new Exception('Ungültiger Link-Typ');
    }
    
    // Prüfen ob Freebie existiert
    $stmt = $pdo->prepare("SELECT id FROM freebies WHERE id = ?");
    $stmt->execute([$freebie_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Freebie nicht gefunden');
    }
    
    // Kurz-Code generieren (6 Zeichen)
    $short_code = generateShortCode();
    
    // Prüfen ob Code bereits existiert (sehr unwahrscheinlich)
    $check = $pdo->prepare("SELECT id FROM freebies WHERE short_link = ? OR thank_you_short_link = ?");
    $check->execute([$short_code, $short_code]);
    
    while ($check->fetch()) {
        $short_code = generateShortCode();
        $check->execute([$short_code, $short_code]);
    }
    
    // Short-Link erstellen
    $short_link = '/f/' . $short_code;
    
    // In Datenbank speichern
    if ($type === 'freebie') {
        $update = $pdo->prepare("UPDATE freebies SET short_link = ? WHERE id = ?");
    } else {
        $update = $pdo->prepare("UPDATE freebies SET thank_you_short_link = ? WHERE id = ?");
    }
    
    $update->execute([$short_link, $freebie_id]);
    
    // Vollständige URL für Response
    $domain = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $full_url = $protocol . '://' . $domain . $short_link;
    
    $response = [
        'success' => true,
        'short_link' => $short_link,
        'full_url' => $full_url,
        'short_code' => $short_code
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Generiert einen 6-stelligen alphanumerischen Code
 */
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    return $code;
}

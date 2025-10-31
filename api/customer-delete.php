<?php
/**
 * API: Kunden löschen (permanent)
 */

session_start();
header('Content-Type: application/json');

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Daten aus JSON-Body lesen
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = intval($input['user_id'] ?? 0);
    
    if ($userId <= 0) {
        throw new Exception('Ungültige Benutzer-ID');
    }
    
    // Prüfen ob Kunde existiert
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Transaktion starten
    $pdo->beginTransaction();
    
    try {
        // 1. Zugewiesene Freebies löschen
        $stmt = $pdo->prepare("DELETE FROM user_freebies WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // 2. Fortschritte löschen (falls vorhanden)
        $stmt = $pdo->prepare("DELETE FROM user_progress WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // 3. Benutzer selbst löschen
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Transaktion bestätigen
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kunde erfolgreich gelöscht',
            'deleted_user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
        
    } catch (Exception $e) {
        // Transaktion rückgängig machen bei Fehler
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

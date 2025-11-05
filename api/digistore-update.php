<?php
/**
 * Digistore24 Produkt-Update API
 * Aktualisiert Produkt-IDs, Limits und Aktivierungsstatus
 */

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Zugriff verweigert']));
}

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // POST-Daten validieren
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Nur POST-Requests erlaubt');
    }
    
    $productDbId = $_POST['product_db_id'] ?? null;
    $productId = trim($_POST['product_id'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // NEUE FELDER: Limits
    $ownFreebiesLimit = isset($_POST['own_freebies_limit']) ? (int)$_POST['own_freebies_limit'] : null;
    $readyFreebiesCount = isset($_POST['ready_freebies_count']) ? (int)$_POST['ready_freebies_count'] : null;
    $referralProgramSlots = isset($_POST['referral_program_slots']) ? (int)$_POST['referral_program_slots'] : null;
    
    if (!$productDbId) {
        throw new Exception('Produkt-ID fehlt');
    }
    
    if (empty($productId)) {
        throw new Exception('Digistore24 Produkt-ID darf nicht leer sein');
    }
    
    // Prüfen ob Produkt existiert
    $stmt = $pdo->prepare("SELECT id, product_name FROM digistore_products WHERE id = ?");
    $stmt->execute([$productDbId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Produkt nicht gefunden');
    }
    
    // Prüfen ob Produkt-ID bereits von anderem Produkt verwendet wird
    $stmt = $pdo->prepare("
        SELECT id, product_name 
        FROM digistore_products 
        WHERE product_id = ? AND id != ? AND is_active = 1
    ");
    $stmt->execute([$productId, $productDbId]);
    $duplicate = $stmt->fetch();
    
    if ($duplicate) {
        throw new Exception("Diese Produkt-ID wird bereits von '{$duplicate['product_name']}' verwendet");
    }
    
    // Produkt aktualisieren (inkl. Limits falls angegeben)
    $updateFields = ['product_id = ?', 'is_active = ?'];
    $updateValues = [$productId, $isActive];
    
    if ($ownFreebiesLimit !== null) {
        $updateFields[] = 'own_freebies_limit = ?';
        $updateValues[] = $ownFreebiesLimit;
    }
    
    if ($readyFreebiesCount !== null) {
        $updateFields[] = 'ready_freebies_count = ?';
        $updateValues[] = $readyFreebiesCount;
    }
    
    if ($referralProgramSlots !== null) {
        $updateFields[] = 'referral_program_slots = ?';
        $updateValues[] = $referralProgramSlots;
    }
    
    $updateFields[] = 'updated_at = NOW()';
    $updateValues[] = $productDbId;
    
    $sql = "UPDATE digistore_products SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);
    
    // Zusammenfassung der Änderungen
    $changes = [];
    $changes[] = "Digistore-ID: {$productId}";
    $changes[] = "Status: " . ($isActive ? 'aktiv' : 'inaktiv');
    
    if ($ownFreebiesLimit !== null) {
        $changes[] = "Eigene Freebies: {$ownFreebiesLimit}";
    }
    if ($readyFreebiesCount !== null) {
        $changes[] = "Fertige Freebies: {$readyFreebiesCount}";
    }
    if ($referralProgramSlots !== null) {
        $changes[] = "Empfehlungs-Slots: {$referralProgramSlots}";
    }
    
    // Log-Eintrag
    $logMessage = sprintf(
        "Admin '%s' hat Produkt '%s' aktualisiert: %s",
        $_SESSION['name'] ?? $_SESSION['email'],
        $product['product_name'],
        implode(', ', $changes)
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at) 
        VALUES (?, 'digistore_product_update', ?, NOW())
    ");
    
    // Fehler beim Logging ignorieren falls Tabelle nicht existiert
    try {
        $stmt->execute([$_SESSION['user_id'], $logMessage]);
    } catch (PDOException $e) {
        // Logging optional - Fehler ignorieren
    }
    
    // Erfolgreiche Antwort
    if (isset($_POST['ajax'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Produkt erfolgreich aktualisiert',
            'product_id' => $productId,
            'is_active' => $isActive,
            'limits_updated' => ($ownFreebiesLimit !== null || $readyFreebiesCount !== null || $referralProgramSlots !== null)
        ]);
    } else {
        // Redirect zurück zum Dashboard
        header('Location: /admin/dashboard.php?page=digistore&success=updated');
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    
    if (isset($_POST['ajax'])) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        header('Location: /admin/dashboard.php?page=digistore&error=' . urlencode($e->getMessage()));
        exit;
    }
}

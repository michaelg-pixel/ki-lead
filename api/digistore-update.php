<?php
/**
 * Digistore24 Produkt-Update API
 * Aktualisiert Produkt-IDs und Aktivierungsstatus
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
    
    // Produkt aktualisieren
    $stmt = $pdo->prepare("
        UPDATE digistore_products 
        SET product_id = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$productId, $isActive, $productDbId]);
    
    // Log-Eintrag (optional)
    $logMessage = sprintf(
        "Admin '%s' hat Produkt '%s' aktualisiert: Digistore-ID='%s', Status=%s",
        $_SESSION['name'] ?? $_SESSION['email'],
        $product['product_name'],
        $productId,
        $isActive ? 'aktiv' : 'inaktiv'
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
            'is_active' => $isActive
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

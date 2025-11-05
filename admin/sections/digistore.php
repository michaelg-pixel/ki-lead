<?php
/**
 * Digistore24 Webhook-Zentrale
 * Admin kann hier Produkt-IDs hinterlegen und Produkte verwalten
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert');
}

// Erfolgsmeldungen
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Produkte aus DB laden
$products = $pdo->query("
    SELECT * FROM digistore_products 
    ORDER BY FIELD(product_type, 'launch', 'starter', 'pro', 'business', 'custom')
")->fetchAll();

// Statistiken
$activeProducts = $pdo->query("SELECT COUNT(*) FROM digistore_products WHERE is_active = 1")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$webhookUrl = 'https://app.mehr-infos-jetzt.de/webhook/digistore24.php';
?>
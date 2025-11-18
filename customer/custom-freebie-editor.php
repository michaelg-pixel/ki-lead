<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Freebie-Limit prüfen
$stmt = $pdo->prepare("SELECT freebie_limit FROM customer_freebie_limits WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$limitData = $stmt->fetch(PDO::FETCH_ASSOC);
$freebieLimit = $limitData['freebie_limit'] ?? 0;

// Anzahl eigener Freebies (ALLE ohne template_id - inkl. Marktplatz-Käufe)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND template_id IS NULL");
$stmt->execute([$customer_id]);
$customCount = $stmt->fetchColumn();

// Bearbeiten oder Neu?
$editMode = false;
$freebie = null;

if (isset($_GET['id'])) {
    $editMode = true;
    // FIX: Erlaubt Bearbeitung von ALLEN eigenen Freebies (custom + Marktplatz)
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND template_id IS NULL
    ");
    $stmt->execute([$_GET['id'], $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie
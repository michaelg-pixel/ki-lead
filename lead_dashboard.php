<?php
session_start();

// Debugging (später entfernen)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// KONFIGURATION
require_once 'config.php';

// Sicherheitsprüfung
if (!isset($_SESSION['lead_id'])) {
    header('Location: lead_login.php');
    exit();
}

$lead_id = $_SESSION['lead_id'];

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Lead-Daten abrufen
$stmt = $conn->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    session_destroy();
    header('Location: lead_login.php');
    exit();
}

// Referral-Daten laden (wenn aktiviert)
$referral_enabled = false;
$referral_link = '';
$referral_stats = ['total' => 0, 'active' => 0, 'pending' => 0];
$delivered_rewards = [];

if (file_exists('referral_config.php')) {
    require_once 'referral_config.php';
    if (REFERRAL_SYSTEM_ENABLED) {
        $referral_enabled = true;
        $referral_link = SITE_URL . '/lead_register.php?ref=' . $lead['referral_code'];
        
        // Statistiken abrufen
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM leads 
            WHERE referred_by = ?
        ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $referral_stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Ausgelieferte Belohnungen abrufen
        $stmt = $conn->prepare("
            SELECT r.*, rt.name, rt.description, rt.delivery_type, rt.reward_value
            FROM referral_rewards r
            JOIN referral_tiers rt ON r.tier_id = rt.id
            WHERE r.user_id = ? AND r.status = 'delivered'
            ORDER BY r.delivered_at DESC
        ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $delivered_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ===== MENÜ-NAVIGATION =====
$current_page = $_GET['page'] ?? 'dashboard';

$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard'],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse'],
];

if ($referral_enabled) {
    $menu_items['anleitung'] = ['icon' => 'fa-book-open', 'label' => 'So funktioniert\'s'];
    $menu_items['empfehlen'] = ['icon' => 'fa-share-alt', 'label' => 'Empfehlen'];
    if (!empty($delivered_rewards)) {
        $menu_items['belohnungen'] = ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen'];
    }
    $menu_items['social'] = ['icon' => 'fa-robot', 'label' => 'KI Social Assistant'];
}

// ===== FREEBIES ABRUFEN =====
$freebies = [];
$stmt = $conn->prepare("
    SELECT f.*, 
           COALESCE(flp.progress, 0) as user_progress,
           flp.last_accessed,
           flp.completed_at,
           (SELECT COUNT(*) FROM freebie_lessons WHERE freebie_id = f.id) as total_lessons
    FROM freebies f
    LEFT JOIN freebie_lead_progress flp ON f.id = flp.freebie_id AND flp.lead_id = ?
    WHERE f.status = 'active'
    ORDER BY f.sort_order ASC, f.created_at DESC
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $freebies[] = $row;
}
$stmt->close();
?>
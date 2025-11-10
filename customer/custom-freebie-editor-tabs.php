<?php
/**
 * Custom Freebie Editor mit Tab-System
 * Tab 1: Einstellungen (Optin-Seite) - Vollständiges Design
 * Tab 2: Videokurs (Module & Lektionen) - MIT ALLEN FEATURES
 * + NISCHEN-KATEGORIEN für Marktplatz
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'settings';

// Bearbeiten oder Neu?
$editMode = false;
$freebie = null;
$course = null;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$_GET['id'], $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        die('Freebie nicht gefunden');
    }
    
    // Kurs laden wenn vorhanden
    if ($freebie['has_course']) {
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
        $stmt->execute([$freebie['id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ✅ KATEGORIEN LADEN - Migration ist bereits durchgeführt
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM freebie_template_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabelle existiert noch nicht
    $categories = [];
}

// Formular speichern (Settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_freebie'])) {
    $headline = trim($_POST['headline'] ?? '');
    $subheadline = trim($_POST['subheadline'] ?? '');
    $preheadline = trim($_POST['preheadline'] ?? '');
    $bullet_points = trim($_POST['bullet_points'] ?? '');
    $bullet_icon_style = $_POST['bullet_icon_style'] ?? 'standard';
    $cta_text = trim($_POST['cta_text'] ?? '');
    $layout = $_POST['layout'] ?? 'hybrid';
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $primary_color = $_POST['primary_color'] ?? '#8B5CF6';
    $raw_code = trim($_POST['raw_code'] ?? '');
    $custom_code = trim($_POST['custom_code'] ?? '');
    $mockup_image_url = trim($_POST['mockup_image_url'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $video_format = $_POST['video_format'] ?? 'widescreen';
    $font_heading = $_POST['font_heading'] ?? 'Inter';
    $font_body = $_POST['font_body'] ?? 'Inter';
    $font_size = $_POST['font_size'] ?? 'medium';
    $optin_display_mode = $_POST['optin_display_mode'] ?? 'direct';
    $popup_message = trim($_POST['popup_message'] ?? 'Trage dich jetzt unverbindlich ein!');
    $cta_animation = $_POST['cta_animation'] ?? 'none';
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    $combined_code = $raw_code;
    if (!empty($custom_code)) {
        $combined_code .= "\n<!-- CUSTOM_TRACKING_CODE -->\n" . $custom_code;
    }
    
    if (!$freebie) {
        $unique_id = bin2hex(random_bytes(16));
        $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $headline)) . '-' . substr($unique_id, 0, 8);
    } else {
        $unique_id = $freebie['unique_id'];
        $url_slug = $freebie['url_slug'];
    }
    
    try {
        if ($freebie) {
            $stmt = $pdo->prepare("
                UPDATE customer_freebies SET
                    headline = ?, subheadline = ?, preheadline = ?,
                    bullet_points = ?, bullet_icon_style = ?, cta_text = ?, layout = ?,
                    background_color = ?, primary_color = ?, raw_code = ?,
                    mockup_image_url = ?, video_url = ?, video_format = ?,
                    optin_display_mode = ?, popup_message = ?, cta_animation = ?,
                    font_heading = ?, font_body = ?, font_size = ?,
                    category_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $headline, $subheadline, $preheadline,
                $bullet_points, $bullet_icon_style, $cta_text, $layout,
                $background_color, $primary_color, $combined_code,
                $mockup_image_url, $video_url, $video_format,
                $optin_display_mode, $popup_message, $cta_animation,
                $font_heading, $font_body, $font_size,
                $category_id,
                $freebie['id']
            ]);
            
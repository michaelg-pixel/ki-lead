<?php
/**
 * Lead Logout
 * Leitet zurück zu lead_register.php mit freebie & customer Parametern
 */

session_start();

// Parameter aus Session holen
$freebie_id = $_SESSION['lead_freebie_id'] ?? null;
$customer_id = $_SESSION['lead_customer_id'] ?? null;

// Session zerstören
session_destroy();

// Redirect-URL aufbauen
$redirect_url = '/lead_register.php';

// Parameter hinzufügen
$params = [];
if ($freebie_id) {
    $params[] = 'freebie=' . (int)$freebie_id;
}
if ($customer_id) {
    $params[] = 'customer=' . (int)$customer_id;
}

if (!empty($params)) {
    $redirect_url .= '?' . implode('&', $params);
}

header('Location: ' . $redirect_url);
exit;

<?php
/**
 * Lead Logout
 * Leitet zurück zu lead_register.php mit freebie & customer Parametern
 * FIXED: Vollständige Session-Bereinigung
 */

// Session nur starten wenn nicht bereits aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parameter aus Session holen BEVOR wir sie zerstören
$freebie_id = $_SESSION['lead_freebie_id'] ?? null;
$customer_id = $_SESSION['lead_customer_id'] ?? null;

// Session vollständig bereinigen
$_SESSION = array(); // Alle Session-Variablen löschen

// Session-Cookie löschen
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

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

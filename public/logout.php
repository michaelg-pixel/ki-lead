<?php
// Keine Ausgabe vor diesem Punkt!
session_start();

// Session-Daten löschen
$_SESSION = array();

// Session-Cookie löschen
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Session zerstören
session_destroy();

// Weiterleitung zum Login mit absolutem Pfad
// Konstruiere die absolute URL zur login.php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$login_url = $protocol . '://' . $host . '/public/login.php';

header('Location: ' . $login_url);
exit;
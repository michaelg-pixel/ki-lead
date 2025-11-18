<?php
// Sichere Session-Konfiguration laden
require_once '../config/security.php';

// Starte Session
startSecureSession();

// Zerstöre Session sicher mit der dedizierten Funktion
destroySecureSession();

// Weiterleitung zum Login mit absolutem Pfad
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$login_url = $protocol . '://' . $host . '/public/login.php?logout=success';

header('Location: ' . $login_url);
exit;

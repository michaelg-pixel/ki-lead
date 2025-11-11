<?php
/**
 * Redirect von alter zu neuer Freebie Editor Struktur
 */

session_start();

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

// Redirect to new structure
if (isset($_GET['id'])) {
    $tab = $_GET['tab'] ?? 'settings';
    
    if ($tab === 'course') {
        // Videokurs Tab -> neue course editor
        header("Location: edit-course.php?id=" . $_GET['id']);
    } else {
        // Settings Tab -> neue freebie editor
        header("Location: edit-freebie.php?id=" . $_GET['id']);
    }
} else {
    // Neu erstellen
    header("Location: edit-freebie.php");
}
exit;
?>
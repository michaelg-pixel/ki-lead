<?php
/**
 * Customer Dashboard Section: Empfehlungsprogramm
 * Vollständige Verwaltung des Referral-Systems
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}
?>
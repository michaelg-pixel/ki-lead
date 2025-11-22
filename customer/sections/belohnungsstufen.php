<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * FIXED VERSION v3 - Mit Bildern im Marktplatz + Debug für Mockups
 * 
 * UPDATE: Sperre wenn Empfehlungsprogramm nicht aktiviert
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// SPERRE: Prüfe ob Empfehlungsprogramm aktiviert ist
require_once __DIR__ . '/belohnungsstufen-lock-check.php';

// Freebie-ID aus URL Parameter holen (optional)
$freebie_id = isset($_GET['freebie_id']) ? (int)$_GET['freebie_id'] : null;
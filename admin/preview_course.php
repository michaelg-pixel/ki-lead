<?php
/**
 * Redirect zu preview_fresh.php
 * Grund: OpCache-Probleme mit dieser Datei
 */

// OpCache für diese Datei leeren
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}

if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Redirect zu preview_fresh.php mit allen Parametern
$query_string = $_SERVER['QUERY_STRING'] ?? '';
$redirect_url = 'preview_fresh.php' . ($query_string ? '?' . $query_string : '');

header("Location: $redirect_url");
exit;
?>
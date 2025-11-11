<?php
// Redirect to custom-freebie-editor.php
// This file was corrupted and is now a simple redirect
header('Location: /customer/custom-freebie-editor.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
?>
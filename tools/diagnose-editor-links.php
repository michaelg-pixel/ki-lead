<?php
/**
 * Diagnose: Warum funktionieren die Editor-Links nicht?
 */

echo "<h2>🔍 Editor Links Diagnose</h2>";
echo "<hr><br>";

// 1. Prüfe welche Dateien existieren
echo "<h3>📁 Dateien prüfen:</h3>";

$files_to_check = [
    '/public/customer/edit-freebie.php',
    '/public/customer/edit-course.php',
    '/customer/custom-freebie-editor-tabs.php',
    '/public/customer/custom-freebie-editor-tabs.php',
];

foreach ($files_to_check as $file) {
    $fullPath = __DIR__ . '/..' . $file;
    $exists = file_exists($fullPath);
    
    if ($exists) {
        echo "✅ <strong>EXISTIERT:</strong> <code>$file</code> (" . filesize($fullPath) . " bytes)<br>";
    } else {
        echo "❌ <strong>FEHLT:</strong> <code>$file</code><br>";
    }
}

echo "<br><hr><h3>🔗 Test-Links:</h3>";
echo "<p>Teste folgende Links direkt:</p>";
echo "<ul>";
echo "<li><a href='/public/customer/edit-freebie.php?id=7' target='_blank'>/public/customer/edit-freebie.php?id=7</a></li>";
echo "<li><a href='/public/customer/edit-course.php?id=8' target='_blank'>/public/customer/edit-course.php?id=8</a></li>";
echo "<li><a href='/customer/custom-freebie-editor-tabs.php?id=7' target='_blank'>/customer/custom-freebie-editor-tabs.php?id=7</a></li>";
echo "</ul>";

echo "<br><hr><h3>💡 Lösung:</h3>";

$editFreebieInPublic = file_exists(__DIR__ . '/../public/customer/edit-freebie.php');
$editFreebieInCustomer = file_exists(__DIR__ . '/../customer/edit-freebie.php');

if ($editFreebieInPublic && !$editFreebieInCustomer) {
    echo "<p>✅ Die Dateien sind in <code>/public/customer/</code></p>";
    echo "<p>❌ ABER: Sie sollten in <code>/customer/</code> sein!</p>";
    echo "<p><strong>Problem:</strong> Die require_once Pfade passen nicht.</p>";
    echo "<p><strong>Lösung:</strong> Dateien verschieben von /public/customer/ nach /customer/</p>";
    echo "<br><a href='/tools/move-editor-files.php' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;'>→ Automatisch verschieben</a>";
} elseif (!$editFreebieInPublic && $editFreebieInCustomer) {
    echo "<p>✅ Die Dateien sind in <code>/customer/</code></p>";
    echo "<p>Die Links in freebies.php müssen angepasst werden!</p>";
} else {
    echo "<p>⚠️ Unklarer Status - bitte manuell prüfen</p>";
}

echo "<br><br><hr>";
echo "<h3>📋 Aktuelle Situation:</h3>";
echo "<pre>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script: " . __FILE__ . "\n";
echo "</pre>";
?>
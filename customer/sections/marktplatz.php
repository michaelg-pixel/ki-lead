<?php
// Marktplatz Section - Lädt die vollständige Version
$complete_file = __DIR__ . '/marktplatz-complete.php';

if (file_exists($complete_file)) {
    include $complete_file;
} else {
    // Fallback wenn complete nicht existiert
    ?>
    <div style="padding: 32px; text-align: center; color: white;">
        <h3>⚠️ Marktplatz wird geladen...</h3>
        <p style="color: #888; margin-top: 16px;">
            Bitte aktualisiere die Seite, falls dieser Text bestehen bleibt.
        </p>
    </div>
    <?php
}
?>
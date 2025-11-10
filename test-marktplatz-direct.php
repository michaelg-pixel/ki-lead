<?php
// Direkter Marktplatz Test (ohne Dashboard)
// Aufruf: https://app.mehr-infos-jetzt.de/test-marktplatz-direct.php

session_start();

// Fake Session für Test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; // Test User
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marktplatz Test</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        .test-info {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="test-info">
        <h2>🧪 Direkter Marktplatz Test</h2>
        <p>User ID: <?= $_SESSION['user_id'] ?></p>
        <p>Dieser Test lädt marktplatz.php direkt ohne Dashboard-Integration.</p>
    </div>

    <?php
    // Lade marktplatz.php direkt
    $customer_id = $_SESSION['user_id'];
    
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    
    // Include marktplatz.php
    include __DIR__ . '/customer/marktplatz.php';
    ?>

    <script>
        console.log('✅ Marktplatz geladen');
        
        // Prüfe ob Elemente existieren
        const container = document.querySelector('.marketplace-container');
        const grid = document.querySelector('.freebies-grid');
        const emptyState = document.querySelector('.empty-state');
        
        console.log('Container:', container ? '✅' : '❌');
        console.log('Grid:', grid ? '✅' : '❌');
        console.log('Empty State:', emptyState ? '✅' : '❌');
        
        if (grid) {
            const cards = grid.querySelectorAll('.freebie-marketplace-card');
            console.log('Freebie Cards:', cards.length);
        }
    </script>
</body>
</html>

<?php
/**
 * Setze Freebie-Limit für aktuellen User
 * Aufruf: https://app.mehr-infos-jetzt.de/set-my-freebie-limit.php
 */

session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Freebie-Limit setzen</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin: 10px 0;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🎁 Freebie-Limit setzen</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Setze dein persönliches Freebie-Limit</p>';

try {
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung fehlgeschlagen');
    }
    
    // Prüfe ob User eingeloggt ist
    if (!isset($_SESSION['user_id'])) {
        echo '<div class="status error">';
        echo '❌ Du bist nicht eingeloggt!';
        echo '</div>';
        echo '<a href="/public/login.php" class="button">Zum Login</a>';
        echo '</div></body></html>';
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // POST Request verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limit'])) {
        $limit = intval($_POST['limit']);
        $product_name = $_POST['product_name'] ?? 'Manuell gesetzt';
        
        // Prüfen ob bereits Limit existiert
        $stmt = $pdo->prepare("SELECT id FROM customer_freebie_limits WHERE customer_id = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, 
                    product_name = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE customer_id = ?
            ");
            $stmt->execute([$limit, $product_name, $user_id]);
            
            echo '<div class="status success">';
            echo '✅ Limit erfolgreich aktualisiert!';
            echo '</div>';
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits 
                (customer_id, freebie_limit, product_name, product_id) 
                VALUES (?, ?, ?, 'MANUAL')
            ");
            $stmt->execute([$user_id, $limit, $product_name]);
            
            echo '<div class="status success">';
            echo '✅ Limit erfolgreich gesetzt!';
            echo '</div>';
        }
        
        echo '<div class="status info">';
        echo '<strong>Dein neues Limit:</strong><br>';
        echo '📦 ' . $limit . ' Freebies<br>';
        echo '🏷️ ' . htmlspecialchars($product_name);
        echo '</div>';
        
        echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies →</a>';
        echo '</div></body></html>';
        exit;
    }
    
    // User-Info anzeigen
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    echo '<div class="status info">';
    echo '<strong>Eingeloggt als:</strong><br>';
    echo 'User ID: ' . $user_id . '<br>';
    echo 'Email: ' . htmlspecialchars($user['email'] ?? 'Unbekannt');
    echo '</div>';
    
    // Aktuelles Limit prüfen
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch();
    
    if ($current) {
        echo '<div class="status success">';
        echo '<strong>Aktuelles Limit:</strong><br>';
        echo '📦 ' . $current['freebie_limit'] . ' Freebies<br>';
        echo '🏷️ ' . htmlspecialchars($current['product_name']);
        echo '</div>';
    } else {
        echo '<div class="status error">';
        echo '⚠️ Noch kein Limit gesetzt';
        echo '</div>';
    }
    
    // Formular anzeigen
    echo '<form method="POST">';
    echo '<label style="display: block; margin-top: 20px; font-weight: 600;">Wähle dein Paket:</label>';
    echo '<select name="limit" required>';
    echo '<option value="">-- Bitte wählen --</option>';
    echo '<option value="5">Starter Paket (5 Freebies)</option>';
    echo '<option value="10">Professional Paket (10 Freebies)</option>';
    echo '<option value="25">Enterprise Paket (25 Freebies)</option>';
    echo '<option value="999">Unlimited Paket (Unbegrenzt)</option>';
    echo '</select>';
    
    echo '<input type="hidden" name="product_name" id="product_name" value="">';
    
    echo '<button type="submit">Limit setzen</button>';
    echo '</form>';
    
    echo '<script>
    document.querySelector("select[name=limit]").addEventListener("change", function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById("product_name").value = selectedOption.text.split(" (")[0];
    });
    </script>';
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '❌ Fehler: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

echo '</div></body></html>';
?>

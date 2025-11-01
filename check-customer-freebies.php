<?php
/**
 * Customer Freebies Tabellen-Check
 * Aufruf: https://app.mehr-infos-jetzt.de/check-customer-freebies.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Freebies Check</title>
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
            max-width: 1000px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #1a1a2e; margin-bottom: 10px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        tr:hover {
            background: #f9fafb;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-missing { background: #fee2e2; color: #991b1b; }
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
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔍 Customer Freebies Tabellen-Check</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Überprüfung der Datenbank-Struktur</p>';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    
    if ($stmt->rowCount() === 0) {
        echo '<div class="status error">';
        echo '<span style="font-size: 24px;">❌</span>';
        echo '<div><strong>Tabelle nicht gefunden!</strong><br>';
        echo 'Die Tabelle "customer_freebies" existiert nicht. Bitte führe das Setup aus.';
        echo '</div></div>';
        echo '<a href="/setup-customer-freebies.php" class="button">Setup ausführen</a>';
    } else {
        echo '<div class="status success">';
        echo '✅ Tabelle "customer_freebies" gefunden';
        echo '</div>';
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE customer_freebies");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h2 style="margin-top: 30px;">📋 Aktuelle Spalten (' . count($columns) . ')</h2>';
        echo '<table>';
        echo '<thead><tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . ($col['Null'] === 'YES' ? '<span style="color: #6b7280;">YES</span>' : '<span style="color: #991b1b;">NO</span>') . '</td>';
            echo '<td>' . ($col['Key'] ? '<span class="badge badge-ok">' . htmlspecialchars($col['Key']) . '</span>' : '-') . '</td>';
            echo '<td>' . ($col['Default'] !== null ? htmlspecialchars($col['Default']) : '<span style="color: #9ca3af;">NULL</span>') . '</td>';
            echo '<td>' . ($col['Extra'] ? htmlspecialchars($col['Extra']) : '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Check required columns
        $required_columns = [
            'id', 'customer_id', 'template_id', 'headline', 'subheadline', 
            'preheadline', 'bullet_points', 'cta_text', 'layout', 
            'background_color', 'primary_color', 'raw_code', 'unique_id', 
            'url_slug', 'mockup_image_url', 'created_at', 'updated_at'
        ];
        
        $existing_columns = array_column($columns, 'Field');
        $missing_columns = array_diff($required_columns, $existing_columns);
        
        echo '<h2 style="margin-top: 30px;">✓ Status-Check</h2>';
        
        if (empty($missing_columns)) {
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">✅</span>';
            echo '<div><strong>Alle benötigten Spalten vorhanden!</strong><br>';
            echo 'Die Tabelle ist vollständig eingerichtet und bereit zur Verwendung.';
            echo '</div></div>';
        } else {
            echo '<div class="status error">';
            echo '<span style="font-size: 24px;">⚠️</span>';
            echo '<div><strong>Fehlende Spalten:</strong><br>';
            echo implode(', ', $missing_columns);
            echo '</div></div>';
        }
        
        // Check for data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer_freebies");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo '<h2 style="margin-top: 30px;">📊 Daten</h2>';
        echo '<div class="status success">';
        echo '<span style="font-size: 24px;">📈</span>';
        echo '<div>';
        echo '<strong>Gespeicherte Customer Freebies:</strong> ' . $result['count'];
        echo '</div></div>';
        
        // Check freebies table
        $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM freebies WHERE is_active = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">🎁</span>';
            echo '<div>';
            echo '<strong>Verfügbare Admin-Templates:</strong> ' . $result['count'];
            echo '</div></div>';
        }
        
        echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
        echo ' ';
        echo '<a href="/admin/dashboard.php?page=freebies" class="button" style="background: #6b7280;">Admin Templates</a>';
    }
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div><strong>Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div></div>';
}

echo '</div></body></html>';
?>

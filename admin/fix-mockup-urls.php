<?php
/**
 * FIX MOCKUP URLs
 * Dieses Script überträgt alle mockup_image_url von freebies zu customer_freebies
 * Aufruf: https://deine-domain.de/admin/fix-mockup-urls.php?token=fix2024
 */

session_start();
require_once '../config/database.php';

// Security Token für einmaligen Zugriff
$security_token = 'fix2024';
$provided_token = $_GET['token'] ?? '';

// Check if admin is logged in OR valid token provided
$is_admin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
$has_valid_token = $provided_token === $security_token;

if (!$is_admin && !$has_valid_token) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Zugriff verweigert</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px 20px;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .container {
                max-width: 600px;
                background: white;
                border-radius: 16px;
                padding: 32px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                text-align: center;
            }
            h1 {
                color: #ef4444;
                margin-bottom: 16px;
            }
            p {
                color: #374151;
                line-height: 1.6;
                margin-bottom: 24px;
            }
            .info {
                background: #dbeafe;
                padding: 16px;
                border-radius: 8px;
                border-left: 4px solid #3b82f6;
                text-align: left;
                margin-top: 24px;
            }
            code {
                background: #f3f4f6;
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
                color: #ef4444;
            }
            a {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin-top: 16px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 Zugriff verweigert</h1>
            <p>Nur für Administratoren oder mit gültigem Token.</p>
            
            <div class="info">
                <strong>💡 Zugriff mit Token:</strong><br>
                Rufe diese URL auf:<br>
                <code>fix-mockup-urls.php?token=fix2024</code>
            </div>
            
            <a href="/admin/dashboard.php">← Zum Admin-Login</a>
        </div>
    </body>
    </html>
    ');
}

$pdo = getDBConnection();

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mockup URLs Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 24px;
        }
        h2 {
            color: #374151;
            margin-top: 32px;
            margin-bottom: 16px;
        }
        .status {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 24px;
            margin-right: 12px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        img {
            max-width: 100px;
            height: auto;
            border-radius: 4px;
        }
        .security-notice {
            background: #fef3c7;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Mockup-URLs Reparieren</h1>';

if ($has_valid_token && !$is_admin) {
    echo '<div class="security-notice">';
    echo '<strong>⚠️ Sicherheitshinweis:</strong> Du nutzt den Token-Zugang. Nach der Reparatur solltest du diese Seite nicht mehr mit Token aufrufen.';
    echo '</div>';
}

try {
    // 1. Prüfen: Wie viele customer_freebies haben KEINE Mockup-URL
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM customer_freebies cf
        INNER JOIN freebies f ON cf.template_id = f.id
        WHERE cf.freebie_type = 'template'
          AND (cf.mockup_image_url IS NULL OR cf.mockup_image_url = '')
          AND f.mockup_image_url IS NOT NULL 
          AND f.mockup_image_url != ''
    ");
    $missing_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<div class='info'>";
    echo "<strong>📊 Analyse:</strong><br>";
    echo "Es wurden <strong>{$missing_count} Customer-Freebies</strong> gefunden, denen die Mockup-URL fehlt.";
    echo "</div>";
    
    if ($missing_count > 0) {
        // 2. UPDATE durchführen
        $stmt = $pdo->prepare("
            UPDATE customer_freebies cf
            INNER JOIN freebies f ON cf.template_id = f.id
            SET cf.mockup_image_url = f.mockup_image_url
            WHERE cf.freebie_type = 'template'
              AND (cf.mockup_image_url IS NULL OR cf.mockup_image_url = '')
              AND f.mockup_image_url IS NOT NULL 
              AND f.mockup_image_url != ''
        ");
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        echo "<div class='success'>";
        echo "<strong>✅ Erfolg!</strong><br>";
        echo "<strong>{$updated} Mockup-URLs</strong> wurden erfolgreich von den Templates übertragen!";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<strong>✅ Alles OK!</strong><br>";
        echo "Alle Customer-Freebies haben bereits ihre Mockup-URLs.";
        echo "</div>";
    }
    
    // 3. Übersicht aller Customer-Freebies anzeigen
    echo "<h2>📋 Übersicht aller Customer-Freebies</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.customer_id,
            u.name as customer_name,
            cf.template_id,
            f.name as template_name,
            cf.headline,
            cf.freebie_type,
            cf.mockup_image_url as customer_mockup,
            f.mockup_image_url as template_mockup
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        LEFT JOIN users u ON cf.customer_id = u.id
        ORDER BY cf.id DESC
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);
    $with_mockup = 0;
    
    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Kunde</th>
            <th>Template</th>
            <th>Überschrift</th>
            <th>Typ</th>
            <th>Mockup Vorschau</th>
            <th>Status</th>
          </tr>";
    
    foreach ($rows as $row) {
        $has_mockup = !empty($row['customer_mockup']);
        if ($has_mockup) $with_mockup++;
        $status_icon = $has_mockup ? '✅' : '❌';
        $status_text = $has_mockup ? 'OK' : 'Fehlt';
        
        echo "<tr>";
        echo "<td>#{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'Unbekannt') . "</td>";
        echo "<td>" . htmlspecialchars($row['template_name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['headline']) . "</td>";
        echo "<td>" . htmlspecialchars($row['freebie_type'] ?? 'template') . "</td>";
        echo "<td>";
        if ($has_mockup) {
            echo "<img src='" . htmlspecialchars($row['customer_mockup']) . "' alt='Mockup' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"inline\";'>";
            echo "<span style='display:none; color: #ef4444;'>❌ Bild nicht gefunden</span>";
        } else {
            echo "<span style='color: #6b7280;'>Kein Mockup</span>";
        }
        echo "</td>";
        echo "<td>{$status_icon} {$status_text}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='info' style='margin-top: 16px;'>";
    echo "<strong>📊 Statistik:</strong> {$with_mockup} von {$total} Customer-Freebies haben ein Mockup-Bild.";
    echo "</div>";
    
    // 4. Template-Übersicht
    echo "<h2>📚 Template-Übersicht</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            mockup_image_url,
            (SELECT COUNT(*) FROM customer_freebies WHERE template_id = f.id) as usage_count
        FROM freebies f
        ORDER BY id
    ");
    
    echo "<table>";
    echo "<tr>
            <th>Template ID</th>
            <th>Name</th>
            <th>Mockup</th>
            <th>Mockup URL</th>
            <th>Verwendet von</th>
          </tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_mockup = !empty($row['mockup_image_url']);
        
        echo "<tr>";
        echo "<td>#{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>";
        if ($has_mockup) {
            echo "<img src='" . htmlspecialchars($row['mockup_image_url']) . "' alt='Template Mockup' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"inline\";'>";
            echo "<span style='display:none; color: #ef4444;'>❌ Bild nicht gefunden</span>";
        } else {
            echo "<span style='color: #ef4444;'>❌ Kein Mockup</span>";
        }
        echo "</td>";
        echo "<td><small style='color: #6b7280;'>" . ($has_mockup ? htmlspecialchars($row['mockup_image_url']) : '-') . "</small></td>";
        echo "<td>{$row['usage_count']} Kunden</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Fehler:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo '
        <a href="/customer/dashboard.php?page=freebies" class="btn">👁️ Freebies im Dashboard ansehen</a>
        <a href="/admin/dashboard.php" class="btn">← Zurück zum Admin-Dashboard</a>
    </div>
</body>
</html>';

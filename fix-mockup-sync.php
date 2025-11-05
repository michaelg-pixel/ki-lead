<?php
/**
 * MOCKUP-URL SYNCHRONISATION FIX
 * 
 * Dieses Skript überträgt automatisch die mockup_image_url von den Admin-Templates 
 * (freebies Tabelle) zu den Customer-Freebies (customer_freebies Tabelle).
 * 
 * PROBLEM: Wenn Admin ein Mockup in einem Template hochlädt, verschwinden die Mockups 
 * im Customer-Dashboard, weil die mockup_image_url nicht synchronisiert wird.
 * 
 * LÖSUNG: Dieses Skript kopiert die mockup_image_url automatisch von freebies zu customer_freebies.
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-mockup-sync.php
 */

session_start();
require_once __DIR__ . '/config/database.php';

// Admin-Check (für Sicherheit)
$is_admin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';

if (!$is_admin) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mockup-URL Synchronisation</title>
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
                color: #1a1a2e;
                margin-bottom: 16px;
            }
            p {
                color: #374151;
                line-height: 1.6;
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
            <p>Nur für Administratoren zugänglich.</p>
            <a href="/admin/dashboard.php">← Zum Admin-Login</a>
        </div>
    </body>
    </html>
    ');
}

$pdo = getDBConnection();

// Statistiken sammeln
$stats = [
    'total_customer_freebies' => 0,
    'with_mockup' => 0,
    'without_mockup' => 0,
    'synced' => 0,
    'errors' => 0
];

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mockup-URL Synchronisation</title>
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
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 32px;
        }
        .status {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
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
        .stat-box {
            display: inline-block;
            background: #f3f4f6;
            padding: 16px 24px;
            border-radius: 8px;
            margin: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            display: block;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Mockup-URL Synchronisation</h1>
        <p class="subtitle">Automatische Übertragung von Mockup-URLs zu Customer-Freebies</p>';

try {
    // 1. Alle customer_freebies mit template_id auflisten
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM customer_freebies 
        WHERE freebie_type = 'template'
    ");
    $stats['total_customer_freebies'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Zählen: Mit Mockup
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM customer_freebies 
        WHERE freebie_type = 'template' 
          AND mockup_image_url IS NOT NULL 
          AND mockup_image_url != ''
    ");
    $stats['with_mockup'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 3. Zählen: Ohne Mockup aber Template hat eines
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM customer_freebies cf
        INNER JOIN freebies f ON cf.template_id = f.id
        WHERE cf.freebie_type = 'template'
          AND (cf.mockup_image_url IS NULL OR cf.mockup_image_url = '')
          AND f.mockup_image_url IS NOT NULL 
          AND f.mockup_image_url != ''
    ");
    $stats['without_mockup'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo '<div class="info">';
    echo '<strong>📊 Analyse:</strong><br>';
    echo 'Gefunden: <strong>' . $stats['without_mockup'] . '</strong> Customer-Freebies ohne Mockup-URL, bei denen das Template eine hat.';
    echo '</div>';
    
    if ($stats['without_mockup'] > 0) {
        // 4. Synchronisation durchführen
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
        $stats['synced'] = $stmt->rowCount();
        
        echo '<div class="success">';
        echo '<strong>✅ Erfolg!</strong><br>';
        echo '<strong>' . $stats['synced'] . '</strong> Mockup-URLs wurden erfolgreich synchronisiert!';
        echo '</div>';
    } else {
        echo '<div class="success">';
        echo '<strong>✅ Alles OK!</strong><br>';
        echo 'Alle Customer-Freebies haben bereits ihre Mockup-URLs.';
        echo '</div>';
    }
    
    // Statistiken anzeigen
    echo '<div style="text-align: center; margin: 32px 0;">';
    echo '<div class="stat-box">';
    echo '<span class="stat-number">' . $stats['total_customer_freebies'] . '</span>';
    echo '<span class="stat-label">Gesamt</span>';
    echo '</div>';
    
    echo '<div class="stat-box">';
    echo '<span class="stat-number">' . $stats['with_mockup'] . '</span>';
    echo '<span class="stat-label">Mit Mockup</span>';
    echo '</div>';
    
    echo '<div class="stat-box">';
    echo '<span class="stat-number">' . $stats['synced'] . '</span>';
    echo '<span class="stat-label">Synchronisiert</span>';
    echo '</div>';
    echo '</div>';
    
    // 5. Detaillierte Übersicht
    echo '<h2>📋 Übersicht aller Customer-Freebies</h2>';
    
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.customer_id,
            u.name as customer_name,
            cf.template_id,
            f.name as template_name,
            cf.headline,
            cf.mockup_image_url as customer_mockup,
            f.mockup_image_url as template_mockup,
            CASE 
                WHEN cf.mockup_image_url IS NOT NULL AND cf.mockup_image_url != '' THEN 'OK'
                WHEN f.mockup_image_url IS NOT NULL AND f.mockup_image_url != '' THEN 'Template hat Mockup'
                ELSE 'Kein Mockup'
            END as status
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        LEFT JOIN users u ON cf.customer_id = u.id
        WHERE cf.freebie_type = 'template'
        ORDER BY cf.id DESC
        LIMIT 50
    ");
    
    echo '<table>';
    echo '<tr>
            <th>ID</th>
            <th>Kunde</th>
            <th>Template</th>
            <th>Überschrift</th>
            <th>Mockup</th>
            <th>Status</th>
          </tr>';
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_mockup = !empty($row['customer_mockup']);
        $status_icon = $has_mockup ? '✅' : ($row['status'] === 'Template hat Mockup' ? '⚠️' : '❌');
        
        echo '<tr>';
        echo '<td>#' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['customer_name'] ?? 'Unbekannt') . '</td>';
        echo '<td>' . htmlspecialchars($row['template_name'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['headline']) . '</td>';
        echo '<td>';
        if ($has_mockup) {
            echo '<img src="' . htmlspecialchars($row['customer_mockup']) . '" 
                       alt="Mockup" 
                       style="max-width: 60px; height: auto; border-radius: 4px;"
                       onerror="this.style.display=\'none\'; this.nextSibling.style.display=\'inline\';">';
            echo '<span style="display:none; color: #ef4444;">❌ Fehler</span>';
        } else {
            echo '<span style="color: #6b7280;">-</span>';
        }
        echo '</td>';
        echo '<td>' . $status_icon . ' ' . htmlspecialchars($row['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (PDOException $e) {
    echo '<div class="warning">';
    echo '<strong>⚠️ Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}

echo '
        <div style="text-align: center; margin-top: 32px;">
            <a href="/admin/dashboard.php?page=freebies" class="btn">📊 Zu den Admin-Freebies</a>
            <a href="/customer/dashboard.php?page=freebies" class="btn">👥 Zu den Customer-Freebies</a>
        </div>
        
        <div class="info" style="margin-top: 32px;">
            <strong>💡 Tipp:</strong> Ab jetzt werden Mockup-URLs automatisch synchronisiert, wenn du ein Template im Admin-Bereich bearbeitest!
        </div>
    </div>
</body>
</html>';

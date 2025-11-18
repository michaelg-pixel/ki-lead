<?php
/**
 * DEBUG: Alle Marktplatz-Käufe anzeigen
 * Übersicht aller User die Marktplatz-Freebies gekauft haben
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

try {
    $pdo = getDBConnection();
    
    // ALLE User mit Marktplatz-Käufen
    $stmt = $pdo->query("
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            u.role,
            u.created_at as user_created,
            COUNT(cf.id) as purchased_count,
            GROUP_CONCAT(cf.headline SEPARATOR ' | ') as purchased_freebies
        FROM users u
        INNER JOIN customer_freebies cf ON u.id = cf.customer_id
        WHERE cf.copied_from_freebie_id IS NOT NULL
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ALLE Marktplatz-Käufe im Detail
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.customer_id,
            cf.headline,
            cf.freebie_type,
            cf.copied_from_freebie_id,
            cf.original_creator_id,
            cf.unique_id,
            cf.url_slug,
            cf.created_at,
            cf.updated_at,
            cf.background_color,
            cf.primary_color,
            u.name as buyer_name,
            u.email as buyer_email,
            seller.name as seller_name,
            seller.email as seller_email
        FROM customer_freebies cf
        LEFT JOIN users u ON cf.customer_id = u.id
        LEFT JOIN users seller ON cf.original_creator_id = seller.id
        WHERE cf.copied_from_freebie_id IS NOT NULL
        ORDER BY cf.created_at DESC
    ");
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Fehler: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒 Marktplatz-Käufe Übersicht</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        .header {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; color: #1a1a2e; margin-bottom: 12px; }
        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .card h2 { margin-bottom: 24px; color: #1a1a2e; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover { background: #f9fafb; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-ok { background: #22c55e; color: white; }
        .badge-error { background: #ef4444; color: white; }
        .badge-warning { background: #fbbf24; color: white; }
        .btn-debug {
            display: inline-block;
            padding: 6px 12px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-debug:hover { background: #5568d3; }
        .issue-indicator {
            color: #ef4444;
            font-weight: bold;
        }
        .ok-indicator {
            color: #22c55e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Marktplatz-Käufe Übersicht</h1>
            <p>Alle User die Marktplatz-Freebies gekauft haben</p>
        </div>
        
        <div class="card">
            <h2>👥 Käufer (<?php echo count($buyers); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gekaufte Freebies</th>
                        <th>Registriert</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buyers as $buyer): ?>
                        <tr>
                            <td><?php echo $buyer['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($buyer['name']); ?></td>
                            <td><?php echo htmlspecialchars($buyer['email']); ?></td>
                            <td>
                                <strong><?php echo $buyer['purchased_count']; ?></strong> Freebies
                                <br><small style="color: #666;"><?php echo htmlspecialchars(substr($buyer['purchased_freebies'], 0, 100)); ?>...</small>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($buyer['user_created'])); ?></td>
                            <td>
                                <a href="debug-freebies-rendering.php?user_id=<?php echo $buyer['user_id']; ?>" class="btn-debug">
                                    🔍 Debug
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>📦 Alle Käufe im Detail (<?php echo count($purchases); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Käufer</th>
                        <th>Headline</th>
                        <th>Type</th>
                        <th>Verkäufer</th>
                        <th>Status</th>
                        <th>Datum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $p): 
                        // Status prüfen
                        $issues = [];
                        if (empty($p['unique_id']) && empty($p['url_slug'])) {
                            $issues[] = 'Kein Identifier';
                        }
                        if (empty($p['created_at']) && empty($p['updated_at'])) {
                            $issues[] = 'Kein Datum';
                        }
                        if (empty($p['background_color']) && empty($p['primary_color'])) {
                            $issues[] = 'Keine Farben';
                        }
                        
                        $hasIssues = !empty($issues);
                    ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['buyer_name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($p['buyer_email']); ?></small><br>
                                <small style="color: #999;">User ID: <?php echo $p['customer_id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($p['headline']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $p['freebie_type'] === 'template' ? 'ok' : 'warning'; ?>">
                                    <?php echo $p['freebie_type'] ?: 'NULL'; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['seller_name']); ?></strong><br>
                                <small style="color: #666;">ID: <?php echo $p['original_creator_id']; ?></small>
                            </td>
                            <td>
                                <?php if ($hasIssues): ?>
                                    <span class="issue-indicator">❌</span>
                                    <?php foreach ($issues as $issue): ?>
                                        <br><small><?php echo $issue; ?></small>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="ok-indicator">✅ OK</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($p['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
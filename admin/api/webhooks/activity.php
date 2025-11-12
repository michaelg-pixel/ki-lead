<?php
/**
 * Webhook Activity Log Viewer
 * Zeigt alle Aktivitäten für einen Webhook
 */

require_once '../../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert');
}

$pdo = getDBConnection();
$webhookId = $_GET['webhook_id'] ?? null;

if (!$webhookId) {
    die('Webhook-ID fehlt');
}

// Webhook-Info laden
$stmt = $pdo->prepare("SELECT name FROM webhook_configurations WHERE id = ?");
$stmt->execute([$webhookId]);
$webhook = $stmt->fetch();

if (!$webhook) {
    die('Webhook nicht gefunden');
}

// Aktivitäten laden
$stmt = $pdo->prepare("
    SELECT 
        wl.*,
        u.name as customer_name,
        u.email as customer_email_verified
    FROM webhook_activity_log wl
    LEFT JOIN users u ON wl.customer_id = u.id
    WHERE wl.webhook_id = ?
    ORDER BY wl.created_at DESC
    LIMIT 100
");
$stmt->execute([$webhookId]);
$activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Aktivitäten - <?php echo htmlspecialchars($webhook['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .event-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .event-purchase {
            background: #d1fae5;
            color: #065f46;
        }
        
        .event-upsell {
            background: #fef3c7;
            color: #92400e;
        }
        
        .event-refund {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .resources {
            font-size: 13px;
            color: #6b7280;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Webhook Aktivitäten</h1>
        <p class="subtitle">Webhook: <strong><?php echo htmlspecialchars($webhook['name']); ?></strong></p>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Gesamt Aktivitäten</h3>
                <div class="value"><?php echo count($activities); ?></div>
            </div>
            <div class="stat-card">
                <h3>Käufe</h3>
                <div class="value"><?php echo count(array_filter($activities, fn($a) => $a['event_type'] === 'purchase')); ?></div>
            </div>
            <div class="stat-card">
                <h3>Upsells</h3>
                <div class="value"><?php echo count(array_filter($activities, fn($a) => $a['is_upsell'] == 1)); ?></div>
            </div>
        </div>
        
        <?php if (empty($activities)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Noch keine Aktivitäten für diesen Webhook</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Datum/Zeit</th>
                        <th>Event</th>
                        <th>Kunde</th>
                        <th>Produkt-ID</th>
                        <th>Ressourcen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <?php
                        $resources = json_decode($activity['resources_granted'], true);
                        $eventClass = 'event-' . ($activity['event_type'] ?? 'purchase');
                        ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></td>
                            <td>
                                <span class="event-badge <?php echo $eventClass; ?>">
                                    <?php 
                                    if ($activity['is_upsell']) {
                                        echo '⬆️ UPSELL';
                                    } else {
                                        echo strtoupper($activity['event_type'] ?? 'PURCHASE');
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($activity['customer_name']): ?>
                                    <strong><?php echo htmlspecialchars($activity['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($activity['customer_email_verified']); ?></small>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($activity['customer_email']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($activity['product_id']); ?></code>
                            </td>
                            <td>
                                <?php if ($resources): ?>
                                    <div class="resources">
                                        <?php if (isset($resources['freebies'])): ?>
                                            🎁 <?php echo $resources['freebies']; ?> Freebies<br>
                                        <?php endif; ?>
                                        <?php if (isset($resources['referral_slots'])): ?>
                                            🚀 <?php echo $resources['referral_slots']; ?> Slots<br>
                                        <?php endif; ?>
                                        <?php if (isset($resources['courses'])): ?>
                                            📚 <?php echo count($resources['courses']); ?> Kurse
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

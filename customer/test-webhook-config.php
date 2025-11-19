<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('❌ Nicht eingeloggt!');
}

$customer_id = $_SESSION['user_id'];
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Webhook Configuration Debug</title>
    <style>
        body {
            background: #1a1a2e;
            color: white;
            font-family: Arial, sans-serif;
            padding: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(102, 126, 234, 0.3);
            font-weight: 600;
        }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        h2 { color: #667eea; margin-top: 40px; }
    </style>
</head>
<body>
    <h1>🔍 Webhook Configuration Debug</h1>
    
    <h2>1️⃣ Dein Produktkauf</h2>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Freebie Limit</th>
            <th>Source</th>
            <th>Granted At</th>
        </tr>
        <?php foreach ($products as $p): ?>
        <tr>
            <td class="success"><?= $p['product_id'] ?></td>
            <td><?= $p['product_name'] ?></td>
            <td><?= $p['freebie_limit'] ?></td>
            <td><?= $p['source'] ?></td>
            <td><?= $p['granted_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>2️⃣ Alle Templates & ihre Kurse</h2>
    <?php
    $stmt = $pdo->query("
        SELECT f.id as freebie_id, f.name as freebie_name, f.course_id, c.title as course_title, c.is_active
        FROM freebies f
        LEFT JOIN courses c ON f.course_id = c.id
        ORDER BY f.id DESC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <tr>
            <th>Template ID</th>
            <th>Template Name</th>
            <th>Course ID</th>
            <th>Course Title</th>
            <th>Course Active</th>
        </tr>
        <?php foreach ($templates as $t): ?>
        <tr>
            <td><?= $t['freebie_id'] ?></td>
            <td><?= $t['freebie_name'] ?></td>
            <td class="<?= $t['course_id'] ? 'success' : 'warning' ?>"><?= $t['course_id'] ?: 'KEIN KURS' ?></td>
            <td><?= $t['course_title'] ?: '-' ?></td>
            <td class="<?= $t['is_active'] ? 'success' : 'error' ?>"><?= $t['is_active'] ? '✓ Aktiv' : '✗ Inaktiv' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>3️⃣ Webhook Configurations (Produkt → Webhook)</h2>
    <?php
    $stmt = $pdo->query("
        SELECT wc.id, wc.name, wc.is_active, wpi.product_id
        FROM webhook_configurations wc
        LEFT JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
        ORDER BY wc.id DESC
    ");
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <tr>
            <th>Webhook ID</th>
            <th>Webhook Name</th>
            <th>Active</th>
            <th>Product IDs</th>
        </tr>
        <?php 
        $grouped = [];
        foreach ($webhooks as $w) {
            if (!isset($grouped[$w['id']])) {
                $grouped[$w['id']] = [
                    'name' => $w['name'],
                    'is_active' => $w['is_active'],
                    'products' => []
                ];
            }
            if ($w['product_id']) {
                $grouped[$w['id']]['products'][] = $w['product_id'];
            }
        }
        
        foreach ($grouped as $id => $data): 
            $hasMyProduct = in_array('639493', $data['products']);
        ?>
        <tr class="<?= $hasMyProduct ? 'success' : '' ?>">
            <td><?= $id ?></td>
            <td><?= $data['name'] ?></td>
            <td class="<?= $data['is_active'] ? 'success' : 'error' ?>"><?= $data['is_active'] ? '✓ Aktiv' : '✗ Inaktiv' ?></td>
            <td class="<?= $hasMyProduct ? 'success' : '' ?>">
                <?= implode(', ', $data['products']) ?: 'KEINE' ?>
                <?= $hasMyProduct ? ' ⬅️ DEIN PRODUKT!' : '' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>4️⃣ Course Access (Webhook → Kurs)</h2>
    <?php
    $stmt = $pdo->query("
        SELECT wca.webhook_id, wca.course_id, c.title as course_title, wc.name as webhook_name
        FROM webhook_course_access wca
        LEFT JOIN courses c ON wca.course_id = c.id
        LEFT JOIN webhook_configurations wc ON wca.webhook_id = wc.id
        ORDER BY wca.webhook_id, wca.course_id
    ");
    $courseAccess = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <tr>
            <th>Webhook ID</th>
            <th>Webhook Name</th>
            <th>Course ID</th>
            <th>Course Title</th>
        </tr>
        <?php if (count($courseAccess) > 0): ?>
            <?php foreach ($courseAccess as $ca): ?>
            <tr>
                <td><?= $ca['webhook_id'] ?></td>
                <td><?= $ca['webhook_name'] ?></td>
                <td><?= $ca['course_id'] ?></td>
                <td><?= $ca['course_title'] ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="error">❌ KEINE COURSE ACCESS EINTRÄGE! Das ist das Problem!</td>
            </tr>
        <?php endif; ?>
    </table>
    
    <h2>📊 Zusammenfassung</h2>
    <div style="background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <p><strong>Problem gefunden:</strong></p>
        <?php
        $hasWebhookForProduct = false;
        foreach ($grouped as $id => $data) {
            if (in_array('639493', $data['products']) && $data['is_active']) {
                $hasWebhookForProduct = true;
                $webhookId = $id;
                break;
            }
        }
        
        if (!$hasWebhookForProduct) {
            echo '<p class="error">❌ Es gibt KEINEN aktiven Webhook für dein Produkt 639493!</p>';
            echo '<p>Lösung: Im Admin Dashboard einen Webhook für Produkt 639493 erstellen</p>';
        } else {
            echo '<p class="success">✓ Webhook gefunden für Produkt 639493 (Webhook ID: ' . $webhookId . ')</p>';
            
            // Check if webhook has course access
            $hasAccess = false;
            foreach ($courseAccess as $ca) {
                if ($ca['webhook_id'] == $webhookId) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                echo '<p class="error">❌ Dieser Webhook gewährt KEINEN Kurszugang!</p>';
                echo '<p>Lösung: Im Admin Dashboard die Kurse mit diesem Webhook verknüpfen</p>';
            }
        }
        ?>
    </div>
    
    <p style="margin-top: 40px; text-align: center;">
        <a href="test-freebies-debug.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
            ← Zurück zum Debug Test
        </a>
    </p>
</body>
</html>

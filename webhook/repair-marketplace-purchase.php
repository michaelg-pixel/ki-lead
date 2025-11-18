<?php
/**
 * 1-Klick Reparatur für leere Marktplatz-Käufe
 * Kopiert alle Felder vom Original zum gekauften Freebie
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Nur POST-Requests erlaubt');
}

$sourceId = (int)($_POST['source_id'] ?? 0);
$targetId = (int)($_POST['target_id'] ?? 0);
$buyerId = (int)($_POST['buyer_id'] ?? 0);

if (!$sourceId || !$targetId || !$buyerId) {
    die('Fehlende Parameter');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reparatur wird ausgeführt...</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        h1 {
            color: #667eea;
            text-align: center;
        }
        .spinner {
            text-align: center;
            font-size: 48px;
            margin: 20px 0;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(102, 126, 234, 0.2);
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Marktplatz-Kauf Reparatur</h1>

<?php
try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    echo '<div class="section">';
    echo '<h2>1️⃣ Lade Original-Freebie...</h2>';
    
    // Original-Freebie laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Original-Freebie nicht gefunden!');
    }
    
    echo '<p class="success">✅ Original-Freebie geladen (ID: ' . $sourceId . ')</p>';
    echo '<p><strong>Headline:</strong> ' . htmlspecialchars($source['headline']) . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>2️⃣ Lade gekauftes Freebie...</h2>';
    
    // Ziel-Freebie laden und Besitzer prüfen
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ? AND customer_id = ?");
    $stmt->execute([$targetId, $buyerId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target) {
        throw new Exception('Gekauftes Freebie nicht gefunden oder falscher Besitzer!');
    }
    
    echo '<p class="success">✅ Gekauftes Freebie geladen (ID: ' . $targetId . ')</p>';
    echo '<p><strong>Aktuelle Headline:</strong> ' . (empty($target['headline']) ? '<span class="error">(LEER)</span>' : htmlspecialchars($target['headline'])) . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>3️⃣ Kopiere Daten...</h2>';
    
    // Felder die kopiert werden sollen
    $fieldsToCopy = [
        'headline',
        'subheadline',
        'preheadline',
        'bullet_points',
        'mockup_image_url',
        'background_color',
        'primary_color',
        'cta_text',
        'layout',
        'email_field_text',
        'button_text',
        'privacy_checkbox_text',
        'thank_you_headline',
        'thank_you_message',
        'course_id',
        'niche'
    ];
    
    $copiedFields = [];
    $skippedFields = [];
    
    foreach ($fieldsToCopy as $field) {
        if (isset($source[$field])) {
            $copiedFields[] = $field;
        } else {
            $skippedFields[] = $field;
        }
    }
    
    // UPDATE Query bauen
    $setClause = [];
    $values = [];
    
    foreach ($copiedFields as $field) {
        $setClause[] = "$field = ?";
        $values[] = $source[$field];
    }
    
    $values[] = $targetId;
    $values[] = $buyerId;
    
    $sql = "UPDATE customer_freebies SET " . implode(', ', $setClause) . " WHERE id = ? AND customer_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    $affectedRows = $stmt->rowCount();
    
    echo '<p class="success">✅ ' . count($copiedFields) . ' Felder kopiert:</p>';
    echo '<ul>';
    foreach ($copiedFields as $field) {
        $sourceValue = $source[$field];
        $preview = is_null($sourceValue) ? '(NULL)' : (strlen($sourceValue) > 50 ? substr($sourceValue, 0, 50) . '...' : $sourceValue);
        echo '<li>' . htmlspecialchars($field) . ': <span class="success">' . htmlspecialchars($preview) . '</span></li>';
    }
    echo '</ul>';
    
    if (count($skippedFields) > 0) {
        echo '<p class="warning">⚠️ ' . count($skippedFields) . ' Felder übersprungen (nicht im Original vorhanden)</p>';
    }
    
    echo '<p><strong>Betroffene Zeilen:</strong> ' . $affectedRows . '</p>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>4️⃣ Prüfe Ergebnis...</h2>';
    
    // Prüfe das Ergebnis
    $stmt = $pdo->prepare("SELECT headline, bullet_points, mockup_image_url, course_id FROM customer_freebies WHERE id = ?");
    $stmt->execute([$targetId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $issues = [];
    
    if (empty($result['headline'])) {
        $issues[] = 'Headline ist noch leer';
    }
    if (empty($result['bullet_points'])) {
        $issues[] = 'Bullet Points sind noch leer';
    }
    
    if (count($issues) > 0) {
        echo '<p class="error">⚠️ Mögliche Probleme:</p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li class="error">' . $issue . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="success">✅ Alle wichtigen Felder sind jetzt gefüllt!</p>';
        echo '<table>';
        echo '<tr><th>Feld</th><th>Status</th></tr>';
        echo '<tr><td>Headline</td><td class="success">✓ ' . htmlspecialchars(substr($result['headline'], 0, 50)) . '...</td></tr>';
        echo '<tr><td>Bullet Points</td><td class="success">✓ ' . strlen($result['bullet_points']) . ' chars</td></tr>';
        echo '<tr><td>Mockup Image</td><td class="success">✓ ' . (empty($result['mockup_image_url']) ? 'N/A' : 'Vorhanden') . '</td></tr>';
        echo '<tr><td>Course ID</td><td class="success">✓ ' . ($result['course_id'] ?? 'N/A') . '</td></tr>';
        echo '</table>';
    }
    
    echo '</div>';
    
    // Commit
    $pdo->commit();
    
    echo '<div class="section">';
    echo '<h2 class="success">✅ REPARATUR ERFOLGREICH!</h2>';
    echo '<p>Das gekaufte Freebie (ID: ' . $targetId . ') hat jetzt den vollständigen Inhalt vom Original (ID: ' . $sourceId . ')!</p>';
    
    echo '<div style="margin: 20px 0;">';
    echo '<a href="diagnose-marketplace-purchase-v2.php?email=' . urlencode($_POST['buyer_email'] ?? '') . '&freebie_id=' . $sourceId . '" class="btn">📊 Zurück zur Diagnose</a>';
    echo '<a href="/customer/dashboard.php?page=freebies" class="btn">📦 Zu Meinen Freebies</a>';
    echo '<a href="/customer/dashboard.php?page=marktplatz" class="btn">🛍️ Zum Marktplatz</a>';
    echo '</div>';
    
    echo '</div>';
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo '<div class="section">';
    echo '<h2 class="error">💥 Fehler bei der Reparatur</h2>';
    echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre style="font-size: 12px; overflow-x: auto; background: #000; padding: 10px; border-radius: 4px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    
    echo '<div style="margin: 20px 0;">';
    echo '<a href="diagnose-marketplace-purchase-v2.php" class="btn">↩️ Zurück zur Diagnose</a>';
    echo '</div>';
    
    echo '</div>';
}
?>

    </div>
</body>
</html>
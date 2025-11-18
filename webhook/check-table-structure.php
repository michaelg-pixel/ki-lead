<?php
/**
 * Check: Welche Spalten hat die customer_freebies Tabelle?
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Tabellenstruktur Check</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: left;
        }
        th {
            background: rgba(102, 126, 234, 0.2);
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Tabellenstruktur Check</h1>

<?php
try {
    $pdo = getDBConnection();
    
    echo '<div class="section">';
    echo '<h2>1️⃣ Spalten in customer_freebies</h2>';
    
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<p class="success">✅ ' . count($columns) . ' Spalten gefunden</p>';
    echo '<table>';
    echo '<tr><th>Spalte</th><th>Typ</th><th>Null?</th><th>Key</th><th>Default</th></tr>';
    
    $columnNames = [];
    foreach ($columns as $col) {
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
        echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
        echo '</tr>';
        
        $columnNames[] = $col['Field'];
    }
    echo '</table>';
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>2️⃣ Felder die der Webhook versucht zu kopieren</h2>';
    
    $webhookFields = [
        'headline',
        'subheadline',
        'preheadline',
        'bullet_points',
        'mockup_image_url',
        'background_color',
        'primary_color',
        'cta_text',
        'layout',
        'email_field_text', // ❌ DIESE FEHLT!
        'button_text',
        'privacy_checkbox_text',
        'thank_you_headline',
        'thank_you_message',
        'course_id',
        'niche'
    ];
    
    $existingFields = [];
    $missingFields = [];
    
    foreach ($webhookFields as $field) {
        if (in_array($field, $columnNames)) {
            $existingFields[] = $field;
        } else {
            $missingFields[] = $field;
        }
    }
    
    echo '<h3 class="success">✅ Existierende Felder (' . count($existingFields) . '):</h3>';
    echo '<pre>' . implode("\n", $existingFields) . '</pre>';
    
    if (count($missingFields) > 0) {
        echo '<h3 class="error">❌ FEHLENDE Felder (' . count($missingFields) . '):</h3>';
        echo '<pre style="color: #ef4444;">' . implode("\n", $missingFields) . '</pre>';
        
        echo '<div style="background: rgba(239, 68, 68, 0.2); padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ef4444;">';
        echo '<h3 class="error">🔴 PROBLEM IDENTIFIZIERT</h3>';
        echo '<p>Der Webhook versucht diese Spalten zu kopieren, aber sie existieren nicht!</p>';
        echo '<p><strong>Lösung:</strong> Webhook-Code muss angepasst werden, um nur existierende Spalten zu nutzen.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>3️⃣ PHP Array für korrekten Webhook-Code</h2>';
    echo '<p>Kopiere dieses Array in den Webhook (nur existierende Felder):</p>';
    echo '<pre>';
    echo '$fieldsToCopy = [' . "\n";
    foreach ($existingFields as $field) {
        echo "    '" . $field . "',\n";
    }
    echo '];</pre>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">ERROR: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

</body>
</html>
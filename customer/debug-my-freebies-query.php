<?php
/**
 * DEBUG: my-freebies.php Query-Analyse
 */

session_start();
require_once '../config/database.php';

$conn = getDBConnection();
$customer_id = $_SESSION['user_id'] ?? 0;

$logs = [];
$query_results = [];

$logs[] = ['type' => 'info', 'msg' => "Session user_id: $customer_id"];

if (!$customer_id) {
    die('❌ Keine Session user_id!');
}

try {
    // EXAKT die Query aus my-freebies.php
    $logs[] = ['type' => 'info', 'msg' => 'Führe Query aus my-freebies.php aus...'];
    
    $stmt = $conn->prepare("
        SELECT 
            cf.*,
            c.title as course_title,
            c.thumbnail as course_thumbnail
        FROM customer_freebies cf
        LEFT JOIN courses c ON cf.course_id = c.id
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $logs[] = ['type' => 'success', 'msg' => count($my_freebies) . ' Freebies gefunden!'];
    
    if (empty($my_freebies)) {
        $logs[] = ['type' => 'error', 'msg' => 'EMPTY! my-freebies.php zeigt "Noch keine eigenen Freebies"'];
        
        // Zusatz-Query: Gibt es ÜBERHAUPT Freebies für diesen User?
        $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM customer_freebies WHERE customer_id = ?");
        $stmt2->execute([$customer_id]);
        $totalCount = $stmt2->fetchColumn();
        
        $logs[] = ['type' => 'info', 'msg' => "Gesamt Freebies für User $customer_id: $totalCount"];
        
        if ($totalCount > 0) {
            // Zeige alle Freebies
            $stmt3 = $conn->prepare("SELECT id, headline, customer_id, freebie_type, copied_from_freebie_id FROM customer_freebies WHERE customer_id = ?");
            $stmt3->execute([$customer_id]);
            $all = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            
            $logs[] = ['type' => 'warning', 'msg' => 'Freebies EXISTIEREN aber werden nicht angezeigt:'];
            foreach ($all as $f) {
                $logs[] = ['type' => 'info', 'msg' => "ID: {$f['id']}, Headline: {$f['headline']}, Type: {$f['freebie_type']}, Copied From: {$f['copied_from_freebie_id']}"];
            }
        }
    } else {
        foreach ($my_freebies as $f) {
            $query_results[] = [
                'id' => $f['id'],
                'headline' => $f['headline'],
                'name' => $f['name'],
                'freebie_type' => $f['freebie_type'],
                'copied_from' => $f['copied_from_freebie_id'],
                'course_title' => $f['course_title']
            ];
        }
    }
    
} catch (Exception $e) {
    $logs[] = ['type' => 'error', 'msg' => 'Fehler: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 my-freebies.php Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        .log-item {
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
        }
        .log-info { background: #e0f2fe; color: #0369a1; }
        .log-success { background: #dcfce7; color: #15803d; }
        .log-warning { background: #fef3c7; color: #ca8a04; }
        .log-error { background: #fee2e2; color: #dc2626; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 my-freebies.php Debug</h1>
            <p>Analysiert warum my-freebies.php keine Freebies anzeigt</p>
        </div>
        
        <div class="card">
            <h2>📋 Protokoll</h2>
            <?php foreach ($logs as $log): ?>
                <div class="log-item log-<?php echo $log['type']; ?>">
                    <?php echo htmlspecialchars($log['msg']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($query_results)): ?>
            <div class="card">
                <h2>✅ Gefundene Freebies</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Headline</th>
                            <th>Type</th>
                            <th>Copied From</th>
                            <th>Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($query_results as $r): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['name'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['headline']); ?></td>
                                <td><?php echo $r['freebie_type'] ?: 'NULL'; ?></td>
                                <td><?php echo $r['copied_from'] ?: 'NULL'; ?></td>
                                <td><?php echo htmlspecialchars($r['course_title'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
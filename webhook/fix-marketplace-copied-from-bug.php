<?php
/**
 * FIX: Marktplatz copied_from_freebie_id Bug
 * 
 * Problem: copied_from_freebie_id zeigt auf Käufer-ID statt Original-Freebie-ID
 * Lösung: Korrekte Original-Freebie-ID finden und aktualisieren
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

$logs = [];
$fixed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
    try {
        $pdo = getDBConnection();
        
        // SCHRITT 1: Alle fehlerhaften Freebies finden
        $logs[] = ['type' => 'info', 'msg' => 'Suche fehlerhafte Freebies...'];
        
        $stmt = $pdo->query("
            SELECT 
                cf1.id,
                cf1.customer_id as buyer_id,
                cf1.headline,
                cf1.copied_from_freebie_id as wrong_id,
                cf1.original_creator_id as seller_id
            FROM customer_freebies cf1
            WHERE cf1.copied_from_freebie_id IS NOT NULL
            AND cf1.original_creator_id IS NOT NULL
            AND cf1.copied_from_freebie_id = cf1.customer_id
        ");
        
        $buggyFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($buggyFreebies)) {
            $logs[] = ['type' => 'success', 'msg' => '✅ Keine fehlerhaften Freebies gefunden!'];
        } else {
            $logs[] = ['type' => 'warning', 'msg' => count($buggyFreebies) . ' fehlerhafte Freebies gefunden!'];
            
            foreach ($buggyFreebies as $buggy) {
                $logs[] = ['type' => 'info', 'msg' => "Bearbeite Freebie ID {$buggy['id']}: {$buggy['headline']}"];
                
                // Original-Freebie vom Verkäufer finden
                $stmt = $pdo->prepare("
                    SELECT id, headline
                    FROM customer_freebies
                    WHERE customer_id = ?
                    AND marketplace_enabled = 1
                    AND headline = ?
                    LIMIT 1
                ");
                $stmt->execute([$buggy['seller_id'], $buggy['headline']]);
                $original = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$original) {
                    // Plan B: Suche ähnliche Headline
                    $stmt = $pdo->prepare("
                        SELECT id, headline
                        FROM customer_freebies
                        WHERE customer_id = ?
                        AND marketplace_enabled = 1
                        AND headline LIKE ?
                        LIMIT 1
                    ");
                    $searchTerm = '%' . substr($buggy['headline'], 0, 30) . '%';
                    $stmt->execute([$buggy['seller_id'], $searchTerm]);
                    $original = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                if (!$original) {
                    $logs[] = ['type' => 'error', 'msg' => "  ❌ Kein Original-Freebie gefunden für ID {$buggy['id']}"];
                    continue;
                }
                
                $logs[] = ['type' => 'info', 'msg' => "  Gefunden: Original-Freebie ID {$original['id']}"];
                
                // Korrektur durchführen
                $stmt = $pdo->prepare("
                    UPDATE customer_freebies
                    SET copied_from_freebie_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$original['id'], $buggy['id']]);
                
                $logs[] = ['type' => 'success', 'msg' => "  ✅ Korrigiert: {$buggy['wrong_id']} → {$original['id']}"];
            }
            
            $fixed = true;
            $logs[] = ['type' => 'success', 'msg' => "\n🎉 ALLE FEHLER BEHOBEN!"];
        }
        
    } catch (Exception $e) {
        $logs[] = ['type' => 'error', 'msg' => '❌ Fehler: ' . $e->getMessage()];
    }
}

// Aktuelle fehlerhafte Einträge anzeigen
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            cf1.id,
            cf1.customer_id as buyer_id,
            cf1.headline,
            cf1.copied_from_freebie_id,
            cf1.original_creator_id as seller_id,
            CASE 
                WHEN cf1.copied_from_freebie_id = cf1.customer_id THEN '❌ FALSCH'
                ELSE '✅ OK'
            END as status
        FROM customer_freebies cf1
        WHERE cf1.copied_from_freebie_id IS NOT NULL
        AND cf1.original_creator_id IS NOT NULL
        ORDER BY cf1.id DESC
    ");
    $allCopiedFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allCopiedFreebies = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Marktplatz Bug Fix</title>
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
        .header p { color: #666; line-height: 1.6; }
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
            margin: 20px 0;
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
        tr:hover { background: #f9fafb; }
        .status-ok { color: #22c55e; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        .btn-fix {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .btn-fix:hover { transform: translateY(-2px); }
        .btn-fix:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
        }
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
        .log-error { background: #fee2e2; color: #dc2626; }
        .warning-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .warning-box h3 { color: #ca8a04; margin-bottom: 12px; }
        .success-box {
            background: #dcfce7;
            border: 2px solid #22c55e;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .success-box h3 { color: #15803d; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Marktplatz Bug Fix</h1>
            <p><strong>Problem:</strong> Bei gekauften Marktplatz-Freebies wurde <code>copied_from_freebie_id</code> fälschlicherweise auf die Käufer-ID gesetzt, statt auf die Original-Freebie-ID des Verkäufers.</p>
            <p><strong>Lösung:</strong> Dieses Tool findet automatisch die korrekten Original-Freebie-IDs und korrigiert die fehlerhaften Einträge.</p>
        </div>
        
        <?php if (!empty($logs)): ?>
            <div class="card">
                <h2>📋 Protokoll</h2>
                <?php foreach ($logs as $log): ?>
                    <div class="log-item log-<?php echo $log['type']; ?>">
                        <?php echo htmlspecialchars($log['msg']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($fixed): ?>
            <div class="success-box">
                <h3>🎉 Bug erfolgreich behoben!</h3>
                <p>Alle fehlerhaften copied_from_freebie_id wurden korrigiert.</p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📊 Alle gekauften Freebies</h2>
            
            <?php 
            $errorCount = count(array_filter($allCopiedFreebies, fn($f) => strpos($f['status'], '❌') !== false));
            ?>
            
            <?php if ($errorCount > 0): ?>
                <div class="warning-box">
                    <h3>⚠️ <?php echo $errorCount; ?> fehlerhafte Einträge gefunden!</h3>
                    <p>Klicke auf "Fehler beheben" um alle fehlerhaften Einträge automatisch zu korrigieren.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="fix" value="1">
                    <button type="submit" class="btn-fix">🔧 Fehler beheben</button>
                </form>
            <?php else: ?>
                <div class="success-box">
                    <h3>✅ Alle Einträge sind korrekt!</h3>
                    <p>Keine fehlerhaften copied_from_freebie_id gefunden.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($allCopiedFreebies)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Käufer ID</th>
                            <th>Headline</th>
                            <th>copied_from</th>
                            <th>Verkäufer ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCopiedFreebies as $f): ?>
                            <tr>
                                <td><?php echo $f['id']; ?></td>
                                <td><?php echo $f['buyer_id']; ?></td>
                                <td><?php echo htmlspecialchars(substr($f['headline'], 0, 50)); ?>...</td>
                                <td><?php echo $f['copied_from_freebie_id']; ?></td>
                                <td><?php echo $f['seller_id']; ?></td>
                                <td class="<?php echo strpos($f['status'], '✅') !== false ? 'status-ok' : 'status-error'; ?>">
                                    <?php echo $f['status']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Noch keine gekauften Freebies vorhanden.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
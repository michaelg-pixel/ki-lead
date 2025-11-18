<?php
/**
 * FIX: Marktplatz copied_from_freebie_id Bug - VERBESSERTE VERSION
 * 
 * Problem: copied_from_freebie_id zeigt auf falsche ID
 * Lösung: Manuelle Auswahl des korrekten Original-Freebies
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

$logs = [];
$fixed = false;
$analysisData = [];

// ANALYSE-MODUS
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_POST['fix'])) {
    try {
        $pdo = getDBConnection();
        
        // Finde fehlerhafte Freebies
        $stmt = $pdo->query("
            SELECT 
                cf.id,
                cf.customer_id as buyer_id,
                cf.headline,
                cf.copied_from_freebie_id,
                cf.original_creator_id as seller_id,
                cf.freebie_type
            FROM customer_freebies cf
            WHERE cf.copied_from_freebie_id IS NOT NULL
            AND cf.original_creator_id IS NOT NULL
            ORDER BY cf.id DESC
        ");
        
        $copiedFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($copiedFreebies as $copied) {
            $analysis = [
                'freebie' => $copied,
                'status' => 'unknown',
                'seller_freebies' => [],
                'correct_original' => null
            ];
            
            // Prüfe ob copied_from gleich buyer_id ist (= FEHLER)
            if ($copied['copied_from_freebie_id'] == $copied['buyer_id']) {
                $analysis['status'] = 'error';
                $analysis['error_type'] = 'copied_from_freebie_id ist gleich buyer_id';
            } else {
                // Prüfe ob die copied_from_freebie_id wirklich zum Verkäufer gehört
                $stmt = $pdo->prepare("
                    SELECT id, customer_id 
                    FROM customer_freebies 
                    WHERE id = ?
                ");
                $stmt->execute([$copied['copied_from_freebie_id']]);
                $referenced = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$referenced) {
                    $analysis['status'] = 'error';
                    $analysis['error_type'] = 'copied_from_freebie_id existiert nicht';
                } elseif ($referenced['customer_id'] != $copied['seller_id']) {
                    $analysis['status'] = 'error';
                    $analysis['error_type'] = 'copied_from_freebie_id gehört nicht dem Verkäufer';
                } else {
                    $analysis['status'] = 'ok';
                }
            }
            
            // Wenn Fehler: Finde ALLE Marktplatz-Freebies vom Verkäufer
            if ($analysis['status'] === 'error') {
                $stmt = $pdo->prepare("
                    SELECT 
                        id, 
                        headline,
                        marketplace_enabled,
                        marketplace_price,
                        digistore_product_id,
                        COALESCE(marketplace_sales_count, 0) as sales
                    FROM customer_freebies
                    WHERE customer_id = ?
                    AND marketplace_enabled = 1
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$copied['seller_id']]);
                $analysis['seller_freebies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Versuche automatisch das richtige zu finden (gleiche Headline)
                foreach ($analysis['seller_freebies'] as $sf) {
                    if ($sf['headline'] === $copied['headline']) {
                        $analysis['correct_original'] = $sf['id'];
                        break;
                    }
                }
            }
            
            $analysisData[] = $analysis;
        }
        
    } catch (Exception $e) {
        $logs[] = ['type' => 'error', 'msg' => '❌ Fehler: ' . $e->getMessage()];
    }
}

// FIX-MODUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
    try {
        $pdo = getDBConnection();
        
        $freebieId = $_POST['freebie_id'] ?? 0;
        $correctOriginalId = $_POST['correct_original_id'] ?? 0;
        
        if (!$freebieId || !$correctOriginalId) {
            throw new Exception("Ungültige Parameter!");
        }
        
        $logs[] = ['type' => 'info', 'msg' => "Korrigiere Freebie ID $freebieId..."];
        
        // Hole aktuelle Daten
        $stmt = $pdo->prepare("
            SELECT id, customer_id, copied_from_freebie_id, original_creator_id
            FROM customer_freebies WHERE id = ?
        ");
        $stmt->execute([$freebieId]);
        $before = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$before) {
            throw new Exception("Freebie nicht gefunden!");
        }
        
        $logs[] = ['type' => 'info', 'msg' => "Vorher: copied_from_freebie_id = " . $before['copied_from_freebie_id']];
        
        // Validiere dass die correct_original_id wirklich zum Verkäufer gehört
        $stmt = $pdo->prepare("
            SELECT customer_id FROM customer_freebies WHERE id = ?
        ");
        $stmt->execute([$correctOriginalId]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$original) {
            throw new Exception("Original-Freebie ID $correctOriginalId existiert nicht!");
        }
        
        if ($original['customer_id'] != $before['original_creator_id']) {
            throw new Exception("Original-Freebie gehört nicht dem Verkäufer!");
        }
        
        // Korrektur durchführen
        $stmt = $pdo->prepare("
            UPDATE customer_freebies
            SET copied_from_freebie_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$correctOriginalId, $freebieId]);
        
        $logs[] = ['type' => 'success', 'msg' => "Nachher: copied_from_freebie_id = $correctOriginalId"];
        $logs[] = ['type' => 'success', 'msg' => "✅ Erfolgreich korrigiert!"];
        
        $fixed = true;
        
    } catch (Exception $e) {
        $logs[] = ['type' => 'error', 'msg' => '❌ Fehler: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Marktplatz Bug Fix v2</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
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
        .analysis-item {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .analysis-item.error { border-color: #ef4444; background: #fee2e2; }
        .analysis-item.ok { border-color: #22c55e; background: #f0fdf4; }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 12px;
            margin: 16px 0;
        }
        .info-label { font-weight: 600; color: #666; }
        .info-value { font-family: monospace; }
        .error-box {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            color: #dc2626;
            font-weight: 600;
        }
        .seller-freebies {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 16px 0;
        }
        .seller-freebie-item {
            background: white;
            padding: 16px;
            margin: 12px 0;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
        }
        .seller-freebie-item:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .seller-freebie-item.suggested {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        .btn-fix {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 16px;
        }
        .btn-fix:hover { transform: translateY(-2px); }
        .log-item {
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
        }
        .log-info { background: #e0f2fe; color: #0369a1; }
        .log-success { background: #dcfce7; color: #15803d; }
        .log-error { background: #fee2e2; color: #dc2626; }
        .success-box {
            background: #dcfce7;
            border: 2px solid #22c55e;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            color: #15803d;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-error { background: #ef4444; color: white; }
        .badge-ok { background: #22c55e; color: white; }
        .badge-warning { background: #fbbf24; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Marktplatz Bug Fix</h1>
            <span class="badge badge-warning">v2 - Verbessert</span>
            <p style="margin-top: 12px;">Detaillierte Analyse und manuelle Korrektur der copied_from_freebie_id</p>
        </div>
        
        <?php if (!empty($logs)): ?>
            <div class="card">
                <h2>📋 Protokoll</h2>
                <?php foreach ($logs as $log): ?>
                    <div class="log-item log-<?php echo $log['type']; ?>">
                        <?php echo htmlspecialchars($log['msg']); ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($fixed): ?>
                    <div class="success-box" style="margin-top: 20px;">
                        <h3>✅ Erfolgreich korrigiert!</h3>
                        <p>Das Freebie sollte jetzt im Dashboard sichtbar sein.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($analysisData)): ?>
            <div class="card">
                <h2>🔍 Analyse aller gekauften Freebies</h2>
                
                <?php foreach ($analysisData as $analysis): ?>
                    <div class="analysis-item <?php echo $analysis['status']; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3>Freebie ID: <?php echo $analysis['freebie']['id']; ?></h3>
                            <span class="badge badge-<?php echo $analysis['status'] === 'error' ? 'error' : 'ok'; ?>">
                                <?php echo $analysis['status'] === 'error' ? '❌ FEHLER' : '✅ OK'; ?>
                            </span>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-label">Headline:</div>
                            <div class="info-value"><?php echo htmlspecialchars($analysis['freebie']['headline']); ?></div>
                            
                            <div class="info-label">Käufer ID:</div>
                            <div class="info-value"><?php echo $analysis['freebie']['buyer_id']; ?></div>
                            
                            <div class="info-label">Verkäufer ID:</div>
                            <div class="info-value"><?php echo $analysis['freebie']['seller_id']; ?></div>
                            
                            <div class="info-label">copied_from:</div>
                            <div class="info-value"><?php echo $analysis['freebie']['copied_from_freebie_id']; ?></div>
                            
                            <div class="info-label">Freebie Type:</div>
                            <div class="info-value"><?php echo $analysis['freebie']['freebie_type']; ?></div>
                        </div>
                        
                        <?php if ($analysis['status'] === 'error'): ?>
                            <div class="error-box">
                                ⚠️ <?php echo $analysis['error_type']; ?>
                            </div>
                            
                            <?php if (!empty($analysis['seller_freebies'])): ?>
                                <div class="seller-freebies">
                                    <h4>📦 Verfügbare Marktplatz-Freebies vom Verkäufer (ID <?php echo $analysis['freebie']['seller_id']; ?>):</h4>
                                    <p style="color: #666; margin: 12px 0;">Wähle das korrekte Original-Freebie aus:</p>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="fix" value="1">
                                        <input type="hidden" name="freebie_id" value="<?php echo $analysis['freebie']['id']; ?>">
                                        
                                        <?php foreach ($analysis['seller_freebies'] as $sf): ?>
                                            <label class="seller-freebie-item <?php echo $sf['id'] == $analysis['correct_original'] ? 'suggested' : ''; ?>">
                                                <input type="radio" name="correct_original_id" value="<?php echo $sf['id']; ?>" 
                                                       <?php echo $sf['id'] == $analysis['correct_original'] ? 'checked' : ''; ?> required>
                                                <div style="margin-left: 8px;">
                                                    <strong>ID: <?php echo $sf['id']; ?></strong> - <?php echo htmlspecialchars($sf['headline']); ?>
                                                    <?php if ($sf['id'] == $analysis['correct_original']): ?>
                                                        <span class="badge badge-ok" style="margin-left: 8px;">✓ Empfohlen</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <small style="color: #666;">
                                                        Preis: <?php echo number_format($sf['marketplace_price'], 2, ',', '.'); ?> € | 
                                                        Verkäufe: <?php echo $sf['sales']; ?> | 
                                                        Product-ID: <?php echo $sf['digistore_product_id'] ?: 'N/A'; ?>
                                                    </small>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                        
                                        <button type="submit" class="btn-fix">🔧 Jetzt korrigieren</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="error-box">
                                    ❌ Verkäufer hat KEINE Marktplatz-Freebies!
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
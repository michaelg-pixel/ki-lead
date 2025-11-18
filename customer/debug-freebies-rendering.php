<?php
/**
 * DEBUG: Warum werden Marktplatz-Freebies nicht angezeigt?
 * Detaillierte Analyse der freebies.php Rendering-Logik
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Admin kann User ID als Parameter übergeben
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$customer_id = $isAdmin && isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($_SESSION['user_id'] ?? 0);

if (!$customer_id) {
    die('❌ Nicht eingeloggt');
}

$logs = [];
$freebies_data = [];
$user_info = null;

try {
    $pdo = getDBConnection();
    
    // User Info laden
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        die('❌ User nicht gefunden');
    }
    
    $logs[] = ['type' => 'info', 'msg' => "Analyse für User ID: $customer_id"];
    $logs[] = ['type' => 'info', 'msg' => "Name: {$user_info['name']}"];
    $logs[] = ['type' => 'info', 'msg' => "Email: {$user_info['email']}"];
    
    // SCHRITT 1: Query ausführen (EXAKT wie in freebies.php!)
    $logs[] = ['type' => 'info', 'msg' => 'Führe Query aus (wie in freebies.php)...'];
    
    $stmt_custom = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE customer_id = ? 
        AND (
            freebie_type = 'custom' 
            OR copied_from_freebie_id IS NOT NULL
            OR original_creator_id IS NOT NULL
        )
        ORDER BY updated_at DESC, created_at DESC
    ");
    $stmt_custom->execute([$customer_id]);
    $custom_freebies = $stmt_custom->fetchAll(PDO::FETCH_ASSOC);
    
    $logs[] = ['type' => 'success', 'msg' => count($custom_freebies) . ' Freebies gefunden!'];
    
    if (empty($custom_freebies)) {
        $logs[] = ['type' => 'warning', 'msg' => 'Keine Freebies gefunden! Dashboard zeigt "Noch keine eigenen Freebies"'];
        
        // Zusätzliche Analyse: Gibt es überhaupt Freebies für diesen User?
        $stmt_any = $pdo->prepare("SELECT COUNT(*) as count FROM customer_freebies WHERE customer_id = ?");
        $stmt_any->execute([$customer_id]);
        $anyCount = $stmt_any->fetchColumn();
        
        if ($anyCount > 0) {
            $logs[] = ['type' => 'error', 'msg' => "ACHTUNG: Es gibt $anyCount Freebies für diesen User, aber sie erfüllen nicht die Query-Bedingungen!"];
            
            // Zeige diese Freebies
            $stmt_all = $pdo->prepare("SELECT id, headline, freebie_type, copied_from_freebie_id, original_creator_id FROM customer_freebies WHERE customer_id = ?");
            $stmt_all->execute([$customer_id]);
            $allFreebies = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
            
            $logs[] = ['type' => 'info', 'msg' => 'Freebies die NICHT in der Query sind:'];
            foreach ($allFreebies as $f) {
                $reason = [];
                if ($f['freebie_type'] !== 'custom' && empty($f['copied_from_freebie_id']) && empty($f['original_creator_id'])) {
                    $reason[] = 'freebie_type != custom';
                    $reason[] = 'copied_from_freebie_id IS NULL';
                    $reason[] = 'original_creator_id IS NULL';
                }
                $logs[] = ['type' => 'warning', 'msg' => "ID {$f['id']}: {$f['headline']} | Grund: " . implode(', ', $reason)];
            }
        }
    }
    
    // SCHRITT 2: Jedes Freebie analysieren
    foreach ($custom_freebies as $index => $freebie) {
        $analysis = [
            'index' => $index + 1,
            'id' => $freebie['id'],
            'headline' => $freebie['headline'],
            'issues' => [],
            'warnings' => [],
            'data' => []
        ];
        
        // Prüfe PFLICHTFELDER
        $requiredFields = ['id', 'customer_id', 'headline', 'unique_id'];
        foreach ($requiredFields as $field) {
            if (empty($freebie[$field])) {
                $analysis['issues'][] = "FEHLT: $field";
            }
        }
        
        // Prüfe optionale aber wichtige Felder
        $analysis['data']['created_at'] = $freebie['created_at'] ?? 'NULL';
        $analysis['data']['updated_at'] = $freebie['updated_at'] ?? 'NULL';
        $analysis['data']['url_slug'] = $freebie['url_slug'] ?? 'NULL';
        $analysis['data']['unique_id'] = $freebie['unique_id'] ?? 'NULL';
        $analysis['data']['mockup_image_url'] = $freebie['mockup_image_url'] ?? 'NULL';
        $analysis['data']['background_color'] = $freebie['background_color'] ?? 'NULL';
        $analysis['data']['primary_color'] = $freebie['primary_color'] ?? 'NULL';
        $analysis['data']['freebie_type'] = $freebie['freebie_type'] ?? 'NULL';
        $analysis['data']['copied_from_freebie_id'] = $freebie['copied_from_freebie_id'] ?? 'NULL';
        $analysis['data']['original_creator_id'] = $freebie['original_creator_id'] ?? 'NULL';
        
        // Prüfe DateTime Parsing
        $dateValue = $freebie['updated_at'] ?: $freebie['created_at'];
        if (empty($dateValue)) {
            $analysis['issues'][] = 'CRITICAL: Kein Datum vorhanden (weder updated_at noch created_at)!';
        } else {
            try {
                $date = new DateTime($dateValue);
                $analysis['data']['formatted_date'] = $date->format('d.m.Y');
            } catch (Exception $e) {
                $analysis['issues'][] = 'DateTime Fehler: ' . $e->getMessage();
            }
        }
        
        // Prüfe identifier
        $identifier = $freebie['url_slug'] ?: $freebie['unique_id'];
        if (empty($identifier)) {
            $analysis['issues'][] = 'CRITICAL: Kein Identifier (weder url_slug noch unique_id)!';
        } else {
            $analysis['data']['identifier'] = $identifier;
        }
        
        // Prüfe URLs
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        
        $preview_url = 'freebie-preview.php?id=' . $freebie['id'];
        $live_url = $protocol . '://' . $domain . '/freebie/' . $identifier;
        
        $analysis['data']['preview_url'] = $preview_url;
        $analysis['data']['live_url'] = $live_url;
        
        // Prüfe Farben
        $bgColor = $freebie['background_color'] ?: '#667eea';
        $primaryColor = $freebie['primary_color'] ?: '#667eea';
        
        if (empty($freebie['background_color'])) {
            $analysis['warnings'][] = 'Keine background_color - nutzt Fallback';
        }
        if (empty($freebie['primary_color'])) {
            $analysis['warnings'][] = 'Keine primary_color - nutzt Fallback';
        }
        
        // Prüfe Marktplatz-Status
        $isMarketplace = !empty($freebie['copied_from_freebie_id']);
        $analysis['data']['is_marketplace'] = $isMarketplace ? 'JA' : 'NEIN';
        
        // Prüfe has_course
        $analysis['data']['has_course'] = isset($freebie['has_course']) ? 'JA' : 'NEIN';
        
        // Status zusammenfassen
        if (!empty($analysis['issues'])) {
            $analysis['status'] = 'error';
            $analysis['result'] = '❌ WIRD NICHT ANGEZEIGT';
        } elseif (!empty($analysis['warnings'])) {
            $analysis['status'] = 'warning';
            $analysis['result'] = '⚠️ Könnte Probleme haben';
        } else {
            $analysis['status'] = 'success';
            $analysis['result'] = '✅ SOLLTE ANGEZEIGT WERDEN';
        }
        
        $freebies_data[] = $analysis;
    }
    
    $logs[] = ['type' => 'success', 'msg' => 'Analyse abgeschlossen!'];
    
} catch (PDOException $e) {
    $logs[] = ['type' => 'error', 'msg' => '❌ Datenbank-Fehler: ' . $e->getMessage()];
} catch (Exception $e) {
    $logs[] = ['type' => 'error', 'msg' => '❌ Fehler: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Freebies Rendering Debug</title>
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
        .user-info {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
        }
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
        .log-error { background: #fee2e2; color: #dc2626; }
        .freebie-analysis {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .freebie-analysis.error { border-color: #ef4444; background: #fee2e2; }
        .freebie-analysis.warning { border-color: #fbbf24; background: #fef3c7; }
        .freebie-analysis.success { border-color: #22c55e; background: #f0fdf4; }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 12px;
            margin: 16px 0;
            font-size: 14px;
        }
        .info-label { font-weight: 600; color: #666; }
        .info-value { font-family: monospace; word-break: break-all; }
        .issue-box {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
        }
        .issue-item {
            color: #dc2626;
            padding: 8px 0;
            font-weight: 600;
        }
        .warning-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
        }
        .warning-item {
            color: #ca8a04;
            padding: 8px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-error { background: #ef4444; color: white; }
        .badge-warning { background: #fbbf24; color: white; }
        .badge-success { background: #22c55e; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Freebies Rendering Debug</h1>
            <p>Detaillierte Analyse warum Marktplatz-Freebies nicht im Dashboard angezeigt werden</p>
            
            <?php if ($user_info): ?>
                <div class="user-info">
                    <strong>Analysiert für:</strong><br>
                    <?php echo htmlspecialchars($user_info['name']); ?> 
                    (<?php echo htmlspecialchars($user_info['email']); ?>) 
                    - User ID: <?php echo $user_info['id']; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📋 Protokoll</h2>
            <?php foreach ($logs as $log): ?>
                <div class="log-item log-<?php echo $log['type']; ?>">
                    <?php echo htmlspecialchars($log['msg']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($freebies_data)): ?>
            <div class="card">
                <h2>🔬 Freebie-Analyse</h2>
                <p style="color: #666; margin-bottom: 24px;">
                    Jedes Freebie wird auf fehlende Felder und Rendering-Probleme geprüft
                </p>
                
                <?php foreach ($freebies_data as $analysis): ?>
                    <div class="freebie-analysis <?php echo $analysis['status']; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3><?php echo $analysis['index']; ?>. <?php echo htmlspecialchars($analysis['headline']); ?></h3>
                            <span class="badge badge-<?php echo $analysis['status']; ?>">
                                <?php echo $analysis['result']; ?>
                            </span>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-label">Freebie ID:</div>
                            <div class="info-value"><?php echo $analysis['id']; ?></div>
                        </div>
                        
                        <?php if (!empty($analysis['issues'])): ?>
                            <div class="issue-box">
                                <h4 style="color: #dc2626; margin-bottom: 12px;">❌ Kritische Probleme:</h4>
                                <?php foreach ($analysis['issues'] as $issue): ?>
                                    <div class="issue-item">• <?php echo htmlspecialchars($issue); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($analysis['warnings'])): ?>
                            <div class="warning-box">
                                <h4 style="color: #ca8a04; margin-bottom: 12px;">⚠️ Warnungen:</h4>
                                <?php foreach ($analysis['warnings'] as $warning): ?>
                                    <div class="warning-item">• <?php echo htmlspecialchars($warning); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <details style="margin-top: 16px;">
                            <summary style="cursor: pointer; font-weight: 600; color: #667eea;">📊 Alle Daten anzeigen</summary>
                            <div class="info-grid" style="margin-top: 16px;">
                                <?php foreach ($analysis['data'] as $key => $value): ?>
                                    <div class="info-label"><?php echo htmlspecialchars($key); ?>:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($value); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
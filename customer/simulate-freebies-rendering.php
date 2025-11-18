<?php
/**
 * RENDERING SIMULATION - Simuliert die komplette freebies.php Logik
 * Zeigt GENAU wo beim Rendering ein Fehler auftritt
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Admin kann User ID als Parameter übergeben
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$customer_id = $isAdmin && isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($_SESSION['user_id'] ?? 0);

if (!$customer_id) {
    die('❌ Nicht eingeloggt');
}

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logs = [];
$rendered_html = [];
$errors = [];

try {
    $pdo = getDBConnection();
    
    // User Info
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $logs[] = ['type' => 'info', 'msg' => "Simuliere Rendering für: {$user_info['name']} (ID: $customer_id)"];
    
    // EXAKT die Query aus freebies.php
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
    
    $logs[] = ['type' => 'success', 'msg' => count($custom_freebies) . ' Freebies gefunden'];
    
    if (empty($custom_freebies)) {
        $logs[] = ['type' => 'warning', 'msg' => 'Keine Freebies - Dashboard zeigt leeren Zustand'];
    } else {
        // Domain für URLs
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        
        // SIMULIERE DIE FOREACH SCHLEIFE
        $logs[] = ['type' => 'info', 'msg' => 'Starte Rendering-Simulation...'];
        
        foreach ($custom_freebies as $index => $freebie) {
            $freebie_id = $freebie['id'];
            $logs[] = ['type' => 'info', 'msg' => "Rendering Freebie #" . ($index + 1) . " (ID: $freebie_id)"];
            
            $step_errors = [];
            $html_parts = [];
            
            try {
                // SCHRITT 1: Farben
                $bgColor = $freebie['background_color'] ?: '#667eea';
                $primaryColor = $freebie['primary_color'] ?: '#667eea';
                $html_parts[] = "Farben: bg=$bgColor, primary=$primaryColor";
                
                // SCHRITT 2: Preview URL
                $preview_url = 'freebie-preview.php?id=' . $freebie['id'];
                $html_parts[] = "Preview URL: $preview_url";
                
                // SCHRITT 3: Identifier
                $identifier = $freebie['url_slug'] ?: $freebie['unique_id'];
                if (!$identifier) {
                    throw new Exception("Kein Identifier (weder url_slug noch unique_id)!");
                }
                $html_parts[] = "Identifier: $identifier";
                
                // SCHRITT 4: Live URL
                $live_url = $protocol . '://' . $domain . '/freebie/' . $identifier;
                $html_parts[] = "Live URL: $live_url";
                
                // SCHRITT 5: Datum formatieren
                $dateValue = $freebie['updated_at'] ?: $freebie['created_at'];
                if (!$dateValue) {
                    throw new Exception("Kein Datum vorhanden!");
                }
                $date = new DateTime($dateValue);
                $formattedDate = $date->format('d.m.Y');
                $html_parts[] = "Datum: $formattedDate";
                
                // SCHRITT 6: Marktplatz-Check
                $isMarketplace = !empty($freebie['copied_from_freebie_id']);
                $html_parts[] = "Marktplatz: " . ($isMarketplace ? 'JA' : 'NEIN');
                
                // SCHRITT 7: has_course Check - HIER IST WAHRSCHEINLICH DER FEHLER!
                if (isset($freebie['has_course'])) {
                    $html_parts[] = "has_course: " . ($freebie['has_course'] ? 'JA' : 'NEIN');
                } else {
                    $step_errors[] = "WARNUNG: has_course existiert nicht im Array!";
                }
                
                // SCHRITT 8: Headline
                $headline = htmlspecialchars($freebie['headline']);
                $html_parts[] = "Headline: $headline";
                
                // SCHRITT 9: Subheadline (optional)
                if (!empty($freebie['subheadline'])) {
                    $subheadline = htmlspecialchars($freebie['subheadline']);
                    $html_parts[] = "Subheadline: $subheadline";
                }
                
                // SCHRITT 10: Mockup Image (optional)
                if (!empty($freebie['mockup_image_url'])) {
                    $html_parts[] = "Mockup: " . htmlspecialchars($freebie['mockup_image_url']);
                } else {
                    $html_parts[] = "Mockup: Placeholder (Emoji)";
                }
                
                // ERFOLG!
                $logs[] = ['type' => 'success', 'msg' => "✅ Freebie $freebie_id erfolgreich gerendert!"];
                if (!empty($step_errors)) {
                    foreach ($step_errors as $err) {
                        $logs[] = ['type' => 'warning', 'msg' => "  ⚠️ $err"];
                    }
                }
                
                $rendered_html[] = [
                    'id' => $freebie_id,
                    'headline' => $freebie['headline'],
                    'status' => 'success',
                    'html_parts' => $html_parts,
                    'warnings' => $step_errors
                ];
                
            } catch (Exception $e) {
                $logs[] = ['type' => 'error', 'msg' => "❌ FEHLER bei Freebie $freebie_id: " . $e->getMessage()];
                $logs[] = ['type' => 'error', 'msg' => "   Zeile: " . $e->getLine()];
                $logs[] = ['type' => 'error', 'msg' => "   RENDERING STOPPT HIER - Nachfolgende Freebies werden NICHT angezeigt!"];
                
                $rendered_html[] = [
                    'id' => $freebie_id,
                    'headline' => $freebie['headline'],
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'html_parts' => $html_parts
                ];
                
                // WICHTIG: In der echten freebies.php würde die Schleife hier abbrechen!
                // (oder zumindest dieses Freebie überspringen)
                break;
            }
        }
        
        $logs[] = ['type' => 'success', 'msg' => 'Rendering-Simulation abgeschlossen!'];
    }
    
} catch (Exception $e) {
    $logs[] = ['type' => 'error', 'msg' => '❌ Fataler Fehler: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Rendering Simulation</title>
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
        .log-error { background: #fee2e2; color: #dc2626; font-weight: bold; }
        .render-item {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .render-item.success { border-color: #22c55e; background: #f0fdf4; }
        .render-item.error { border-color: #ef4444; background: #fee2e2; }
        .html-part {
            background: white;
            padding: 8px 12px;
            margin: 4px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #22c55e; color: white; }
        .badge-error { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Rendering Simulation</h1>
            <p>Simuliert die EXAKTE Rendering-Logik aus freebies.php und zeigt wo Fehler auftreten</p>
            
            <?php if ($user_info): ?>
                <div class="user-info">
                    <strong>Simuliert für:</strong><br>
                    <?php echo htmlspecialchars($user_info['name']); ?> 
                    (<?php echo htmlspecialchars($user_info['email']); ?>) 
                    - User ID: <?php echo $user_info['id']; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📋 Rendering-Protokoll</h2>
            <?php foreach ($logs as $log): ?>
                <div class="log-item log-<?php echo $log['type']; ?>">
                    <?php echo htmlspecialchars($log['msg']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($rendered_html)): ?>
            <div class="card">
                <h2>🎨 Gerenderte Freebies</h2>
                <p style="color: #666; margin-bottom: 24px;">
                    Zeigt welche Freebies erfolgreich gerendert wurden und wo Fehler auftraten
                </p>
                
                <?php foreach ($rendered_html as $item): ?>
                    <div class="render-item <?php echo $item['status']; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3>Freebie ID: <?php echo $item['id']; ?> - <?php echo htmlspecialchars($item['headline']); ?></h3>
                            <span class="badge badge-<?php echo $item['status']; ?>">
                                <?php echo $item['status'] === 'success' ? '✅ ERFOLGREICH' : '❌ FEHLER'; ?>
                            </span>
                        </div>
                        
                        <?php if ($item['status'] === 'error'): ?>
                            <div style="background: #fee2e2; padding: 16px; border-radius: 8px; margin-bottom: 16px; color: #dc2626; font-weight: bold;">
                                ❌ Fehler: <?php echo htmlspecialchars($item['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h4 style="margin: 16px 0 12px 0; color: #666;">Gerenderte HTML-Teile:</h4>
                        <?php foreach ($item['html_parts'] as $part): ?>
                            <div class="html-part">✓ <?php echo htmlspecialchars($part); ?></div>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($item['warnings'])): ?>
                            <h4 style="margin: 16px 0 12px 0; color: #ca8a04;">⚠️ Warnungen:</h4>
                            <?php foreach ($item['warnings'] as $warning): ?>
                                <div style="background: #fef3c7; padding: 8px 12px; margin: 4px 0; border-radius: 4px; color: #ca8a04;">
                                    <?php echo htmlspecialchars($warning); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
/**
 * MARKTPLATZ KAUFPROZESS TEST v2
 * Simuliert einen DigiStore24-Kauf mit Realtime-Handling
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

$testResults = [];
$errors = [];

// Test durchführen wenn Form abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $buyerEmail = $_POST['buyer_email'] ?? '';
    $buyerName = $_POST['buyer_name'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        // === SCHRITT 1: Marktplatz-Freebie finden ===
        $testResults[] = ['step' => 1, 'title' => 'Marktplatz-Freebie suchen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT 
                cf.id, 
                cf.customer_id as seller_id,
                cf.headline,
                cf.marketplace_price,
                cf.digistore_product_id,
                cf.marketplace_sales_count,
                cf.template_id,
                cf.freebie_type,
                cf.subheadline,
                cf.preheadline,
                cf.mockup_image_url,
                cf.background_color,
                cf.primary_color,
                cf.cta_text,
                cf.bullet_points,
                cf.layout,
                cf.email_field_text,
                cf.button_text,
                cf.privacy_checkbox_text,
                cf.thank_you_headline,
                cf.thank_you_message,
                cf.course_id,
                cf.niche
            FROM customer_freebies cf
            WHERE cf.digistore_product_id = ? 
            AND cf.marketplace_enabled = 1
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$freebie) {
            throw new Exception("❌ Kein Marktplatz-Freebie mit product_id '$productId' gefunden!");
        }
        
        $testResults[0]['status'] = 'success';
        $testResults[0]['data'] = [
            'freebie_id' => $freebie['id'],
            'seller_id' => $freebie['seller_id'],
            'headline' => $freebie['headline'],
            'price' => $freebie['marketplace_price'],
            'current_sales' => $freebie['marketplace_sales_count'] ?? 0
        ];
        
        // === SCHRITT 2: Käufer-Account prüfen/erstellen ===
        $testResults[] = ['step' => 2, 'title' => 'Käufer-Account prüfen/erstellen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE email = ?");
        $stmt->execute([$buyerEmail]);
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $buyerId = null;
        $buyerCreated = false;
        $credentials = [];
        
        if (!$buyer) {
            // === Neuen Käufer erstellen ===
            $rawCode = 'RAW-TEST-' . date('His');
            $password = 'Test' . rand(1000, 9999);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Customer erstellen
            $stmt = $pdo->prepare("
                INSERT INTO customers (name, email, password, role, is_active, raw_code, source, created_at)
                VALUES (?, ?, ?, 'customer', 1, ?, 'marketplace_test', NOW())
            ");
            
            $stmt->execute([$buyerName, $buyerEmail, $hashedPassword, $rawCode]);
            $buyerId = $pdo->lastInsertId();
            
            // Standard-Limits für Marktplatz-Käufer
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
                VALUES (?, 2, 'Marktplatz Käufer', 'marketplace_test')
            ");
            $stmt->execute([$buyerId]);
            
            $buyerCreated = true;
            $credentials = [
                'email' => $buyerEmail,
                'password' => $password,
                'raw_code' => $rawCode,
                'customer_id' => $buyerId
            ];
        } else {
            $buyerId = $buyer['id'];
            $credentials = [
                'email' => $buyerEmail,
                'existing_account' => true,
                'customer_id' => $buyerId
            ];
        }
        
        $testResults[1]['status'] = 'success';
        $testResults[1]['data'] = array_merge(['created' => $buyerCreated], $credentials);
        
        // === SCHRITT 3: Duplikat-Check ===
        $testResults[] = ['step' => 3, 'title' => 'Duplikat-Check', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT id FROM customer_freebies 
            WHERE customer_id = ? AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$buyerId, $freebie['id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            throw new Exception("⚠️ Freebie wurde bereits von diesem Käufer gekauft!");
        }
        
        $testResults[2]['status'] = 'success';
        $testResults[2]['data'] = ['duplicate_found' => false];
        
        // === SCHRITT 4: Freebie kopieren ===
        $testResults[] = ['step' => 4, 'title' => 'Freebie in Käufer-Account kopieren', 'status' => 'running'];
        
        // Neues unique_id generieren
        $uniqueId = bin2hex(random_bytes(16));
        
        // Freebie kopieren
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id,
                template_id,
                freebie_type,
                headline,
                subheadline,
                preheadline,
                mockup_image_url,
                background_color,
                primary_color,
                cta_text,
                bullet_points,
                layout,
                email_field_text,
                button_text,
                privacy_checkbox_text,
                thank_you_headline,
                thank_you_message,
                email_provider,
                email_api_key,
                email_list_id,
                course_id,
                unique_id,
                niche,
                original_creator_id,
                copied_from_freebie_id,
                marketplace_enabled,
                created_at
            ) VALUES (
                ?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                NULL, NULL, NULL, ?, ?, ?, ?, ?, 0, NOW()
            )
        ");
        
        $stmt->execute([
            $buyerId, // buyer's customer_id
            $freebie['template_id'],
            $freebie['headline'],
            $freebie['subheadline'],
            $freebie['preheadline'],
            $freebie['mockup_image_url'],
            $freebie['background_color'],
            $freebie['primary_color'],
            $freebie['cta_text'],
            $freebie['bullet_points'],
            $freebie['layout'],
            $freebie['email_field_text'],
            $freebie['button_text'],
            $freebie['privacy_checkbox_text'],
            $freebie['thank_you_headline'],
            $freebie['thank_you_message'],
            $freebie['course_id'],
            $uniqueId,
            $freebie['niche'],
            $freebie['seller_id'], // Original-Ersteller
            $freebie['id'] // Original-Freebie
        ]);
        
        $copiedId = $pdo->lastInsertId();
        
        // Freebie-Link generieren
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $freebieLink = $protocol . '://' . $domain . '/freebie/index.php?id=' . $uniqueId;
        
        $testResults[3]['status'] = 'success';
        $testResults[3]['data'] = [
            'copied_freebie_id' => $copiedId,
            'unique_id' => $uniqueId,
            'freebie_link' => $freebieLink,
            'headline' => $freebie['headline']
        ];
        
        // === SCHRITT 5: Verkaufszähler erhöhen ===
        $testResults[] = ['step' => 5, 'title' => 'Verkaufszähler aktualisieren', 'status' => 'running'];
        
        $oldCount = $freebie['marketplace_sales_count'] ?? 0;
        
        $stmt = $pdo->prepare("
            UPDATE customer_freebies 
            SET marketplace_sales_count = COALESCE(marketplace_sales_count, 0) + 1
            WHERE id = ?
        ");
        $stmt->execute([$freebie['id']]);
        
        $testResults[4]['status'] = 'success';
        $testResults[4]['data'] = [
            'original_freebie_id' => $freebie['id'],
            'old_sales_count' => $oldCount,
            'new_sales_count' => $oldCount + 1
        ];
        
        // === SCHRITT 6: Rechtstexte prüfen ===
        $testResults[] = ['step' => 6, 'title' => 'Rechtstexte für Thank-You-Page prüfen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT impressum, datenschutz 
            FROM legal_texts 
            WHERE user_id = ?
        ");
        $stmt->execute([$freebie['seller_id']]);
        $legalTexts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $hasImpressum = $legalTexts && !empty(trim($legalTexts['impressum'] ?? ''));
        $hasDatenschutz = $legalTexts && !empty(trim($legalTexts['datenschutz'] ?? ''));
        
        $testResults[5]['status'] = $hasImpressum && $hasDatenschutz ? 'success' : 'warning';
        $testResults[5]['data'] = [
            'seller_id' => $freebie['seller_id'],
            'has_impressum' => $hasImpressum,
            'has_datenschutz' => $hasDatenschutz,
            'impressum_link' => $hasImpressum ? "/impressum.php?user={$freebie['seller_id']}" : null,
            'datenschutz_link' => $hasDatenschutz ? "/datenschutz.php?user={$freebie['seller_id']}" : null,
            'note' => !$hasImpressum || !$hasDatenschutz ? 'Verkäufer sollte Rechtstexte hinterlegen!' : 'Alle Rechtstexte vorhanden'
        ];
        
        // === ERFOLG! ===
        $testResults[] = [
            'step' => 'final',
            'title' => '🎉 Test erfolgreich abgeschlossen!',
            'status' => 'success',
            'data' => [
                'message' => 'Der Marktplatz-Kaufprozess funktioniert korrekt!',
                'login_credentials' => $buyerCreated ? $credentials : ['email' => $buyerEmail, 'note' => 'Bestehender Account'],
                'next_steps' => [
                    $buyerCreated ? 'Käufer kann sich mit den obigen Zugangsdaten einloggen' : 'Käufer kann sich mit seinem bestehenden Account einloggen',
                    'Freebie ist im Dashboard unter "Landingpages" sichtbar',
                    'Freebie kann bearbeitet und personalisiert werden',
                    'Thank-You-Page zeigt Rechtstexte des Verkäufers',
                    'Dashboard: https://app.mehr-infos-jetzt.de/?page=freebies'
                ]
            ]
        ];
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        
        // Fehler-Details für Debugging
        $testResults[] = [
            'step' => 'error',
            'title' => '❌ Fehler aufgetreten',
            'status' => 'error',
            'data' => [
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]
        ];
    }
}

// Verfügbare Marktplatz-Freebies laden
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.customer_id as seller_id,
            cf.headline,
            cf.digistore_product_id,
            cf.marketplace_price,
            COALESCE(cf.marketplace_sales_count, 0) as sales_count
        FROM customer_freebies cf
        WHERE cf.marketplace_enabled = 1
        AND cf.digistore_product_id IS NOT NULL
        AND cf.digistore_product_id != ''
        ORDER BY cf.created_at DESC
    ");
    $availableFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $availableFreebies = [];
    $errors[] = "Fehler beim Laden der Freebies: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Marktplatz Kaufprozess Test v2</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 36px;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .test-form {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .hint {
            font-size: 13px;
            color: #888;
            margin-top: 6px;
        }
        
        .btn-test {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .results {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .step {
            background: #f9fafb;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #e5e7eb;
        }
        
        .step.success {
            background: #f0fdf4;
            border-left-color: #22c55e;
        }
        
        .step.warning {
            background: #fef3c7;
            border-left-color: #fbbf24;
        }
        
        .step.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        
        .step.running {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .step-icon {
            font-size: 28px;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .step-data {
            background: white;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        
        .step-data pre {
            margin: 0;
            white-space: pre-wrap;
        }
        
        .error-box {
            background: #fee2e2;
            border: 2px solid #ef4444;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .error-box h3 {
            color: #991b1b;
            margin-bottom: 12px;
        }
        
        .error-box p {
            color: #7f1d1d;
            margin-bottom: 8px;
        }
        
        .available-freebies {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .available-freebies h3 {
            color: #1e40af;
            margin-bottom: 16px;
        }
        
        .freebie-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .freebie-info {
            flex: 1;
        }
        
        .freebie-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .freebie-meta {
            font-size: 13px;
            color: #666;
        }
        
        .final-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
        }
        
        .final-success h2 {
            font-size: 28px;
            margin-bottom: 16px;
        }
        
        .next-steps {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin-top: 24px;
            text-align: left;
        }
        
        .next-steps ul {
            list-style: none;
            padding: 0;
        }
        
        .next-steps li {
            padding: 8px 0;
            padding-left: 32px;
            position: relative;
        }
        
        .next-steps li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-size: 20px;
        }
        
        .credentials-box {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
        
        .credentials-box h3 {
            margin-bottom: 12px;
        }
        
        .credential-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .credential-item:last-child {
            border-bottom: none;
        }
        
        .credential-label {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .credential-value {
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }
        
        .login-link {
            display: inline-block;
            background: white;
            color: #16a34a;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 16px;
            transition: all 0.3s;
        }
        
        .login-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Marktplatz Kaufprozess Test v2</h1>
            <p>Simuliert einen DigiStore24-Kauf mit Realtime-Handling</p>
        </div>
        
        <?php if (!empty($availableFreebies)): ?>
            <div class="available-freebies">
                <h3>📦 Verfügbare Marktplatz-Freebies (<?php echo count($availableFreebies); ?>)</h3>
                <?php foreach ($availableFreebies as $freebie): ?>
                    <div class="freebie-item">
                        <div class="freebie-info">
                            <div class="freebie-title"><?php echo htmlspecialchars($freebie['headline']); ?></div>
                            <div class="freebie-meta">
                                Product-ID: <strong><?php echo htmlspecialchars($freebie['digistore_product_id']); ?></strong> | 
                                Preis: <?php echo number_format($freebie['marketplace_price'], 2, ',', '.'); ?> € | 
                                Verkäufe: <?php echo $freebie['sales_count']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($errors)): ?>
            <div class="available-freebies">
                <h3>📦 Keine Marktplatz-Freebies gefunden</h3>
                <p style="color: #666;">Es sind aktuell keine Freebies im Marktplatz aktiv.</p>
            </div>
        <?php endif; ?>
        
        <div class="test-form">
            <h2 style="margin-bottom: 24px;">Test-Parameter</h2>
            <form method="POST">
                <div class="form-group">
                    <label>DigiStore24 Product-ID *</label>
                    <input type="text" name="product_id" required value="<?php echo $_POST['product_id'] ?? ''; ?>" placeholder="z.B. 613818">
                    <div class="hint">Die Product-ID des Marktplatz-Freebies (siehe Liste oben)</div>
                </div>
                
                <div class="form-group">
                    <label>Käufer E-Mail *</label>
                    <input type="email" name="buyer_email" required value="<?php echo $_POST['buyer_email'] ?? 'test@example.com'; ?>">
                    <div class="hint">E-Mail-Adresse des Test-Käufers</div>
                </div>
                
                <div class="form-group">
                    <label>Käufer Name *</label>
                    <input type="text" name="buyer_name" required value="<?php echo $_POST['buyer_name'] ?? 'Maximilian Mustermann'; ?>">
                    <div class="hint">Name des Test-Käufers</div>
                </div>
                
                <button type="submit" class="btn-test">🚀 Kaufprozess testen</button>
            </form>
        </div>
        
        <?php if (!empty($errors) && empty($testResults)): ?>
            <div class="error-box">
                <h3>❌ Fehler</h3>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($testResults)): ?>
            <div class="results">
                <h2 style="margin-bottom: 24px;">📊 Test-Ergebnisse</h2>
                
                <?php foreach ($testResults as $result): ?>
                    <?php if ($result['step'] === 'final'): ?>
                        <div class="final-success">
                            <h2><?php echo $result['title']; ?></h2>
                            <p style="font-size: 18px; margin-bottom: 8px;"><?php echo $result['data']['message']; ?></p>
                            
                            <?php if (!empty($result['data']['login_credentials']) && isset($result['data']['login_credentials']['password'])): ?>
                                <div class="credentials-box">
                                    <h3>🔑 Login-Zugangsdaten</h3>
                                    <div class="credential-item">
                                        <div class="credential-label">E-Mail:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($result['data']['login_credentials']['email']); ?></div>
                                    </div>
                                    <div class="credential-item">
                                        <div class="credential-label">Passwort:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($result['data']['login_credentials']['password']); ?></div>
                                    </div>
                                    <div class="credential-item">
                                        <div class="credential-label">RAW-Code:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($result['data']['login_credentials']['raw_code']); ?></div>
                                    </div>
                                    
                                    <a href="https://app.mehr-infos-jetzt.de/public/login.php" class="login-link" target="_blank">
                                        🚀 Jetzt einloggen
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="next-steps">
                                <h3 style="margin-bottom: 12px;">Nächste Schritte:</h3>
                                <ul>
                                    <?php foreach ($result['data']['next_steps'] as $step): ?>
                                        <li><?php echo htmlspecialchars($step); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="step <?php echo $result['status']; ?>">
                            <div class="step-header">
                                <span class="step-icon">
                                    <?php 
                                    echo match($result['status']) {
                                        'success' => '✅',
                                        'warning' => '⚠️',
                                        'error' => '❌',
                                        'running' => '⏳',
                                        default => '❓'
                                    };
                                    ?>
                                </span>
                                <span class="step-title">
                                    <?php if (is_numeric($result['step'])): ?>
                                        Schritt <?php echo $result['step']; ?>: 
                                    <?php endif; ?>
                                    <?php echo $result['title']; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($result['data'])): ?>
                                <div class="step-data">
                                    <pre><?php echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
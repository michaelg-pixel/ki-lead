<?php
/**
 * MARKTPLATZ KAUFPROZESS TEST - FINALE WORKING VERSION
 * Basierend auf ECHTER Datenbankstruktur
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

$testResults = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $buyerEmail = $_POST['buyer_email'] ?? '';
    $buyerName = $_POST['buyer_name'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        // === SCHRITT 1: Marktplatz-Freebie finden ===
        $testResults[] = ['step' => 1, 'title' => 'Marktplatz-Freebie suchen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT * FROM customer_freebies 
            WHERE digistore_product_id = ? AND marketplace_enabled = 1
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
            'seller_id' => $freebie['customer_id'],
            'headline' => $freebie['headline'],
            'price' => $freebie['marketplace_price'],
            'sales' => $freebie['marketplace_sales_count'] ?? 0
        ];
        
        // === SCHRITT 2: Käufer-Account prüfen/erstellen ===
        $testResults[] = ['step' => 2, 'title' => 'Käufer-Account prüfen/erstellen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$buyerEmail]);
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $buyerId = null;
        $buyerCreated = false;
        $credentials = [];
        
        if (!$buyer) {
            $rawCode = 'RAW-TEST-' . date('His');
            $password = 'Test' . rand(1000, 9999);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active, raw_code, source, created_at)
                VALUES (?, ?, ?, 'customer', 1, ?, 'marketplace_test', NOW())
            ");
            $stmt->execute([$buyerName, $buyerEmail, $hashedPassword, $rawCode]);
            $buyerId = $pdo->lastInsertId();
            
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
                'user_id' => $buyerId
            ];
        } else {
            $buyerId = $buyer['id'];
            $credentials = ['email' => $buyerEmail, 'existing_account' => true];
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
        
        if ($stmt->fetch()) {
            throw new Exception("⚠️ Freebie wurde bereits gekauft!");
        }
        
        $testResults[2]['status'] = 'success';
        $testResults[2]['data'] = ['duplicate_found' => false];
        
        // === SCHRITT 4: Freebie kopieren (MIT ALLEN PFLICHTFELDERN!) ===
        $testResults[] = ['step' => 4, 'title' => 'Freebie kopieren', 'status' => 'running'];
        
        $uniqueId = bin2hex(random_bytes(16));
        
        // PFLICHTFELDER: customer_id, headline, cta_text, unique_id
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id,
                headline,
                cta_text,
                unique_id,
                original_creator_id,
                copied_from_freebie_id,
                marketplace_enabled,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([
            $buyerId,
            $freebie['headline'],
            $freebie['cta_text'], // PFLICHTFELD!
            $uniqueId,
            $freebie['customer_id'],
            $freebie['id']
        ]);
        
        $copiedId = $pdo->lastInsertId();
        
        // Optionale Felder kopieren
        $optionalFields = [
            'template_id', 'niche', 'subheadline', 'preheadline', 
            'mockup_image_url', 'background_color', 'primary_color',
            'bullet_points', 'layout', 'course_id'
        ];
        
        $updates = [];
        $values = [];
        
        foreach ($optionalFields as $field) {
            if (isset($freebie[$field]) && $freebie[$field] !== null && $freebie[$field] !== '') {
                $updates[] = "$field = ?";
                $values[] = $freebie[$field];
            }
        }
        
        if (!empty($updates)) {
            $values[] = $copiedId;
            $stmt = $pdo->prepare("UPDATE customer_freebies SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($values);
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $freebieLink = "$protocol://$domain/freebie/index.php?id=$uniqueId";
        
        $testResults[3]['status'] = 'success';
        $testResults[3]['data'] = [
            'copied_freebie_id' => $copiedId,
            'unique_id' => $uniqueId,
            'freebie_link' => $freebieLink
        ];
        
        // === SCHRITT 5: Verkaufszähler ===
        $testResults[] = ['step' => 5, 'title' => 'Verkaufszähler', 'status' => 'running'];
        
        $oldCount = $freebie['marketplace_sales_count'] ?? 0;
        
        $stmt = $pdo->prepare("
            UPDATE customer_freebies 
            SET marketplace_sales_count = COALESCE(marketplace_sales_count, 0) + 1
            WHERE id = ?
        ");
        $stmt->execute([$freebie['id']]);
        
        $testResults[4]['status'] = 'success';
        $testResults[4]['data'] = [
            'old_count' => $oldCount,
            'new_count' => $oldCount + 1
        ];
        
        // === SCHRITT 6: Rechtstexte ===
        $testResults[] = ['step' => 6, 'title' => 'Rechtstexte prüfen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("SELECT impressum, datenschutz FROM legal_texts WHERE user_id = ?");
        $stmt->execute([$freebie['customer_id']]);
        $legal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $hasImpressum = $legal && !empty(trim($legal['impressum'] ?? ''));
        $hasDatenschutz = $legal && !empty(trim($legal['datenschutz'] ?? ''));
        
        $testResults[5]['status'] = $hasImpressum && $hasDatenschutz ? 'success' : 'warning';
        $testResults[5]['data'] = [
            'has_impressum' => $hasImpressum,
            'has_datenschutz' => $hasDatenschutz
        ];
        
        // === ERFOLG ===
        $testResults[] = [
            'step' => 'final',
            'title' => '🎉 Test erfolgreich!',
            'status' => 'success',
            'data' => [
                'message' => 'Marktplatz-Kaufprozess funktioniert!',
                'login_credentials' => $buyerCreated ? $credentials : ['email' => $buyerEmail],
                'next_steps' => [
                    $buyerCreated ? 'Login mit obigen Zugangsdaten' : 'Login mit bestehendem Account',
                    'Freebie unter "Landingpages" sichtbar',
                    'Dashboard: https://app.mehr-infos-jetzt.de/?page=freebies'
                ]
            ]
        ];
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $testResults[] = [
            'step' => 'error',
            'title' => '❌ Fehler',
            'status' => 'error',
            'data' => ['message' => $e->getMessage(), 'line' => $e->getLine()]
        ];
    }
}

// Verfügbare Freebies laden
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT id, customer_id, headline, digistore_product_id, marketplace_price,
               COALESCE(marketplace_sales_count, 0) as sales_count
        FROM customer_freebies
        WHERE marketplace_enabled = 1 AND digistore_product_id IS NOT NULL AND digistore_product_id != ''
        ORDER BY created_at DESC
    ");
    $availableFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $availableFreebies = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Marktplatz Test - FINAL</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 36px; color: #1a1a2e; margin-bottom: 12px; }
        .header p { color: #666; }
        .test-form, .results {
            background: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
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
        }
        .btn-test:hover { transform: translateY(-2px); }
        .step {
            background: #f9fafb;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #e5e7eb;
        }
        .step.success { background: #f0fdf4; border-left-color: #22c55e; }
        .step.warning { background: #fef3c7; border-left-color: #fbbf24; }
        .step.error { background: #fee2e2; border-left-color: #ef4444; }
        .step-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .step-icon { font-size: 28px; }
        .step-title { font-size: 18px; font-weight: 600; }
        .step-data {
            background: white;
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
        }
        .available-freebies {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .freebie-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .final-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
        }
        .credentials-box {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
        .credential-item { padding: 8px 0; }
        .credential-value { font-family: monospace; font-size: 16px; font-weight: bold; margin-top: 4px; }
        .login-link {
            display: inline-block;
            background: white;
            color: #16a34a;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 16px;
        }
        .next-steps ul { list-style: none; padding: 0; }
        .next-steps li { padding: 8px 0; padding-left: 32px; position: relative; }
        .next-steps li:before { content: "✓"; position: absolute; left: 0; font-size: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Marktplatz Test - FINAL</h1>
            <p>Basierend auf echter Datenbankstruktur</p>
        </div>
        
        <?php if (!empty($availableFreebies)): ?>
            <div class="available-freebies">
                <h3>📦 Verfügbare Freebies (<?php echo count($availableFreebies); ?>)</h3>
                <?php foreach ($availableFreebies as $f): ?>
                    <div class="freebie-item">
                        <strong><?php echo htmlspecialchars($f['headline']); ?></strong><br>
                        <small>Product-ID: <strong><?php echo $f['digistore_product_id']; ?></strong> | 
                        Preis: <?php echo number_format($f['marketplace_price'], 2, ',', '.'); ?> € | 
                        Verkäufe: <?php echo $f['sales_count']; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="test-form">
            <h2 style="margin-bottom: 24px;">Test-Parameter</h2>
            <form method="POST">
                <div class="form-group">
                    <label>DigiStore24 Product-ID *</label>
                    <input type="text" name="product_id" required value="<?php echo $_POST['product_id'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Käufer E-Mail *</label>
                    <input type="email" name="buyer_email" required value="<?php echo $_POST['buyer_email'] ?? 'test@example.com'; ?>">
                </div>
                <div class="form-group">
                    <label>Käufer Name *</label>
                    <input type="text" name="buyer_name" required value="<?php echo $_POST['buyer_name'] ?? 'Test Käufer'; ?>">
                </div>
                <button type="submit" class="btn-test">🚀 Kaufprozess testen</button>
            </form>
        </div>
        
        <?php if (!empty($testResults)): ?>
            <div class="results">
                <h2 style="margin-bottom: 24px;">📊 Test-Ergebnisse</h2>
                
                <?php foreach ($testResults as $r): ?>
                    <?php if ($r['step'] === 'final'): ?>
                        <div class="final-success">
                            <h2><?php echo $r['title']; ?></h2>
                            <p><?php echo $r['data']['message']; ?></p>
                            
                            <?php if (!empty($r['data']['login_credentials']) && isset($r['data']['login_credentials']['password'])): ?>
                                <div class="credentials-box">
                                    <h3>🔑 Login-Daten</h3>
                                    <div class="credential-item">
                                        <div>E-Mail:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($r['data']['login_credentials']['email']); ?></div>
                                    </div>
                                    <div class="credential-item">
                                        <div>Passwort:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($r['data']['login_credentials']['password']); ?></div>
                                    </div>
                                    <div class="credential-item">
                                        <div>RAW-Code:</div>
                                        <div class="credential-value"><?php echo htmlspecialchars($r['data']['login_credentials']['raw_code']); ?></div>
                                    </div>
                                    <a href="https://app.mehr-infos-jetzt.de/public/login.php" class="login-link" target="_blank">🚀 Jetzt einloggen</a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="next-steps" style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 12px; margin-top: 24px; text-align: left;">
                                <h3>Nächste Schritte:</h3>
                                <ul>
                                    <?php foreach ($r['data']['next_steps'] as $s): ?>
                                        <li><?php echo htmlspecialchars($s); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="step <?php echo $r['status']; ?>">
                            <div class="step-header">
                                <span class="step-icon">
                                    <?php echo match($r['status']) { 'success' => '✅', 'warning' => '⚠️', 'error' => '❌', default => '⏳' }; ?>
                                </span>
                                <span class="step-title">
                                    <?php if (is_numeric($r['step'])): ?>Schritt <?php echo $r['step']; ?>: <?php endif; ?>
                                    <?php echo $r['title']; ?>
                                </span>
                            </div>
                            <?php if (!empty($r['data'])): ?>
                                <div class="step-data">
                                    <pre><?php echo json_encode($r['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
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
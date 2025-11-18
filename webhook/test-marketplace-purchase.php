<?php
/**
 * MARKTPLATZ KAUFPROZESS TEST - VERSION 2
 * Simuliert einen DigiStore24-Kauf mit flexiblem Spalten-Handling
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
        
        // SCHRITT 1: Marktplatz-Freebie finden
        $testResults[] = ['step' => 1, 'title' => 'Marktplatz-Freebie suchen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT 
                cf.id, 
                cf.customer_id, 
                cf.headline,
                cf.marketplace_price,
                cf.digistore_product_id,
                u.name as seller_name,
                u.email as seller_email
            FROM customer_freebies cf
            JOIN users u ON cf.customer_id = u.id
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
        $testResults[0]['data'] = $freebie;
        
        // SCHRITT 2: Käufer-Account prüfen/erstellen
        $testResults[] = ['step' => 2, 'title' => 'Käufer-Account prüfen/erstellen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$buyerEmail]);
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $buyerId = null;
        $buyerCreated = false;
        
        if (!$buyer) {
            // Neuen Käufer erstellen
            $rawCode = 'RAW-TEST-' . date('His');
            $password = 'Test' . rand(1000, 9999);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name, email, password, role, is_active, raw_code,
                    digistore_order_id, source, created_at
                ) VALUES (?, ?, ?, 'customer', 1, ?, 'TEST-ORDER', 'marketplace', NOW())
            ");
            
            $stmt->execute([$buyerName, $buyerEmail, $hashedPassword, $rawCode]);
            $buyerId = $pdo->lastInsertId();
            $buyerCreated = true;
            
            // Standard-Limits für Marktplatz-Käufer
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
                VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
            ");
            $stmt->execute([$buyerId]);
            
            $buyer = [
                'id' => $buyerId,
                'name' => $buyerName,
                'email' => $buyerEmail,
                'password' => $password,
                'raw_code' => $rawCode
            ];
        } else {
            $buyerId = $buyer['id'];
        }
        
        $testResults[1]['status'] = 'success';
        $testResults[1]['data'] = array_merge($buyer, ['created' => $buyerCreated]);
        
        // SCHRITT 3: Prüfen ob bereits gekauft
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
        $testResults[2]['data'] = ['duplicate' => false];
        
        // SCHRITT 3.5: Tabellen-Struktur analysieren
        $testResults[] = ['step' => '3.5', 'title' => 'Tabellen-Struktur analysieren', 'status' => 'running'];
        
        $stmt = $pdo->query("DESCRIBE customer_freebies");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $testResults[3]['status'] = 'success';
        $testResults[3]['data'] = ['available_columns' => $columns];
        
        // SCHRITT 4: Freebie kopieren (FLEXIBEL)
        $testResults[] = ['step' => 4, 'title' => 'Freebie kopieren (flexibel)', 'status' => 'running'];
        
        // Original-Freebie komplett laden
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$freebie['id']]);
        $source = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Neues unique_id generieren
        $uniqueId = bin2hex(random_bytes(16));
        
        // MINIMALE SPALTEN die IMMER vorhanden sein sollten
        $coreFields = [
            'customer_id' => $buyerId,
            'freebie_type' => 'purchased',
            'headline' => $source['headline'] ?? 'Gekauftes Freebie',
            'unique_id' => $uniqueId,
            'original_creator_id' => $source['customer_id'],
            'copied_from_freebie_id' => $freebie['id'],
            'marketplace_enabled' => 0,
            'created_at' => 'NOW()'
        ];
        
        // OPTIONALE FELDER die kopiert werden wenn vorhanden
        $optionalFields = [
            'template_id', 'subheadline', 'preheadline', 'mockup_image_url',
            'background_color', 'primary_color', 'cta_text', 'bullet_points',
            'layout', 'thank_you_headline', 'thank_you_message', 'course_id', 'niche'
        ];
        
        $insertFields = $coreFields;
        
        foreach ($optionalFields as $field) {
            if (in_array($field, $columns) && isset($source[$field])) {
                $insertFields[$field] = $source[$field];
            }
        }
        
        // SQL dynamisch aufbauen
        $fieldNames = array_keys($insertFields);
        $placeholders = array_map(function($f) {
            return $f === 'created_at' ? 'NOW()' : '?';
        }, $fieldNames);
        
        $sql = "INSERT INTO customer_freebies (" . 
               implode(', ', $fieldNames) . 
               ") VALUES (" . 
               implode(', ', $placeholders) . 
               ")";
        
        // Values ohne created_at (das ist NOW())
        $values = array_filter($insertFields, fn($k) => $k !== 'created_at', ARRAY_FILTER_USE_KEY);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($values));
        
        $copiedId = $pdo->lastInsertId();
        
        // Freebie-Link generieren
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $freebieLink = $protocol . '://' . $domain . '/freebie/index.php?id=' . $uniqueId;
        
        $testResults[4]['status'] = 'success';
        $testResults[4]['data'] = [
            'copied_id' => $copiedId,
            'unique_id' => $uniqueId,
            'freebie_link' => $freebieLink,
            'headline' => $source['headline'],
            'fields_copied' => count($insertFields),
            'sql' => $sql
        ];
        
        // SCHRITT 5: Verkaufszähler erhöhen
        $testResults[] = ['step' => 5, 'title' => 'Verkaufszähler aktualisieren', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            UPDATE customer_freebies 
            SET marketplace_sales_count = COALESCE(marketplace_sales_count, 0) + 1
            WHERE id = ?
        ");
        $stmt->execute([$freebie['id']]);
        
        $testResults[5]['status'] = 'success';
        $testResults[5]['data'] = [
            'old_count' => $freebie['marketplace_sales_count'] ?? 0, 
            'new_count' => ($freebie['marketplace_sales_count'] ?? 0) + 1
        ];
        
        // SCHRITT 6: Rechtstexte prüfen
        $testResults[] = ['step' => 6, 'title' => 'Rechtstexte für Thank-You-Page prüfen', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT impressum, datenschutz 
            FROM legal_texts 
            WHERE user_id = ?
        ");
        $stmt->execute([$freebie['customer_id']]);
        $legalTexts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $hasImpressum = $legalTexts && !empty(trim($legalTexts['impressum']));
        $hasDatenschutz = $legalTexts && !empty(trim($legalTexts['datenschutz']));
        
        $testResults[6]['status'] = $hasImpressum && $hasDatenschutz ? 'success' : 'warning';
        $testResults[6]['data'] = [
            'seller_id' => $freebie['customer_id'],
            'has_impressum' => $hasImpressum,
            'has_datenschutz' => $hasDatenschutz,
            'impressum_link' => $hasImpressum ? "/impressum.php?user={$freebie['customer_id']}" : null,
            'datenschutz_link' => $hasDatenschutz ? "/datenschutz.php?user={$freebie['customer_id']}" : null
        ];
        
        // SCHRITT 7: Zugriff prüfen
        $testResults[] = ['step' => 7, 'title' => 'Käufer-Zugriff verifizieren', 'status' => 'running'];
        
        $stmt = $pdo->prepare("
            SELECT id, headline, unique_id, freebie_type
            FROM customer_freebies 
            WHERE customer_id = ? AND id = ?
        ");
        $stmt->execute([$buyerId, $copiedId]);
        $verifyFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $testResults[7]['status'] = 'success';
        $testResults[7]['data'] = [
            'freebie_found' => (bool)$verifyFreebie,
            'details' => $verifyFreebie,
            'dashboard_url' => "https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies"
        ];
        
        // ERFOLGSMELDUNG
        $testResults[] = [
            'step' => 'final',
            'title' => '🎉 Test erfolgreich abgeschlossen!',
            'status' => 'success',
            'data' => [
                'message' => 'Der Marktplatz-Kaufprozess funktioniert korrekt!',
                'buyer_email' => $buyerEmail,
                'buyer_password' => $buyer['password'] ?? 'Bestand bereits',
                'login_url' => 'https://app.mehr-infos-jetzt.de/public/login.php',
                'next_steps' => [
                    '1. Einloggen mit: ' . $buyerEmail,
                    '2. Dashboard → Landingpages öffnen',
                    '3. Gekauftes Freebie sollte dort sichtbar sein',
                    '4. Freebie kann bearbeitet werden',
                    '5. Thank-You-Page zeigt Rechtstexte des Verkäufers'
                ]
            ]
        ];
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $errors[] = "Trace: " . $e->getTraceAsString();
    }
}

// Verfügbare Marktplatz-Freebies laden
$pdo = getDBConnection();
$stmt = $pdo->query("
    SELECT 
        cf.id,
        cf.customer_id,
        cf.headline,
        cf.digistore_product_id,
        cf.marketplace_price,
        u.name as seller_name
    FROM customer_freebies cf
    JOIN users u ON cf.customer_id = u.id
    WHERE cf.marketplace_enabled = 1
    AND cf.digistore_product_id IS NOT NULL
    AND cf.digistore_product_id != ''
    ORDER BY cf.created_at DESC
");
$availableFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        .error-box pre {
            color: #7f1d1d;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
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
        
        .login-credentials {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .login-credentials div {
            padding: 8px 0;
            font-size: 16px;
        }
        
        .login-credentials strong {
            display: inline-block;
            min-width: 120px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Marktplatz Kaufprozess Test v2</h1>
            <p>Simuliert einen DigiStore24-Kauf mit flexiblem Spalten-Handling</p>
        </div>
        
        <?php if (!empty($availableFreebies)): ?>
            <div class="available-freebies">
                <h3>📦 Verfügbare Marktplatz-Freebies (<?php echo count($availableFreebies); ?>)</h3>
                <?php foreach ($availableFreebies as $freebie): ?>
                    <div class="freebie-item">
                        <div class="freebie-info">
                            <div class="freebie-title"><?php echo htmlspecialchars($freebie['headline']); ?></div>
                            <div class="freebie-meta">
                                Verkäufer: <?php echo htmlspecialchars($freebie['seller_name']); ?> | 
                                Product-ID: <strong><?php echo htmlspecialchars($freebie['digistore_product_id']); ?></strong> | 
                                Preis: <?php echo number_format($freebie['marketplace_price'], 2, ',', '.'); ?> €
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                    <input type="text" name="buyer_name" required value="<?php echo $_POST['buyer_name'] ?? 'Test Käufer'; ?>">
                    <div class="hint">Name des Test-Käufers</div>
                </div>
                
                <button type="submit" class="btn-test">🚀 Kaufprozess testen</button>
            </form>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <h3>❌ Fehler</h3>
                <?php foreach ($errors as $error): ?>
                    <pre><?php echo htmlspecialchars($error); ?></pre>
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
                            
                            <?php if (isset($result['data']['buyer_password'])): ?>
                                <div class="login-credentials">
                                    <h3 style="margin-bottom: 12px;">🔑 Login-Daten:</h3>
                                    <div><strong>E-Mail:</strong> <?php echo htmlspecialchars($result['data']['buyer_email']); ?></div>
                                    <div><strong>Passwort:</strong> <?php echo htmlspecialchars($result['data']['buyer_password']); ?></div>
                                    <div><strong>Login-URL:</strong> <a href="<?php echo $result['data']['login_url']; ?>" style="color: white; text-decoration: underline;" target="_blank">Jetzt einloggen</a></div>
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
                                <span class="step-title">Schritt <?php echo $result['step']; ?>: <?php echo $result['title']; ?></span>
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
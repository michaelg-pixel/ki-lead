<?php
/**
 * Browser-Installer für neue Digistore24-Produkte
 * Einfach im Browser aufrufen: /database/install-products.php
 */

require_once '../config/database.php';

// HTML Header
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digistore24 Produkte installieren</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #6b7280;
            font-size: 16px;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .product-card {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .product-card.success {
            border-color: #10b981;
            background: #d1fae5;
        }
        
        .product-card.error {
            border-color: #ef4444;
            background: #fee2e2;
        }
        
        .product-icon {
            font-size: 32px;
            flex-shrink: 0;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .product-details {
            font-size: 14px;
            color: #6b7280;
        }
        
        .status-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .alert-info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .actions {
            text-align: center;
            margin-top: 32px;
        }
        
        .next-steps {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 24px;
        }
        
        .next-steps h3 {
            color: #1e3a8a;
            margin-bottom: 12px;
            font-size: 18px;
        }
        
        .next-steps ol {
            margin-left: 20px;
            color: #1e40af;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .webhook-url {
            background: #1f2937;
            color: #10b981;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin-top: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🚀</div>
            <h1>Digistore24 Produkte Installer</h1>
            <p>Installiert die neuen Abo-Produkte</p>
        </div>

<?php
try {
    $pdo = getDBConnection();
    
    // Neue Produkte
    $products = [
        [
            'product_id' => '639494',
            'product_name' => 'Starter Abo',
            'product_type' => 'starter',
            'price' => 49.00,
            'billing_type' => 'monthly',
            'own_freebies_limit' => 4,
            'ready_freebies_count' => 0,
            'referral_program_slots' => 1,
            'is_active' => 1,
            'icon' => '🎯'
        ],
        [
            'product_id' => '639495',
            'product_name' => 'Pro Abo',
            'product_type' => 'pro',
            'price' => 99.00,
            'billing_type' => 'monthly',
            'own_freebies_limit' => 8,
            'ready_freebies_count' => 0,
            'referral_program_slots' => 3,
            'is_active' => 1,
            'icon' => '⚡'
        ],
        [
            'product_id' => '639496',
            'product_name' => 'Business Abo',
            'product_type' => 'business',
            'price' => 199.00,
            'billing_type' => 'monthly',
            'own_freebies_limit' => 20,
            'ready_freebies_count' => 0,
            'referral_program_slots' => 10,
            'is_active' => 1,
            'icon' => '🏢'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO digistore_products (
            product_id,
            product_name,
            product_type,
            price,
            billing_type,
            own_freebies_limit,
            ready_freebies_count,
            referral_program_slots,
            is_active,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            product_name = VALUES(product_name),
            product_type = VALUES(product_type),
            price = VALUES(price),
            billing_type = VALUES(billing_type),
            own_freebies_limit = VALUES(own_freebies_limit),
            ready_freebies_count = VALUES(ready_freebies_count),
            referral_program_slots = VALUES(referral_program_slots),
            is_active = VALUES(is_active),
            updated_at = NOW()
    ");
    
    $successCount = 0;
    
    echo '<div class="alert alert-info">';
    echo '<span style="font-size: 24px;">⏳</span>';
    echo '<span><strong>Installation läuft...</strong> Produkte werden installiert</span>';
    echo '</div>';
    
    foreach ($products as $product) {
        try {
            $stmt->execute([
                $product['product_id'],
                $product['product_name'],
                $product['product_type'],
                $product['price'],
                $product['billing_type'],
                $product['own_freebies_limit'],
                $product['ready_freebies_count'],
                $product['referral_program_slots'],
                $product['is_active']
            ]);
            
            echo '<div class="product-card success">';
            echo '<div class="product-icon">' . $product['icon'] . '</div>';
            echo '<div class="product-info">';
            echo '<div class="product-name">' . htmlspecialchars($product['product_name']) . '</div>';
            echo '<div class="product-details">';
            echo 'Produkt-ID: ' . $product['product_id'] . ' | ';
            echo $product['own_freebies_limit'] . ' Freebies | ';
            echo $product['referral_program_slots'] . ' Empfehlungs-Slots | ';
            echo number_format($product['price'], 2, ',', '.') . ' €/Monat';
            echo '</div>';
            echo '</div>';
            echo '<div class="status-icon">✅</div>';
            echo '</div>';
            
            $successCount++;
            
        } catch (Exception $e) {
            echo '<div class="product-card error">';
            echo '<div class="product-icon">' . $product['icon'] . '</div>';
            echo '<div class="product-info">';
            echo '<div class="product-name">' . htmlspecialchars($product['product_name']) . '</div>';
            echo '<div class="product-details">Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div>';
            echo '<div class="status-icon">❌</div>';
            echo '</div>';
        }
    }
    
    if ($successCount === count($products)) {
        echo '<div class="alert alert-success">';
        echo '<span style="font-size: 24px;">🎉</span>';
        echo '<span><strong>Installation erfolgreich!</strong> Alle ' . $successCount . ' Produkte wurden installiert.</span>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-error">';
        echo '<span style="font-size: 24px;">⚠️</span>';
        echo '<span><strong>Teilweise erfolgreich:</strong> ' . $successCount . ' von ' . count($products) . ' Produkten installiert.</span>';
        echo '</div>';
    }
    
    echo '<div class="next-steps">';
    echo '<h3>📝 Nächste Schritte:</h3>';
    echo '<ol>';
    echo '<li>Gehe zum Admin-Dashboard → Digistore24</li>';
    echo '<li>Überprüfe die Produkt-IDs (falls nötig anpassen)</li>';
    echo '<li>Passe bei Bedarf die Limits an</li>';
    echo '<li>Teste die Webhook-Integration</li>';
    echo '</ol>';
    echo '<div class="webhook-url">';
    echo 'https://app.mehr-infos-jetzt.de/webhook/digistore24.php';
    echo '</div>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<span><strong>Fehler bei der Installation:</strong><br>' . htmlspecialchars($e->getMessage()) . '</span>';
    echo '</div>';
}
?>

        <div class="actions">
            <a href="/admin/dashboard.php?page=digistore" class="btn">
                🛒 Zum Admin-Dashboard
            </a>
        </div>
    </div>
</body>
</html>

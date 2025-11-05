<?php
/**
 * Installiert die neuen Digistore24-Produkte
 * Starter, Pro und Business Abos
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🚀 Installiere neue Digistore24-Produkte...\n\n";
    
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
            'is_active' => 1
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
            'is_active' => 1
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
            'is_active' => 1
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
    
    foreach ($products as $product) {
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
        
        echo "✅ {$product['product_name']} installiert/aktualisiert\n";
        echo "   - Produkt-ID: {$product['product_id']}\n";
        echo "   - Eigene Freebies: {$product['own_freebies_limit']}\n";
        echo "   - Empfehlungs-Slots: {$product['referral_program_slots']}\n";
        echo "   - Preis: {$product['price']} € / Monat\n\n";
    }
    
    echo "\n🎉 Installation erfolgreich abgeschlossen!\n\n";
    echo "📝 Nächste Schritte:\n";
    echo "1. Gehe zum Admin-Dashboard → Digistore24\n";
    echo "2. Überprüfe die Produkt-IDs (639494, 639495, 639496)\n";
    echo "3. Passe bei Bedarf die Limits an\n";
    echo "4. Teste die Webhook-Integration\n\n";
    
    echo "🔗 Webhook-URL für Digistore24:\n";
    echo "https://app.mehr-infos-jetzt.de/webhook/digistore24.php\n\n";
    
} catch (Exception $e) {
    echo "❌ Fehler bei der Installation:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

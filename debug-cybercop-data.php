<?php
// Diagnose Script für cybercop33@web.de
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbankverbindung
$host = 'localhost';
$dbname = 'u527827258_app';
$username = 'u527827258_app';
$password = '4Y@eQKj93R';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<pre>";
    echo "=== DIAGNOSE FÜR cybercop33@web.de ===\n\n";
    
    // 1. User-ID finden
    $stmt = $pdo->prepare('SELECT id, email, username FROM users WHERE email = ?');
    $stmt->execute(['cybercop33@web.de']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User nicht gefunden!\n";
        exit;
    }
    
    echo "=== USER ===\n";
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Username: {$user['username']}\n\n";
    
    $user_id = $user['id'];
    
    // 2. Customer Freebies
    echo "=== CUSTOMER FREEBIES ===\n";
    $stmt = $pdo->prepare('
        SELECT id, unique_id, headline, mockup_image_url, created_at 
        FROM customer_freebies 
        WHERE customer_id = ? 
        ORDER BY created_at DESC
    ');
    $stmt->execute([$user_id]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Anzahl: " . count($freebies) . "\n\n";
    
    if (count($freebies) > 0) {
        foreach ($freebies as $f) {
            echo "Freebie #{$f['id']}\n";
            echo "  Unique ID: {$f['unique_id']}\n";
            echo "  Titel: {$f['headline']}\n";
            echo "  Mockup: " . ($f['mockup_image_url'] ?: 'Kein Bild') . "\n";
            echo "  Link: https://mehr-infos-jetzt.de/f/?id={$f['unique_id']}\n";
            echo "  Erstellt: {$f['created_at']}\n\n";
        }
    } else {
        echo "❌ Keine Freebies gefunden!\n\n";
    }
    
    // 3. Reward Definitions
    echo "=== REWARD DEFINITIONS ===\n";
    $stmt = $pdo->prepare('
        SELECT 
            rd.id, 
            rd.freebie_id, 
            rd.reward_title, 
            rd.reward_description,
            rd.referrals_required,
            rd.delivery_type,
            cf.headline as freebie_name
        FROM reward_definitions rd
        LEFT JOIN customer_freebies cf ON cf.id = rd.freebie_id
        WHERE rd.user_id = ?
        ORDER BY rd.freebie_id, rd.referrals_required
    ');
    $stmt->execute([$user_id]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Anzahl: " . count($rewards) . "\n\n";
    
    if (count($rewards) > 0) {
        foreach ($rewards as $r) {
            echo "Reward #{$r['id']}\n";
            echo "  Freebie: {$r['freebie_name']} (ID: {$r['freebie_id']})\n";
            echo "  Titel: {$r['reward_title']}\n";
            echo "  Beschreibung: {$r['reward_description']}\n";
            echo "  Empfehlungen benötigt: {$r['referrals_required']}\n";
            echo "  Auslieferung: {$r['delivery_type']}\n\n";
        }
    } else {
        echo "❌ Keine Belohnungen gefunden!\n\n";
    }
    
    // 4. Prüfe auch alte Spalten
    echo "=== SPALTEN-CHECK ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'customer_id'");
    $col = $stmt->fetch();
    echo "customer_freebies.customer_id: " . ($col ? "✅ EXISTS" : "❌ MISSING") . "\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'user_id'");
    $col = $stmt->fetch();
    echo "reward_definitions.user_id: " . ($col ? "✅ EXISTS" : "❌ MISSING") . "\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'freebie_id'");
    $col = $stmt->fetch();
    echo "reward_definitions.freebie_id: " . ($col ? "✅ EXISTS" : "❌ MISSING") . "\n\n";
    
    // 5. Zeige alle Freebies im System
    echo "=== ALLE FREEBIES IM SYSTEM ===\n";
    $stmt = $pdo->query('
        SELECT cf.id, cf.customer_id, cf.headline, u.email
        FROM customer_freebies cf
        LEFT JOIN users u ON u.id = cf.customer_id
        ORDER BY cf.id DESC
        LIMIT 10
    ');
    $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Letzte 10 Freebies:\n";
    foreach ($all_freebies as $af) {
        echo "  ID: {$af['id']} | Customer: {$af['customer_id']} ({$af['email']}) | {$af['headline']}\n";
    }
    
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre>❌ Datenbankfehler: " . $e->getMessage() . "</pre>";
}

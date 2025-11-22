<?php
/**
 * Migration: ENUM erweitern + alle Customer bekommen Zustimmung
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== MIGRATION: AV Contract Acceptances ===\n\n";
    
    // 1. ENUM erweitern um 'mailgun_consent'
    echo "1️⃣ ENUM erweitern...\n";
    $pdo->exec("
        ALTER TABLE av_contract_acceptances 
        MODIFY acceptance_type ENUM('registration', 'update', 'renewal', 'mailgun_consent') 
        NOT NULL DEFAULT 'registration'
    ");
    echo "✅ ENUM erweitert (registration, update, renewal, mailgun_consent)\n\n";
    
    // 2. Alle Customer ohne Zustimmung finden
    echo "2️⃣ Customer ohne Zustimmung suchen...\n";
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.created_at
        FROM users u
        WHERE u.role = 'customer'
        AND NOT EXISTS (
            SELECT 1 FROM av_contract_acceptances 
            WHERE user_id = u.id
        )
        ORDER BY u.id
    ");
    $users_without_consent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 " . count($users_without_consent) . " Customer ohne Zustimmung gefunden\n\n";
    
    if (empty($users_without_consent)) {
        echo "✅ Alle Customer haben bereits eine Zustimmung!\n";
    } else {
        // 3. Zustimmung für alle Customer erstellen
        echo "3️⃣ Zustimmung für alle Customer erstellen...\n";
        
        $stmt_insert = $pdo->prepare("
            INSERT INTO av_contract_acceptances (
                user_id,
                accepted_at,
                ip_address,
                user_agent,
                av_contract_version,
                acceptance_type,
                created_at
            ) VALUES (?, ?, 'migration', 'Migration Script', '1.0', 'registration', NOW())
        ");
        
        $inserted = 0;
        foreach ($users_without_consent as $user) {
            // Verwende created_at des Users als accepted_at
            $stmt_insert->execute([
                $user['id'],
                $user['created_at']
            ]);
            $inserted++;
            echo "  ✅ User #{$user['id']} ({$user['email']})\n";
        }
        
        echo "\n✅ {$inserted} Zustimmungen erstellt!\n";
    }
    
    // 4. Finale Statistik
    echo "\n=== FINALE STATISTIK ===\n\n";
    
    $stmt = $pdo->query("
        SELECT acceptance_type, COUNT(*) as count 
        FROM av_contract_acceptances 
        GROUP BY acceptance_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['acceptance_type']}: {$row['count']} Einträge\n";
    }
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM users WHERE role = 'customer'
    ");
    $total_customers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as with_consent 
        FROM av_contract_acceptances a
        INNER JOIN users u ON a.user_id = u.id
        WHERE u.role = 'customer'
    ");
    $customers_with_consent = $stmt->fetchColumn();
    
    echo "\n📊 Gesamt: {$total_customers} Customer\n";
    echo "✅ Mit Zustimmung: {$customers_with_consent} Customer\n";
    
    if ($customers_with_consent == $total_customers) {
        echo "\n🎉 PERFEKT! Alle Customer haben jetzt eine Zustimmung!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

<?php
// Diagnose Script für cybercop33@web.de
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verwende die bestehende config.php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDBConnection();
    
    echo "<pre>";
    echo "=== DIAGNOSE FÜR cybercop33@web.de ===\n\n";
    
    // 1. User-ID finden
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ?');
    $stmt->execute(['cybercop33@web.de']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User nicht gefunden!\n";
        exit;
    }
    
    echo "=== USER ===\n";
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n\n";
    
    $user_id = $user['id'];
    
    // 2. Prüfe ALLE Spalten in reward_definitions
    echo "=== REWARD_DEFINITIONS SPALTEN ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // 3. Reward Definitions mit dynamischer Abfrage
    echo "=== REWARD DEFINITIONS (Rohdaten) ===\n";
    $stmt = $pdo->prepare('SELECT * FROM reward_definitions WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Anzahl: " . count($rewards) . "\n\n";
    
    if (count($rewards) > 0) {
        foreach ($rewards as $r) {
            echo "Reward #{$r['id']}\n";
            foreach ($r as $key => $value) {
                if ($key !== 'id') {
                    echo "  {$key}: " . (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) . "\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "❌ Keine Belohnungen gefunden!\n\n";
    }
    
    // 4. Customer Freebies (nur Anzahl)
    echo "=== CUSTOMER FREEBIES ===\n";
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM customer_freebies WHERE customer_id = ?');
    $stmt->execute([$user_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Anzahl: {$count['count']}\n\n";
    
    // 5. Zeige erste 3 Freebies mit Details
    $stmt = $pdo->prepare('
        SELECT id, unique_id, headline 
        FROM customer_freebies 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ');
    $stmt->execute([$user_id]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Erste 3 Freebies:\n";
    foreach ($freebies as $f) {
        echo "  ID: {$f['id']} | {$f['headline']}\n";
        echo "  Link: https://mehr-infos-jetzt.de/f/?id={$f['unique_id']}\n\n";
    }
    
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre>❌ Datenbankfehler: " . $e->getMessage() . "\n";
    echo "Zeile: " . $e->getLine() . "\n";
    echo "Datei: " . $e->getFile() . "</pre>";
} catch (Exception $e) {
    echo "<pre>❌ Fehler: " . $e->getMessage() . "</pre>";
}

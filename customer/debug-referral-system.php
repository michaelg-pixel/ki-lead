<?php
/**
 * Debug-Script: Prüfe Referral System Struktur
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Bitte einloggen!");
}

require_once __DIR__ . '/../config/database.php';

echo "<h1>🔍 Referral System Debug</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    echo "=== USER INFO ===\n";
    echo "Session User ID: $userId\n";
    echo "Session Name: " . ($_SESSION['name'] ?? 'N/A') . "\n";
    echo "Session Email: " . ($_SESSION['email'] ?? 'N/A') . "\n\n";
    
    // Prüfe ob users Tabelle existiert
    echo "=== TABELLEN CHECK ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "✅ users Tabelle existiert\n";
        
        // Zeige Spalten
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSpalten in users Tabelle:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
        // Prüfe ob referral Spalten vorhanden sind
        $hasReferralEnabled = false;
        $hasRefCode = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'referral_enabled') $hasReferralEnabled = true;
            if ($col['Field'] === 'ref_code') $hasRefCode = true;
        }
        
        echo "\n";
        echo ($hasReferralEnabled ? "✅" : "❌") . " referral_enabled Spalte\n";
        echo ($hasRefCode ? "✅" : "❌") . " ref_code Spalte\n";
        
    } else {
        echo "❌ users Tabelle existiert NICHT\n";
    }
    
    // Prüfe customers Tabelle
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->fetch()) {
        echo "\n✅ customers Tabelle existiert (VERALTET!)\n";
        
        // Zeige Spalten
        $stmt = $pdo->query("SHOW COLUMNS FROM customers");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSpalten in customers Tabelle:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    echo "\n=== AKTUELLER USER STATUS ===\n";
    
    // Versuche aus users zu lesen
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "User aus users Tabelle:\n";
            echo "  - ID: " . $user['id'] . "\n";
            echo "  - Name: " . $user['name'] . "\n";
            echo "  - Email: " . $user['email'] . "\n";
            echo "  - Role: " . ($user['role'] ?? 'N/A') . "\n";
            echo "  - referral_enabled: " . ($user['referral_enabled'] ?? 'SPALTE FEHLT') . "\n";
            echo "  - ref_code: " . ($user['ref_code'] ?? 'SPALTE FEHLT') . "\n";
        } else {
            echo "❌ User nicht in users Tabelle gefunden!\n";
        }
    } catch (PDOException $e) {
        echo "❌ Fehler beim Lesen aus users: " . $e->getMessage() . "\n";
    }
    
    // Versuche aus customers zu lesen
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$userId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo "\nUser aus customers Tabelle:\n";
            echo "  - ID: " . $customer['id'] . "\n";
            echo "  - Name: " . $customer['name'] . "\n";
            echo "  - Email: " . $customer['email'] . "\n";
            echo "  - referral_enabled: " . ($customer['referral_enabled'] ?? 'SPALTE FEHLT') . "\n";
            echo "  - ref_code: " . ($customer['ref_code'] ?? 'SPALTE FEHLT') . "\n";
            echo "  - referral_code: " . ($customer['referral_code'] ?? 'SPALTE FEHLT') . "\n";
        } else {
            echo "\n❌ User nicht in customers Tabelle gefunden!\n";
        }
    } catch (PDOException $e) {
        echo "\n❌ customers Tabelle existiert nicht oder Fehler: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== REFERRAL STATS CHECK ===\n";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_stats'");
        if ($stmt->fetch()) {
            echo "✅ referral_stats Tabelle existiert\n";
            
            // Zeige Spalten
            $stmt = $pdo->query("SHOW COLUMNS FROM referral_stats");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\nSpalten:\n";
            foreach ($columns as $col) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
            
            // Prüfe Eintrag für User
            $stmt = $pdo->prepare("SELECT * FROM referral_stats WHERE customer_id = ?");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                echo "\n✅ Stats-Eintrag vorhanden\n";
            } else {
                echo "\n⚠️ Kein Stats-Eintrag für User ID $userId\n";
            }
        } else {
            echo "❌ referral_stats Tabelle existiert NICHT\n";
        }
    } catch (PDOException $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== EMPFEHLUNG ===\n";
    
    // Prüfe welche Tabelle verwendet werden sollte
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $hasUsers = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    $hasCustomers = $stmt->fetch();
    
    if ($hasUsers && !$hasCustomers) {
        echo "➡️ Verwende USERS Tabelle\n";
    } elseif ($hasCustomers && !$hasUsers) {
        echo "➡️ Verwende CUSTOMERS Tabelle\n";
    } elseif ($hasUsers && $hasCustomers) {
        echo "⚠️ BEIDE Tabellen existieren - MIGRATION UNVOLLSTÄNDIG!\n";
        echo "   Bitte Migration abschließen oder eine Tabelle löschen.\n";
    } else {
        echo "❌ KEINE passende Tabelle gefunden!\n";
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='/customer/dashboard.php?page=empfehlungsprogramm'>← Zurück zum Dashboard</a>";

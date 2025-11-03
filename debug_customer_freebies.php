<?php
/**
 * Debug: Prüfe Freebies für Customer
 */

require_once __DIR__ . '/config/database.php';

$customer_id = 4; // cybercop33@web.de

try {
    $db = getDBConnection();
    
    echo "=== Freebie Debug für Customer ID: {$customer_id} ===\n\n";
    
    // 1. Customer Info
    echo "=== Customer Informationen ===\n";
    $stmt = $db->prepare("SELECT id, email, company_name, ref_code FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "Email: {$customer['email']}\n";
        echo "Company: " . ($customer['company_name'] ?? 'N/A') . "\n";
        echo "Ref Code: {$customer['ref_code']}\n\n";
    } else {
        echo "❌ Customer nicht gefunden!\n";
        exit;
    }
    
    // 2. Prüfe customer_freebies Tabellenstruktur
    echo "=== customer_freebies Tabellenstruktur ===\n";
    $stmt = $db->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Spalten:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // 3. Suche Freebies in customer_freebies
    echo "=== Freebies in customer_freebies ===\n";
    $stmt = $db->prepare("SELECT * FROM customer_freebies WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customerFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customerFreebies)) {
        echo "❌ Keine Freebies in customer_freebies gefunden!\n\n";
    } else {
        echo "✓ " . count($customerFreebies) . " Freebie(s) gefunden:\n";
        foreach ($customerFreebies as $cf) {
            echo "\nID: {$cf['id']}\n";
            echo "  unique_id: " . ($cf['unique_id'] ?? 'NULL') . "\n";
            echo "  template_id: " . ($cf['template_id'] ?? 'NULL') . "\n";
            
            // Prüfe welche Name-Spalte existiert
            if (isset($cf['name'])) {
                echo "  name: {$cf['name']}\n";
            } elseif (isset($cf['title'])) {
                echo "  title: {$cf['title']}\n";
            } elseif (isset($cf['freebie_name'])) {
                echo "  freebie_name: {$cf['freebie_name']}\n";
            }
            
            echo "  Alle Spalten: " . implode(', ', array_keys($cf)) . "\n";
        }
        echo "\n";
    }
    
    // 4. Suche Freebies in freebies Tabelle (Fallback)
    echo "=== Freebies in freebies Tabelle ===\n";
    $stmt = $db->prepare("SELECT id, unique_id, name, user_id FROM freebies WHERE user_id = ?");
    $stmt->execute([$customer_id]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($freebies)) {
        echo "❌ Keine Freebies in freebies Tabelle gefunden!\n\n";
    } else {
        echo "✓ " . count($freebies) . " Freebie(s) gefunden:\n";
        foreach ($freebies as $f) {
            echo "\nID: {$f['id']}\n";
            echo "  unique_id: {$f['unique_id']}\n";
            echo "  name: {$f['name']}\n";
        }
        echo "\n";
    }
    
    // 5. Zeige ALLE Freebies (unabhängig von customer_id)
    echo "=== Alle Freebies im System ===\n";
    $stmt = $db->query("SELECT id, unique_id, user_id FROM freebies ORDER BY id DESC LIMIT 10");
    $allFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allFreebies)) {
        echo "❌ Überhaupt keine Freebies im System!\n\n";
    } else {
        echo "Letzte 10 Freebies:\n";
        foreach ($allFreebies as $f) {
            echo "  ID: {$f['id']} | unique_id: {$f['unique_id']} | user_id: {$f['user_id']}\n";
        }
        echo "\n";
    }
    
    // 6. Empfehlung
    echo "=== Empfehlung ===\n";
    if (empty($customerFreebies) && empty($freebies)) {
        echo "❌ Customer hat noch keine Freebies erstellt!\n";
        echo "\n";
        echo "Nächste Schritte:\n";
        echo "1. Als Customer einloggen (cybercop33@web.de)\n";
        echo "2. Ein Freebie erstellen im Customer-Dashboard\n";
        echo "3. Dann wird es hier für Leads verfügbar sein\n";
    } elseif (!empty($freebies) && empty($customerFreebies)) {
        echo "✓ Freebies existieren in 'freebies' Tabelle\n";
        echo "⚠️ ABER nicht in 'customer_freebies'\n";
        echo "\nDas ist das Problem! Die Freebies müssen in 'customer_freebies' sein.\n";
    } else {
        echo "✓ Freebies existieren und sollten angezeigt werden!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

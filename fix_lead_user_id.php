<?php
/**
 * Migration: Verknüpfe bestehende Leads mit ihren Customers
 * Korrigierte Version mit richtigen Spaltennamen
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "=== Lead User-ID Migration (Korrigiert) ===\n\n";
    
    // Schritt 0: Prüfe Tabellenstruktur
    echo "=== Tabellenstruktur prüfen ===\n";
    $stmt = $db->query("DESCRIBE lead_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Vorhandene Spalten in lead_users:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Finde die richtige Spalte für den Referral-Code
    $referrer_column = null;
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['referrer_code', 'referred_by', 'ref_code'])) {
            $referrer_column = $col['Field'];
            break;
        }
    }
    
    if (!$referrer_column) {
        echo "❌ Keine passende Referrer-Spalte gefunden!\n";
        echo "Verfügbare Spalten: " . implode(', ', array_column($columns, 'Field')) . "\n";
        exit(1);
    }
    
    echo "✓ Verwende Spalte für Referrer: {$referrer_column}\n\n";
    
    // Schritt 1: Finde alle Leads ohne user_id aber mit Referrer-Code
    $query = "
        SELECT 
            lu.id,
            lu.email,
            lu.name,
            lu.{$referrer_column} as referrer_code,
            lu.user_id,
            lu.registered_at
        FROM lead_users lu
        WHERE lu.user_id IS NULL 
        AND lu.{$referrer_column} IS NOT NULL
        AND lu.{$referrer_column} != ''
        ORDER BY lu.registered_at DESC
    ";
    
    $stmt = $db->query($query);
    $leadsWithoutUserId = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($leadsWithoutUserId);
    
    echo "Gefunden: {$count} Leads ohne user_id aber mit Referrer-Code\n\n";
    
    if ($count === 0) {
        echo "✅ Keine Leads zum Aktualisieren gefunden.\n";
        
        // Zeige trotzdem Beispiel-Leads
        echo "\n=== Aktuelle Leads (erste 10) ===\n";
        $stmt = $db->query("
            SELECT 
                lu.id,
                lu.email,
                lu.user_id,
                lu.{$referrer_column} as referrer_code,
                u.company_name
            FROM lead_users lu
            LEFT JOIN users u ON lu.user_id = u.id
            ORDER BY lu.registered_at DESC
            LIMIT 10
        ");
        $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($examples as $ex) {
            echo sprintf(
                "ID: %d | Email: %s | user_id: %s | Referrer: %s | Company: %s\n",
                $ex['id'],
                $ex['email'],
                $ex['user_id'] ?? 'NULL',
                $ex['referrer_code'] ?? 'NULL',
                $ex['company_name'] ?? 'N/A'
            );
        }
        exit;
    }
    
    // Schritt 2: Für jeden Lead, finde den Customer anhand des Referral-Codes
    $updated = 0;
    $failed = 0;
    
    // Prüfe welche Spalte in users für ref_code verwendet wird
    $stmt = $db->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userRefColumn = null;
    
    foreach ($userColumns as $col) {
        if (in_array($col['Field'], ['ref_code', 'referral_code', 'reference_code'])) {
            $userRefColumn = $col['Field'];
            break;
        }
    }
    
    if (!$userRefColumn) {
        echo "❌ Keine Referral-Code Spalte in users Tabelle gefunden!\n";
        exit(1);
    }
    
    echo "✓ Users Tabelle verwendet Spalte: {$userRefColumn}\n\n";
    
    foreach ($leadsWithoutUserId as $lead) {
        $refCode = $lead['referrer_code'];
        
        echo "Lead: {$lead['email']} (ID: {$lead['id']})\n";
        echo "  Referral-Code: {$refCode}\n";
        
        // Suche den Customer mit diesem Referral-Code
        $stmt = $db->prepare("
            SELECT id, email, company_name 
            FROM users 
            WHERE {$userRefColumn} = ?
            LIMIT 1
        ");
        $stmt->execute([$refCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo "  ✓ Customer gefunden: {$customer['company_name']} ({$customer['email']})\n";
            
            // Update Lead mit user_id
            $updateStmt = $db->prepare("
                UPDATE lead_users 
                SET user_id = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$customer['id'], $lead['id']]);
            
            echo "  ✅ user_id gesetzt: {$customer['id']}\n\n";
            $updated++;
        } else {
            echo "  ❌ Kein Customer mit {$userRefColumn}='{$refCode}' gefunden\n\n";
            $failed++;
        }
    }
    
    echo "\n=== Migration Abgeschlossen ===\n";
    echo "✅ Erfolgreich aktualisiert: {$updated}\n";
    echo "❌ Fehlgeschlagen: {$failed}\n";
    echo "📊 Gesamt: {$count}\n";
    
    // Zeige Beispiel-Leads nach Migration
    echo "\n=== Beispiel-Leads nach Migration ===\n";
    $stmt = $db->query("
        SELECT 
            lu.id,
            lu.email,
            lu.user_id,
            lu.{$referrer_column} as referrer_code,
            u.company_name
        FROM lead_users lu
        LEFT JOIN users u ON lu.user_id = u.id
        ORDER BY lu.registered_at DESC
        LIMIT 10
    ");
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($examples as $ex) {
        echo sprintf(
            "ID: %d | Email: %s | user_id: %s | Referrer: %s | Company: %s\n",
            $ex['id'],
            $ex['email'],
            $ex['user_id'] ?? 'NULL',
            $ex['referrer_code'] ?? 'NULL',
            $ex['company_name'] ?? 'N/A'
        );
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    error_log("Lead Migration Error: " . $e->getMessage());
    exit(1);
}

<?php
/**
 * Migration: Verknüpfe bestehende Leads mit ihren Customers
 * Setzt fehlende user_id basierend auf referred_by Referral-Code
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "=== Lead User-ID Migration ===\n\n";
    
    // Schritt 1: Finde alle Leads ohne user_id aber mit referred_by
    $stmt = $db->query("
        SELECT 
            lu.id,
            lu.email,
            lu.name,
            lu.referred_by,
            lu.created_at
        FROM lead_users lu
        WHERE lu.user_id IS NULL 
        AND lu.referred_by IS NOT NULL
        ORDER BY lu.created_at DESC
    ");
    
    $leadsWithoutUserId = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($leadsWithoutUserId);
    
    echo "Gefunden: {$count} Leads ohne user_id aber mit referred_by\n\n";
    
    if ($count === 0) {
        echo "✅ Keine Leads zum Aktualisieren gefunden.\n";
        exit;
    }
    
    // Schritt 2: Für jeden Lead, finde den Customer anhand des Referral-Codes
    $updated = 0;
    $failed = 0;
    
    foreach ($leadsWithoutUserId as $lead) {
        $refCode = $lead['referred_by'];
        
        echo "Lead: {$lead['email']} (ID: {$lead['id']})\n";
        echo "  Referral-Code: {$refCode}\n";
        
        // Suche den Customer mit diesem Referral-Code
        $stmt = $db->prepare("
            SELECT id, email, company_name 
            FROM users 
            WHERE referral_code = ?
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
            echo "  ❌ Kein Customer mit Referral-Code '{$refCode}' gefunden\n\n";
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
            lu.referred_by,
            u.company_name
        FROM lead_users lu
        LEFT JOIN users u ON lu.user_id = u.id
        ORDER BY lu.created_at DESC
        LIMIT 5
    ");
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($examples as $ex) {
        echo sprintf(
            "ID: %d | Email: %s | user_id: %s | Company: %s\n",
            $ex['id'],
            $ex['email'],
            $ex['user_id'] ?? 'NULL',
            $ex['company_name'] ?? 'N/A'
        );
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    error_log("Lead Migration Error: " . $e->getMessage());
    exit(1);
}

<?php
/**
 * WEBHOOK UPDATE: Respektiert manuelle Admin-Änderungen
 * 
 * Füge diese Funktionen in /webhook/digistore24.php ein
 * oder nutze dieses File als Referenz
 */

/**
 * AKTUALISIERTE Freebie-Limit Funktion
 * Respektiert manuelle Admin-Änderungen
 */
function setFreebieLimit_v2($pdo, $userId, $productId, $productConfig) {
    $freebieLimit = $productConfig['own_freebies_limit'] ?? 5;
    $productName = $productConfig['product_name'] ?? 'Unbekannt';
    
    // Prüfen ob bereits ein Limit existiert
    $stmt = $pdo->prepare("
        SELECT id, freebie_limit, source, product_id 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // KRITISCH: Manuelle Änderungen NICHT überschreiben!
        if ($existing['source'] === 'manual') {
            logWebhook([
                'info' => 'Freebie limit set manually by admin - not overwriting',
                'user_id' => $userId,
                'manual_limit' => $existing['freebie_limit'],
                'webhook_would_set' => $freebieLimit
            ], 'info');
            return;
        }
        
        // Bei webhook/upgrade: Nur upgraden, nie downgraden
        if ($freebieLimit > $existing['freebie_limit']) {
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, 
                    product_id = ?, 
                    product_name = ?, 
                    source = 'webhook',
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$freebieLimit, $productId, $productName, $userId]);
            
            logWebhook([
                'success' => 'Freebie limit upgraded via webhook',
                'user_id' => $userId,
                'old_limit' => $existing['freebie_limit'],
                'new_limit' => $freebieLimit,
                'product_id' => $productId
            ], 'success');
        } else {
            logWebhook([
                'info' => 'Webhook limit not higher - keeping existing',
                'user_id' => $userId,
                'current_limit' => $existing['freebie_limit'],
                'webhook_limit' => $freebieLimit
            ], 'info');
        }
    } else {
        // Neues Limit erstellen (immer via webhook)
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebie_limits (
                customer_id, freebie_limit, product_id, product_name, source
            ) VALUES (?, ?, ?, ?, 'webhook')
        ");
        $stmt->execute([$userId, $freebieLimit, $productId, $productName]);
        
        logWebhook([
            'success' => 'Freebie limit created via webhook',
            'user_id' => $userId,
            'limit' => $freebieLimit,
            'product_id' => $productId
        ], 'success');
    }
}

/**
 * AKTUALISIERTE Referral-Slots Funktion
 * Respektiert manuelle Admin-Änderungen und speichert Produkt-Referenz
 */
function setReferralSlots_v2($pdo, $userId, $productConfig) {
    $slots = $productConfig['referral_program_slots'] ?? 1;
    $productId = $productConfig['product_id'] ?? '';
    $productName = $productConfig['product_name'] ?? '';
    
    try {
        // Prüfen ob bereits Slots existieren
        $stmt = $pdo->prepare("
            SELECT id, total_slots, source 
            FROM customer_referral_slots 
            WHERE customer_id = ?
        ");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // KRITISCH: Manuelle Änderungen NICHT überschreiben!
            if ($existing['source'] === 'manual') {
                logWebhook([
                    'info' => 'Referral slots set manually by admin - not overwriting',
                    'user_id' => $userId,
                    'manual_slots' => $existing['total_slots'],
                    'webhook_would_set' => $slots
                ], 'info');
                return;
            }
            
            // Bei webhook: Nur upgraden
            if ($slots > $existing['total_slots']) {
                $stmt = $pdo->prepare("
                    UPDATE customer_referral_slots 
                    SET total_slots = ?, 
                        product_id = ?,
                        product_name = ?,
                        source = 'webhook',
                        updated_at = NOW()
                    WHERE customer_id = ?
                ");
                $stmt->execute([$slots, $productId, $productName, $userId]);
                
                logWebhook([
                    'success' => 'Referral slots upgraded via webhook',
                    'user_id' => $userId,
                    'old_slots' => $existing['total_slots'],
                    'new_slots' => $slots
                ], 'success');
            }
        } else {
            // Neue Slots erstellen
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (
                    customer_id, total_slots, used_slots, 
                    product_id, product_name, source, created_at
                ) VALUES (?, ?, 0, ?, ?, 'webhook', NOW())
            ");
            $stmt->execute([$userId, $slots, $productId, $productName]);
            
            logWebhook([
                'success' => 'Referral slots created via webhook',
                'user_id' => $userId,
                'slots' => $slots,
                'product_id' => $productId
            ], 'success');
        }
    } catch (PDOException $e) {
        logWebhook([
            'error' => 'Failed to set referral slots',
            'user_id' => $userId,
            'message' => $e->getMessage()
        ], 'error');
    }
}

/*
 * ANLEITUNG ZUM INTEGRIEREN:
 * 
 * 1. Öffne: /webhook/digistore24.php
 * 
 * 2. Ersetze die Funktion setFreebieLimit() (ca. Zeile 280)
 *    mit setFreebieLimit_v2() von oben
 * 
 * 3. Ersetze die Funktion setReferralSlots() (ca. Zeile 327)
 *    mit setReferralSlots_v2() von oben
 * 
 * 4. Im handleNewCustomer() (ca. Zeile 73):
 *    Ändere:
 *      setFreebieLimit($pdo, $userId, $productId, $productConfig);
 *    zu:
 *      setFreebieLimit_v2($pdo, $userId, $productId, $productConfig);
 *    
 *    Ändere:
 *      setReferralSlots($pdo, $userId, $productConfig);
 *    zu:
 *      setReferralSlots_v2($pdo, $userId, $productConfig);
 * 
 * 5. Wichtig: $productConfig braucht jetzt auch 'product_id'
 *    In getProductConfig() ist das schon drin!
 */

// Test der neuen Funktionen (entferne nach Integration)
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Webhook Update - Source Tracking</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #10b981; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 6px; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 6px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Webhook Update bereit!</h1>
        
        <div class='success'>
            <strong>Die neuen Webhook-Funktionen sind fertig!</strong>
            <p style='margin-top: 10px;'>Diese Funktionen respektieren jetzt manuelle Admin-Änderungen.</p>
        </div>
        
        <div class='info'>
            <h3>🔧 Was wurde verbessert:</h3>
            <ul style='margin: 10px 0 0 20px;'>
                <li><strong>Source-Tracking:</strong> Webhook prüft ob Limit manuell gesetzt wurde</li>
                <li><strong>Schutz vor Überschreiben:</strong> Manuelle Limits werden NICHT überschrieben</li>
                <li><strong>Produkt-Referenz:</strong> Speichert product_id und product_name</li>
                <li><strong>Upgrade-Logik:</strong> Nur höhere Limits werden gesetzt</li>
                <li><strong>Logging:</strong> Detaillierte Logs für alle Aktionen</li>
            </ul>
        </div>
        
        <div class='info'>
            <h3>📝 Beispiel-Szenarien:</h3>
            <pre>
<strong>Szenario 1: Manuelle Änderung geschützt</strong>
1. Admin setzt Kunde manuell auf 15 Freebies (source=manual)
2. Kunde kauft Pro Abo (8 Freebies via webhook)
3. ✅ Webhook überschreibt NICHT → Bleibt bei 15

<strong>Szenario 2: Upgrade via Webhook</strong>
1. Kunde hat Starter (4 Freebies, source=webhook)
2. Kunde upgraded zu Pro (8 Freebies)
3. ✅ Webhook upgraded → 4 → 8

<strong>Szenario 3: Downgrade verhindert</strong>
1. Kunde hat Business (20 Freebies, source=webhook)
2. Kunde kauft versehentlich Starter (4 Freebies)
3. ✅ Webhook downgradet NICHT → Bleibt bei 20
            </pre>
        </div>
        
        <div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 6px;'>
            <strong>⚠️ WICHTIG - Manuelle Integration erforderlich:</strong>
            <p style='margin-top: 10px;'>Die Funktionen in diesem File müssen manuell in <code>/webhook/digistore24.php</code> integriert werden.</p>
            <p>Folge der Anleitung im Code-Kommentar oben.</p>
        </div>
        
        <a href='/database/fix-limits-conflicts.php' style='display: inline-block; background: #ef4444; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-right: 10px;'>
            ← Zurück zu Konflikt-Fixes
        </a>
        
        <a href='/admin/dashboard.php?page=digistore' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
            → Zum Admin-Dashboard
        </a>
    </div>
</body>
</html>";

<?php
/**
 * Test-Szenario für Reward-System
 * Erstellt Test-Lead und simuliert erfolgreiche Empfehlungen
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/test-reward-scenario.php
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();

// User ID für cybercop33@web.de ermitteln
$userStmt = $pdo->prepare("SELECT id, email, company_name FROM users WHERE email = ?");
$userStmt->execute(['cybercop33@web.de']);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('<h1>❌ Fehler</h1><p>User cybercop33@web.de nicht gefunden!</p>');
}

$userId = $user['id'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test-Szenario: Reward System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(to bottom right, #1f2937, #111827);
            color: white;
        }
        .container {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        h1 {
            color: #8B5CF6;
            margin-bottom: 10px;
        }
        .step {
            margin: 30px 0;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            border-left: 4px solid #8B5CF6;
        }
        .success {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #10b981;
        }
        .error {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ef4444;
        }
        .info {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #3b82f6;
        }
        .warning {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #f59e0b;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 10px 10px 0;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        .btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(0,0,0,0.2);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(139, 92, 246, 0.2);
            font-weight: 600;
            color: #8B5CF6;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test-Szenario: Reward System</h1>
        <p style="color: #9ca3af;">Teste das Belohnungssystem mit echten Daten</p>
        
        <div class="info">
            <strong>👤 Test-User:</strong> <?php echo htmlspecialchars($user['email']); ?> 
            (ID: <?php echo $userId; ?>)
            <br>
            <strong>🏢 Company:</strong> <?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?>
        </div>

        <?php
        
        // ========================================
        // SCHRITT 1: Test-Lead erstellen/finden
        // ========================================
        
        if (isset($_GET['create_lead'])) {
            echo '<div class="step">';
            echo '<h2>📝 Schritt 1: Test-Lead erstellen</h2>';
            
            try {
                // Prüfe ob Test-Lead bereits existiert
                $leadStmt = $pdo->prepare("
                    SELECT * FROM lead_users 
                    WHERE user_id = ? AND email LIKE '%test%'
                    LIMIT 1
                ");
                $leadStmt->execute([$userId]);
                $existingLead = $leadStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingLead) {
                    echo '<div class="warning">';
                    echo '<strong>ℹ️ Test-Lead existiert bereits:</strong><br>';
                    echo 'Email: ' . htmlspecialchars($existingLead['email']) . '<br>';
                    echo 'ID: ' . $existingLead['id'] . '<br>';
                    echo 'Empfehlungen: ' . $existingLead['successful_referrals'];
                    echo '</div>';
                    
                    $leadId = $existingLead['id'];
                } else {
                    // Erstelle neuen Test-Lead
                    $referralCode = substr(md5(uniqid()), 0, 8);
                    
                    $insertStmt = $pdo->prepare("
                        INSERT INTO lead_users 
                        (user_id, email, name, referral_code, successful_referrals, total_referrals, created_at)
                        VALUES (?, ?, ?, ?, 0, 0, NOW())
                    ");
                    
                    $testEmail = 'test.lead.' . time() . '@example.com';
                    $insertStmt->execute([
                        $userId,
                        $testEmail,
                        'Test Lead',
                        $referralCode
                    ]);
                    
                    $leadId = $pdo->lastInsertId();
                    
                    echo '<div class="success">';
                    echo '<strong>✅ Test-Lead erstellt:</strong><br>';
                    echo 'Email: ' . htmlspecialchars($testEmail) . '<br>';
                    echo 'ID: ' . $leadId . '<br>';
                    echo 'Ref-Code: ' . $referralCode;
                    echo '</div>';
                }
                
                // Zeige Lead-Details
                $lead = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
                $lead->execute([$leadId]);
                $leadData = $lead->fetch(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>Feld</th><th>Wert</th></tr>';
                echo '<tr><td>ID</td><td>' . $leadData['id'] . '</td></tr>';
                echo '<tr><td>Email</td><td>' . htmlspecialchars($leadData['email']) . '</td></tr>';
                echo '<tr><td>Name</td><td>' . htmlspecialchars($leadData['name']) . '</td></tr>';
                echo '<tr><td>Referral Code</td><td><strong>' . htmlspecialchars($leadData['referral_code']) . '</strong></td></tr>';
                echo '<tr><td>Erfolgreiche Empfehlungen</td><td><strong>' . $leadData['successful_referrals'] . '</strong></td></tr>';
                echo '<tr><td>Gesamt Empfehlungen</td><td>' . $leadData['total_referrals'] . '</td></tr>';
                echo '<tr><td>Rewards Earned</td><td>' . ($leadData['rewards_earned'] ?? 0) . '</td></tr>';
                echo '</table>';
                
                echo '<a href="?set_referrals=' . $leadId . '" class="btn btn-success">▶️ Weiter: Empfehlungen hinzufügen</a>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
        }
        
        // ========================================
        // SCHRITT 2: Empfehlungen hinzufügen
        // ========================================
        
        if (isset($_GET['set_referrals'])) {
            $leadId = intval($_GET['set_referrals']);
            $referrals = isset($_GET['amount']) ? intval($_GET['amount']) : 3;
            
            echo '<div class="step">';
            echo '<h2>🎯 Schritt 2: Empfehlungen simulieren</h2>';
            
            try {
                // Update Lead
                $updateStmt = $pdo->prepare("
                    UPDATE lead_users 
                    SET successful_referrals = ?,
                        total_referrals = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$referrals, $referrals, $leadId]);
                
                // Lead-Details laden
                $lead = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
                $lead->execute([$leadId]);
                $leadData = $lead->fetch(PDO::FETCH_ASSOC);
                
                echo '<div class="success">';
                echo '<strong>✅ Empfehlungen gesetzt:</strong><br>';
                echo 'Lead: ' . htmlspecialchars($leadData['email']) . '<br>';
                echo 'Erfolgreiche Empfehlungen: <strong>' . $leadData['successful_referrals'] . '</strong>';
                echo '</div>';
                
                // Zeige welche Belohnungen erreicht werden
                $rewardsStmt = $pdo->prepare("
                    SELECT * FROM reward_definitions 
                    WHERE user_id = ? 
                    AND required_referrals <= ?
                    AND is_active = 1
                    ORDER BY tier_level ASC
                ");
                $rewardsStmt->execute([$userId, $referrals]);
                $eligibleRewards = $rewardsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($eligibleRewards) > 0) {
                    echo '<div class="info">';
                    echo '<strong>🎁 Berechtigte Belohnungen:</strong><br>';
                    echo 'Der Lead hat jetzt Anspruch auf ' . count($eligibleRewards) . ' Belohnung(en)';
                    echo '</div>';
                    
                    echo '<table>';
                    echo '<tr><th>Stufe</th><th>Name</th><th>Titel</th><th>Erforderlich</th><th>Tag</th></tr>';
                    foreach ($eligibleRewards as $reward) {
                        echo '<tr>';
                        echo '<td>' . $reward['tier_level'] . '</td>';
                        echo '<td>' . htmlspecialchars($reward['tier_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($reward['reward_title']) . '</td>';
                        echo '<td>' . $reward['required_referrals'] . '</td>';
                        echo '<td>' . ($reward['reward_tag'] ? '<span class="badge badge-success">' . htmlspecialchars($reward['reward_tag']) . '</span>' : '<span class="badge badge-warning">Fallback</span>') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="warning">';
                    echo '<strong>⚠️ Keine berechtigten Belohnungen!</strong><br>';
                    echo 'Der Lead hat ' . $referrals . ' Empfehlungen, aber keine aktive Belohnung mit so wenigen oder weniger erforderlichen Empfehlungen.';
                    echo '</div>';
                }
                
                // Verschiedene Referral-Mengen anbieten
                echo '<div style="margin: 20px 0;">';
                echo '<p style="color: #9ca3af; margin-bottom: 10px;">Andere Mengen testen:</p>';
                echo '<a href="?set_referrals=' . $leadId . '&amount=1" class="btn">1 Empfehlung</a>';
                echo '<a href="?set_referrals=' . $leadId . '&amount=3" class="btn">3 Empfehlungen</a>';
                echo '<a href="?set_referrals=' . $leadId . '&amount=5" class="btn">5 Empfehlungen</a>';
                echo '<a href="?set_referrals=' . $leadId . '&amount=10" class="btn">10 Empfehlungen</a>';
                echo '</div>';
                
                echo '<a href="?trigger_cron=' . $leadId . '" class="btn btn-success">▶️ Weiter: Cronjob auslösen</a>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
        }
        
        // ========================================
        // SCHRITT 3: Cronjob triggern
        // ========================================
        
        if (isset($_GET['trigger_cron'])) {
            $leadId = intval($_GET['trigger_cron']);
            
            echo '<div class="step">';
            echo '<h2>⚡ Schritt 3: Cronjob auslösen</h2>';
            
            // Lead-Details
            $lead = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
            $lead->execute([$leadId]);
            $leadData = $lead->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="info">';
            echo '<strong>📋 Vor dem Cronjob:</strong><br>';
            echo 'Lead: ' . htmlspecialchars($leadData['email']) . '<br>';
            echo 'Empfehlungen: ' . $leadData['successful_referrals'] . '<br>';
            echo 'Rewards Earned: ' . ($leadData['rewards_earned'] ?? 0);
            echo '</div>';
            
            // Cronjob URL
            $cronUrl = 'https://app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php';
            
            echo '<div class="code">';
            echo 'curl ' . $cronUrl;
            echo '</div>';
            
            echo '<a href="' . $cronUrl . '" target="_blank" class="btn btn-success">🚀 Cronjob jetzt auslösen</a>';
            echo '<a href="?check_result=' . $leadId . '" class="btn">📊 Ergebnis prüfen</a>';
            
            echo '</div>';
        }
        
        // ========================================
        // SCHRITT 4: Ergebnis prüfen
        // ========================================
        
        if (isset($_GET['check_result'])) {
            $leadId = intval($_GET['check_result']);
            
            echo '<div class="step">';
            echo '<h2>📊 Schritt 4: Ergebnis prüfen</h2>';
            
            try {
                // Lead-Details nach Cronjob
                $lead = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
                $lead->execute([$leadId]);
                $leadData = $lead->fetch(PDO::FETCH_ASSOC);
                
                // Ausgelieferte Belohnungen
                $deliveriesStmt = $pdo->prepare("
                    SELECT 
                        rd.*,
                        rdef.tier_name,
                        rdef.reward_title,
                        rdef.reward_tag
                    FROM reward_deliveries rd
                    LEFT JOIN reward_definitions rdef ON rd.reward_id = rdef.id
                    WHERE rd.lead_id = ?
                    ORDER BY rd.delivered_at DESC
                ");
                $deliveriesStmt->execute([$leadId]);
                $deliveries = $deliveriesStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<div class="success">';
                echo '<strong>✅ Lead-Status nach Cronjob:</strong><br>';
                echo 'Email: ' . htmlspecialchars($leadData['email']) . '<br>';
                echo 'Empfehlungen: ' . $leadData['successful_referrals'] . '<br>';
                echo 'Rewards Earned: <strong>' . ($leadData['rewards_earned'] ?? 0) . '</strong> 🎁<br>';
                echo 'Ausgelieferte Belohnungen: <strong>' . count($deliveries) . '</strong>';
                echo '</div>';
                
                if (count($deliveries) > 0) {
                    echo '<h3 style="color: #10b981;">🎉 Ausgelieferte Belohnungen:</h3>';
                    echo '<table>';
                    echo '<tr><th>Zeitpunkt</th><th>Stufe</th><th>Titel</th><th>Tag</th><th>Methode</th></tr>';
                    
                    foreach ($deliveries as $delivery) {
                        $details = json_decode($delivery['delivery_details'], true);
                        
                        echo '<tr>';
                        echo '<td>' . date('d.m.Y H:i', strtotime($delivery['delivered_at'])) . '</td>';
                        echo '<td>' . htmlspecialchars($delivery['tier_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($delivery['reward_title']) . '</td>';
                        echo '<td>' . ($delivery['reward_tag'] ? '<span class="badge badge-success">' . htmlspecialchars($delivery['reward_tag']) . '</span>' : '<span class="badge badge-warning">Fallback</span>') . '</td>';
                        echo '<td><span class="badge badge-success">' . htmlspecialchars($delivery['delivery_method']) . '</span></td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Details anzeigen
                    echo '<h3 style="color: #8B5CF6;">📝 Delivery Details:</h3>';
                    foreach ($deliveries as $delivery) {
                        $details = json_decode($delivery['delivery_details'], true);
                        
                        echo '<div class="code">';
                        echo htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT));
                        echo '</div>';
                    }
                    
                    echo '<div class="info">';
                    echo '<strong>✅ Jetzt in Quentn prüfen:</strong><br>';
                    echo '1. Wurde der Tag "' . ($delivery['reward_tag'] ?? 'reward_X_earned') . '" gesetzt?<br>';
                    echo '2. Wurden die Custom Fields aktualisiert?<br>';
                    echo '3. Wurde die Kampagne getriggert?<br>';
                    echo '4. Wurde die E-Mail versendet?';
                    echo '</div>';
                    
                } else {
                    echo '<div class="warning">';
                    echo '<strong>⚠️ Keine Belohnungen ausgeliefert!</strong><br><br>';
                    echo '<strong>Mögliche Gründe:</strong><br>';
                    echo '• Belohnung bereits ausgeliefert (keine Doppel-Zusendung)<br>';
                    echo '• Keine aktive Belohnung für diese Anzahl Empfehlungen<br>';
                    echo '• Email-API nicht konfiguriert<br>';
                    echo '• Lead hat nicht genug successful_referrals';
                    echo '</div>';
                    
                    // Zeige was der Cronjob gesucht hätte
                    $expectedStmt = $pdo->prepare("
                        SELECT * FROM reward_definitions 
                        WHERE user_id = ? 
                        AND required_referrals <= ?
                        AND is_active = 1
                    ");
                    $expectedStmt->execute([$userId, $leadData['successful_referrals']]);
                    $expectedRewards = $expectedStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($expectedRewards) > 0) {
                        echo '<div class="info">';
                        echo '<strong>Expected Rewards (hätte ausgeliefert werden sollen):</strong>';
                        echo '</div>';
                        
                        echo '<table>';
                        echo '<tr><th>Stufe</th><th>Titel</th><th>Erforderlich</th><th>Tag</th></tr>';
                        foreach ($expectedRewards as $reward) {
                            echo '<tr>';
                            echo '<td>' . $reward['tier_level'] . '</td>';
                            echo '<td>' . htmlspecialchars($reward['reward_title']) . '</td>';
                            echo '<td>' . $reward['required_referrals'] . '</td>';
                            echo '<td>' . ($reward['reward_tag'] ? htmlspecialchars($reward['reward_tag']) : 'reward_' . $reward['tier_level'] . '_earned') . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                }
                
                echo '<a href="?create_lead=1" class="btn">🔄 Von vorne beginnen</a>';
                echo '<a href="?" class="btn btn-danger">❌ Zurücksetzen</a>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
        }
        
        // ========================================
        // START: Übersicht anzeigen
        // ========================================
        
        if (!isset($_GET['create_lead']) && !isset($_GET['set_referrals']) && !isset($_GET['trigger_cron']) && !isset($_GET['check_result'])) {
            echo '<div class="step">';
            echo '<h2>🚀 Test starten</h2>';
            echo '<p style="color: #9ca3af;">Dieser Test simuliert einen Lead der Empfehlungen sammelt und eine Belohnung verdient.</p>';
            
            echo '<h3>📋 Was wird getestet:</h3>';
            echo '<ol style="line-height: 2;">';
            echo '<li>Test-Lead erstellen oder existierenden verwenden</li>';
            echo '<li>Empfehlungen simulieren (successful_referrals erhöhen)</li>';
            echo '<li>Cronjob manuell triggern</li>';
            echo '<li>Prüfen ob Tag gesetzt und Custom Fields aktualisiert wurden</li>';
            echo '<li>In Quentn verifizieren dass Kampagne getriggert wurde</li>';
            echo '</ol>';
            
            // Zeige vorhandene Belohnungen
            $rewardsStmt = $pdo->prepare("
                SELECT * FROM reward_definitions 
                WHERE user_id = ?
                ORDER BY tier_level ASC
            ");
            $rewardsStmt->execute([$userId]);
            $rewards = $rewardsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h3>🏆 Deine aktiven Belohnungen:</h3>';
            
            if (count($rewards) > 0) {
                echo '<table>';
                echo '<tr><th>Stufe</th><th>Name</th><th>Titel</th><th>Erforderlich</th><th>Status</th><th>Tag</th></tr>';
                foreach ($rewards as $reward) {
                    echo '<tr>';
                    echo '<td>' . $reward['tier_level'] . '</td>';
                    echo '<td>' . htmlspecialchars($reward['tier_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($reward['reward_title']) . '</td>';
                    echo '<td>' . $reward['required_referrals'] . '</td>';
                    echo '<td>' . ($reward['is_active'] ? '<span class="badge badge-success">Aktiv</span>' : '<span class="badge badge-danger">Inaktiv</span>') . '</td>';
                    echo '<td>' . ($reward['reward_tag'] ? '<span class="badge badge-success">' . htmlspecialchars($reward['reward_tag']) . '</span>' : '<span class="badge badge-warning">Fallback</span>') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">';
                echo '⚠️ Keine Belohnungen definiert! Bitte erstelle zuerst Belohnungen unter <a href="/customer/dashboard.php?page=belohnungsstufen" style="color: #f59e0b; text-decoration: underline;">Belohnungsstufen</a>';
                echo '</div>';
            }
            
            echo '<br><a href="?create_lead=1" class="btn btn-success">🚀 Test starten</a>';
            
            echo '</div>';
        }
        
        ?>
    </div>
</body>
</html>

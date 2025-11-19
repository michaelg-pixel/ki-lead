<?php
/**
 * Manual Test Script für Reward Auto-Delivery
 * 
 * Testet die automatische Belohnungsauslieferung manuell
 * OHNE dass der Cronjob läuft
 * 
 * USAGE: php test-auto-delivery.php
 * oder im Browser: https://app.mehr-infos-jetzt.de/api/rewards/test-auto-delivery.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/email-delivery-service.php';
require_once __DIR__ . '/auto-deliver-cron.php';

// HTML Output für Browser
$isBrowser = php_sapi_name() !== 'cli';

if ($isBrowser) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reward Auto-Delivery Test</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(to bottom right, #1f2937, #111827);
                color: #e5e7eb;
                padding: 40px 20px;
                min-height: 100vh;
            }
            .container {
                max-width: 900px;
                margin: 0 auto;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 30px;
                border-radius: 12px;
                margin-bottom: 30px;
                text-align: center;
            }
            .header h1 {
                font-size: 28px;
                color: white;
                margin-bottom: 10px;
            }
            .header p {
                color: rgba(255,255,255,0.9);
                font-size: 14px;
            }
            .card {
                background: rgba(31, 41, 55, 0.8);
                border: 1px solid rgba(102, 126, 234, 0.3);
                border-radius: 12px;
                padding: 30px;
                margin-bottom: 20px;
            }
            .card h2 {
                color: #667eea;
                font-size: 20px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .status {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .status-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
            .status-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
            .status-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
            .status-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
            .log-item {
                background: rgba(0, 0, 0, 0.3);
                border-left: 3px solid #667eea;
                padding: 12px;
                margin: 8px 0;
                border-radius: 6px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
            }
            .log-success { border-left-color: #10b981; color: #6ee7b7; }
            .log-error { border-left-color: #ef4444; color: #fca5a5; }
            .log-warning { border-left-color: #fbbf24; color: #fcd34d; }
            .stat {
                display: flex;
                justify-content: space-between;
                padding: 12px;
                background: rgba(0, 0, 0, 0.2);
                border-radius: 8px;
                margin: 8px 0;
            }
            .stat-label { color: #9ca3af; }
            .stat-value { color: white; font-weight: 600; }
            pre {
                background: rgba(0, 0, 0, 0.4);
                padding: 15px;
                border-radius: 8px;
                overflow-x: auto;
                font-size: 13px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎁 Reward Auto-Delivery Test</h1>
                <p>Teste die automatische Belohnungsauslieferung</p>
            </div>
    <?php
}

function outputLog($message, $type = 'info') {
    global $isBrowser;
    
    $timestamp = date('Y-m-d H:i:s');
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    if ($isBrowser) {
        echo "<div class='log-item log-{$type}'>[{$timestamp}] {$icons[$type]} {$message}</div>";
    } else {
        echo "[{$timestamp}] {$icons[$type]} {$message}\n";
    }
}

try {
    if ($isBrowser) {
        echo "<div class='card'>";
        echo "<h2>🔍 System-Check</h2>";
    }
    
    // 1. Datenbank-Verbindung testen
    outputLog("Teste Datenbankverbindung...", 'info');
    $pdo = getDBConnection();
    outputLog("Datenbankverbindung OK", 'success');
    
    // 2. Prüfe benötigte Tabellen
    outputLog("Prüfe Tabellen...", 'info');
    $tables = ['lead_users', 'reward_definitions', 'customer_email_api_settings'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() == 0) {
            outputLog("Tabelle '{$table}' fehlt!", 'error');
            throw new Exception("Erforderliche Tabelle fehlt: {$table}");
        }
    }
    outputLog("Alle erforderlichen Tabellen vorhanden", 'success');
    
    // 3. Prüfe Email-API-Konfigurationen
    outputLog("Prüfe Email-API-Konfigurationen...", 'info');
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_email_api_settings WHERE is_active = TRUE");
    $apiCount = $stmt->fetchColumn();
    outputLog("Aktive Email-APIs: {$apiCount}", $apiCount > 0 ? 'success' : 'warning');
    
    if ($isBrowser) {
        echo "</div>";
        echo "<div class='card'>";
        echo "<h2>📊 Statistiken</h2>";
    }
    
    // 4. Statistiken laden
    outputLog("Lade Statistiken...", 'info');
    
    // Leads mit Empfehlungen
    $stmt = $pdo->query("SELECT COUNT(*) FROM lead_users WHERE successful_referrals > 0");
    $leadsWithReferrals = $stmt->fetchColumn();
    
    // Aktive Belohnungsstufen
    $stmt = $pdo->query("SELECT COUNT(*) FROM reward_definitions WHERE is_active = 1");
    $activeRewards = $stmt->fetchColumn();
    
    // Bereits ausgelieferte Belohnungen
    $stmt = $pdo->query("SELECT COUNT(*) FROM reward_deliveries");
    $deliveredRewards = $stmt->fetchColumn();
    
    if ($isBrowser) {
        echo "<div class='stat'><span class='stat-label'>Leads mit Empfehlungen:</span><span class='stat-value'>{$leadsWithReferrals}</span></div>";
        echo "<div class='stat'><span class='stat-label'>Aktive Belohnungsstufen:</span><span class='stat-value'>{$activeRewards}</span></div>";
        echo "<div class='stat'><span class='stat-label'>Ausgelieferte Belohnungen:</span><span class='stat-value'>{$deliveredRewards}</span></div>";
        echo "</div>";
        echo "<div class='card'>";
        echo "<h2>🚀 Auto-Delivery Test</h2>";
    }
    
    outputLog("Leads mit Empfehlungen: {$leadsWithReferrals}", 'info');
    outputLog("Aktive Belohnungsstufen: {$activeRewards}", 'info');
    outputLog("Bereits ausgeliefert: {$deliveredRewards}", 'info');
    
    // 5. Führe Auto-Delivery aus
    outputLog("Starte Auto-Delivery Prozess...", 'info');
    
    $autoDelivery = new RewardAutoDelivery();
    $result = $autoDelivery->run();
    
    if ($isBrowser) {
        echo "</div>";
        echo "<div class='card'>";
        echo "<h2>📋 Ergebnis</h2>";
    }
    
    if ($result['success']) {
        if ($isBrowser) {
            echo "<div class='status status-success'>✅ Test erfolgreich abgeschlossen</div>";
            echo "<div class='stat'><span class='stat-label'>Ausgeliefert:</span><span class='stat-value'>{$result['delivered']}</span></div>";
            echo "<div class='stat'><span class='stat-label'>Fehlgeschlagen:</span><span class='stat-value'>{$result['failed']}</span></div>";
            echo "<div class='stat'><span class='stat-label'>Gesamt geprüft:</span><span class='stat-value'>{$result['total']}</span></div>";
        } else {
            outputLog("Test erfolgreich abgeschlossen!", 'success');
            outputLog("Ausgeliefert: {$result['delivered']}", 'success');
            outputLog("Fehlgeschlagen: {$result['failed']}", $result['failed'] > 0 ? 'warning' : 'info');
            outputLog("Gesamt geprüft: {$result['total']}", 'info');
        }
    } else {
        outputLog("Test fehlgeschlagen: " . ($result['error'] ?? 'Unbekannter Fehler'), 'error');
    }
    
    if ($isBrowser) {
        echo "</div>";
        echo "<div class='card'>";
        echo "<h2>💡 Nächste Schritte</h2>";
        echo "<p style='margin-bottom: 15px;'>Der Test ist abgeschlossen. Um die automatische Auslieferung zu aktivieren:</p>";
        echo "<ol style='margin-left: 20px; line-height: 1.8;'>";
        echo "<li>Installiere den Cronjob mit: <code>bash scripts/setup-reward-cronjob.sh</code></li>";
        echo "<li>Oder richte manuell einen Cronjob ein: <code>*/10 * * * * php " . __DIR__ . "/auto-deliver-cron.php</code></li>";
        echo "<li>Überprüfe die Logs: <code>tail -f logs/reward-delivery.log</code></li>";
        echo "</ol>";
        echo "<br><a href='?' class='btn'>🔄 Test erneut ausführen</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    outputLog("KRITISCHER FEHLER: " . $e->getMessage(), 'error');
    
    if ($isBrowser) {
        echo "</div>";
        echo "<div class='card'>";
        echo "<h2>❌ Fehler</h2>";
        echo "<div class='status status-error'>Test fehlgeschlagen</div>";
        echo "<p style='margin: 15px 0;'>Fehlermeldung:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<p style='margin: 15px 0;'>Stack Trace:</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
}

if ($isBrowser) {
    echo "</div></body></html>";
}

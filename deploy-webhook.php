<?php
/**
 * GitHub Webhook Deployment Handler
 * 
 * INSTALLATION:
 * 1. Diese Datei auf den Server hochladen nach:
 *    /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/deploy-webhook.php
 * 
 * 2. In GitHub Repository → Settings → Webhooks → Add webhook
 *    Payload URL: https://app.mehr-infos-jetzt.de/deploy-webhook.php
 *    Content type: application/json
 *    Secret: [Geheimes Token - siehe unten]
 *    Which events: Just the push event
 * 
 * 3. Secret Token unten eintragen (ändern!)
 */

// SICHERHEIT: Ändere diesen Secret Key!
define('WEBHOOK_SECRET', 'dein-super-geheimer-webhook-key-12345');

// Logging
define('LOG_FILE', __DIR__ . '/logs/deployment.log');

/**
 * Log-Nachricht schreiben
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Webhook-Signatur verifizieren
 */
function verifySignature($payload, $signature) {
    $calculated = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($calculated, $signature);
}

// Hauptlogik
try {
    // Header prüfen
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Method not allowed');
    }
    
    // Payload lesen
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    // Signatur prüfen
    if (!verifySignature($payload, $signature)) {
        logMessage('❌ FEHLER: Ungültige Signatur');
        http_response_code(403);
        die('Invalid signature');
    }
    
    // JSON parsen
    $data = json_decode($payload, true);
    
    // Nur main branch deployen
    if (!isset($data['ref']) || $data['ref'] !== 'refs/heads/main') {
        logMessage('ℹ️  Push auf anderen Branch ignoriert: ' . ($data['ref'] ?? 'unknown'));
        die('Not main branch');
    }
    
    logMessage('========================================');
    logMessage('🚀 NEUES DEPLOYMENT GESTARTET');
    logMessage('========================================');
    logMessage('Commit: ' . ($data['head_commit']['message'] ?? 'unknown'));
    logMessage('Author: ' . ($data['pusher']['name'] ?? 'unknown'));
    
    // Zum Projekt-Verzeichnis wechseln
    $projectDir = __DIR__;
    chdir($projectDir);
    logMessage("📂 Arbeitsverzeichnis: $projectDir");
    
    // Git-Befehle ausführen
    $commands = [
        'git fetch origin main 2>&1',
        'git reset --hard origin/main 2>&1',
        'mkdir -p customer/sections 2>&1',
        'mkdir -p admin/sections 2>&1',
        'chmod -R 755 customer/ 2>&1',
        'chmod -R 755 admin/ 2>&1',
    ];
    
    foreach ($commands as $cmd) {
        logMessage("▶️  Führe aus: $cmd");
        $output = shell_exec($cmd);
        if ($output) {
            logMessage("   Output: " . trim($output));
        }
    }
    
    // Prüfe Dateien
    $files = [
        'customer/dashboard.php',
        'customer/sections/einstellungen.php',
        'customer/sections/kurse.php',
        'customer/sections/fortschritt.php',
        'customer/sections/freebies.php',
    ];
    
    logMessage('🔍 Prüfe Dateien:');
    foreach ($files as $file) {
        if (file_exists($file)) {
            logMessage("   ✅ $file");
        } else {
            logMessage("   ❌ $file FEHLT!");
        }
    }
    
    // Letzter Commit
    $lastCommit = shell_exec('git log -1 --oneline 2>&1');
    logMessage("📝 Aktueller Commit: " . trim($lastCommit));
    
    logMessage('========================================');
    logMessage('✅ DEPLOYMENT ERFOLGREICH');
    logMessage('========================================');
    
    echo "Deployment successful!\n";
    
} catch (Exception $e) {
    logMessage('❌ FEHLER: ' . $e->getMessage());
    http_response_code(500);
    echo "Deployment failed: " . $e->getMessage();
}

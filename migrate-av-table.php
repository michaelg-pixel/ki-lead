<?php
/**
 * Browser Migration: av_contract_acceptances Tabelle
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/migrate-av-table.php
 * 
 * SICHERHEIT: Nach erfolgreicher Migration wird dieses Script automatisch gelöscht
 */

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/database.php';

// Starte sichere Session
startSecureSession();

// Admin-Check (nur Admins dürfen migrieren)
$isAdmin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';

// Wenn nicht eingeloggt, Login-Link anzeigen
if (!isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Migration - Login erforderlich</title>
        <style>
            body { font-family: sans-serif; padding: 2rem; text-align: center; background: #f5f7fa; }
            .box { max-width: 500px; margin: 0 auto; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 0.5rem; margin-top: 1rem; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>🔒 Login erforderlich</h1>
            <p>Bitte logge dich als Admin ein, um die Migration durchzuführen.</p>
            <a href="/public/login.php" class="btn">Zum Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Migration durchführen
$migration_log = [];
$migration_success = false;
$migration_error = null;

try {
    $pdo = getDBConnection();
    
    $migration_log[] = "🔧 Starte Migration für av_contract_acceptances...";
    
    // Prüfe ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'av_contract_acceptances'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $migration_log[] = "📋 Tabelle av_contract_acceptances existiert nicht - erstelle neu...";
        
        $sql = "
        CREATE TABLE av_contract_acceptances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            accepted_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            av_contract_version VARCHAR(50) NOT NULL,
            acceptance_type VARCHAR(50) NOT NULL DEFAULT 'registration',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_acceptance_type (acceptance_type),
            INDEX idx_accepted_at (accepted_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        $migration_log[] = "✅ Tabelle av_contract_acceptances erfolgreich erstellt!";
    } else {
        $migration_log[] = "✅ Tabelle av_contract_acceptances existiert bereits";
        
        // Prüfe ob acceptance_type Spalte existiert
        $stmt = $pdo->query("SHOW COLUMNS FROM av_contract_acceptances LIKE 'acceptance_type'");
        if ($stmt->rowCount() === 0) {
            $migration_log[] = "📋 Spalte acceptance_type fehlt - füge hinzu...";
            $pdo->exec("
                ALTER TABLE av_contract_acceptances 
                ADD COLUMN acceptance_type VARCHAR(50) NOT NULL DEFAULT 'registration' AFTER av_contract_version
            ");
            $pdo->exec("
                ALTER TABLE av_contract_acceptances 
                ADD INDEX idx_acceptance_type (acceptance_type)
            ");
            $migration_log[] = "✅ Spalte acceptance_type hinzugefügt!";
        } else {
            $migration_log[] = "✅ Spalte acceptance_type existiert bereits";
        }
    }
    
    // Zeige Statistiken
    $migration_log[] = "";
    $migration_log[] = "📊 Aktuelle Statistiken:";
    $stmt = $pdo->query("
        SELECT acceptance_type, COUNT(*) as count 
        FROM av_contract_acceptances 
        GROUP BY acceptance_type
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stats)) {
        $migration_log[] = "   Noch keine Einträge vorhanden";
    } else {
        foreach ($stats as $stat) {
            $migration_log[] = "   - {$stat['acceptance_type']}: {$stat['count']} Einträge";
        }
    }
    
    $migration_log[] = "";
    $migration_log[] = "✅ Migration erfolgreich abgeschlossen!";
    $migration_success = true;
    
} catch (PDOException $e) {
    $migration_error = $e->getMessage();
    $migration_log[] = "❌ FEHLER: " . $migration_error;
}

// Zeige nur Admins die Option zum Löschen
$show_delete_button = $isAdmin && $migration_success;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration: av_contract_acceptances</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .header h1 {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .status-box {
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .status-success {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        
        .status-error {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        
        .status-box h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .status-success h2 {
            color: #065f46;
        }
        
        .status-error h2 {
            color: #991b1b;
        }
        
        .log-container {
            background: #1f2937;
            color: #e5e7eb;
            padding: 1.5rem;
            border-radius: 0.75rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.8;
            margin-bottom: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .log-line {
            margin-bottom: 0.25rem;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 0.9375rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .warning-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .warning-box p {
            color: #78350f;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Datenbank Migration</h1>
            <p>av_contract_acceptances Tabelle</p>
        </div>
        
        <?php if ($migration_success): ?>
        <div class="status-box status-success">
            <h2>✅ Migration erfolgreich!</h2>
            <p style="color: #065f46; margin-top: 0.5rem;">Die Tabelle av_contract_acceptances wurde erfolgreich erstellt/aktualisiert.</p>
        </div>
        <?php else: ?>
        <div class="status-box status-error">
            <h2>❌ Migration fehlgeschlagen</h2>
            <p style="color: #991b1b; margin-top: 0.5rem;">Es ist ein Fehler aufgetreten. Siehe Log unten.</p>
        </div>
        <?php endif; ?>
        
        <div class="log-container">
            <?php foreach ($migration_log as $line): ?>
            <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($show_delete_button): ?>
        <div class="warning-box">
            <p><strong>⚠️ Sicherheitshinweis:</strong> Dieses Migrations-Script sollte aus Sicherheitsgründen gelöscht werden. Klicke unten auf "Script löschen", um es automatisch zu entfernen.</p>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn btn-primary">
                ← Zum Empfehlungsprogramm
            </a>
            
            <?php if ($isAdmin): ?>
            <a href="/admin/dashboard.php" class="btn btn-secondary">
                Admin Dashboard
            </a>
            <?php endif; ?>
            
            <?php if ($show_delete_button): ?>
            <button onclick="deleteScript()" class="btn btn-danger">
                🗑️ Script löschen
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        async function deleteScript() {
            if (!confirm('Soll dieses Migrations-Script wirklich gelöscht werden?\n\nDies kann nicht rückgängig gemacht werden.')) {
                return;
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_script=1'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Script erfolgreich gelöscht!\n\nDu wirst zum Dashboard weitergeleitet.');
                    window.location.href = '/customer/dashboard.php?page=empfehlungsprogramm';
                } else {
                    alert('❌ Fehler beim Löschen: ' + result.error);
                }
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
            }
        }
    </script>
</body>
</html>
<?php

// Script-Löschung (nur via POST und nur für Admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_script']) && $isAdmin) {
    header('Content-Type: application/json');
    
    $script_path = __FILE__;
    
    if (file_exists($script_path)) {
        if (unlink($script_path)) {
            echo json_encode([
                'success' => true,
                'message' => 'Script erfolgreich gelöscht'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Datei konnte nicht gelöscht werden (Berechtigungsproblem)'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Script-Datei nicht gefunden'
        ]);
    }
    exit;
}
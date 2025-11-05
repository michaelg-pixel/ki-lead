<?php
/**
 * Create admin_logs Table
 * Für Admin-Aktivitäts-Logging
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Admin Logs Tabelle erstellen</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background: #f3f4f6; padding: 10px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600; }
            td { padding: 10px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>📝 Admin Logs Tabelle erstellen</h1>
            <p>Erstellt die Tabelle für Admin-Aktivitäts-Logging.</p>";
    
    // Prüfen ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'admin_logs'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='step'><strong>Schritt 1:</strong> Erstelle Tabelle <code>admin_logs</code>...</div>";
        
        $pdo->exec("
            CREATE TABLE admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL COMMENT 'Art der Aktion',
                details TEXT NULL COMMENT 'Detaillierte Beschreibung',
                ip_address VARCHAR(45) NULL COMMENT 'IP-Adresse',
                user_agent VARCHAR(255) NULL COMMENT 'Browser/Client Info',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Admin-Aktivitäts-Logs'
        ");
        
        echo "<div class='success'>✅ Tabelle <code>admin_logs</code> erfolgreich erstellt!</div>";
        
        // Zeige Tabellenstruktur
        echo "<div class='step'>
            <h3>📊 Tabellenstruktur:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Spalte</th>
                        <th>Typ</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>";
        
        $columns = $pdo->query("SHOW FULL COLUMNS FROM admin_logs")->fetchAll();
        foreach ($columns as $col) {
            echo "<tr>
                <td><code>{$col['Field']}</code></td>
                <td>{$col['Type']}</td>
                <td style='color: #666;'>{$col['Comment']}</td>
            </tr>";
        }
        
        echo "</tbody></table></div>";
        
        // Beispiel-Log einfügen
        echo "<div class='step'><strong>Schritt 2:</strong> Füge Test-Log ein...</div>";
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'] ?? 1,
                'table_created',
                'Admin-Logs Tabelle wurde erfolgreich erstellt',
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            echo "<div class='success'>✅ Test-Log wurde erfolgreich eingefügt!</div>";
            
            // Zeige Test-Log
            $testLog = $pdo->query("SELECT * FROM admin_logs ORDER BY id DESC LIMIT 1")->fetch();
            
            echo "<div class='info'>
                <strong>📄 Erster Log-Eintrag:</strong><br>
                <small style='display: block; margin-top: 8px; color: #666;'>
                    <strong>ID:</strong> {$testLog['id']}<br>
                    <strong>Admin:</strong> {$testLog['admin_id']}<br>
                    <strong>Aktion:</strong> {$testLog['action']}<br>
                    <strong>Details:</strong> {$testLog['details']}<br>
                    <strong>Zeitpunkt:</strong> {$testLog['created_at']}
                </small>
            </div>";
            
        } catch (Exception $e) {
            echo "<div class='info'>ℹ️ Test-Log konnte nicht erstellt werden (optional): " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        echo "<div class='success'>
            <strong>✅ Setup erfolgreich abgeschlossen!</strong>
            <p style='margin: 10px 0 0 0;'>
                Die Admin-Logs Tabelle ist jetzt bereit. Alle Admin-Aktionen werden ab sofort protokolliert.
            </p>
        </div>";
        
        echo "<div class='info'>
            <strong>📋 Was wird geloggt?</strong>
            <ul style='margin: 10px 0; padding-left: 20px;'>
                <li>Manuelle Limits-Änderungen bei Kunden</li>
                <li>Kunde hinzufügen/bearbeiten/löschen</li>
                <li>Freebie-Zuweisungen</li>
                <li>Status-Änderungen</li>
                <li>Und weitere wichtige Admin-Aktionen</li>
            </ul>
        </div>";
        
    } else {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Tabelle <code>admin_logs</code> existiert bereits.</div>";
        
        // Zeige Statistik
        $logCount = $pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
        $recentLogs = $pdo->query("
            SELECT al.*, u.name as admin_name, u.email as admin_email
            FROM admin_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 5
        ")->fetchAll();
        
        echo "<div class='step'>
            <h3>📊 Statistik:</h3>
            <p><strong>Gesamte Logs:</strong> $logCount</p>
        </div>";
        
        if (count($recentLogs) > 0) {
            echo "<div class='step'>
                <h3>🕐 Letzte 5 Aktivitäten:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Aktion</th>
                            <th>Details</th>
                            <th>Zeitpunkt</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($recentLogs as $log) {
                $adminName = $log['admin_name'] ?? 'Unbekannt';
                $details = strlen($log['details']) > 60 
                    ? substr($log['details'], 0, 60) . '...' 
                    : $log['details'];
                
                echo "<tr>
                    <td>{$adminName}</td>
                    <td><code>{$log['action']}</code></td>
                    <td style='font-size: 12px;'>{$details}</td>
                    <td style='font-size: 12px;'>" . date('d.m.Y H:i', strtotime($log['created_at'])) . "</td>
                </tr>";
            }
            
            echo "</tbody></table></div>";
        }
    }
    
    echo "
    <div style='text-align: center; margin-top: 30px;'>
        <a href='/admin/dashboard.php?page=users' class='btn'>→ Zur Kundenverwaltung</a>
    </div>
    
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler beim Erstellen der Tabelle</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Trace:</strong></p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 11px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>
    </div>
    </body>
    </html>";
}

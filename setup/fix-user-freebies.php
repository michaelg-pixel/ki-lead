<?php
/**
 * Fix: user_freebies Tabelle neu erstellen
 * Löscht die alte Tabelle und erstellt sie neu mit korrektem Foreign Key
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

$pdo = getDBConnection();
$messages = [];
$errors = [];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix user_freebies Tabelle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .step {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { transform: translateY(-2px); }
        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix: user_freebies Tabelle</h1>
        <p class="subtitle">Korrigiert Foreign Key zu 'freebies' Tabelle</p>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            
            <?php
            try {
                $messages[] = "🔄 Starte Fix...";
                
                // 1. Prüfen ob user_freebies existiert
                if (tableExists('user_freebies')) {
                    $messages[] = "ℹ️ user_freebies Tabelle gefunden";
                    
                    // 2. Alte Tabelle löschen (alle Zuweisungen gehen verloren!)
                    $pdo->exec("DROP TABLE IF EXISTS user_freebies");
                    $messages[] = "✅ Alte user_freebies Tabelle gelöscht";
                } else {
                    $messages[] = "ℹ️ user_freebies Tabelle existiert nicht";
                }
                
                // 3. Neue Tabelle mit korrektem Foreign Key erstellen
                $messages[] = "🔄 Erstelle neue user_freebies Tabelle...";
                
                $pdo->exec("
                    CREATE TABLE user_freebies (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        freebie_id INT NOT NULL,
                        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        assigned_by INT NULL,
                        completed TINYINT(1) DEFAULT 0,
                        completed_at DATETIME NULL,
                        INDEX idx_user (user_id),
                        INDEX idx_freebie (freebie_id),
                        INDEX idx_assigned (assigned_at),
                        UNIQUE KEY unique_assignment (user_id, freebie_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (freebie_id) REFERENCES freebies(id) ON DELETE CASCADE,
                        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $messages[] = "✅ Neue user_freebies Tabelle erstellt (verweist auf 'freebies')!";
                $messages[] = "🎉 Fix erfolgreich abgeschlossen!";
                
            } catch (Exception $e) {
                $errors[] = "❌ Fehler: " . $e->getMessage();
            }
            ?>
            
            <!-- Ergebnis anzeigen -->
            <?php foreach ($messages as $msg): ?>
                <div class="step <?php echo (strpos($msg, '✅') !== false || strpos($msg, '🎉') !== false) ? 'success' : ''; ?>">
                    <p><?php echo $msg; ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $err): ?>
                <div class="step error">
                    <p><?php echo htmlspecialchars($err); ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($errors) === 0): ?>
                <div class="step warning">
                    <h3>⚠️ Wichtig</h3>
                    <p><strong>Alle bisherigen Freebie-Zuweisungen wurden gelöscht!</strong></p>
                    <p style="margin-top: 10px;">Du kannst jetzt wieder Freebies an Kunden zuweisen.</p>
                </div>
                
                <a href="/admin/dashboard.php?page=users" class="btn">
                    ➡️ Zur Kundenverwaltung
                </a>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- Warnung -->
            <div class="step warning">
                <h3>⚠️ Warnung</h3>
                <p>Dieser Fix wird die <code>user_freebies</code> Tabelle neu erstellen.</p>
                <p style="margin-top: 10px;"><strong>ACHTUNG:</strong> Alle bisherigen Freebie-Zuweisungen gehen verloren!</p>
            </div>
            
            <div class="step">
                <h3>Was wird gemacht?</h3>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>Alte <code>user_freebies</code> Tabelle löschen</li>
                    <li>Neue Tabelle erstellen mit Foreign Key zu <code>freebies</code></li>
                    <li>Danach können Freebies zugewiesen werden</li>
                </ol>
            </div>
            
            <form method="POST">
                <button type="submit" class="btn">
                    🔧 Jetzt fixen
                </button>
            </form>
            
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * Browser-basiertes Migrations-Tool
 * Führt SQL-Migrationen aus migrations/ Ordner aus
 */

session_start();

// Admin/Customer Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'customer'])) {
    die('Keine Berechtigung');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$migrations_dir = __DIR__ . '/../migrations/';
$executed = false;
$results = [];

// Migration ausführen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
    $migration_file = $_POST['migration_file'];
    $migration_path = $migrations_dir . $migration_file;
    
    if (file_exists($migration_path) && pathinfo($migration_file, PATHINFO_EXTENSION) === 'sql') {
        try {
            $sql = file_get_contents($migration_path);
            
            // Split SQL statements (einfache Variante)
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
            );
            
            $pdo->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $pdo->exec($statement);
                    $results[] = "✅ Ausgeführt: " . substr($statement, 0, 100) . "...";
                }
            }
            
            $pdo->commit();
            $executed = true;
            $results[] = "✅ Migration erfolgreich abgeschlossen!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $results[] = "❌ Fehler: " . $e->getMessage();
        }
    } else {
        $results[] = "❌ Migrationsdatei nicht gefunden oder ungültig";
    }
}

// Verfügbare Migrationen scannen
$migrations = [];
if (is_dir($migrations_dir)) {
    $files = scandir($migrations_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $migrations[] = $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank Migrationen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        
        h1 {
            color: #1a1a2e;
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 32px;
        }
        
        .warning {
            background: rgba(251, 146, 60, 0.1);
            border-left: 4px solid #f97316;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .warning-title {
            font-weight: 600;
            color: #ea580c;
            margin-bottom: 8px;
        }
        
        .migration-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 2px solid #e5e7eb;
        }
        
        .migration-name {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        
        .migration-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .results {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .results-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .result-item {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .result-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
        }
        
        .result-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .back-link:hover {
            gap: 12px;
        }
        
        pre {
            background: #1a1a2e;
            color: #fff;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <a href="/customer/dashboard.php" class="back-link">← Zurück zum Dashboard</a>
            <h1>🔧 Datenbank Migrationen</h1>
            <p class="subtitle">Führe Datenbank-Updates sicher aus</p>
            
            <div class="warning">
                <div class="warning-title">⚠️ Wichtig</div>
                <div>
                    Migrationen ändern die Datenbankstruktur. Stelle sicher, dass du ein Backup hast, bevor du fortfährst.
                    Im Fehlerfall wird die Migration automatisch rückgängig gemacht (Rollback).
                </div>
            </div>
            
            <?php if ($executed && !empty($results)): ?>
                <div class="results">
                    <div class="results-title">Migrations-Ergebnisse:</div>
                    <?php foreach ($results as $result): ?>
                        <div class="result-item <?php echo strpos($result, '✅') !== false ? 'result-success' : 'result-error'; ?>">
                            <?php echo htmlspecialchars($result); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($migrations)): ?>
                <p>Keine Migrationen gefunden im <code>migrations/</code> Ordner.</p>
            <?php else: ?>
                <?php foreach ($migrations as $migration): ?>
                    <div class="migration-item">
                        <div class="migration-name">📄 <?php echo htmlspecialchars($migration); ?></div>
                        
                        <details>
                            <summary style="cursor: pointer; color: #667eea; font-weight: 600;">
                                SQL-Code anzeigen
                            </summary>
                            <pre><?php echo htmlspecialchars(file_get_contents($migrations_dir . $migration)); ?></pre>
                        </details>
                        
                        <form method="POST" style="margin-top: 12px;" onsubmit="return confirm('Migration wirklich ausführen?');">
                            <input type="hidden" name="migration_file" value="<?php echo htmlspecialchars($migration); ?>">
                            <div class="migration-actions">
                                <button type="submit" name="execute_migration" class="btn btn-primary">
                                    ▶️ Migration ausführen
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 16px;">💡 Hilfe</h2>
            <p style="color: #666; line-height: 1.6;">
                <strong>Was sind Migrationen?</strong><br>
                Migrationen sind SQL-Skripte, die Änderungen an der Datenbankstruktur vornehmen (z.B. neue Tabellen erstellen, Spalten hinzufügen).
                <br><br>
                <strong>Wie funktioniert es?</strong><br>
                1. Wähle eine Migration aus der Liste<br>
                2. Überprüfe den SQL-Code (optional)<br>
                3. Klicke auf "Migration ausführen"<br>
                4. Das System führt alle SQL-Befehle automatisch aus
            </p>
        </div>
    </div>
</body>
</html>

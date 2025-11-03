<?php
/**
 * Admin Datenbank-Tool
 * URL: https://app.mehr-infos-jetzt.de/admin-db-tool.php
 * 
 * SICHERHEITSHINWEIS: Nach Verwendung LÖSCHEN oder umbenennen!
 */

// Einfache Authentifizierung
session_start();
$ADMIN_PASSWORD = 'IhrSicheresPasswort123!'; // ÄNDERN SIE DIES!

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .login-box {
                    background: white;
                    padding: 2rem;
                    border-radius: 1rem;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
                    max-width: 400px;
                    width: 90%;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 0.75rem;
                    border: 2px solid #e5e7eb;
                    border-radius: 0.5rem;
                    font-size: 1rem;
                    margin-bottom: 1rem;
                }
                button {
                    width: 100%;
                    padding: 0.75rem;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    border: none;
                    border-radius: 0.5rem;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>🔐 Admin Authentifizierung</h2>
                <form method="POST">
                    <input type="password" name="password" placeholder="Passwort" required autofocus>
                    <button type="submit">Anmelden</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? 'home';
$result = null;
$error = null;

try {
    $pdo = getDBConnection();
    
    // Migration ausführen
    if ($action === 'migrate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Prüfen ob Spalte bereits existiert
            $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'freebie_id'");
            if ($stmt->rowCount() > 0) {
                $result = ['type' => 'warning', 'message' => 'Migration bereits ausgeführt! Spalte freebie_id existiert bereits.'];
            } else {
                // Spalte hinzufügen
                $pdo->exec("ALTER TABLE reward_definitions ADD COLUMN freebie_id INT NULL COMMENT 'Verknüpfung zum Freebie (optional)'");
                
                // Foreign Key hinzufügen (falls freebies Tabelle existiert)
                try {
                    $pdo->exec("
                        ALTER TABLE reward_definitions 
                        ADD CONSTRAINT fk_reward_definitions_freebie
                        FOREIGN KEY (freebie_id) 
                        REFERENCES freebies(id) 
                        ON DELETE SET NULL
                        ON UPDATE CASCADE
                    ");
                } catch (PDOException $e) {
                    // Foreign Key Fehler ignorieren falls Tabelle nicht existiert
                }
                
                // Indices erstellen
                $pdo->exec("CREATE INDEX idx_reward_definitions_freebie ON reward_definitions(freebie_id)");
                $pdo->exec("CREATE INDEX idx_reward_definitions_user_freebie ON reward_definitions(user_id, freebie_id)");
                
                $result = ['type' => 'success', 'message' => 'Migration erfolgreich ausgeführt! Spalte freebie_id wurde hinzugefügt.'];
            }
        } catch (PDOException $e) {
            $error = 'Migration Fehler: ' . $e->getMessage();
        }
    }
    
    // Custom Query ausführen
    if ($action === 'query' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $query = $_POST['query'] ?? '';
        
        if (empty($query)) {
            $error = 'Bitte geben Sie eine SQL-Query ein';
        } else {
            try {
                $stmt = $pdo->query($query);
                
                // Prüfen ob es ein SELECT ist
                if (stripos(trim($query), 'SELECT') === 0) {
                    $result = [
                        'type' => 'query',
                        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                        'count' => $stmt->rowCount()
                    ];
                } else {
                    $result = [
                        'type' => 'success',
                        'message' => 'Query erfolgreich ausgeführt. Betroffene Zeilen: ' . $stmt->rowCount()
                    ];
                }
            } catch (PDOException $e) {
                $error = 'Query Fehler: ' . $e->getMessage();
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
}

// Quick-Info Queries
$quickQueries = [
    'reward_definitions_structure' => "SHOW COLUMNS FROM reward_definitions",
    'reward_definitions_count' => "SELECT COUNT(*) as total FROM reward_definitions",
    'freebies_structure' => "SHOW COLUMNS FROM freebies",
    'freebies_count' => "SELECT COUNT(*) as total FROM freebies",
    'check_freebie_id' => "SHOW COLUMNS FROM reward_definitions LIKE 'freebie_id'",
    'rewards_with_freebie' => "SELECT COUNT(*) as count FROM reward_definitions WHERE freebie_id IS NOT NULL",
    'users_with_rewards' => "SELECT u.id, u.name, u.email, COUNT(rd.id) as reward_count FROM users u LEFT JOIN reward_definitions rd ON u.id = rd.user_id GROUP BY u.id ORDER BY reward_count DESC LIMIT 10"
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin DB Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(to bottom right, #1f2937, #111827);
            color: #e5e7eb;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.875rem;
            color: white;
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
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-secondary {
            background: #374151;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card h2 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 2px solid #10b981;
            color: #10b981;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            color: #ef4444;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.2);
            border: 2px solid #f59e0b;
            color: #f59e0b;
        }
        
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 1rem;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            resize: vertical;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #111827;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #374151;
        }
        
        th {
            background: #1f2937;
            color: #667eea;
            font-weight: 600;
        }
        
        tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .quick-queries {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .quick-query-btn {
            padding: 1rem;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }
        
        .quick-query-btn:hover {
            background: #374151;
            border-color: #667eea;
            color: white;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        code {
            background: #111827;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-database"></i> Admin Datenbank-Tool</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Abmelden
                </button>
            </form>
        </div>
        
        <!-- Warnung -->
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Sicherheitswarnung:</strong>
            Dieses Tool ermöglicht direkten Datenbankzugriff. Nach Verwendung bitte löschen oder umbenennen!
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong><i class="fas fa-times-circle"></i> Fehler:</strong>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <?php if ($result['type'] === 'success'): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Erfolg:</strong>
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
            <?php elseif ($result['type'] === 'warning'): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-info-circle"></i> Hinweis:</strong>
                <?php echo htmlspecialchars($result['message']); ?>
            </div>
            <?php elseif ($result['type'] === 'query' && !empty($result['data'])): ?>
            <div class="card">
                <h2><i class="fas fa-table"></i> Query-Ergebnis (<?php echo $result['count']; ?> Zeilen)</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($result['data'][0]) as $column): ?>
                                <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['data'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($result['type'] === 'query'): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Query erfolgreich ausgeführt</strong>
                (0 Zeilen zurückgegeben)
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="grid-2">
            <!-- Migration -->
            <div class="card">
                <h2><i class="fas fa-sync-alt"></i> Migration ausführen</h2>
                <p style="color: #9ca3af; margin-bottom: 1rem;">
                    Fügt die <code>freebie_id</code> Spalte zur <code>reward_definitions</code> Tabelle hinzu.
                </p>
                
                <form method="POST" action="?action=migrate">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Migration wirklich ausführen?')">
                        <i class="fas fa-play"></i> Migration starten
                    </button>
                </form>
                
                <div class="warning-box" style="font-size: 0.875rem;">
                    <strong>Was wird gemacht:</strong><br>
                    1. Spalte <code>freebie_id INT NULL</code> hinzufügen<br>
                    2. Foreign Key zu <code>freebies</code> erstellen<br>
                    3. Indices für Performance erstellen
                </div>
            </div>
            
            <!-- SQL Query -->
            <div class="card">
                <h2><i class="fas fa-terminal"></i> SQL Query ausführen</h2>
                <form method="POST" action="?action=query">
                    <textarea name="query" placeholder="SELECT * FROM reward_definitions LIMIT 10;" required></textarea>
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Query ausführen
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Queries -->
        <div class="card">
            <h2><i class="fas fa-bolt"></i> Schnell-Abfragen</h2>
            <div class="quick-queries">
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($quickQueries['reward_definitions_structure']); ?>">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-table"></i><br>
                        <strong>Reward Definitions</strong><br>
                        Struktur anzeigen
                    </button>
                </form>
                
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($quickQueries['check_freebie_id']); ?>">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-check"></i><br>
                        <strong>Migration Status</strong><br>
                        freebie_id prüfen
                    </button>
                </form>
                
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($quickQueries['freebies_structure']); ?>">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-gift"></i><br>
                        <strong>Freebies</strong><br>
                        Struktur anzeigen
                    </button>
                </form>
                
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($quickQueries['rewards_with_freebie']); ?>">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-link"></i><br>
                        <strong>Verknüpfungen</strong><br>
                        Rewards mit Freebie
                    </button>
                </form>
                
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($quickQueries['users_with_rewards']); ?>">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-users"></i><br>
                        <strong>User Statistik</strong><br>
                        Top 10 Users
                    </button>
                </form>
                
                <form method="POST" action="?action=query" style="margin: 0;">
                    <input type="hidden" name="query" value="SELECT * FROM reward_definitions ORDER BY created_at DESC LIMIT 10">
                    <button type="submit" class="quick-query-btn">
                        <i class="fas fa-list"></i><br>
                        <strong>Letzte Rewards</strong><br>
                        Neueste 10 anzeigen
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Hilfe -->
        <div class="card">
            <h2><i class="fas fa-question-circle"></i> Nützliche Queries</h2>
            <div style="display: grid; gap: 1rem;">
                <div>
                    <strong>Alle Rewards mit Freebies:</strong>
                    <pre style="background: #111827; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-top: 0.5rem;"><code>SELECT rd.*, f.title as freebie_title 
FROM reward_definitions rd 
LEFT JOIN freebies f ON rd.freebie_id = f.id 
ORDER BY rd.created_at DESC;</code></pre>
                </div>
                
                <div>
                    <strong>User mit ihren Freebies:</strong>
                    <pre style="background: #111827; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-top: 0.5rem;"><code>SELECT u.name, u.email, COUNT(f.id) as freebie_count 
FROM users u 
LEFT JOIN freebies f ON u.id = f.customer_id 
GROUP BY u.id;</code></pre>
                </div>
                
                <div>
                    <strong>Belohnungsstufen pro Freebie:</strong>
                    <pre style="background: #111827; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-top: 0.5rem;"><code>SELECT f.title, COUNT(rd.id) as reward_count 
FROM freebies f 
LEFT JOIN reward_definitions rd ON f.id = rd.freebie_id 
GROUP BY f.id;</code></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus auf Query-Textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('textarea[name="query"]');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    // Tab-Taste für Einrückung
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                        this.selectionStart = this.selectionEnd = start + 4;
                    }
                });
            }
        });
    </script>
</body>
</html>

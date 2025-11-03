<?php
/**
 * Quick Fix für Freebies
 * URL: https://app.mehr-infos-jetzt.de/fix-freebies-quick.php
 */

require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? 'check';
$result = [];
$error = null;

try {
    $pdo = getDBConnection();
    
    // Aktuellen Status prüfen
    $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_active'");
    $has_is_active = $stmt->rowCount() > 0;
    
    // Freebies laden
    $stmt = $pdo->query("SELECT id, name, user_id, created_at FROM freebies ORDER BY id DESC LIMIT 10");
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktion: is_active hinzufügen
    if ($action === 'add_is_active' && !$has_is_active) {
        $pdo->exec("ALTER TABLE freebies ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        $pdo->exec("UPDATE freebies SET is_active = 1");
        $result[] = "✅ Spalte 'is_active' hinzugefügt und alle Freebies aktiviert";
        $has_is_active = true;
    }
    
    // Aktion: User zuweisen
    if ($action === 'assign' && isset($_POST['freebie_id']) && isset($_POST['user_id'])) {
        $freebie_id = (int)$_POST['freebie_id'];
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE freebies SET user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $freebie_id]);
        
        $result[] = "✅ Freebie ID $freebie_id wurde User ID $user_id zugewiesen";
        
        // Neu laden
        $stmt = $pdo->query("SELECT id, name, user_id, created_at FROM freebies ORDER BY id DESC LIMIT 10");
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = "Fehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Fix - Freebies</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .content {
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid #f59e0b;
            color: #92400e;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            justify-content: center;
            margin: 0.5rem 0;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        h2 {
            color: #111827;
            margin: 1.5rem 0 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ Quick Fix - Freebies</h1>
            <p>Schnelle Reparatur in 2 Schritten</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php foreach ($result as $msg): ?>
            <div class="alert alert-success">
                <?php echo $msg; ?>
            </div>
            <?php endforeach; ?>
            
            <!-- Schritt 1: is_active -->
            <div class="box">
                <h2>Schritt 1: is_active Spalte</h2>
                
                <?php if ($has_is_active): ?>
                <div class="alert alert-success">
                    ✅ Spalte 'is_active' ist vorhanden
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Spalte 'is_active' fehlt
                </div>
                <form method="GET">
                    <input type="hidden" name="action" value="add_is_active">
                    <button type="submit" class="btn btn-primary">
                        Spalte hinzufügen
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <!-- Schritt 2: Freebies anzeigen und zuweisen -->
            <div class="box">
                <h2>Schritt 2: Freebies User zuweisen</h2>
                
                <?php if (empty($freebies)): ?>
                <div class="alert alert-warning">
                    ⚠️ Keine Freebies in der Datenbank
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>User ID</th>
                            <th>Erstellt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($freebies as $f): ?>
                        <tr style="<?php echo empty($f['user_id']) ? 'background: #fee;' : ''; ?>">
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo htmlspecialchars($f['name']); ?></td>
                            <td>
                                <?php if (empty($f['user_id'])): ?>
                                    <strong style="color: #ef4444;">NULL ❌</strong>
                                <?php else: ?>
                                    <?php echo $f['user_id']; ?> ✅
                                <?php endif; ?>
                            </td>
                            <td><?php echo $f['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p style="color: #6b7280; margin: 1rem 0;">
                    <strong>Hinweis:</strong> Rot markierte Freebies haben keine User-Zuordnung (user_id ist NULL)
                </p>
                
                <form method="POST" action="?action=assign">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Freebie ID:
                    </label>
                    <input type="number" name="freebie_id" placeholder="z.B. 16" required>
                    
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Deine User ID:
                    </label>
                    <input type="number" name="user_id" placeholder="z.B. 1" required>
                    
                    <button type="submit" class="btn btn-success">
                        Freebie mir zuweisen
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <!-- Info -->
            <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 0.5rem; margin-top: 2rem;">
                <h3 style="color: #1e40af; margin-bottom: 0.5rem;">ℹ️ So funktioniert's:</h3>
                <ol style="color: #1e3a8a; margin-left: 1.5rem;">
                    <li>Erst die <code>is_active</code> Spalte hinzufügen</li>
                    <li>Dann Deine User-ID eingeben und Freebie zuweisen</li>
                    <li>Fertig! Gehe zum Empfehlungsprogramm</li>
                </ol>
            </div>
            
            <!-- Navigation -->
            <div style="margin-top: 2rem; display: grid; gap: 0.5rem;">
                <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn btn-primary" style="text-decoration: none;">
                    Zum Empfehlungsprogramm →
                </a>
                <button onclick="location.reload()" class="btn" style="background: #e5e7eb; color: #374151;">
                    Seite neu laden
                </button>
            </div>
        </div>
    </div>
</body>
</html>
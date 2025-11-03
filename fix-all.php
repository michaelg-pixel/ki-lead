<?php
/**
 * All-in-One Fix für Empfehlungsprogramm
 * URL: https://app.mehr-infos-jetzt.de/fix-all.php
 */

require_once __DIR__ . '/config/database.php';

$result = [];
$error = null;

try {
    $pdo = getDBConnection();
    
    // PROBLEM 1: Freebie ID 16 User zuweisen
    if (isset($_POST['assign_freebie'])) {
        $user_id = 4; // Ihre User-ID
        $freebie_id = 16;
        
        $stmt = $pdo->prepare("UPDATE freebies SET user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $freebie_id]);
        
        $result[] = "✅ Freebie ID 16 wurde User ID 4 zugewiesen";
    }
    
    // PROBLEM 2: is_active Spalte hinzufügen (falls fehlt)
    if (isset($_POST['add_is_active'])) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_active'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $pdo->exec("UPDATE freebies SET is_active = 1");
                $result[] = "✅ Spalte is_active hinzugefügt";
            } else {
                $result[] = "ℹ️ Spalte is_active bereits vorhanden";
            }
        } catch (PDOException $e) {
            $error = "Fehler bei is_active: " . $e->getMessage();
        }
    }
    
    // PROBLEM 3: customer_freebies Tabelle erstellen/reparieren
    if (isset($_POST['fix_customer_freebies'])) {
        try {
            // Tabelle neu erstellen
            $pdo->exec("DROP TABLE IF EXISTS customer_freebies");
            $pdo->exec("
                CREATE TABLE customer_freebies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    freebie_id INT NOT NULL,
                    is_unlocked TINYINT(1) DEFAULT 0,
                    unlocked_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_customer_freebie (customer_id, freebie_id),
                    KEY idx_customer_id (customer_id),
                    KEY idx_freebie_id (freebie_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $result[] = "✅ Tabelle customer_freebies erstellt";
        } catch (PDOException $e) {
            $error = "Fehler bei customer_freebies: " . $e->getMessage();
        }
    }
    
    // ALLES AUF EINMAL FIX
    if (isset($_POST['fix_all'])) {
        $pdo->beginTransaction();
        
        try {
            // 1. Freebie zuweisen
            $stmt = $pdo->prepare("UPDATE freebies SET user_id = 4 WHERE id = 16");
            $stmt->execute();
            $result[] = "✅ Freebie zugewiesen";
            
            // 2. is_active hinzufügen
            $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_active'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $pdo->exec("UPDATE freebies SET is_active = 1");
                $result[] = "✅ is_active hinzugefügt";
            }
            
            // 3. customer_freebies erstellen
            $pdo->exec("DROP TABLE IF EXISTS customer_freebies");
            $pdo->exec("
                CREATE TABLE customer_freebies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    freebie_id INT NOT NULL,
                    is_unlocked TINYINT(1) DEFAULT 0,
                    unlocked_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_customer_freebie (customer_id, freebie_id),
                    KEY idx_customer_id (customer_id),
                    KEY idx_freebie_id (freebie_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $result[] = "✅ customer_freebies Tabelle erstellt";
            
            $pdo->commit();
            $result[] = "✅✅✅ ALLES ERFOLGREICH REPARIERT!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Fehler: " . $e->getMessage();
        }
    }
    
    // Status prüfen
    $stmt = $pdo->query("SELECT id, name, user_id FROM freebies WHERE id = 16");
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_active'");
    $has_is_active = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    $has_customer_freebies = $stmt->rowCount() > 0;
    
    $all_fixed = ($freebie['user_id'] == 4) && $has_is_active && $has_customer_freebies;
    
} catch (PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All-in-One Fix</title>
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
            max-width: 600px;
            width: 100%;
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
            font-weight: 600;
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
        
        .status-box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-ok {
            color: #10b981;
            font-weight: 600;
        }
        
        .status-error {
            color: #ef4444;
            font-weight: 600;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0.5rem 0;
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
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.4);
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
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">🔧 All-in-One Fix</h1>
            <p>Repariert alle Probleme auf einmal</p>
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
            
            <?php if ($all_fixed): ?>
            <div class="alert alert-success" style="font-size: 1.25rem; text-align: center;">
                🎉 ALLES REPARIERT! 🎉
            </div>
            
            <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn btn-success" style="text-decoration: none;">
                ✅ Zum Empfehlungsprogramm
            </a>
            
            <?php else: ?>
            
            <div class="status-box">
                <h2>Aktueller Status</h2>
                
                <div class="status-item">
                    <span>1. Freebie User-Zuordnung:</span>
                    <span class="<?php echo ($freebie['user_id'] == 4) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo ($freebie['user_id'] == 4) ? '✅ OK' : '❌ NULL'; ?>
                    </span>
                </div>
                
                <div class="status-item">
                    <span>2. Spalte is_active:</span>
                    <span class="<?php echo $has_is_active ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $has_is_active ? '✅ Vorhanden' : '❌ Fehlt'; ?>
                    </span>
                </div>
                
                <div class="status-item">
                    <span>3. Tabelle customer_freebies:</span>
                    <span class="<?php echo $has_customer_freebies ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $has_customer_freebies ? '✅ Existiert' : '❌ Fehlt'; ?>
                    </span>
                </div>
            </div>
            
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 0.75rem; padding: 1.5rem; margin: 1.5rem 0;">
                <h3 style="color: #856404; margin-bottom: 0.5rem;">⚡ Schnelle Lösung</h3>
                <p style="color: #856404; margin-bottom: 1rem;">
                    Klicke auf den Button unten, um ALLE Probleme auf einmal zu beheben:
                </p>
                
                <ul style="color: #856404; margin: 1rem 0 1rem 1.5rem;">
                    <li>Freebie ID 16 → User ID 4 zuweisen</li>
                    <li>Spalte is_active hinzufügen</li>
                    <li>Tabelle customer_freebies erstellen</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="fix_all" class="btn btn-primary" onclick="return confirm('Alles reparieren?')">
                    🚀 ALLES JETZT REPARIEREN
                </button>
            </form>
            
            <?php endif; ?>
            
            <div style="margin-top: 2rem; text-align: center; color: #6b7280; font-size: 0.875rem;">
                <p>Nach der Reparatur kannst du diese Datei löschen</p>
            </div>
        </div>
    </div>
</body>
</html>
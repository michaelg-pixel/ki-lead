<?php
/**
 * Freebies Tabellen Reparatur-Tool
 * URL: https://app.mehr-infos-jetzt.de/repair-freebies-table.php
 */

require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? 'check';
$result = null;
$error = null;

try {
    $pdo = getDBConnection();
    
    // Aktuelle Struktur prüfen
    $freebies_structure = [];
    $customer_freebies_structure = [];
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM freebies");
        $freebies_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Tabelle 'freebies' existiert nicht: " . $e->getMessage();
    }
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
        $customer_freebies_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Tabelle existiert nicht - ist OK
    }
    
    // Fehlende Spalten identifizieren
    $freebies_columns = array_column($freebies_structure, 'Field');
    $missing_in_freebies = [];
    
    $required_freebies_columns = [
        'id', 'title', 'description', 'image_path', 'customer_id', 'is_active', 'created_at', 'updated_at'
    ];
    
    foreach ($required_freebies_columns as $col) {
        if (!in_array($col, $freebies_columns)) {
            $missing_in_freebies[] = $col;
        }
    }
    
    // Reparatur ausführen
    if ($action === 'repair' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();
            
            // Freebies Tabelle reparieren
            if (in_array('customer_id', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN customer_id INT NULL AFTER id");
                $result[] = "✅ Spalte 'customer_id' hinzugefügt";
            }
            
            if (in_array('is_active', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $result[] = "✅ Spalte 'is_active' hinzugefügt";
            }
            
            if (in_array('description', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN description TEXT NULL AFTER title");
                $result[] = "✅ Spalte 'description' hinzugefügt";
            }
            
            if (in_array('image_path', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN image_path VARCHAR(255) NULL");
                $result[] = "✅ Spalte 'image_path' hinzugefügt";
            }
            
            if (in_array('created_at', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                $result[] = "✅ Spalte 'created_at' hinzugefügt";
            }
            
            if (in_array('updated_at', $missing_in_freebies)) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                $result[] = "✅ Spalte 'updated_at' hinzugefügt";
            }
            
            // Foreign Key zu users hinzufügen
            if (in_array('customer_id', $missing_in_freebies)) {
                try {
                    $pdo->exec("
                        ALTER TABLE freebies 
                        ADD CONSTRAINT fk_freebies_customer
                        FOREIGN KEY (customer_id) 
                        REFERENCES users(id) 
                        ON DELETE CASCADE
                    ");
                    $result[] = "✅ Foreign Key zu 'users' hinzugefügt";
                } catch (PDOException $e) {
                    // Foreign Key Fehler ignorieren
                    $result[] = "⚠️ Foreign Key übersprungen (evtl. bereits vorhanden)";
                }
            }
            
            // customer_freebies Tabelle erstellen falls nicht vorhanden
            if (empty($customer_freebies_structure)) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS customer_freebies (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT NOT NULL,
                        freebie_id INT NOT NULL,
                        is_unlocked TINYINT(1) DEFAULT 0,
                        unlocked_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_customer_freebie (customer_id, freebie_id),
                        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (freebie_id) REFERENCES freebies(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $result[] = "✅ Tabelle 'customer_freebies' erstellt";
            }
            
            // Bestehende Freebies aktualisieren (customer_id auf NULL setzen falls leer)
            $pdo->exec("UPDATE freebies SET is_active = 1 WHERE is_active IS NULL");
            $result[] = "✅ Bestehende Freebies auf aktiv gesetzt";
            
            $pdo->commit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Reparatur Fehler: " . $e->getMessage();
        }
        
        // Struktur neu laden
        $stmt = $pdo->query("SHOW COLUMNS FROM freebies");
        $freebies_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
            $customer_freebies_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        $freebies_columns = array_column($freebies_structure, 'Field');
        $missing_in_freebies = [];
        foreach ($required_freebies_columns as $col) {
            if (!in_array($col, $freebies_columns)) {
                $missing_in_freebies[] = $col;
            }
        }
    }
    
    // Update customer_id für bestehendes Freebie
    if ($action === 'assign' && isset($_POST['freebie_id']) && isset($_POST['user_id'])) {
        $freebie_id = (int)$_POST['freebie_id'];
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE freebies SET customer_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $freebie_id]);
        
        $result = ["✅ Freebie ID $freebie_id wurde User ID $user_id zugewiesen"];
    }
    
} catch (PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebies Tabellen Reparatur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1000px;
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
        
        .status-box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.875rem;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #374151;
            color: white;
        }
        
        .missing {
            background: #fee;
            color: #c00;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            justify-content: center;
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
        
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        h2 {
            color: #111827;
            margin: 1.5rem 0 1rem;
        }
        
        code {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: monospace;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-wrench"></i> Freebies Tabellen Reparatur</h1>
            <p>Automatische Reparatur der Datenbank-Struktur</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Fehler</strong><br>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <?php foreach ($result as $msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Status -->
            <div class="status-box">
                <h2><i class="fas fa-table"></i> Tabelle: freebies</h2>
                
                <?php if (!empty($missing_in_freebies)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Fehlende Spalten:</strong> <?php echo implode(', ', $missing_in_freebies); ?>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Alle erforderlichen Spalten vorhanden!</strong>
                </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Spalte</th>
                            <th>Typ</th>
                            <th>Null</th>
                            <th>Default</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($freebies_structure as $col): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($col['Field']); ?></strong></td>
                            <td><?php echo htmlspecialchars($col['Type']); ?></td>
                            <td><?php echo htmlspecialchars($col['Null']); ?></td>
                            <td><?php echo htmlspecialchars($col['Default'] ?? '-'); ?></td>
                            <td>
                                <?php if (in_array($col['Field'], $required_freebies_columns)): ?>
                                    <span style="color: #10b981;">✅ OK</span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">➖ Extra</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php foreach ($missing_in_freebies as $missing): ?>
                        <tr class="missing">
                            <td><strong><?php echo htmlspecialchars($missing); ?></strong></td>
                            <td colspan="3">❌ FEHLT</td>
                            <td>❌ Muss hinzugefügt werden</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- customer_freebies Status -->
            <div class="status-box">
                <h2><i class="fas fa-table"></i> Tabelle: customer_freebies</h2>
                
                <?php if (empty($customer_freebies_structure)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Tabelle existiert nicht!</strong>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Tabelle existiert mit <?php echo count($customer_freebies_structure); ?> Spalten</strong>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Reparatur Button -->
            <?php if (!empty($missing_in_freebies) || empty($customer_freebies_structure)): ?>
            <form method="POST" action="?action=repair">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Reparatur jetzt ausführen?')">
                    <i class="fas fa-wrench"></i>
                    Automatische Reparatur starten
                </button>
            </form>
            <?php endif; ?>
            
            <!-- Freebies zuweisen -->
            <div class="status-box">
                <h2><i class="fas fa-user-tag"></i> Bestehendes Freebie einem User zuweisen</h2>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    Falls ein Freebie existiert aber keinen Besitzer hat (customer_id ist NULL)
                </p>
                
                <form method="POST" action="?action=assign">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Freebie ID:</label>
                    <input type="number" name="freebie_id" placeholder="z.B. 16" required>
                    
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">User ID (Besitzer):</label>
                    <input type="number" name="user_id" placeholder="z.B. 1" required>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link"></i>
                        Freebie zuweisen
                    </button>
                </form>
            </div>
            
            <!-- Buttons -->
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync"></i>
                Status neu laden
            </button>
            
            <!-- Footer -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 0.875rem;">
                <p><i class="fas fa-info-circle"></i> Nach erfolgreicher Reparatur können Sie diese Datei löschen</p>
            </div>
        </div>
    </div>
</body>
</html>
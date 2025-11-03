<?php
/**
 * Intelligenter Fix - Analysiert zuerst die Struktur
 * URL: https://app.mehr-infos-jetzt.de/smart-fix.php
 */

require_once __DIR__ . '/config/database.php';

$result = [];
$error = null;

try {
    $pdo = getDBConnection();
    
    // Aktuelle Struktur analysieren
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($existing_columns, 'Field');
    
    // Benötigte Spalten
    $required_columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'customer_id' => 'INT NOT NULL',
        'freebie_id' => 'INT NOT NULL',
        'is_unlocked' => 'TINYINT(1) DEFAULT 0',
        'unlocked_at' => 'TIMESTAMP NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    // Fehlende Spalten identifizieren
    $missing_columns = [];
    foreach ($required_columns as $col_name => $col_def) {
        if (!in_array($col_name, $column_names)) {
            $missing_columns[$col_name] = $col_def;
        }
    }
    
    // ALLES REPARIEREN
    if (isset($_POST['fix_all'])) {
        $pdo->beginTransaction();
        
        try {
            // 1. Freebie zuweisen
            $stmt = $pdo->prepare("UPDATE freebies SET user_id = 4 WHERE id = 16");
            $stmt->execute();
            $result[] = "✅ Freebie ID 16 → User ID 4 zugewiesen";
            
            // 2. is_active zu freebies hinzufügen
            $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_active'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE freebies ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $pdo->exec("UPDATE freebies SET is_active = 1");
                $result[] = "✅ Spalte is_active zu freebies hinzugefügt";
            }
            
            // 3. Fehlende Spalten zu customer_freebies hinzufügen
            foreach ($missing_columns as $col_name => $col_def) {
                if ($col_name == 'id') continue; // ID nicht nachträglich hinzufügen
                
                // Spalte hinzufügen
                $sql = "ALTER TABLE customer_freebies ADD COLUMN $col_name $col_def";
                $pdo->exec($sql);
                $result[] = "✅ Spalte $col_name zu customer_freebies hinzugefügt";
            }
            
            // 4. Index erstellen falls noch nicht vorhanden
            try {
                $pdo->exec("ALTER TABLE customer_freebies ADD UNIQUE KEY unique_customer_freebie (customer_id, freebie_id)");
                $result[] = "✅ Unique Index erstellt";
            } catch (PDOException $e) {
                if ($e->getCode() != '42000') { // Ignoriere "Duplicate key name"
                    throw $e;
                }
            }
            
            $pdo->commit();
            $result[] = "<strong>🎉 ALLES ERFOLGREICH REPARIERT!</strong>";
            
            // Struktur neu laden
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
            $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $column_names = array_column($existing_columns, 'Field');
            
            // Neu prüfen was fehlt
            $missing_columns = [];
            foreach ($required_columns as $col_name => $col_def) {
                if (!in_array($col_name, $column_names)) {
                    $missing_columns[$col_name] = $col_def;
                }
            }
            
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
    
    $all_fixed = ($freebie['user_id'] == 4) && $has_is_active && empty($missing_columns);
    
} catch (PDOException $e) {
    $error = "Datenbankfehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Fix</title>
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
            background: #f9fafb;
            font-weight: 600;
        }
        
        .missing {
            background: #fee;
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
            text-decoration: none;
            text-align: center;
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
        
        h2 {
            color: #111827;
            margin: 1.5rem 0 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">🧠 Smart Fix</h1>
            <p>Analysiert die Struktur und repariert intelligent</p>
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
            <div class="alert alert-success" style="font-size: 1.25rem; text-align: center; padding: 1.5rem;">
                🎉 ALLES REPARIERT! 🎉
            </div>
            
            <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn btn-success">
                ✅ Zum Empfehlungsprogramm
            </a>
            
            <div style="margin-top: 1rem; text-align: center; color: #6b7280; font-size: 0.875rem;">
                <p>💡 <strong>Tipp:</strong> Drücke Strg + Shift + R für einen Hard-Refresh!</p>
            </div>
            
            <?php else: ?>
            
            <!-- Aktuelle Struktur -->
            <h2>📋 Aktuelle Struktur: customer_freebies</h2>
            <table>
                <thead>
                    <tr>
                        <th>Spalte</th>
                        <th>Typ</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existing_columns as $col): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($col['Field']); ?></strong></td>
                        <td><?php echo htmlspecialchars($col['Type']); ?></td>
                        <td style="color: #10b981;">✅ Vorhanden</td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php foreach ($missing_columns as $col_name => $col_def): ?>
                    <tr class="missing">
                        <td><strong><?php echo htmlspecialchars($col_name); ?></strong></td>
                        <td><?php echo htmlspecialchars($col_def); ?></td>
                        <td style="color: #ef4444;">❌ FEHLT</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Was wird gemacht -->
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 0.75rem; padding: 1.5rem; margin: 1.5rem 0;">
                <h3 style="color: #856404; margin-bottom: 0.5rem;">⚡ Was wird repariert:</h3>
                <ul style="color: #856404; margin: 1rem 0 1rem 1.5rem;">
                    <li>Freebie ID 16 → User ID 4 zuweisen</li>
                    <li>Spalte is_active zu freebies hinzufügen</li>
                    <?php foreach ($missing_columns as $col_name => $col_def): ?>
                    <li>Spalte <code><?php echo $col_name; ?></code> zu customer_freebies hinzufügen</li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (empty($missing_columns)): ?>
                <p style="color: #10b981; font-weight: bold;">✅ Alle Spalten sind bereits vorhanden!</p>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <button type="submit" name="fix_all" class="btn btn-primary">
                    🚀 JETZT REPARIEREN
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
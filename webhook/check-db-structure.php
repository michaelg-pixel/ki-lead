<?php
/**
 * Datenbankstruktur-Check
 * Zeigt alle Spalten und Pflichtfelder der customer_freebies Tabelle
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Nur für Admins');
}

try {
    $pdo = getDBConnection();
    
    // Struktur auslesen
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ENUM-Werte für freebie_type auslesen
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field = 'freebie_type'");
    $typeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('❌ Fehler: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Datenbank-Struktur Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 40px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 32px;
            margin-bottom: 30px;
        }
        .section {
            background: #16213e;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #0f3460;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #0f3460;
            padding: 12px;
            text-align: left;
            color: #667eea;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #0f3460;
        }
        tr:hover {
            background: #0f3460;
        }
        .required {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .nullable {
            color: #888;
        }
        .type {
            color: #22c55e;
        }
        .default {
            color: #fbbf24;
        }
        .enum-box {
            background: #0f3460;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .enum-values {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .enum-value {
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Datenbank-Struktur: customer_freebies</h1>
        
        <div class="section">
            <h2>📋 Alle Spalten</h2>
            <table>
                <thead>
                    <tr>
                        <th>Spalte</th>
                        <th>Typ</th>
                        <th>NULL erlaubt?</th>
                        <th>Default-Wert</th>
                        <th>Pflichtfeld?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $col): ?>
                        <?php 
                        $isRequired = ($col['Null'] === 'NO' && 
                                      $col['Default'] === null && 
                                      $col['Extra'] !== 'auto_increment');
                        ?>
                        <tr>
                            <td><strong><?php echo $col['Field']; ?></strong></td>
                            <td class="type"><?php echo $col['Type']; ?></td>
                            <td class="<?php echo $col['Null'] === 'YES' ? 'nullable' : ''; ?>">
                                <?php echo $col['Null']; ?>
                            </td>
                            <td class="default">
                                <?php echo $col['Default'] ?? 'NULL'; ?>
                            </td>
                            <td>
                                <?php if ($isRequired): ?>
                                    <span class="required">JA - PFLICHTFELD!</span>
                                <?php else: ?>
                                    <span class="nullable">Nein</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>🎯 ENUM: Erlaubte Werte für freebie_type</h2>
            <div class="enum-box">
                <strong>Typ-Definition:</strong> <?php echo $typeInfo['Type']; ?>
                
                <?php
                // ENUM-Werte extrahieren
                preg_match("/^enum\(\'(.*)\'\)$/", $typeInfo['Type'], $matches);
                $enumValues = explode("','", $matches[1] ?? '');
                ?>
                
                <div class="enum-values">
                    <?php foreach ($enumValues as $value): ?>
                        <span class="enum-value"><?php echo $value; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>⚠️ PFLICHTFELDER (müssen beim INSERT angegeben werden)</h2>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($columns as $col): ?>
                    <?php 
                    $isRequired = ($col['Null'] === 'NO' && 
                                  $col['Default'] === null && 
                                  $col['Extra'] !== 'auto_increment');
                    if ($isRequired):
                    ?>
                        <li style="padding: 8px 0; color: #ef4444;">
                            <strong>• <?php echo $col['Field']; ?></strong> 
                            (<?php echo $col['Type']; ?>)
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
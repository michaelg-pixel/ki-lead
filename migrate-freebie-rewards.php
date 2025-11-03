<?php
/**
 * Datenbank Migration Tool - EINFACH
 * URL: https://app.mehr-infos-jetzt.de/migrate-freebie-rewards.php
 * 
 * WICHTIG: Nach Verwendung LÖSCHEN!
 */

require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? 'home';
$result = null;
$error = null;

try {
    $pdo = getDBConnection();
    
    // Migration Status prüfen
    $migrationStatus = null;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'freebie_id'");
        $migrationStatus = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $error = 'Fehler beim Prüfen: ' . $e->getMessage();
    }
    
    // Migration ausführen
    if ($action === 'migrate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if ($migrationStatus) {
                $result = ['type' => 'warning', 'message' => 'Migration bereits ausgeführt! Spalte freebie_id existiert bereits.'];
            } else {
                $pdo->beginTransaction();
                
                // Schritt 1: Spalte hinzufügen
                $pdo->exec("ALTER TABLE reward_definitions ADD COLUMN freebie_id INT NULL COMMENT 'Verknüpfung zum Freebie (optional)'");
                
                // Schritt 2: Foreign Key hinzufügen
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
                    // Foreign Key Fehler ignorieren (Tabelle existiert evtl. nicht)
                }
                
                // Schritt 3: Indices erstellen
                $pdo->exec("CREATE INDEX idx_reward_definitions_freebie ON reward_definitions(freebie_id)");
                $pdo->exec("CREATE INDEX idx_reward_definitions_user_freebie ON reward_definitions(user_id, freebie_id)");
                
                $pdo->commit();
                
                $result = ['type' => 'success', 'message' => 'Migration erfolgreich! Spalte freebie_id wurde hinzugefügt.'];
                $migrationStatus = true;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Migration Fehler: ' . $e->getMessage();
        }
    }
    
    // Rollback - nur für Notfall
    if ($action === 'rollback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();
            
            // Indices löschen
            try {
                $pdo->exec("DROP INDEX idx_reward_definitions_freebie ON reward_definitions");
                $pdo->exec("DROP INDEX idx_reward_definitions_user_freebie ON reward_definitions");
            } catch (PDOException $e) {}
            
            // Foreign Key löschen
            try {
                $pdo->exec("ALTER TABLE reward_definitions DROP FOREIGN KEY fk_reward_definitions_freebie");
            } catch (PDOException $e) {}
            
            // Spalte löschen
            $pdo->exec("ALTER TABLE reward_definitions DROP COLUMN freebie_id");
            
            $pdo->commit();
            
            $result = ['type' => 'success', 'message' => 'Rollback erfolgreich! Spalte freebie_id wurde entfernt.'];
            $migrationStatus = false;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Rollback Fehler: ' . $e->getMessage();
        }
    }
    
    // Infos sammeln
    $tableInfo = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions");
        $tableInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    
    $rewardCount = 0;
    $rewardsWithFreebie = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM reward_definitions");
        $rewardCount = $stmt->fetch()['count'];
        
        if ($migrationStatus) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reward_definitions WHERE freebie_id IS NOT NULL");
            $rewardsWithFreebie = $stmt->fetch()['count'];
        }
    } catch (PDOException $e) {}
    
} catch (PDOException $e) {
    $error = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank Migration</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
        }
        
        .card {
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
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
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
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid #3b82f6;
            color: #1e40af;
        }
        
        .status-box {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
        
        .status-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .status-value {
            font-weight: 600;
            color: #111827;
        }
        
        .status-value.success {
            color: #10b981;
        }
        
        .status-value.error {
            color: #ef4444;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0.5rem;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .info-box ul {
            margin-left: 1.5rem;
            color: #1e3a8a;
        }
        
        .table-preview {
            max-height: 300px;
            overflow-y: auto;
            background: #f9fafb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        th, td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #374151;
            color: white;
            position: sticky;
            top: 0;
        }
        
        .buttons {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .danger-zone {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
        }
        
        .danger-zone h3 {
            color: #dc2626;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-database"></i> Datenbank Migration</h1>
                <p>Freebie-Integration für Reward Tiers</p>
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
                    <?php if ($result['type'] === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Erfolgreich!</strong><br>
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    </div>
                    <?php elseif ($result['type'] === 'warning'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Hinweis</strong><br>
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Status -->
                <div class="status-box">
                    <h2 style="margin-bottom: 1rem; color: #111827;">
                        <i class="fas fa-info-circle"></i> Migrations-Status
                    </h2>
                    
                    <div class="status-item">
                        <span class="status-label">Migration:</span>
                        <span class="status-value <?php echo $migrationStatus ? 'success' : 'error'; ?>">
                            <?php if ($migrationStatus): ?>
                                <i class="fas fa-check-circle"></i> Bereits durchgeführt
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Nicht durchgeführt
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label">Tabelle:</span>
                        <span class="status-value">reward_definitions</span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label">Gesamt Rewards:</span>
                        <span class="status-value"><?php echo number_format($rewardCount); ?></span>
                    </div>
                    
                    <?php if ($migrationStatus): ?>
                    <div class="status-item">
                        <span class="status-label">Mit Freebie verknüpft:</span>
                        <span class="status-value success"><?php echo number_format($rewardsWithFreebie); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info -->
                <div class="info-box">
                    <h3><i class="fas fa-lightbulb"></i> Was macht diese Migration?</h3>
                    <ul>
                        <li>Fügt Spalte <code>freebie_id</code> hinzu</li>
                        <li>Erstellt Foreign Key zu <code>freebies</code> Tabelle</li>
                        <li>Erstellt Performance-Indices</li>
                        <li>Ermöglicht Verknüpfung von Rewards mit Freebies</li>
                    </ul>
                </div>
                
                <!-- Tabellen-Vorschau -->
                <?php if (!empty($tableInfo)): ?>
                <details style="margin: 1.5rem 0;">
                    <summary style="cursor: pointer; font-weight: 600; color: #374151; padding: 0.5rem;">
                        <i class="fas fa-table"></i> Tabellen-Struktur anzeigen
                    </summary>
                    <div class="table-preview">
                        <table>
                            <thead>
                                <tr>
                                    <th>Spalte</th>
                                    <th>Typ</th>
                                    <th>Null</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableInfo as $column): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($column['Field']); ?></strong>
                                        <?php if ($column['Field'] === 'freebie_id'): ?>
                                            <span style="background: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-left: 0.5rem;">NEU</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Default'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                <?php endif; ?>
                
                <!-- Buttons -->
                <div class="buttons">
                    <?php if (!$migrationStatus): ?>
                    <form method="POST" action="?action=migrate" onsubmit="return confirm('Migration jetzt ausführen?')">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i>
                            Migration starten
                        </button>
                    </form>
                    <?php else: ?>
                    <button class="btn btn-primary" disabled>
                        <i class="fas fa-check"></i>
                        Migration bereits durchgeführt
                    </button>
                    <?php endif; ?>
                    
                    <button onclick="location.reload()" class="btn btn-secondary">
                        <i class="fas fa-sync"></i>
                        Status aktualisieren
                    </button>
                </div>
                
                <!-- Danger Zone -->
                <?php if ($migrationStatus): ?>
                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                    <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                        Nur verwenden, wenn Sie die Migration rückgängig machen möchten!
                    </p>
                    <form method="POST" action="?action=rollback" onsubmit="return confirm('⚠️ ACHTUNG: Rollback wirklich ausführen?\n\nDies löscht die freebie_id Spalte und alle Verknüpfungen!')">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-undo"></i>
                            Rollback ausführen
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Footer -->
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 0.875rem;">
                    <p><i class="fas fa-shield-alt"></i> Nach erfolgreicher Migration können Sie diese Datei löschen</p>
                    <p style="margin-top: 0.5rem;">Datei: <code>migrate-freebie-rewards.php</code></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
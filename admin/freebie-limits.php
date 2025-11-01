<?php
session_start();

// Admin-Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// Speichern von Änderungen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'save') {
            $id = $_POST['id'] ?? null;
            $product_id = $_POST['product_id'] ?? '';
            $product_name = $_POST['product_name'] ?? '';
            $freebie_limit = intval($_POST['freebie_limit'] ?? 5);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($product_id) || empty($product_name)) {
                throw new Exception('Produkt-ID und Name sind erforderlich');
            }
            
            if ($id) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE product_freebie_config 
                    SET product_id = ?, product_name = ?, freebie_limit = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$product_id, $product_name, $freebie_limit, $is_active, $id]);
                $success = "Konfiguration aktualisiert";
            } else {
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO product_freebie_config (product_id, product_name, freebie_limit, is_active)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$product_id, $product_name, $freebie_limit, $is_active]);
                $success = "Konfiguration erstellt";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM product_freebie_config WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Konfiguration gelöscht";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Alle Konfigurationen laden
$stmt = $pdo->query("
    SELECT * FROM product_freebie_config 
    ORDER BY is_active DESC, freebie_limit DESC
");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiken
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT cfl.customer_id) as total_customers,
        SUM(cfl.freebie_limit) as total_limits,
        COUNT(cf.id) as total_custom_freebies
    FROM customer_freebie_limits cfl
    LEFT JOIN customer_freebies cf ON cfl.customer_id = cf.customer_id AND cf.freebie_type = 'custom'
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie-Limit Verwaltung - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            padding: 32px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            color: white;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #888;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .config-panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .panel-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #aaa;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: white;
            font-size: 15px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 0;
        }
        
        .form-checkbox input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .configs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        
        .configs-table th {
            background: rgba(102, 126, 234, 0.2);
            padding: 16px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .configs-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .configs-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .badge-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/dashboard.php" class="back-btn">← Zurück zum Admin-Dashboard</a>
        
        <div class="header">
            <h1>🎁 Freebie-Limit Verwaltung</h1>
            <p>Verwalte Produkt-Konfigurationen und Freebie-Limits für deine Kunden</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistiken -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_customers'] ?? 0); ?></div>
                <div class="stat-label">Kunden mit Limits</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_limits'] ?? 0); ?></div>
                <div class="stat-label">Gesamt-Limit (alle Kunden)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_custom_freebies'] ?? 0); ?></div>
                <div class="stat-label">Erstellte Custom Freebies</div>
            </div>
        </div>
        
        <!-- Neue Konfiguration erstellen -->
        <div class="config-panel">
            <h2 class="panel-title">➕ Neue Produkt-Konfiguration</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="save">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Produkt-ID (Digistore24)</label>
                        <input type="text" name="product_id" class="form-input" 
                               placeholder="z.B. STARTER_001" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Produkt-Name</label>
                        <input type="text" name="product_name" class="form-input" 
                               placeholder="z.B. Starter Paket" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Freebie-Limit</label>
                        <input type="number" name="freebie_limit" class="form-input" 
                               value="5" min="0" required>
                    </div>
                </div>
                
                <div class="form-checkbox">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Aktiv</label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    💾 Konfiguration erstellen
                </button>
            </form>
        </div>
        
        <!-- Bestehende Konfigurationen -->
        <div class="config-panel">
            <h2 class="panel-title">📋 Bestehende Konfigurationen</h2>
            
            <?php if (empty($configs)): ?>
                <p style="color: #888; text-align: center; padding: 40px;">
                    Noch keine Konfigurationen vorhanden.
                </p>
            <?php else: ?>
                <table class="configs-table">
                    <thead>
                        <tr>
                            <th>Produkt-ID</th>
                            <th>Produkt-Name</th>
                            <th>Limit</th>
                            <th>Status</th>
                            <th>Erstellt</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configs as $config): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($config['product_id']); ?></code></td>
                                <td><?php echo htmlspecialchars($config['product_name']); ?></td>
                                <td><strong><?php echo $config['freebie_limit']; ?></strong> Freebies</td>
                                <td>
                                    <span class="badge <?php echo $config['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $config['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($config['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-secondary btn-small" 
                                                onclick="editConfig(<?php echo htmlspecialchars(json_encode($config)); ?>)">
                                            ✏️ Bearbeiten
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Wirklich löschen?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $config['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">
                                                🗑️ Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function editConfig(config) {
            // Fill form with config data
            document.querySelector('input[name="product_id"]').value = config.product_id;
            document.querySelector('input[name="product_name"]').value = config.product_name;
            document.querySelector('input[name="freebie_limit"]').value = config.freebie_limit;
            document.querySelector('#is_active').checked = config.is_active == 1;
            
            // Add hidden ID field
            const form = document.querySelector('form');
            let idInput = form.querySelector('input[name="id"]');
            if (!idInput) {
                idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                form.appendChild(idInput);
            }
            idInput.value = config.id;
            
            // Change button text
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '💾 Konfiguration aktualisieren';
            
            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</body>
</html>
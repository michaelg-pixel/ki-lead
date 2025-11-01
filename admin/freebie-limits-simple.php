<?php
session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// Limit Update verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    try {
        $userId = intval($_POST['user_id']);
        $newLimit = intval($_POST['freebie_limit']);
        
        // User prüfen
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'customer'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User nicht gefunden');
        }
        
        // Limit setzen
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name)
            VALUES (?, ?, 'ADMIN_SET', 'Admin gesetzt')
            ON DUPLICATE KEY UPDATE 
                freebie_limit = ?,
                product_id = 'ADMIN_SET',
                product_name = 'Admin gesetzt',
                updated_at = NOW()
        ");
        $stmt->execute([$userId, $newLimit, $newLimit]);
        
        $success = "✅ Freebie-Limit erfolgreich auf {$newLimit} gesetzt für " . htmlspecialchars($user['email']);
        
    } catch (Exception $e) {
        $error = "❌ Fehler: " . $e->getMessage();
    }
}

// User laden
$users = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        u.is_active,
        u.created_at,
        COALESCE(cfl.freebie_limit, 0) as freebie_limit,
        cfl.product_name as limit_source,
        (SELECT COUNT(*) FROM customer_freebies cf WHERE cf.customer_id = u.id AND cf.freebie_type = 'custom') as used_count
    FROM users u
    LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
    WHERE u.role = 'customer'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie-Limits verwalten - Simple</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 24px;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 600;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid #22c55e;
            color: #15803d;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            color: #b91c1c;
        }
        .user-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        .user-info h3 {
            color: #1a1a2e;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .user-info p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .user-stats {
            display: flex;
            gap: 16px;
            margin-top: 12px;
        }
        .stat {
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .stat-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }
        .limit-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .limit-input {
            width: 100px;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
        }
        .limit-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .preset-btns {
            display: flex;
            gap: 8px;
        }
        .preset-btn {
            padding: 8px 12px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .preset-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .btn-save {
            padding: 10px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 24px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/dashboard.php" class="back-link">← Zurück zum Dashboard</a>
        
        <h1>🎁 Freebie-Limits verwalten</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p><strong>E-Mail:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Quelle:</strong> <?php echo $user['limit_source'] ?: 'Nicht gesetzt'; ?></p>
                    
                    <div class="user-stats">
                        <div class="stat">
                            <div class="stat-label">Aktuelles Limit</div>
                            <div class="stat-value"><?php echo $user['freebie_limit']; ?></div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">Genutzt</div>
                            <div class="stat-value"><?php echo $user['used_count']; ?></div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">Verfügbar</div>
                            <div class="stat-value"><?php echo max(0, $user['freebie_limit'] - $user['used_count']); ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="limit-form">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    
                    <div class="preset-btns">
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 5)">5</button>
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 10)">10</button>
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 25)">25</button>
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 50)">50</button>
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 100)">100</button>
                        <button type="button" class="preset-btn" onclick="setUserLimit(this, 999)">∞</button>
                    </div>
                    
                    <input type="number" 
                           name="freebie_limit" 
                           class="limit-input" 
                           value="<?php echo $user['freebie_limit']; ?>"
                           min="0" 
                           max="999"
                           required>
                    
                    <button type="submit" class="btn-save">💾 Speichern</button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($users)): ?>
            <p style="text-align: center; color: #64748b; padding: 40px;">
                Keine Kunden gefunden.
            </p>
        <?php endif; ?>
    </div>
    
    <script>
        function setUserLimit(button, value) {
            const form = button.closest('form');
            const input = form.querySelector('input[name="freebie_limit"]');
            input.value = value;
        }
    </script>
</body>
</html>

<?php
session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// Freebie-Limit Update verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_limit') {
            $userId = intval($_POST['user_id']);
            $newLimit = intval($_POST['freebie_limit']);
            
            // Prüfen ob User existiert
            $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ? AND role = 'customer'");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User nicht gefunden');
            }
            
            // Limit setzen oder aktualisieren
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
            
            $success = "Freebie-Limit erfolgreich auf {$newLimit} gesetzt für " . htmlspecialchars($user['email']);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    
    // Bei AJAX-Request JSON zurückgeben
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => isset($success),
            'message' => $success ?? $error ?? 'Unbekannter Fehler'
        ]);
        exit;
    }
}

// Benutzer mit Freebie-Limits laden
$users = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        u.is_active,
        u.created_at,
        u.digistore_product_id,
        u.digistore_product_name,
        cfl.freebie_limit,
        cfl.product_name as limit_source,
        (SELECT COUNT(*) FROM customer_freebies cf WHERE cf.customer_id = u.id AND cf.freebie_type = 'custom') as custom_freebies_count
    FROM users u
    LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User verwalten - KI Leadsystem Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }
        
        .nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 24px;
        }
        
        h1 {
            font-size: 32px;
            color: #1a1a2e;
            margin-bottom: 24px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #15803d;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #b91c1c;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .filters input {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
        }
        
        .table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-admin {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
        }
        
        .badge-customer {
            background: rgba(34, 197, 94, 0.2);
            color: #16a34a;
        }
        
        .limit-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .limit-value {
            font-weight: 700;
            color: #667eea;
            font-size: 16px;
        }
        
        .limit-usage {
            font-size: 12px;
            color: #64748b;
        }
        
        .limit-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .limit-low {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .limit-medium {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }
        
        .limit-high {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .limit-unlimited {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .btn-edit {
            padding: 6px 12px;
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            color: #667eea;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-edit:hover {
            background: rgba(102, 126, 234, 0.2);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            margin-bottom: 24px;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .modal-header p {
            color: #64748b;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .preset-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .preset-btn {
            padding: 10px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .preset-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            color: #667eea;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .btn {
            flex: 1;
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
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .current-info {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .current-info .label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .current-info .value {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        @media (max-width: 768px) {
            .preset-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="nav">
        <span>🌟 KI Leadsystem Admin</span>
        <a href="/admin/dashboard.php">← Dashboard</a>
    </div>
    
    <div class="container">
        <h1>👥 User verwalten</h1>
        
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
        
        <div class="filters">
            <input type="text" id="searchInput" placeholder="🔍 Suche nach Name oder E-Mail..." onkeyup="filterTable()">
        </div>
        
        <div class="table-wrapper">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Freebie-Limit</th>
                        <th>Genutzt</th>
                        <th>Quelle</th>
                        <th>Aktiv</th>
                        <th>Registriert</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $limit = $user['freebie_limit'] ?? 0;
                        $used = $user['custom_freebies_count'] ?? 0;
                        $remaining = max(0, $limit - $used);
                        
                        // Badge-Klasse basierend auf Limit
                        $limitBadgeClass = 'limit-low';
                        if ($limit >= 999) {
                            $limitBadgeClass = 'limit-unlimited';
                        } elseif ($limit >= 25) {
                            $limitBadgeClass = 'limit-high';
                        } elseif ($limit >= 10) {
                            $limitBadgeClass = 'limit-medium';
                        }
                    ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo strtoupper($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'customer'): ?>
                                    <div class="limit-info">
                                        <span class="limit-value"><?php echo $limit; ?></span>
                                        <span class="limit-badge <?php echo $limitBadgeClass; ?>">
                                            <?php echo $limit >= 999 ? '∞' : $limit; ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'customer'): ?>
                                    <span class="limit-usage">
                                        <?php echo $used; ?> / <?php echo $limit; ?>
                                        <?php if ($remaining > 0): ?>
                                            <span style="color: #16a34a;">(<?php echo $remaining; ?> frei)</span>
                                        <?php else: ?>
                                            <span style="color: #dc2626;">(Limit erreicht)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['limit_source']): ?>
                                    <span style="font-size: 11px; color: #64748b;">
                                        <?php echo htmlspecialchars($user['limit_source']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $user['is_active'] ? '<span style="color: #16a34a;">✓</span>' : '<span style="color: #dc2626;">✗</span>'; ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['role'] === 'customer'): ?>
                                    <button class="btn-edit" 
                                            onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>', <?php echo $limit; ?>, <?php echo $used; ?>)">
                                        ✏️ Limit ändern
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Freebie-Limit ändern</h2>
                <p id="modalUserInfo"></p>
            </div>
            
            <div class="current-info">
                <div class="label">Aktuelles Limit</div>
                <div class="value" id="currentLimitDisplay">0</div>
            </div>
            
            <form id="editForm">
                <input type="hidden" id="editUserId" name="user_id">
                <input type="hidden" name="action" value="update_limit">
                <input type="hidden" name="ajax" value="1">
                
                <div class="form-group">
                    <label class="form-label">Preset wählen:</label>
                    <div class="preset-buttons">
                        <button type="button" class="preset-btn" onclick="setLimit(5)">5</button>
                        <button type="button" class="preset-btn" onclick="setLimit(10)">10</button>
                        <button type="button" class="preset-btn" onclick="setLimit(25)">25</button>
                        <button type="button" class="preset-btn" onclick="setLimit(50)">50</button>
                        <button type="button" class="preset-btn" onclick="setLimit(100)">100</button>
                        <button type="button" class="preset-btn" onclick="setLimit(250)">250</button>
                        <button type="button" class="preset-btn" onclick="setLimit(500)">500</button>
                        <button type="button" class="preset-btn" onclick="setLimit(999)">∞</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Oder manuell eingeben:</label>
                    <input type="number" 
                           class="form-input" 
                           id="freebieLimit" 
                           name="freebie_limit" 
                           min="0" 
                           max="999" 
                           placeholder="z.B. 15"
                           required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(userId, userName, userEmail, currentLimit, usedCount) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('freebieLimit').value = currentLimit;
            document.getElementById('currentLimitDisplay').textContent = currentLimit >= 999 ? '∞ Unbegrenzt' : currentLimit;
            document.getElementById('modalUserInfo').textContent = userName + ' (' + userEmail + ')';
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        function setLimit(value) {
            document.getElementById('freebieLimit').value = value;
        }
        
        // Form Submit via AJAX
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Fehler beim Speichern');
                console.error(error);
            });
        });
        
        // Tabellen-Filter
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[1];
                const emailCell = rows[i].getElementsByTagName('td')[2];
                
                if (nameCell && emailCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    const emailText = emailCell.textContent || emailCell.innerText;
                    
                    if (nameText.toLowerCase().indexOf(filter) > -1 || 
                        emailText.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
        
        // Modal schließen bei Klick außerhalb
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // ESC-Taste zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>

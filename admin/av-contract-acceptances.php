<?php
/**
 * Admin: AV-Vertrags-Zustimmungen Übersicht
 * 
 * Zeigt alle AV-Vertrags-Zustimmungen für Audit und rechtliche Nachweispflicht
 * DSGVO-konform gem. Art. 28 DSGVO
 * 
 * ERWEITERT: Inkl. Mailgun-Zustimmungen
 */

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../config/security.php';

// Starte sichere Session
startSecureSession();

// Login-Check
requireLogin('/public/login.php');

// Admin-Rollen-Prüfung
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/av_contract_helpers.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filter
$filter_type = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');

try {
    $pdo = getDBConnection();
    
    // Build Query
    $where_clauses = [];
    $params = [];
    
    if ($filter_type !== 'all') {
        $where_clauses[] = "a.acceptance_type = ?";
        $params[] = $filter_type;
    }
    
    if (!empty($search)) {
        $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR a.ip_address LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    // Count total
    $count_sql = "
        SELECT COUNT(*) as total
        FROM av_contract_acceptances a
        JOIN users u ON a.user_id = u.id
        {$where_sql}
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total / $per_page);
    
    // Get data
    $sql = "
        SELECT 
            a.id,
            a.user_id,
            u.name as user_name,
            u.email as user_email,
            a.accepted_at,
            a.ip_address,
            a.user_agent,
            a.av_contract_version,
            a.acceptance_type,
            a.created_at
        FROM av_contract_acceptances a
        JOIN users u ON a.user_id = u.id
        {$where_sql}
        ORDER BY a.accepted_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $acceptances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics
    $stats_sql = "
        SELECT 
            acceptance_type,
            COUNT(*) as count,
            MAX(accepted_at) as last_acceptance
        FROM av_contract_acceptances
        GROUP BY acceptance_type
    ";
    $stmt = $pdo->query($stats_sql);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching AV acceptances: " . $e->getMessage());
    $acceptances = [];
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AV-Vertrags-Zustimmungen - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-sub {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            align-self: flex-end;
        }
        
        .btn-primary {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #7c3aed;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #1f2937;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-registration {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-update {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-renewal {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-mailgun_consent {
            background: #fce7f3;
            color: #9f1239;
        }
        
        .ip-address {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .user-agent {
            font-size: 12px;
            color: #6b7280;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: #f9fafb;
        }
        
        .pagination a.active {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .back-link {
            display: inline-block;
            color: #8b5cf6;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/dashboard.php" class="back-link">← Zurück zum Dashboard</a>
        
        <div class="header">
            <h1>🔒 AV-Vertrags-Zustimmungen</h1>
            <p>DSGVO-konforme Nachweispflicht gem. Art. 28 DSGVO | Inkl. Mailgun-Zustimmungen</p>
        </div>
        
        <div class="stats">
            <?php 
            $total_count = 0;
            $type_labels = [
                'registration' => 'Registrierungen',
                'update' => 'Aktualisierungen',
                'renewal' => 'Erneuerungen',
                'mailgun_consent' => 'Mailgun + AVV'
            ];
            
            foreach ($stats as $stat): 
                $total_count += $stat['count'];
            ?>
            <div class="stat-card">
                <div class="stat-label"><?= $type_labels[$stat['acceptance_type']] ?? $stat['acceptance_type'] ?></div>
                <div class="stat-value"><?= number_format($stat['count'], 0, ',', '.') ?></div>
                <div class="stat-sub">Letzte: <?= date('d.m.Y', strtotime($stat['last_acceptance'])) ?></div>
            </div>
            <?php endforeach; ?>
            
            <div class="stat-card">
                <div class="stat-label">Gesamt</div>
                <div class="stat-value"><?= number_format($total_count, 0, ',', '.') ?></div>
                <div class="stat-sub">Alle Zustimmungen</div>
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Typ</label>
                        <select name="type">
                            <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>Alle Typen</option>
                            <option value="registration" <?= $filter_type === 'registration' ? 'selected' : '' ?>>Registrierung</option>
                            <option value="update" <?= $filter_type === 'update' ? 'selected' : '' ?>>Aktualisierung</option>
                            <option value="renewal" <?= $filter_type === 'renewal' ? 'selected' : '' ?>>Erneuerung</option>
                            <option value="mailgun_consent" <?= $filter_type === 'mailgun_consent' ? 'selected' : '' ?>>Mailgun + AVV</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Suche</label>
                        <input type="text" name="search" placeholder="Name, E-Mail oder IP..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filtern</button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <?php if (empty($acceptances)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>Keine Einträge gefunden</h3>
                <p>Es wurden keine AV-Vertrags-Zustimmungen gefunden.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Benutzer</th>
                        <th>Zeitpunkt</th>
                        <th>IP-Adresse</th>
                        <th>Typ</th>
                        <th>Version</th>
                        <th>User-Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($acceptances as $acceptance): 
                        $type_display = $type_labels[$acceptance['acceptance_type']] ?? ucfirst($acceptance['acceptance_type']);
                    ?>
                    <tr>
                        <td>#<?= $acceptance['id'] ?></td>
                        <td>
                            <div style="font-weight: 500;"><?= htmlspecialchars($acceptance['user_name']) ?></div>
                            <div style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($acceptance['user_email']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?= date('d.m.Y', strtotime($acceptance['accepted_at'])) ?></div>
                            <div style="font-size: 12px; color: #6b7280;"><?= date('H:i:s', strtotime($acceptance['accepted_at'])) ?> Uhr</div>
                        </td>
                        <td>
                            <span class="ip-address"><?= htmlspecialchars($acceptance['ip_address']) ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $acceptance['acceptance_type'] ?>">
                                <?= $type_display ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($acceptance['av_contract_version']) ?></td>
                        <td>
                            <div class="user-agent" title="<?= htmlspecialchars($acceptance['user_agent']) ?>">
                                <?= htmlspecialchars($acceptance['user_agent']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&type=<?= urlencode($filter_type) ?>&search=<?= urlencode($search) ?>" 
                   class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
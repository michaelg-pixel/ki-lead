<?php
/**
 * Admin Dashboard - Belohnungsauslieferungen
 * Übersicht aller ausgelieferten Belohnungen mit Statistiken
 */

require_once __DIR__ . '/../config/database.php';

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Filter-Optionen
$filter_status = $_GET['status'] ?? 'all';
$filter_customer = $_GET['customer'] ?? 'all';
$search = $_GET['search'] ?? '';

// Statistiken laden
$stats_query = "
    SELECT 
        COUNT(*) as total_deliveries,
        COUNT(DISTINCT lead_id) as unique_leads,
        COUNT(CASE WHEN email_sent = 1 THEN 1 END) as emails_sent,
        COUNT(CASE WHEN delivery_status = 'claimed' THEN 1 END) as claimed_count,
        COUNT(CASE WHEN DATE(delivered_at) = CURDATE() THEN 1 END) as today_deliveries
    FROM reward_deliveries
";

if ($filter_customer !== 'all') {
    $stats_query .= " WHERE user_id = " . (int)$filter_customer;
}

$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Auslieferungen laden mit Filter
$deliveries_query = "
    SELECT 
        rd.*,
        lu.name as lead_name,
        lu.email as lead_email,
        u.company_name,
        rdef.tier_name,
        rdef.tier_level
    FROM reward_deliveries rd
    LEFT JOIN lead_users lu ON rd.lead_id = lu.id
    LEFT JOIN users u ON rd.user_id = u.id
    LEFT JOIN reward_definitions rdef ON rd.reward_id = rdef.id
    WHERE 1=1
";

if ($filter_status !== 'all') {
    $deliveries_query .= " AND rd.delivery_status = '" . $pdo->quote($filter_status) . "'";
}

if ($filter_customer !== 'all') {
    $deliveries_query .= " AND rd.user_id = " . (int)$filter_customer;
}

if (!empty($search)) {
    $deliveries_query .= " AND (
        lu.name LIKE '%" . $pdo->quote($search) . "%' OR
        lu.email LIKE '%" . $pdo->quote($search) . "%' OR
        rd.reward_title LIKE '%" . $pdo->quote($search) . "%'
    )";
}

$deliveries_query .= " ORDER BY rd.delivered_at DESC LIMIT 100";

$deliveries = $pdo->query($deliveries_query)->fetchAll(PDO::FETCH_ASSOC);

// Kunden für Filter laden
$customers = $pdo->query("
    SELECT DISTINCT u.id, u.company_name 
    FROM users u
    INNER JOIN reward_deliveries rd ON u.id = rd.user_id
    ORDER BY u.company_name
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belohnungsauslieferungen - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #1a1a1a;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 800;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 900;
            color: #8B5CF6;
        }
        
        .filters {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .btn {
            padding: 10px 20px;
            background: #8B5CF6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background: #7C3AED;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            padding: 16px;
            text-align: left;
            font-size: 14px;
            font-weight: 700;
            color: #6b7280;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-delivered {
            background: #dbeafe;
            color: #1e3a8a;
        }
        
        .status-claimed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .email-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .email-sent {
            background: #d1fae5;
            color: #065f46;
        }
        
        .email-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .reward-title {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .tier-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: #8B5CF6;
            color: white;
            margin-right: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎁 Belohnungsauslieferungen</h1>
        <a href="/admin/dashboard.php" style="text-decoration: none; color: #8B5CF6; font-weight: 600;">
            ← Zurück zum Dashboard
        </a>
    </div>
    
    <div class="container">
        <!-- Statistiken -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Gesamt Auslieferungen</div>
                <div class="stat-value"><?php echo number_format($stats['total_deliveries'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Einzigartige Leads</div>
                <div class="stat-value"><?php echo number_format($stats['unique_leads'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✉️</div>
                <div class="stat-label">Emails Versendet</div>
                <div class="stat-value"><?php echo number_format($stats['emails_sent'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Eingelöst</div>
                <div class="stat-value"><?php echo number_format($stats['claimed_count'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-label">Heute</div>
                <div class="stat-value"><?php echo number_format($stats['today_deliveries'], 0, ',', '.'); ?></div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Alle</option>
                            <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Ausgeliefert</option>
                            <option value="claimed" <?php echo $filter_status === 'claimed' ? 'selected' : ''; ?>>Eingelöst</option>
                            <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>Abgelaufen</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Kunde</label>
                        <select name="customer">
                            <option value="all">Alle Kunden</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                        <?php echo $filter_customer == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['company_name'] ?? 'Kunde #' . $customer['id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Suche</label>
                        <input type="text" 
                               name="search" 
                               placeholder="Lead-Name oder Email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn">
                            <i class="fas fa-filter"></i> Filtern
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Auslieferungen Tabelle -->
        <div class="table-container">
            <?php if (empty($deliveries)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div style="font-size: 18px; margin-bottom: 8px;">Keine Auslieferungen gefunden</div>
                    <div style="font-size: 14px;">Passe die Filter an oder warte auf neue Belohnungen</div>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Belohnung</th>
                            <th>Kunde</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>Ausgeliefert</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($delivery['lead_name']); ?></div>
                                    <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($delivery['lead_email']); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($delivery['tier_name'])): ?>
                                        <span class="tier-badge"><?php echo htmlspecialchars($delivery['tier_name']); ?></span>
                                    <?php endif; ?>
                                    <div class="reward-title"><?php echo htmlspecialchars($delivery['reward_title']); ?></div>
                                    <?php if (!empty($delivery['reward_value'])): ?>
                                        <div style="font-size: 12px; color: #8B5CF6; font-weight: 600;">
                                            <?php echo htmlspecialchars($delivery['reward_value']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($delivery['company_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $delivery['delivery_status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'delivered' => '📦 Ausgeliefert',
                                            'claimed' => '✅ Eingelöst',
                                            'expired' => '⏰ Abgelaufen'
                                        ];
                                        echo $status_labels[$delivery['delivery_status']] ?? $delivery['delivery_status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="email-badge <?php echo $delivery['email_sent'] ? 'email-sent' : 'email-pending'; ?>">
                                        <?php echo $delivery['email_sent'] ? '✓ Gesendet' : '⏳ Ausstehend'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($delivery['delivered_at'])); ?></td>
                                <td>
                                    <button onclick="showDetails(<?php echo $delivery['id']; ?>)" 
                                            style="padding: 6px 12px; background: #e0e7ff; color: #4f46e5; 
                                                   border: none; border-radius: 6px; cursor: pointer; font-size: 12px; 
                                                   font-weight: 600;">
                                        <i class="fas fa-eye"></i> Ansehen
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showDetails(deliveryId) {
            alert('Details-Modal wird implementiert für Delivery ID: ' + deliveryId);
            // TODO: Modal mit allen Details öffnen
        }
    </script>
</body>
</html>

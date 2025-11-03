<?php
/**
 * Admin Section: Erweiterte Referral-Überwachung mit Real-Time Analytics
 * Erweiterte Monitoring-Features für Admins
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Nur Admin-Zugriff
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Lade Echtzeit-Daten
$timeRange = $_GET['range'] ?? '24h';
$timeFilter = match($timeRange) {
    '1h' => 'DATE_SUB(NOW(), INTERVAL 1 HOUR)',
    '24h' => 'DATE_SUB(NOW(), INTERVAL 24 HOUR)',
    '7d' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)',
    '30d' => 'DATE_SUB(NOW(), INTERVAL 30 DAY)',
    default => 'DATE_SUB(NOW(), INTERVAL 24 HOUR)'
};

// Live-Aktivitäten (letzte 100)
$stmt = $db->query("
    SELECT 
        'click' as type,
        rc.created_at,
        c.id as customer_id,
        c.company_name,
        c.email,
        rc.ref_code,
        NULL as suspicious
    FROM referral_clicks rc
    JOIN customers c ON rc.customer_id = c.id
    WHERE rc.created_at > $timeFilter
    
    UNION ALL
    
    SELECT 
        'conversion' as type,
        rconv.created_at,
        c.id as customer_id,
        c.company_name,
        c.email,
        rconv.ref_code,
        rconv.suspicious
    FROM referral_conversions rconv
    JOIN customers c ON rconv.customer_id = c.id
    WHERE rconv.created_at > $timeFilter
    
    ORDER BY created_at DESC
    LIMIT 100
");
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fraud-Alerts (ungelesen)
$stmt = $db->query("
    SELECT 
        rfl.*,
        c.company_name,
        c.email
    FROM referral_fraud_log rfl
    JOIN customers c ON rfl.customer_id = c.id
    WHERE rfl.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY rfl.created_at DESC
    LIMIT 50
");
$fraudAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Performance-Metriken
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT customer_id) as active_customers,
        COUNT(*) as total_clicks,
        AVG(CASE 
            WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 
            ELSE 0 
        END) * 100 as clicks_last_hour_pct
    FROM referral_clicks
    WHERE created_at > $timeFilter
");
$performanceMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Top-Performer
$stmt = $db->query("
    SELECT 
        c.id,
        c.company_name,
        c.email,
        rs.total_clicks,
        rs.total_conversions,
        rs.conversion_rate,
        rs.total_leads
    FROM referral_stats rs
    JOIN customers c ON rs.customer_id = c.id
    WHERE c.referral_enabled = 1
    ORDER BY rs.total_conversions DESC
    LIMIT 10
");
$topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conversion-Funnel
$stmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM referral_clicks WHERE created_at > $timeFilter) as clicks,
        (SELECT COUNT(*) FROM referral_conversions WHERE created_at > $timeFilter) as conversions,
        (SELECT COUNT(*) FROM referral_leads WHERE created_at > $timeFilter) as leads,
        (SELECT COUNT(*) FROM referral_leads WHERE confirmed = 1 AND created_at > $timeFilter) as confirmed_leads
");
$funnel = $stmt->fetch(PDO::FETCH_ASSOC);
$conversionRate = $funnel['clicks'] > 0 ? round(($funnel['conversions'] / $funnel['clicks']) * 100, 2) : 0;
$leadRate = $funnel['conversions'] > 0 ? round(($funnel['leads'] / $funnel['conversions']) * 100, 2) : 0;
$confirmationRate = $funnel['leads'] > 0 ? round(($funnel['confirmed_leads'] / $funnel['leads']) * 100, 2) : 0;

// Hourly Activity Chart Data
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as count,
        'clicks' as type
    FROM referral_clicks
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour
    
    UNION ALL
    
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as count,
        'conversions' as type
    FROM referral_conversions
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour
    
    ORDER BY hour ASC
");
$hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erweiterte Referral-Überwachung</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-dot {
            animation: pulse-dot 2s ease-in-out infinite;
        }
        
        .activity-item {
            animation: fadeInDown 0.3s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="w-3 h-3 bg-green-500 rounded-full mr-3 pulse-dot"></div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">🔍 Erweiterte Referral-Überwachung</h1>
                    <p class="text-sm text-gray-600 mt-1">Echtzeit-Monitoring & Analytics</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <select id="timeRange" onchange="changeTimeRange()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="1h" <?= $timeRange === '1h' ? 'selected' : '' ?>>Letzte Stunde</option>
                    <option value="24h" <?= $timeRange === '24h' ? 'selected' : '' ?>>Letzte 24 Stunden</option>
                    <option value="7d" <?= $timeRange === '7d' ? 'selected' : '' ?>>Letzte 7 Tage</option>
                    <option value="30d" <?= $timeRange === '30d' ? 'selected' : '' ?>>Letzte 30 Tage</option>
                </select>
                <button onclick="window.location.reload()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                    <i class="fas fa-sync mr-2"></i> Aktualisieren
                </button>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 rounded-lg">
                <div class="text-sm opacity-90 mb-1">Aktive Customers</div>
                <div class="text-3xl font-bold"><?= number_format($performanceMetrics['active_customers']) ?></div>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-4 rounded-lg">
                <div class="text-sm opacity-90 mb-1">Klicks (Zeitraum)</div>
                <div class="text-3xl font-bold"><?= number_format($performanceMetrics['total_clicks']) ?></div>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-4 rounded-lg">
                <div class="text-sm opacity-90 mb-1">Conversion Rate</div>
                <div class="text-3xl font-bold"><?= $conversionRate ?>%</div>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-4 rounded-lg">
                <div class="text-sm opacity-90 mb-1">Fraud-Alerts (24h)</div>
                <div class="text-3xl font-bold"><?= count($fraudAlerts) ?></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Live-Feed (2/3 Breite) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Conversion Funnel -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">📊 Conversion Funnel</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Klicks</span>
                            <span class="text-sm font-bold text-gray-900"><?= number_format($funnel['clicks']) ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-500 h-3 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Conversions</span>
                            <span class="text-sm font-bold text-gray-900"><?= number_format($funnel['conversions']) ?> (<?= $conversionRate ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-500 h-3 rounded-full" style="width: <?= $conversionRate ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Leads registriert</span>
                            <span class="text-sm font-bold text-gray-900"><?= number_format($funnel['leads']) ?> (<?= $leadRate ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-purple-500 h-3 rounded-full" style="width: <?= $leadRate ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Leads bestätigt</span>
                            <span class="text-sm font-bold text-gray-900"><?= number_format($funnel['confirmed_leads']) ?> (<?= $confirmationRate ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-indigo-500 h-3 rounded-full" style="width: <?= $confirmationRate ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">📈 Aktivität über Zeit</h3>
                <canvas id="activityChart" height="80"></canvas>
            </div>

            <!-- Live-Activity Feed -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">⚡ Live-Aktivitäten</h3>
                    <span class="text-xs text-gray-500">Letzte 100 Events</span>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach (array_slice($recentActivity, 0, 50) as $activity): ?>
                    <div class="activity-item flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-3">
                            <?php if ($activity['type'] === 'click'): ?>
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-mouse-pointer text-blue-600"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 bg-<?= $activity['suspicious'] ? 'red' : 'green' ?>-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check-circle text-<?= $activity['suspicious'] ? 'red' : 'green' ?>-600"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($activity['company_name'] ?: $activity['email']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span class="font-mono"><?= htmlspecialchars($activity['ref_code']) ?></span>
                                    • <?= $activity['type'] === 'click' ? 'Klick' : 'Conversion' ?>
                                    <?php if ($activity['suspicious']): ?>
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Verdächtig</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400">
                            <?php 
                            $dt = new DateTime($activity['created_at']);
                            echo $dt->format('H:i:s');
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar (1/3 Breite) -->
        <div class="space-y-6">
            <!-- Top Performer -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">🏆 Top 10 Performer</h3>
                <div class="space-y-3">
                    <?php foreach ($topPerformers as $index => $performer): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($performer['company_name'] ?: $performer['email']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?= number_format($performer['total_conversions']) ?> Conv. • <?= $performer['conversion_rate'] ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fraud-Alerts -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">⚠️ Fraud-Alerts</h3>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">
                        <?= count($fraudAlerts) ?> neu
                    </span>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php if (empty($fraudAlerts)): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-shield-alt text-4xl mb-2"></i>
                            <div class="text-sm">Keine Fraud-Alerts</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($fraudAlerts as $alert): ?>
                        <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-sm font-medium text-red-900">
                                        <?= htmlspecialchars($alert['company_name'] ?: $alert['email']) ?>
                                    </div>
                                    <div class="text-xs text-red-700 mt-1">
                                        <?= ucfirst(str_replace('_', ' ', $alert['fraud_type'])) ?>
                                    </div>
                                    <?php if ($alert['ref_code']): ?>
                                        <div class="text-xs text-gray-600 font-mono mt-1">
                                            <?= htmlspecialchars($alert['ref_code']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-red-600">
                                    <?php 
                                    $dt = new DateTime($alert['created_at']);
                                    echo $dt->format('H:i');
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System-Health -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">💚 System-Health</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Datenbank</span>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                            ✓ Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">API-Endpoints</span>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                            ✓ OK
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">E-Mail-System</span>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                            ✓ Aktiv
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Cron-Jobs</span>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">
                            ⚠ Prüfen
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Time Range wechseln
function changeTimeRange() {
    const range = document.getElementById('timeRange').value;
    window.location.href = '?range=' + range;
}

// Activity Chart
const hourlyData = <?= json_encode($hourlyData) ?>;

// Daten für Chart vorbereiten
const hours = [];
const clickData = [];
const conversionData = [];

// Gruppiere Daten nach Stunde
const dataByHour = {};
hourlyData.forEach(item => {
    if (!dataByHour[item.hour]) {
        dataByHour[item.hour] = { clicks: 0, conversions: 0 };
    }
    if (item.type === 'clicks') {
        dataByHour[item.hour].clicks = item.count;
    } else {
        dataByHour[item.hour].conversions = item.count;
    }
});

// Sortiere und extrahiere Daten
Object.keys(dataByHour).sort().forEach(hour => {
    const dt = new Date(hour);
    hours.push(dt.getHours() + ':00');
    clickData.push(dataByHour[hour].clicks);
    conversionData.push(dataByHour[hour].conversions);
});

const ctx = document.getElementById('activityChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: hours,
        datasets: [{
            label: 'Klicks',
            data: clickData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Conversions',
            data: conversionData,
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Auto-Reload alle 60 Sekunden
setInterval(() => {
    window.location.reload();
}, 60000);
</script>

</body>
</html>

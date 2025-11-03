<?php
/**
 * Admin Section: Referral Program Overview
 * Read-Only Monitoring aller Customer-Aktivitäten
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';

// Nur Admin-Zugriff
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$referral = new ReferralHelper($db);

// Lade alle Stats
$allStats = $referral->getAllStats();

// Aggregierte Gesamt-Statistik
$totalStats = [
    'active_programs' => 0,
    'total_clicks' => 0,
    'total_conversions' => 0,
    'total_leads' => 0,
    'avg_conversion_rate' => 0
];

foreach ($allStats as $stat) {
    if ($stat['referral_enabled']) {
        $totalStats['active_programs']++;
        $totalStats['total_clicks'] += $stat['total_clicks'];
        $totalStats['total_conversions'] += $stat['total_conversions'];
        $totalStats['total_leads'] += $stat['total_leads'];
    }
}

if ($totalStats['active_programs'] > 0) {
    $totalStats['avg_conversion_rate'] = round(
        array_sum(array_column($allStats, 'conversion_rate')) / $totalStats['active_programs'], 
        2
    );
}
?>

<div class="referral-admin-section">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">🎯 Empfehlungsprogramm - Übersicht</h1>
                <p class="text-gray-600 mt-1">Monitoring aller Customer-Aktivitäten (Read-Only)</p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="refreshData()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Aktualisieren
                </button>
                <button onclick="exportData()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Gesamt-Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-sm p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Aktive Programme</h3>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold"><?php echo $totalStats['active_programs']; ?></div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Gesamt-Klicks</h3>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold"><?php echo number_format($totalStats['total_clicks']); ?></div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Conversions</h3>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold"><?php echo number_format($totalStats['total_conversions']); ?></div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Registrierte Leads</h3>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold"><?php echo number_format($totalStats['total_leads']); ?></div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-sm p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium opacity-90">Ø Conv. Rate</h3>
                <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold"><?php echo $totalStats['avg_conversion_rate']; ?>%</div>
        </div>
    </div>

    <!-- Filter & Suche -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                <input type="text" id="searchInput" placeholder="E-Mail oder Firmenname..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="statusFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="all">Alle</option>
                    <option value="active">Nur Aktive</option>
                    <option value="inactive">Nur Inaktive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sortierung</label>
                <select id="sortBy" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="conversions">Nach Conversions</option>
                    <option value="clicks">Nach Klicks</option>
                    <option value="leads">Nach Leads</option>
                    <option value="rate">Nach Conv. Rate</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="applyFilters()" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Filter anwenden
                </button>
            </div>
        </div>
    </div>

    <!-- Customer-Liste -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral-Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Klicks</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Conversions</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Verdächtig</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Leads</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Conv. Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letzte Aktivität</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody" class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allStats as $stat): ?>
                    <tr class="hover:bg-gray-50 transition" data-customer-id="<?php echo $stat['id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($stat['company_name'] ?: $stat['email']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($stat['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-600"><?php echo htmlspecialchars($stat['referral_code']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($stat['referral_enabled']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Aktiv
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Inaktiv
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <span class="text-gray-900 font-medium"><?php echo number_format($stat['total_clicks']); ?></span>
                            <span class="text-gray-500 text-xs ml-1">(<?php echo number_format($stat['unique_clicks']); ?>)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                            <?php echo number_format($stat['total_conversions']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <?php if ($stat['suspicious_conversions'] > 0): ?>
                                <span class="text-red-600 font-medium"><?php echo $stat['suspicious_conversions']; ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <span class="text-gray-900 font-medium"><?php echo number_format($stat['total_leads']); ?></span>
                            <span class="text-green-600 text-xs ml-1">(<?php echo number_format($stat['confirmed_leads']); ?>)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <?php 
                            $rate = floatval($stat['conversion_rate']);
                            $colorClass = $rate >= 10 ? 'text-green-600' : ($rate >= 5 ? 'text-yellow-600' : 'text-gray-600');
                            ?>
                            <span class="font-medium <?php echo $colorClass; ?>"><?php echo $rate; ?>%</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                            $lastActivity = $stat['last_conversion_at'] ?: $stat['last_click_at'];
                            if ($lastActivity) {
                                $datetime = new DateTime($lastActivity);
                                echo $datetime->format('d.m.Y H:i');
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="viewDetails(<?php echo $stat['id']; ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                Details
                            </button>
                            <button onclick="viewFraudLog(<?php echo $stat['id']; ?>)" class="text-gray-600 hover:text-gray-900">
                                Fraud-Log
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($allStats)): ?>
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                            Noch keine aktiven Empfehlungsprogramme
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Customer Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modalContent" class="mt-4">
            <!-- Content wird via JavaScript geladen -->
        </div>
    </div>
</div>

<script>
// Lade Customer-Details
async function viewDetails(customerId) {
    try {
        const response = await fetch(`/api/referral/get-customer-details.php?customer_id=${customerId}`);
        const data = await response.json();
        
        if (data.success) {
            showDetailsModal(data.data);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Details:', error);
    }
}

// Zeige Details Modal
function showDetailsModal(data) {
    document.getElementById('modalTitle').textContent = `Details: ${data.company_name || data.email}`;
    
    const content = `
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold mb-3">Letzte Klicks (10)</h4>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    ${data.recent_clicks.map(click => `
                        <div class="text-sm p-2 bg-gray-50 rounded">
                            <div class="font-mono text-xs text-gray-600">${click.date}</div>
                            <div class="text-gray-800">${click.ref_code}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Letzte Conversions (10)</h4>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    ${data.recent_conversions.map(conv => `
                        <div class="text-sm p-2 ${conv.suspicious === '1' ? 'bg-red-50' : 'bg-green-50'} rounded">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs text-gray-600">${conv.date}</span>
                                <span class="text-xs ${conv.suspicious === '1' ? 'text-red-600' : 'text-green-600'}">${conv.source}</span>
                            </div>
                            <div class="text-gray-800">${conv.ref_code}</div>
                            ${conv.time_to_convert ? `<div class="text-xs text-gray-500">${conv.time_to_convert}s</div>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <h4 class="font-semibold mb-3">Registrierte Leads (${data.leads.length})</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Datum</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">E-Mail</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${data.leads.map(lead => `
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">${lead.date}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">${lead.email}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full ${lead.confirmed === '1' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                        ${lead.confirmed === '1' ? 'Bestätigt' : 'Ausstehend'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('detailsModal').classList.remove('hidden');
}

// Zeige Fraud-Log
async function viewFraudLog(customerId) {
    try {
        const response = await fetch(`/api/referral/get-fraud-log.php?customer_id=${customerId}`);
        const data = await response.json();
        
        if (data.success) {
            showFraudLogModal(data.data);
        }
    } catch (error) {
        console.error('Fehler beim Laden des Fraud-Logs:', error);
    }
}

// Schließe Modal
function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

// Daten aktualisieren
function refreshData() {
    location.reload();
}

// Export CSV
function exportData() {
    window.location.href = '/api/referral/export-stats.php';
}

// Filter anwenden
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const sortBy = document.getElementById('sortBy').value;
    
    const rows = Array.from(document.querySelectorAll('#customerTableBody tr[data-customer-id]'));
    
    // Filtern
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isActive = row.querySelector('.bg-green-100') !== null;
        
        let showRow = true;
        
        // Such-Filter
        if (search && !text.includes(search)) {
            showRow = false;
        }
        
        // Status-Filter
        if (status === 'active' && !isActive) {
            showRow = false;
        }
        if (status === 'inactive' && isActive) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
    
    // Sortieren
    const tbody = document.getElementById('customerTableBody');
    rows.sort((a, b) => {
        let aVal, bVal;
        
        switch(sortBy) {
            case 'conversions':
                aVal = parseInt(a.cells[4].textContent.trim());
                bVal = parseInt(b.cells[4].textContent.trim());
                break;
            case 'clicks':
                aVal = parseInt(a.cells[3].textContent.trim());
                bVal = parseInt(b.cells[3].textContent.trim());
                break;
            case 'leads':
                aVal = parseInt(a.cells[6].textContent.trim());
                bVal = parseInt(b.cells[6].textContent.trim());
                break;
            case 'rate':
                aVal = parseFloat(a.cells[7].textContent.trim());
                bVal = parseFloat(b.cells[7].textContent.trim());
                break;
        }
        
        return bVal - aVal;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Suche bei Eingabe
document.getElementById('searchInput')?.addEventListener('input', function() {
    applyFilters();
});
</script>

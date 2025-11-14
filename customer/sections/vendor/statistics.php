<?php
/**
 * Vendor Statistics Page (Tab 3)
 * Zeigt Download- und Claim-Statistiken für alle Templates (Kostenlose Belohnungen)
 */

if (!isset($customer) || !$customer['is_vendor']) {
    echo '<p style="color: #ef4444;">Zugriff verweigert. Nur für Vendors.</p>';
    return;
}

// $vendor_id wird von vendor-bereich.php bereitgestellt
if (!isset($vendor_id)) {
    echo '<p style="color: #ef4444;">Fehler: Vendor-ID nicht verfügbar.</p>';
    return;
}
?>

<style>
.stats-container {
    padding: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(to bottom right, #1f2937, #374151);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
    border-color: rgba(102, 126, 234, 0.5);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #9ca3af;
    font-size: 0.875rem;
    font-weight: 500;
}

.chart-card {
    background: linear-gradient(to bottom right, #1f2937, #374151);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.chart-card h3 {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.chart-container {
    position: relative;
    height: 300px;
}

.table-card {
    background: linear-gradient(to bottom right, #1f2937, #374151);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.table-card h3 {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stats-table {
    width: 100%;
    border-collapse: collapse;
}

.stats-table thead {
    background: rgba(0, 0, 0, 0.2);
}

.stats-table th {
    padding: 1rem;
    text-align: left;
    color: #9ca3af;
    font-weight: 600;
    font-size: 0.875rem;
    border-bottom: 2px solid rgba(102, 126, 234, 0.3);
}

.stats-table td {
    padding: 1rem;
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-table tbody tr:hover {
    background: rgba(102, 126, 234, 0.1);
}

.template-name {
    font-weight: 600;
    color: white;
}

.metric {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.metric-value {
    font-weight: 600;
}

.metric-icon {
    font-size: 1.125rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.badge-info {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.badge-warning {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.loading-spinner {
    text-align: center;
    padding: 4rem;
    color: #9ca3af;
}

.loading-spinner i {
    font-size: 3rem;
    color: #667eea;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem;
    color: #374151;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .stats-table {
        font-size: 0.875rem;
    }
    
    .stats-table th,
    .stats-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<div class="stats-container">
    
    <!-- Loading State -->
    <div id="loadingState" class="loading-spinner">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
        <p style="margin-top: 1rem;">Lade Statistiken...</p>
    </div>
    
    <!-- Main Content -->
    <div id="statsContent" style="display: none;">
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-value" id="totalTemplates">0</div>
                <div class="stat-label">Templates</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⬇️</div>
                <div class="stat-value" id="totalImports">0</div>
                <div class="stat-label">Gesamt-Downloads</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value" id="totalClaims">0</div>
                <div class="stat-label">Gesamt-Claims</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value" id="avgDownloads">0</div>
                <div class="stat-label">Ø Downloads pro Template</div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="chart-card">
            <h3>📈 Downloads & Claims über Zeit (letzte 30 Tage)</h3>
            <div class="chart-container">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
        
        <!-- Top Templates -->
        <div class="table-card">
            <h3>🏆 Top Templates nach Downloads</h3>
            <div style="overflow-x: auto;">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Kategorie</th>
                            <th>Downloads</th>
                            <th>Claims</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="topTemplatesBody">
                        <!-- Wird dynamisch gefüllt -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Imports -->
        <div class="table-card">
            <h3>⬇️ Letzte Downloads (Top 10)</h3>
            <div style="overflow-x: auto;">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Template</th>
                            <th>Customer</th>
                        </tr>
                    </thead>
                    <tbody id="recentImportsBody">
                        <!-- Wird dynamisch gefüllt -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Claims -->
        <div class="table-card">
            <h3>🎁 Letzte Claims (Top 10)</h3>
            <div style="overflow-x: auto;">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Template</th>
                            <th>Customer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentClaimsBody">
                        <!-- Wird dynamisch gefüllt -->
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- Empty State -->
    <div id="emptyState" style="display: none;" class="empty-state">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📊</div>
        <h3 style="color: white; margin-bottom: 0.5rem;">Noch keine Statistiken</h3>
        <p>Erstelle dein erstes Template im Templates-Tab</p>
    </div>
    
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let timelineChart = null;

// Seite laden
document.addEventListener('DOMContentLoaded', function() {
    // Nur laden wenn Tab aktiv ist
    if (document.getElementById('loadingState')) {
        loadStatistics();
    }
});

// Statistiken laden
async function loadStatistics() {
    try {
        const response = await fetch('/api/vendor/stats/overview.php');
        const data = await response.json();
        
        if (data.success) {
            if (data.stats.total_templates === 0) {
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('emptyState').style.display = 'block';
            } else {
                renderStatistics(data);
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('statsContent').style.display = 'block';
            }
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
        document.getElementById('loadingState').innerHTML = `
            <div style="font-size: 3rem; color: #ef4444;">⚠️</div>
            <p style="margin-top: 1rem; color: #ef4444;">Fehler beim Laden der Statistiken</p>
            <p style="margin-top: 0.5rem; font-size: 0.875rem;">${error.message}</p>
        `;
    }
}

// Statistiken anzeigen
function renderStatistics(data) {
    const stats = data.stats;
    
    // Stats Cards
    document.getElementById('totalTemplates').textContent = stats.total_templates;
    document.getElementById('totalImports').textContent = stats.total_imports;
    document.getElementById('totalClaims').textContent = stats.total_claims;
    
    // Durchschnittliche Downloads
    const avgDownloads = stats.total_templates > 0 
        ? Math.round(stats.total_imports / stats.total_templates) 
        : 0;
    document.getElementById('avgDownloads').textContent = avgDownloads;
    
    // Timeline Chart
    if (data.timeline) {
        renderTimelineChart(data.timeline);
    }
    
    // Top Templates
    if (data.top_templates) {
        renderTopTemplates(data.top_templates);
    }
    
    // Recent Imports
    if (data.recent_imports) {
        renderRecentImports(data.recent_imports);
    }
    
    // Recent Claims
    if (data.recent_claims) {
        renderRecentClaims(data.recent_claims);
    }
}

// Timeline Chart
function renderTimelineChart(timeline) {
    const ctx = document.getElementById('timelineChart');
    if (!ctx) return;
    
    // Destroy existing chart
    if (timelineChart) {
        timelineChart.destroy();
    }
    
    const dates = timeline.map(t => t.date);
    const imports = timeline.map(t => t.imports);
    const claims = timeline.map(t => t.claims);
    
    timelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Downloads',
                    data: imports,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Claims',
                    data: claims,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#9ca3af'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ca3af',
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#9ca3af'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });
}

// Top Templates
function renderTopTemplates(templates) {
    const tbody = document.getElementById('topTemplatesBody');
    
    if (templates.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #9ca3af;">Keine Templates vorhanden</td></tr>';
        return;
    }
    
    tbody.innerHTML = templates.map(t => `
        <tr>
            <td class="template-name">${escapeHtml(t.template_name)}</td>
            <td><span class="badge badge-info">${escapeHtml(t.category || 'N/A')}</span></td>
            <td>
                <div class="metric">
                    <span class="metric-icon">⬇️</span>
                    <span class="metric-value">${t.times_imported}</span>
                </div>
            </td>
            <td>
                <div class="metric">
                    <span class="metric-icon">🎁</span>
                    <span class="metric-value">${t.times_claimed}</span>
                </div>
            </td>
            <td>
                <span class="badge ${t.is_published ? 'badge-success' : 'badge-warning'}">
                    ${t.is_published ? '✓ Veröffentlicht' : '⏸ Entwurf'}
                </span>
            </td>
        </tr>
    `).join('');
}

// Recent Imports
function renderRecentImports(imports) {
    const tbody = document.getElementById('recentImportsBody');
    
    if (imports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #9ca3af;">Keine Downloads vorhanden</td></tr>';
        return;
    }
    
    tbody.innerHTML = imports.map(i => `
        <tr>
            <td>${formatDate(i.import_date)}</td>
            <td class="template-name">${escapeHtml(i.template_name)}</td>
            <td>${escapeHtml(i.customer_name)}</td>
        </tr>
    `).join('');
}

// Recent Claims
function renderRecentClaims(claims) {
    const tbody = document.getElementById('recentClaimsBody');
    
    if (claims.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #9ca3af;">Keine Claims vorhanden</td></tr>';
        return;
    }
    
    tbody.innerHTML = claims.map(c => `
        <tr>
            <td>${formatDate(c.claimed_at)}</td>
            <td class="template-name">${escapeHtml(c.template_name)}</td>
            <td>${escapeHtml(c.customer_name)}</td>
            <td>
                <span class="badge ${c.claim_status === 'delivered' ? 'badge-success' : c.claim_status === 'failed' ? 'badge-warning' : 'badge-info'}">
                    ${c.claim_status}
                </span>
            </td>
        </tr>
    `).join('');
}

// Helper: Format Date
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

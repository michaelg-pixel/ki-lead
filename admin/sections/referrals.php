<?php
/**
 * Admin Dashboard Section: Empfehlungsprogramm-Übersicht
 * Zeigt alle Referral-Aktivitäten aller Kunden
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

// Statistiken abrufen
$stats = [];

// Gesamtanzahl Klicks
$stats['total_clicks'] = $pdo->query("
    SELECT COUNT(*) FROM referral_clicks
")->fetchColumn() ?: 0;

// Unique Klicks
$stats['unique_clicks'] = $pdo->query("
    SELECT COUNT(DISTINCT fingerprint) FROM referral_clicks
")->fetchColumn() ?: 0;

// Conversions
$stats['total_conversions'] = $pdo->query("
    SELECT COUNT(*) FROM referral_conversions
")->fetchColumn() ?: 0;

// Leads
$stats['total_leads'] = $pdo->query("
    SELECT COUNT(*) FROM referral_leads
")->fetchColumn() ?: 0;

// Bestätigte Leads
$stats['confirmed_leads'] = $pdo->query("
    SELECT COUNT(*) FROM referral_leads WHERE confirmed = 1
")->fetchColumn() ?: 0;

// Aktive User mit Referral
$stats['active_users'] = $pdo->query("
    SELECT COUNT(*) FROM users WHERE referral_enabled = 1
")->fetchColumn() ?: 0;

// User-Übersicht mit Referral-Stats
$users_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.referral_enabled,
        u.ref_code,
        (SELECT COUNT(*) FROM referral_clicks WHERE user_id = u.id) as clicks,
        (SELECT COUNT(*) FROM referral_conversions WHERE user_id = u.id) as conversions,
        (SELECT COUNT(*) FROM referral_leads WHERE user_id = u.id) as leads
    FROM users u
    WHERE u.role = 'customer'
    ORDER BY clicks DESC, leads DESC
    LIMIT 50
";
$users = $pdo->query($users_query)->fetchAll(PDO::FETCH_ASSOC);

// Letzte Aktivitäten
$recent_clicks = $pdo->query("
    SELECT 
        rc.*,
        u.name as user_name,
        u.email as user_email
    FROM referral_clicks rc
    LEFT JOIN users u ON rc.user_id = u.id
    ORDER BY rc.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$recent_leads = $pdo->query("
    SELECT 
        rl.*,
        u.name as user_name,
        u.email as user_email
    FROM referral_leads rl
    LEFT JOIN users u ON rl.user_id = u.id
    ORDER BY rl.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .referral-admin {
        padding: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-card h3 {
        font-size: 14px;
        color: var(--text-secondary);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-card .value {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary-light);
        margin-bottom: 5px;
    }
    
    .stat-card .label {
        font-size: 12px;
        color: var(--text-muted);
    }
    
    .section {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: white;
    }
    
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }
    
    .tab {
        padding: 12px 20px;
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }
    
    .tab:hover {
        color: var(--primary-light);
    }
    
    .tab.active {
        color: var(--primary-light);
        border-bottom-color: var(--primary);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid var(--border);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
    }
    
    td {
        padding: 12px;
        border-bottom: 1px solid var(--border-light);
        color: var(--text-primary);
    }
    
    tbody tr:hover {
        background: rgba(168, 85, 247, 0.05);
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-success {
        background: rgba(74, 222, 128, 0.2);
        color: var(--success);
    }
    
    .badge-warning {
        background: rgba(251, 191, 36, 0.2);
        color: var(--warning);
    }
    
    .badge-danger {
        background: rgba(239, 68, 68, 0.2);
        color: var(--danger);
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }
</style>

<div class="referral-admin">
    <!-- Statistiken -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>👥 Aktive Nutzer</h3>
            <div class="value"><?php echo $stats['active_users']; ?></div>
            <div class="label">mit Referral aktiviert</div>
        </div>
        
        <div class="stat-card">
            <h3>🔗 Gesamt-Klicks</h3>
            <div class="value"><?php echo $stats['total_clicks']; ?></div>
            <div class="label"><?php echo $stats['unique_clicks']; ?> unique</div>
        </div>
        
        <div class="stat-card">
            <h3>✅ Conversions</h3>
            <div class="value"><?php echo $stats['total_conversions']; ?></div>
            <div class="label">abgeschlossen</div>
        </div>
        
        <div class="stat-card">
            <h3>📧 Leads</h3>
            <div class="value"><?php echo $stats['total_leads']; ?></div>
            <div class="label"><?php echo $stats['confirmed_leads']; ?> bestätigt</div>
        </div>
    </div>
    
    <!-- User-Übersicht -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">👥 User mit Empfehlungsaktivität</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Ref-Code</th>
                        <th>Status</th>
                        <th>Klicks</th>
                        <th>Conversions</th>
                        <th>Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><code><?php echo htmlspecialchars($user['ref_code'] ?: '-'); ?></code></td>
                            <td>
                                <?php if ($user['referral_enabled']): ?>
                                    <span class="badge badge-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['clicks']; ?></td>
                            <td><?php echo $user['conversions']; ?></td>
                            <td><?php echo $user['leads']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div>Keine User mit Empfehlungsaktivität gefunden</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Aktivitäten-Tabs -->
    <div class="section">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('clicks')">Letzte Klicks</button>
            <button class="tab" onclick="switchTab('leads')">Letzte Leads</button>
        </div>
        
        <!-- Klicks Tab -->
        <div id="tab-clicks" class="tab-content active">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>User</th>
                            <th>Ref-Code</th>
                            <th>Fingerprint</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_clicks) > 0): ?>
                            <?php foreach ($recent_clicks as $click): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($click['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($click['user_name'] ?: 'Unbekannt'); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($click['user_email']); ?></small>
                                </td>
                                <td><code><?php echo htmlspecialchars($click['ref_code']); ?></code></td>
                                <td><code style="font-size: 11px;"><?php echo substr($click['fingerprint'], 0, 16); ?>...</code></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <div>Noch keine Klicks vorhanden</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Leads Tab -->
        <div id="tab-leads" class="tab-content">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Lead E-Mail</th>
                            <th>Empfohlen von</th>
                            <th>Ref-Code</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_leads) > 0): ?>
                            <?php foreach ($recent_leads as $lead): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($lead['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($lead['user_name'] ?: 'Unbekannt'); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($lead['user_email']); ?></small>
                                </td>
                                <td><code><?php echo htmlspecialchars($lead['ref_code']); ?></code></td>
                                <td>
                                    <?php if ($lead['confirmed']): ?>
                                        <span class="badge badge-success">Bestätigt</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Ausstehend</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div>Noch keine Leads vorhanden</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Alle Tabs deaktivieren
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Alle Tab-Contents ausblenden
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Aktiven Tab aktivieren
    event.target.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}
</script>

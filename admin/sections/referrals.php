<?php
/**
 * Admin Dashboard Section: Empfehlungsprogramm-Übersicht
 * Zeigt alle Referral-Aktivitäten aller Kunden + Lead Users
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

// 🆕 LEAD USERS STATISTIKEN
$lead_stats = [];

// Prüfen ob lead_users Tabelle existiert
$table_exists = $pdo->query("SHOW TABLES LIKE 'lead_users'")->rowCount() > 0;

if ($table_exists) {
    // Gesamtanzahl Lead Users
    $lead_stats['total_lead_users'] = $pdo->query("
        SELECT COUNT(*) FROM lead_users
    ")->fetchColumn() ?: 0;
    
    // Neue Leads letzte 7 Tage
    $lead_stats['leads_7days'] = $pdo->query("
        SELECT COUNT(*) FROM lead_users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn() ?: 0;
    
    // Neue Leads letzte 30 Tage
    $lead_stats['leads_30days'] = $pdo->query("
        SELECT COUNT(*) FROM lead_users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn() ?: 0;
    
    // Leads mit aktiven Referrals
    $lead_stats['leads_with_referrals'] = $pdo->query("
        SELECT COUNT(*) FROM lead_users 
        WHERE total_referrals > 0
    ")->fetchColumn() ?: 0;
    
    // Gesamte Empfehlungen (erfolgreich)
    $lead_stats['total_successful_referrals'] = $pdo->query("
        SELECT COALESCE(SUM(successful_referrals), 0) FROM lead_users
    ")->fetchColumn() ?: 0;
    
    // Top Customers mit meisten Leads
    $top_customers = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.company_name,
            u.email,
            u.referral_enabled,
            COUNT(lu.id) as total_leads,
            COUNT(CASE WHEN lu.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as leads_7days,
            COUNT(CASE WHEN lu.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as leads_30days,
            COALESCE(SUM(lu.successful_referrals), 0) as total_referrals
        FROM users u
        LEFT JOIN lead_users lu ON lu.user_id = u.id
        WHERE u.role = 'customer'
        GROUP BY u.id
        HAVING total_leads > 0
        ORDER BY total_leads DESC, total_referrals DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Letzte Lead-Registrierungen (ANONYMISIERT für Datenschutz)
    $recent_lead_users = $pdo->query("
        SELECT 
            lu.id,
            CONCAT(LEFT(lu.email, 3), '***@***', RIGHT(SUBSTRING_INDEX(lu.email, '@', -1), 3)) as email_masked,
            lu.referral_code,
            lu.successful_referrals,
            lu.total_referrals,
            lu.rewards_earned,
            lu.created_at,
            u.name as customer_name,
            u.company_name,
            u.id as customer_id
        FROM lead_users lu
        LEFT JOIN users u ON lu.user_id = u.id
        ORDER BY lu.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $lead_stats = [
        'total_lead_users' => 0,
        'leads_7days' => 0,
        'leads_30days' => 0,
        'leads_with_referrals' => 0,
        'total_successful_referrals' => 0
    ];
    $top_customers = [];
    $recent_lead_users = [];
}

// User-Übersicht mit Referral-Stats (ALTES SYSTEM)
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

// Letzte Aktivitäten (ALTES SYSTEM)
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
    
    /* 🆕 Lead Users Stats - andere Farbe */
    .stat-card.lead-stat {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
        border-color: rgba(16, 185, 129, 0.3);
    }
    
    .stat-card.lead-stat .value {
        color: #10b981;
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
    
    .section-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 4px;
    }
    
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    
    .tab {
        padding: 12px 20px;
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        white-space: nowrap;
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
    
    .badge-info {
        background: rgba(59, 130, 246, 0.2);
        color: #3b82f6;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }
    
    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border-left: 4px solid #3b82f6;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        color: var(--text-secondary);
    }
    
    .info-box strong {
        color: #3b82f6;
    }
</style>

<div class="referral-admin">
    <!-- 🆕 LEAD USERS STATISTIKEN -->
    <?php if ($table_exists && $lead_stats['total_lead_users'] > 0): ?>
    <div class="section">
        <div class="section-header">
            <div>
                <h2 class="section-title">🚀 Lead Users Dashboard-Zugang</h2>
                <p class="section-subtitle">Registrierte Leads mit Dashboard-Zugang und Empfehlungsprogramm</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card lead-stat">
                <h3>📧 Gesamt Leads</h3>
                <div class="value"><?php echo number_format($lead_stats['total_lead_users'], 0, ',', '.'); ?></div>
                <div class="label">registrierte Lead Users</div>
            </div>
            
            <div class="stat-card lead-stat">
                <h3>📅 Neue (7 Tage)</h3>
                <div class="value"><?php echo number_format($lead_stats['leads_7days'], 0, ',', '.'); ?></div>
                <div class="label">letzte Woche</div>
            </div>
            
            <div class="stat-card lead-stat">
                <h3>📅 Neue (30 Tage)</h3>
                <div class="value"><?php echo number_format($lead_stats['leads_30days'], 0, ',', '.'); ?></div>
                <div class="label">letzter Monat</div>
            </div>
            
            <div class="stat-card lead-stat">
                <h3>🎯 Aktive Empfehler</h3>
                <div class="value"><?php echo number_format($lead_stats['leads_with_referrals'], 0, ',', '.'); ?></div>
                <div class="label">mit Empfehlungen</div>
            </div>
            
            <div class="stat-card lead-stat">
                <h3>✅ Erfolgreiche Referrals</h3>
                <div class="value"><?php echo number_format($lead_stats['total_successful_referrals'], 0, ',', '.'); ?></div>
                <div class="label">gesamt empfohlen</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ALTE REFERRAL STATISTIKEN -->
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
            <h3>📧 Leads (alt)</h3>
            <div class="value"><?php echo $stats['total_leads']; ?></div>
            <div class="label"><?php echo $stats['confirmed_leads']; ?> bestätigt</div>
        </div>
    </div>
    
    <!-- 🆕 TOP CUSTOMERS MIT LEADS -->
    <?php if ($table_exists && count($top_customers) > 0): ?>
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">🏆 Top Kunden mit Lead Users</h2>
        </div>
        
        <div class="info-box">
            <strong>ℹ️ Datenschutz:</strong> Diese Übersicht zeigt nur aggregierte Daten pro Kunde. E-Mail-Adressen der Leads sind anonymisiert.
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kunde</th>
                        <th>Referral</th>
                        <th>Gesamt Leads</th>
                        <th>7 Tage</th>
                        <th>30 Tage</th>
                        <th>Empfehlungen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_customers as $customer): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                            <?php if ($customer['company_name']): ?>
                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($customer['company_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($customer['referral_enabled']): ?>
                                <span class="badge badge-success">Aktiv</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inaktiv</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo number_format($customer['total_leads'], 0, ',', '.'); ?></strong></td>
                        <td><?php echo number_format($customer['leads_7days'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($customer['leads_30days'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($customer['total_referrals'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- User-Übersicht (ALTES SYSTEM) -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">👥 User mit Empfehlungsaktivität (Legacy)</h2>
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
            <?php if ($table_exists && count($recent_lead_users) > 0): ?>
            <button class="tab active" onclick="switchTab('lead-users')">🚀 Lead Users (<?php echo count($recent_lead_users); ?>)</button>
            <?php endif; ?>
            <button class="tab <?php echo ($table_exists && count($recent_lead_users) > 0) ? '' : 'active'; ?>" onclick="switchTab('clicks')">Letzte Klicks (Legacy)</button>
            <button class="tab" onclick="switchTab('leads')">Letzte Leads (Legacy)</button>
        </div>
        
        <!-- 🆕 Lead Users Tab -->
        <?php if ($table_exists && count($recent_lead_users) > 0): ?>
        <div id="tab-lead-users" class="tab-content active">
            <div class="info-box">
                <strong>🔒 Datenschutz:</strong> E-Mail-Adressen sind anonymisiert (z.B. "abc***@***.de"). Für Support-Anfragen können Details im Einzelfall eingesehen werden.
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Registrierung</th>
                            <th>E-Mail (anonymisiert)</th>
                            <th>Kunde</th>
                            <th>Referral Code</th>
                            <th>Empfehlungen</th>
                            <th>Belohnungen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_lead_users as $lead): ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($lead['created_at'])); ?></td>
                            <td><code><?php echo htmlspecialchars($lead['email_masked']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lead['customer_name']); ?></strong>
                                <?php if ($lead['company_name']): ?>
                                <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($lead['company_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($lead['referral_code']); ?></code></td>
                            <td>
                                <span class="badge badge-info"><?php echo $lead['successful_referrals']; ?> / <?php echo $lead['total_referrals']; ?></span>
                            </td>
                            <td>
                                <?php if ($lead['rewards_earned'] > 0): ?>
                                    <span class="badge badge-success">🎁 <?php echo $lead['rewards_earned']; ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Klicks Tab -->
        <div id="tab-clicks" class="tab-content <?php echo ($table_exists && count($recent_lead_users) > 0) ? '' : 'active'; ?>">
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

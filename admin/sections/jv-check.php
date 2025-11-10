<?php
/**
 * JV Check Verifizierung über Digistore-Webhook
 * Prüft ob Partner-Provisionen korrekt eingerichtet sind
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert');
}

// Definiere erwartete Partner-Benutzernamen
$expectedPartners = ['fmd2039', 'magnodesign'];

// Webhook-Logs durchsuchen (falls vorhanden)
$webhookLogFile = __DIR__ . '/../../webhook/webhook-logs.txt';
$jvStats = [
    'total_sales' => 0,
    'jv_correct' => 0,
    'jv_missing' => 0,
    'customers_verified' => []
];

// Prüfe ob Webhook-Log-Datei existiert
if (file_exists($webhookLogFile)) {
    $logContent = file_get_contents($webhookLogFile);
    $logEntries = explode("\n", $logContent);
    
    foreach ($logEntries as $entry) {
        if (empty($entry)) continue;
        
        // Suche nach JSON-Daten im Log
        if (preg_match('/\{.*\}/', $entry, $matches)) {
            $data = json_decode($matches[0], true);
            
            if ($data && isset($data['event']) && $data['event'] === 'payment.success') {
                $jvStats['total_sales']++;
                
                $partnerUsername = $data['partner_username'] ?? null;
                $email = $data['buyer']['email'] ?? 'unknown';
                
                // Prüfe ob JV korrekt eingerichtet
                if ($partnerUsername && in_array($partnerUsername, $expectedPartners)) {
                    $jvStats['jv_correct']++;
                    $jvStats['customers_verified'][] = [
                        'email' => $email,
                        'partner' => $partnerUsername,
                        'status' => 'verified'
                    ];
                } else {
                    $jvStats['jv_missing']++;
                    $jvStats['customers_verified'][] = [
                        'email' => $email,
                        'partner' => $partnerUsername ?? 'nicht gesetzt',
                        'status' => 'missing'
                    ];
                }
            }
        }
    }
}

// Kunden aus Datenbank laden mit JV-Status
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.digistore_product_id,
        u.digistore_product_name,
        u.created_at,
        u.jv_partner_username,
        CASE 
            WHEN u.jv_partner_username IN ('fmd2039', 'magnodesign') THEN 'verified'
            WHEN u.jv_partner_username IS NULL THEN 'unknown'
            ELSE 'missing'
        END as jv_status
    FROM users u
    WHERE u.role = 'customer'
    ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll();

// Statistiken berechnen
$totalCustomers = count($customers);
$verifiedCount = count(array_filter($customers, fn($c) => $c['jv_status'] === 'verified'));
$missingCount = count(array_filter($customers, fn($c) => $c['jv_status'] === 'missing'));
$unknownCount = count(array_filter($customers, fn($c) => $c['jv_status'] === 'unknown'));
?>

<style>
.jv-container {
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.jv-info-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.jv-info-box h3 {
    margin: 0 0 16px 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.jv-info-box p {
    margin: 8px 0;
    opacity: 0.95;
    line-height: 1.6;
}

.expected-partners {
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 12px;
    font-family: 'Courier New', monospace;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 2px solid #e5e7eb;
}

.stat-card h4 {
    margin: 0 0 8px 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.stat-card .stat-value {
    font-size: 32px;
    font-weight: bold;
}

.stat-card.verified .stat-value {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-card.missing .stat-value {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-card.unknown .stat-value {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.customers-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.customers-table table {
    width: 100%;
    border-collapse: collapse;
}

.customers-table thead {
    background: #f9fafb;
}

.customers-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.customers-table td {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    font-size: 14px;
}

.jv-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.jv-badge.verified {
    background: #d1fae5;
    color: #065f46;
}

.jv-badge.missing {
    background: #fee2e2;
    color: #991b1b;
}

.jv-badge.unknown {
    background: #fef3c7;
    color: #92400e;
}

.partner-name {
    font-family: 'Courier New', monospace;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #e5e7eb;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.help-box {
    background: #f0f9ff;
    border-left: 4px solid #3b82f6;
    padding: 16px 20px;
    border-radius: 8px;
    margin: 24px 0;
    font-size: 14px;
    color: #1e40af;
}

.help-box strong {
    display: block;
    margin-bottom: 8px;
    color: #1e3a8a;
}

@media (max-width: 768px) {
    .jv-container {
        padding: 20px;
    }
    
    .customers-table {
        overflow-x: auto;
    }
}
</style>

<div class="jv-container">
    <!-- Info Box -->
    <div class="jv-info-box">
        <h3>
            <span style="font-size: 28px;">✅</span>
            JV Check Verifizierung über Digistore-Webhook (empfohlen)
        </h3>
        <p>
            Wenn dein Kunde das <strong>JV-Partnerprogramm (JointVenture)</strong> korrekt eingerichtet hat, 
            erscheint bei jedem Verkauf in der Webhook-Datenstruktur der Parameter:
        </p>
        <ul style="margin: 12px 0; padding-left: 20px; opacity: 0.95;">
            <li><code>partner_commission_amount</code></li>
            <li><code>partner_commission_percent</code></li>
            <li><code>partner_username</code></li>
        </ul>
        <p>
            Du kannst also in deinem Webhook-Skript prüfen, ob bei Verkäufen des Kunden 
            <code>partner_username</code> einer dieser Werte ist:
        </p>
        <div class="expected-partners">
            <?php foreach ($expectedPartners as $partner): ?>
                <div>✓ <?php echo htmlspecialchars($partner); ?></div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top: 12px;">
            <strong>✅ JV korrekt eingerichtet:</strong> partner_username stimmt überein<br>
            <strong>❌ JV fehlt:</strong> partner_username ist nicht gesetzt oder falsch
        </p>
    </div>
    
    <!-- Statistiken -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Gesamte Kunden</h4>
            <div class="stat-value"><?php echo $totalCustomers; ?></div>
        </div>
        <div class="stat-card verified">
            <h4>✅ JV Verifiziert</h4>
            <div class="stat-value"><?php echo $verifiedCount; ?></div>
        </div>
        <div class="stat-card missing">
            <h4>❌ JV Fehlt</h4>
            <div class="stat-value"><?php echo $missingCount; ?></div>
        </div>
        <div class="stat-card unknown">
            <h4>⚠️ Noch unbekannt</h4>
            <div class="stat-value"><?php echo $unknownCount; ?></div>
        </div>
    </div>
    
    <div class="help-box">
        <strong>📖 So funktioniert's:</strong>
        1. Das Webhook-Skript empfängt Digistore24-Daten<br>
        2. Es prüft automatisch den <code>partner_username</code> Parameter<br>
        3. Wenn der Partner-Username übereinstimmt, wird der Status als "Verifiziert" markiert<br>
        4. Die Tabelle unten zeigt den JV-Status für jeden Kunden an
    </div>
    
    <!-- Kunden-Tabelle -->
    <div class="customers-table">
        <?php if (empty($customers)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <p>Noch keine Kunden vorhanden</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Kunde</th>
                        <th>E-Mail</th>
                        <th>Produkt</th>
                        <th>Partner</th>
                        <th>JV Status</th>
                        <th>Registriert</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['digistore_product_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($customer['jv_partner_username']): ?>
                                    <span class="partner-name">
                                        <?php echo htmlspecialchars($customer['jv_partner_username']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">nicht gesetzt</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($customer['jv_status'] === 'verified'): ?>
                                    <span class="jv-badge verified">✅ Verifiziert</span>
                                <?php elseif ($customer['jv_status'] === 'missing'): ?>
                                    <span class="jv-badge missing">❌ Fehlt</span>
                                <?php else: ?>
                                    <span class="jv-badge unknown">⚠️ Unbekannt</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($customer['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

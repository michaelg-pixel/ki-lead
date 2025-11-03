<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * Zeigt Leads die über den Empfehlungslink des Kunden registriert wurden
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// Benutzer-Details laden
try {
    $stmt = $pdo->prepare("
        SELECT 
            referral_enabled,
            ref_code,
            company_name,
            company_email,
            company_imprint_html
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User nicht gefunden");
    }
    
    // KORRIGIERT: Leads dieses Kunden laden (nicht referral_leads)
    // Zähle alle Leads die mit diesem User verknüpft sind
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_leads,
            SUM(CASE WHEN referrer_code IS NOT NULL THEN 1 ELSE 0 END) as referred_leads,
            SUM(total_referrals) as total_referrals,
            SUM(successful_referrals) as successful_referrals
        FROM lead_users 
        WHERE user_id = ?
    ");
    $stmt_stats->execute([$customer_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Falls keine Stats existieren, initialisiere mit 0
    if (!$stats) {
        $stats = [
            'total_leads' => 0,
            'referred_leads' => 0,
            'total_referrals' => 0,
            'successful_referrals' => 0
        ];
    }
    
    // KORRIGIERT: Lead-Empfehlungen laden (Leads die über Links anderer Leads kamen)
    $stmt_referrals = $pdo->prepare("
        SELECT 
            lr.referred_name as name,
            lr.referred_email as email,
            lr.status,
            lr.invited_at,
            lu_referrer.name as referrer_name,
            lu_referrer.email as referrer_email
        FROM lead_referrals lr
        INNER JOIN lead_users lu_referrer ON lr.referrer_id = lu_referrer.id
        WHERE lu_referrer.user_id = ?
        ORDER BY lr.invited_at DESC
        LIMIT 50
    ");
    $stmt_referrals->execute([$customer_id]);
    $referrals = $stmt_referrals->fetchAll(PDO::FETCH_ASSOC);
    
    // KORRIGIERT: Direkt registrierte Leads (die sich direkt registriert haben, ohne Empfehlung)
    $stmt_direct_leads = $pdo->prepare("
        SELECT 
            name,
            email,
            'active' as status,
            registered_at as invited_at,
            NULL as referrer_name,
            NULL as referrer_email
        FROM lead_users
        WHERE user_id = ? AND referrer_code IS NULL
        ORDER BY registered_at DESC
        LIMIT 50
    ");
    $stmt_direct_leads->execute([$customer_id]);
    $direct_leads = $stmt_direct_leads->fetchAll(PDO::FETCH_ASSOC);
    
    // Kombiniere beide Listen
    $all_leads = array_merge($referrals, $direct_leads);
    
    // Sortiere nach Datum
    usort($all_leads, function($a, $b) {
        return strtotime($b['invited_at']) - strtotime($a['invited_at']);
    });
    
    // Freebies laden - für Link-Generierung
    $stmt_freebies = $pdo->prepare("
        SELECT DISTINCT
            f.id,
            f.unique_id,
            f.name as title,
            f.description,
            f.mockup_image_url as image_path,
            f.user_id,
            f.created_at,
            'own' as freebie_type
        FROM freebies f
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt_freebies->execute([$customer_id]);
    $freebies = $stmt_freebies->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktivitäts-Chart-Daten - Letzte 7 Tage
    $stmt_chart = $pdo->prepare("
        SELECT 
            DATE(registered_at) as date,
            COUNT(*) as count
        FROM lead_users
        WHERE user_id = ?
            AND registered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(registered_at)
        ORDER BY date ASC
    ");
    $stmt_chart->execute([$customer_id]);
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Empfehlungsprogramm Error: " . $e->getMessage());
    $user = [
        'referral_enabled' => 0,
        'ref_code' => '',
        'company_name' => '',
        'company_email' => '',
        'company_imprint_html' => ''
    ];
    $stats = [
        'total_leads' => 0,
        'referred_leads' => 0,
        'total_referrals' => 0,
        'successful_referrals' => 0
    ];
    $all_leads = [];
    $freebies = [];
    $chart_data = [];
}

$referralEnabled = $user['referral_enabled'] ?? 0;
$referralCode = $user['ref_code'] ?? '';
$companyName = $user['company_name'] ?? '';
$companyEmail = $user['company_email'] ?? '';
$companyImprint = $user['company_imprint_html'] ?? '';

// Basis-URL für Referral-Links
$baseUrl = 'https://app.mehr-infos-jetzt.de';

// Chart-Daten vorbereiten
$chart_labels = [];
$chart_registrations = [];

// Alle 7 Tage abdecken
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d.m', strtotime($date));
    $chart_labels[] = $label;
    
    // Finde Registrierungen für dieses Datum
    $count = 0;
    foreach ($chart_data as $data) {
        if ($data['date'] === $date) {
            $count = $data['count'];
            break;
        }
    }
    $chart_registrations[] = $count;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #374151;
            border-radius: 30px;
            transition: 0.3s;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        input:checked + .toggle-slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .freebie-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .freebie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
            border-color: rgba(102, 126, 234, 0.6);
        }
        
        .freebie-card.selected {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }
        
        .section-title {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .section-text {
            color: #9ca3af;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(0, 0, 0, 0.2);
            padding: 12px;
            text-align: left;
            color: #9ca3af;
            font-weight: 600;
            font-size: 13px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        tbody tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-badge.pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        /* Mobile Responsive */
        @media (max-width: 640px) {
            .page-title {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(to bottom right, #1f2937, #111827, #1f2937); min-height: 100vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 1rem;">
        
        <!-- Header -->
        <div class="animate-fade-in-up" style="opacity: 0; margin-bottom: 1.5rem;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div style="flex: 1; min-width: 200px;">
                        <h1 class="page-title">
                            <i class="fas fa-rocket"></i> Empfehlungsprogramm
                        </h1>
                        <p class="page-subtitle">
                            Deine Leads und Empfehlungslinks
                        </p>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="color: white; font-weight: 600; font-size: 0.875rem;">
                            <?php echo $referralEnabled ? 'Aktiviert' : 'Deaktiviert'; ?>
                        </span>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                   id="referralToggle" 
                                   <?php echo $referralEnabled ? 'checked' : ''; ?>
                                   onchange="toggleReferralProgram(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiken -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <!-- Gesamt Leads -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; padding: 0.625rem;">
                        <i class="fas fa-users" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                <div style="color: white;">
                    <div class="stat-value">
                        <?php echo number_format($stats['total_leads']); ?>
                    </div>
                    <div class="stat-label">
                        Gesamt Leads
                    </div>
                </div>
            </div>
            
            <!-- Über Empfehlung -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; padding: 0.625rem;">
                        <i class="fas fa-link" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                <div style="color: white;">
                    <div class="stat-value">
                        <?php echo number_format($stats['referred_leads']); ?>
                    </div>
                    <div class="stat-label">
                        Über Empfehlung
                    </div>
                </div>
            </div>
            
            <!-- Empfehlungen der Leads -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-between; margin-bottom: 0.75rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem; padding: 0.625rem;">
                        <i class="fas fa-share-alt" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                <div style="color: white;">
                    <div class="stat-value">
                        <?php echo number_format($stats['successful_referrals']); ?>
                    </div>
                    <div class="stat-label">
                        Lead-Empfehlungen
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empfehlungslink -->
        <?php if ($referralEnabled): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s; margin-bottom: 1.5rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 class="section-title">
                    <i class="fas fa-link"></i> Dein Empfehlungslink
                </h3>
                <p class="section-text">
                    Teile diesen Link und erhalte neue Leads
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <input type="text" 
                           id="referralLinkInput" 
                           value="<?php echo $baseUrl; ?>/lead_login.php?ref=<?php echo htmlspecialchars($referralCode); ?>"
                           readonly
                           style="flex: 1; min-width: 200px; padding: 0.625rem 0.875rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white; font-family: monospace; font-size: 0.8125rem;">
                    <button onclick="copyReferralLink()" 
                            style="padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                        <i class="fas fa-copy"></i>
                        <span id="copyButtonText">Kopieren</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Aktivitätsgraph -->
        <?php if (!empty($chart_data)): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.5s; margin-bottom: 1.5rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i> Lead-Registrierungen (Letzte 7 Tage)
                </h3>
                <div style="height: 250px;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Leads-Liste -->
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.6s;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 class="section-title">
                    <i class="fas fa-users"></i> Deine Leads (<?php echo count($all_leads); ?>)
                </h3>
                
                <?php if (empty($all_leads)): ?>
                <div style="text-align: center; padding: 3rem 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                    <div style="font-size: 3rem; color: #374151; margin-bottom: 1rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h4 style="color: white; font-size: 1.125rem; margin-bottom: 0.5rem;">
                        Noch keine Leads
                    </h4>
                    <p style="color: #9ca3af; font-size: 0.875rem;">
                        Teile deinen Empfehlungslink um Leads zu generieren
                    </p>
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Status</th>
                                <th>Empfohlen von</th>
                                <th>Registriert am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_leads as $lead): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $lead['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Ausstehend',
                                            'active' => 'Aktiv',
                                            'converted' => 'Konvertiert'
                                        ];
                                        echo $status_labels[$lead['status']] ?? 'Aktiv';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($lead['referrer_name']): ?>
                                        <span style="color: #10b981;">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($lead['referrer_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6b7280;">
                                            <i class="fas fa-minus"></i> Direkt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($lead['invited_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Empfehlungsprogramm aktivieren/deaktivieren
        function toggleReferralProgram(enabled) {
            fetch('/api/referral/toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    enabled: enabled
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(
                        enabled ? 'Empfehlungsprogramm aktiviert!' : 'Empfehlungsprogramm deaktiviert',
                        'success'
                    );
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Fehler: ' + (data.message || 'Unbekannter Fehler'), 'error');
                    document.getElementById('referralToggle').checked = !enabled;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
                document.getElementById('referralToggle').checked = !enabled;
            });
        }
        
        // Link kopieren
        function copyReferralLink() {
            const input = document.getElementById('referralLinkInput');
            const button = document.getElementById('copyButtonText');
            
            input.select();
            input.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(input.value).then(() => {
                button.textContent = 'Kopiert!';
                setTimeout(() => {
                    button.textContent = 'Kopieren';
                }, 2000);
                showNotification('Link in Zwischenablage kopiert!', 'success');
            }).catch(err => {
                console.error('Kopieren fehlgeschlagen:', err);
                showNotification('Kopieren fehlgeschlagen', 'error');
            });
        }
        
        // Notification anzeigen
        function showNotification(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 0.875rem 1.25rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                max-width: 90%;
                font-size: 0.875rem;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Chart initialisieren
        <?php if (!empty($chart_data)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Registrierungen',
                            data: <?php echo json_encode($chart_registrations); ?>,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#9ca3af',
                                font: {
                                    size: window.innerWidth < 640 ? 10 : 12
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9ca3af',
                                font: {
                                    size: window.innerWidth < 640 ? 9 : 11
                                }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#9ca3af',
                                font: {
                                    size: window.innerWidth < 640 ? 9 : 11
                                }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        });
        <?php endif; ?>
        
        // Animation Styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

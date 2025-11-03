<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * Verwendet bestehende referral_stats Tabelle und users Tabelle
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
    
    // Statistiken aus referral_stats laden
    $stmt_stats = $pdo->prepare("
        SELECT 
            total_clicks,
            unique_clicks,
            total_conversions,
            suspicious_conversions,
            total_leads,
            confirmed_leads,
            conversion_rate,
            last_click_at,
            last_conversion_at
        FROM referral_stats 
        WHERE customer_id = ?
    ");
    $stmt_stats->execute([$customer_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Falls keine Stats existieren, initialisiere mit 0
    if (!$stats) {
        $stats = [
            'total_clicks' => 0,
            'unique_clicks' => 0,
            'total_conversions' => 0,
            'suspicious_conversions' => 0,
            'total_leads' => 0,
            'confirmed_leads' => 0,
            'conversion_rate' => 0.00,
            'last_click_at' => null,
            'last_conversion_at' => null
        ];
    }
    
    // Letzte 7 Tage Aktivität für Chart
    $stmt_clicks_chart = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM referral_clicks
        WHERE user_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt_clicks_chart->execute([$customer_id]);
    $clicks_chart_data = $stmt_clicks_chart->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_conv_chart = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM referral_conversions
        WHERE user_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt_conv_chart->execute([$customer_id]);
    $conv_chart_data = $stmt_conv_chart->fetchAll(PDO::FETCH_ASSOC);
    
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
        'total_clicks' => 0,
        'unique_clicks' => 0,
        'total_conversions' => 0,
        'suspicious_conversions' => 0,
        'total_leads' => 0,
        'confirmed_leads' => 0,
        'conversion_rate' => 0.00,
        'last_click_at' => null,
        'last_conversion_at' => null
    ];
    $clicks_chart_data = [];
    $conv_chart_data = [];
}

$referralEnabled = $user['referral_enabled'] ?? 0;
$referralCode = $user['ref_code'] ?? '';
$companyName = $user['company_name'] ?? '';
$companyEmail = $user['company_email'] ?? '';
$companyImprint = $user['company_imprint_html'] ?? '';

// Referral-Link generieren
$baseUrl = 'https://app.mehr-infos-jetzt.de';
$referralLink = $referralCode ? $baseUrl . '/f/index.php?ref=' . $referralCode : '';

// Chart-Daten vorbereiten
$chart_labels = [];
$chart_clicks = [];
$chart_conversions = [];

// Alle 7 Tage abdecken
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d.m', strtotime($date));
    $chart_labels[] = $label;
    
    // Finde clicks für dieses Datum
    $clicks = 0;
    foreach ($clicks_chart_data as $data) {
        if ($data['date'] === $date) {
            $clicks = $data['count'];
            break;
        }
    }
    $chart_clicks[] = $clicks;
    
    // Finde conversions für dieses Datum
    $conversions = 0;
    foreach ($conv_chart_data as $data) {
        if ($data['date'] === $date) {
            $conversions = $data['count'];
            break;
        }
    }
    $chart_conversions[] = $conversions;
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
    </style>
</head>
<body style="background: linear-gradient(to bottom right, #1f2937, #111827, #1f2937); min-height: 100vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 2rem 1rem;">
        
        <!-- Header -->
        <div class="animate-fade-in-up" style="opacity: 0; margin-bottom: 2rem;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1rem; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                            <i class="fas fa-rocket"></i> Empfehlungsprogramm
                        </h1>
                        <p style="color: rgba(255, 255, 255, 0.9);">
                            Teile deine Freebies und verdiene automatisch Provisionen
                        </p>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: white; font-weight: 600;">
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Klicks -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-between; margin-bottom: 1rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.75rem; padding: 0.75rem;">
                        <i class="fas fa-mouse-pointer" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <span style="color: rgba(255, 255, 255, 0.7); font-size: 0.75rem;"><?php echo number_format($stats['unique_clicks']); ?> unique</span>
                </div>
                <div style="color: white;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_clicks']); ?>
                    </div>
                    <div style="color: rgba(255, 255, 255, 0.8);">
                        Link-Klicks gesamt
                    </div>
                </div>
            </div>
            
            <!-- Conversions -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-between; margin-bottom: 1rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.75rem; padding: 0.75rem;">
                        <i class="fas fa-check-circle" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <?php if ($stats['suspicious_conversions'] > 0): ?>
                    <span style="color: rgba(255, 255, 255, 0.7); font-size: 0.75rem;">⚠️ <?php echo $stats['suspicious_conversions']; ?> verdächtig</span>
                    <?php endif; ?>
                </div>
                <div style="color: white;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_conversions']); ?>
                    </div>
                    <div style="color: rgba(255, 255, 255, 0.8);">
                        Conversions
                    </div>
                </div>
            </div>
            
            <!-- Leads -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-between; margin-bottom: 1rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.75rem; padding: 0.75rem;">
                        <i class="fas fa-users" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                    <span style="color: rgba(255, 255, 255, 0.7); font-size: 0.75rem;"><?php echo number_format($stats['confirmed_leads']); ?> bestätigt</span>
                </div>
                <div style="color: white;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['total_leads']); ?>
                    </div>
                    <div style="color: rgba(255, 255, 255, 0.8);">
                        Registrierte Leads
                    </div>
                </div>
            </div>
            
            <!-- Conversion Rate -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-between; margin-bottom: 1rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); border-radius: 0.75rem; padding: 0.75rem;">
                        <i class="fas fa-percentage" style="color: white; font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div style="color: white;">
                    <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?php echo number_format($stats['conversion_rate'], 2); ?>%
                    </div>
                    <div style="color: rgba(255, 255, 255, 0.8);">
                        Conversion Rate
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Link -->
        <?php if ($referralEnabled && $referralLink): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.5s; margin-bottom: 2rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                    <i class="fas fa-link"></i> Dein Empfehlungslink
                </h3>
                <p style="color: #9ca3af; margin-bottom: 1rem; font-size: 0.875rem;">
                    Teile diesen Link und erhalte automatisch Provisionen für jeden generierten Lead
                </p>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <input type="text" 
                           id="referralLinkInput" 
                           value="<?php echo htmlspecialchars($referralLink); ?>" 
                           readonly
                           style="flex: 1; min-width: 300px; padding: 0.75rem 1rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white; font-family: monospace;">
                    <button onclick="copyReferralLink()" 
                            style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-copy"></i>
                        <span id="copyButtonText">Kopieren</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Aktivitätsgraph -->
        <?php if (!empty($clicks_chart_data) || !empty($conv_chart_data)): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.6s; margin-bottom: 2rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                    <i class="fas fa-chart-line"></i> Aktivität (Letzte 7 Tage)
                </h3>
                <div style="height: 300px;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Firmendaten -->
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.7s;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                    <i class="fas fa-building"></i> Firmendaten
                </h3>
                <p style="color: #9ca3af; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    Diese Daten werden in E-Mails an deine Leads verwendet (für Impressum)
                </p>
                
                <form id="companyForm" onsubmit="saveCompanyData(event)">
                    <div style="display: grid; gap: 1rem;">
                        <div>
                            <label style="display: block; color: #9ca3af; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500;">
                                Firmenname *
                            </label>
                            <input type="text" 
                                   name="company_name" 
                                   value="<?php echo htmlspecialchars($companyName); ?>"
                                   required
                                   style="width: 100%; padding: 0.75rem 1rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white;">
                        </div>
                        
                        <div>
                            <label style="display: block; color: #9ca3af; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500;">
                                E-Mail-Adresse *
                            </label>
                            <input type="email" 
                                   name="company_email" 
                                   value="<?php echo htmlspecialchars($companyEmail); ?>"
                                   required
                                   style="width: 100%; padding: 0.75rem 1rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white;">
                        </div>
                        
                        <div>
                            <label style="display: block; color: #9ca3af; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500;">
                                Impressum (HTML erlaubt) *
                            </label>
                            <textarea name="company_imprint_html" 
                                      rows="6"
                                      required
                                      placeholder="z.B.: Max Mustermann<br>Musterstraße 1<br>12345 Musterstadt<br>Deutschland<br>UID: DE123456789"
                                      style="width: 100%; padding: 0.75rem 1rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white; resize: vertical; font-family: monospace; font-size: 0.875rem;"><?php echo htmlspecialchars($companyImprint); ?></textarea>
                            <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem;">
                                HTML-Tags erlaubt: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;b&gt;, &lt;em&gt;, &lt;i&gt;
                            </p>
                        </div>
                        
                        <button type="submit" 
                                style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-save"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.8s; margin-top: 2rem;">
            <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.1)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 1rem; padding: 1.5rem;">
                <div style="display: flex; gap: 1rem;">
                    <div style="color: #3b82f6; font-size: 1.5rem;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h4 style="color: white; font-weight: 600; margin-bottom: 0.5rem;">
                            So funktioniert's
                        </h4>
                        <ul style="color: #9ca3af; list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.5rem;">✓ Aktiviere das Programm oben</li>
                            <li style="margin-bottom: 0.5rem;">✓ Fülle deine Firmendaten aus</li>
                            <li style="margin-bottom: 0.5rem;">✓ Kopiere deinen Empfehlungslink</li>
                            <li style="margin-bottom: 0.5rem;">✓ Teile den Link per E-Mail, Social Media oder auf deiner Website</li>
                            <li style="margin-bottom: 0.5rem;">✓ Verdiene automatisch für jeden registrierten Lead</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let referralEnabled = <?php echo $referralEnabled ? 'true' : 'false'; ?>;
        let referralCode = '<?php echo $referralCode; ?>';
        
        // Toggle Empfehlungsprogramm
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
        
        // Referral-Link kopieren
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
        
        // Firmendaten speichern
        function saveCompanyData(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                company_name: formData.get('company_name'),
                company_email: formData.get('company_email'),
                company_imprint_html: formData.get('company_imprint_html')
            };
            
            fetch('/api/referral/update-company.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Firmendaten erfolgreich gespeichert!', 'success');
                } else {
                    showNotification('Fehler: ' + (data.message || 'Unbekannter Fehler'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
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
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Chart initialisieren
        <?php if (!empty($clicks_chart_data) || !empty($conv_chart_data)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Klicks',
                            data: <?php echo json_encode($chart_clicks); ?>,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Conversions',
                            data: <?php echo json_encode($chart_conversions); ?>,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
                                color: '#9ca3af'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#9ca3af'
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
        });
        <?php endif; ?>
        
        // Animation Styles hinzufügen
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
<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * Zeigt Freebie-Auswahl, korrekten Referral-Link und konfigurierte Belohnungen
 * FIXED: Lädt nun auch freigeschaltete Template-Freebies aus customer_freebies
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
    
    // Statistiken
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
    
    if (!$stats) {
        $stats = [
            'total_leads' => 0,
            'referred_leads' => 0,
            'total_referrals' => 0,
            'successful_referrals' => 0
        ];
    }
    
    // Lead-Empfehlungen laden
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
    
    // Direkt registrierte Leads
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
    usort($all_leads, function($a, $b) {
        return strtotime($b['invited_at']) - strtotime($a['invited_at']);
    });
    
    // FREEBIES LADEN - KOMBINIERT EIGENE UND FREIGESCHALTETE TEMPLATE-FREEBIES
    // 1. Eigene Freebies (custom type aus customer_freebies)
    $stmt_custom = $pdo->prepare("
        SELECT 
            cf.id as customer_freebie_id,
            cf.unique_id,
            cf.headline as title,
            cf.subheadline as description,
            cf.mockup_image_url as image_path,
            cf.created_at,
            'custom' as freebie_source
        FROM customer_freebies cf
        WHERE cf.customer_id = ? 
        AND cf.freebie_type = 'custom'
    ");
    $stmt_custom->execute([$customer_id]);
    $custom_freebies = $stmt_custom->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Freigeschaltete Template-Freebies (aus customer_freebies mit template_id)
    $stmt_templates = $pdo->prepare("
        SELECT DISTINCT
            cf.id as customer_freebie_id,
            cf.unique_id,
            COALESCE(cf.headline, f.headline, f.name) as title,
            COALESCE(cf.subheadline, f.subheadline) as description,
            COALESCE(cf.mockup_image_url, f.mockup_image_url) as image_path,
            cf.created_at,
            'template' as freebie_source,
            f.id as template_id
        FROM customer_freebies cf
        INNER JOIN freebies f ON cf.template_id = f.id
        WHERE cf.customer_id = ?
        AND (cf.freebie_type = 'template' OR cf.freebie_type IS NULL)
        AND cf.template_id IS NOT NULL
    ");
    $stmt_templates->execute([$customer_id]);
    $template_freebies = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);
    
    // Kombiniere beide Listen
    $freebies = array_merge($custom_freebies, $template_freebies);
    
    // Nach Datum sortieren (neueste zuerst)
    usort($freebies, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Belohnungen laden (die der Kunde konfiguriert hat)
    $stmt_rewards = $pdo->prepare("
        SELECT 
            id,
            tier_level,
            tier_name,
            tier_description,
            required_referrals,
            reward_type,
            reward_title,
            reward_description,
            reward_icon,
            reward_color,
            reward_value,
            is_active
        FROM reward_definitions 
        WHERE user_id = ?
        ORDER BY tier_level ASC
    ");
    $stmt_rewards->execute([$customer_id]);
    $rewards = $stmt_rewards->fetchAll(PDO::FETCH_ASSOC);
    
    // Chart-Daten
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
    $rewards = [];
    $chart_data = [];
}

$referralEnabled = $user['referral_enabled'] ?? 0;
$referralCode = $user['ref_code'] ?? '';

// Basis-URL
$baseUrl = 'https://app.mehr-infos-jetzt.de';

// Chart-Daten vorbereiten
$chart_labels = [];
$chart_registrations = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d.m', strtotime($date));
    $chart_labels[] = $label;
    
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
        
        .freebie-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .badge-custom {
            background: rgba(251, 191, 36, 0.95);
            color: white;
        }
        
        .badge-template {
            background: rgba(59, 130, 246, 0.95);
            color: white;
        }
        
        .reward-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .reward-card .icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.2);
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
                            Wähle ein Freebie und teile deinen Empfehlungslink
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
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo number_format($stats['total_leads']); ?></div>
                    <div class="stat-label">Gesamt Leads</div>
                </div>
            </div>
            
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo number_format($stats['referred_leads']); ?></div>
                    <div class="stat-label">Über Empfehlung</div>
                </div>
            </div>
            
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo number_format($stats['successful_referrals']); ?></div>
                    <div class="stat-label">Lead-Empfehlungen</div>
                </div>
            </div>
        </div>
        
        <!-- Freebie Auswahl -->
        <?php if ($referralEnabled): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s; margin-bottom: 1.5rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 class="section-title">
                    <i class="fas fa-gift"></i> Wähle dein Freebie
                </h3>
                <p class="section-text">
                    Wähle ein Freebie aus, das du über dein Empfehlungsprogramm teilen möchtest
                </p>
                
                <?php if (empty($freebies)): ?>
                <div style="text-align: center; padding: 3rem 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem;">
                    <div style="font-size: 3rem; color: #374151; margin-bottom: 1rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h4 style="color: white; font-size: 1.125rem; margin-bottom: 0.5rem;">
                        Keine Freebies verfügbar
                    </h4>
                    <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1.5rem;">
                        Du hast noch keine Freebies freigeschaltet oder erstellt
                    </p>
                    <a href="?page=freebies" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600;">
                        <i class="fas fa-plus"></i> Freebie freischalten
                    </a>
                </div>
                <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                    <?php foreach ($freebies as $freebie): ?>
                    <div class="freebie-card" 
                         data-freebie-unique-id="<?php echo htmlspecialchars($freebie['unique_id']); ?>"
                         onclick="selectFreebie('<?php echo htmlspecialchars($freebie['unique_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freebie['title'], ENT_QUOTES); ?>')">
                        
                        <span class="freebie-badge <?php echo $freebie['freebie_source'] === 'custom' ? 'badge-custom' : 'badge-template'; ?>">
                            <?php echo $freebie['freebie_source'] === 'custom' ? '✨ Eigenes' : '📚 Template'; ?>
                        </span>
                        
                        <?php if (!empty($freebie['image_path'])): ?>
                        <div style="width: 100%; height: 120px; border-radius: 0.5rem; overflow: hidden; margin-bottom: 1rem; background: #111827;">
                            <img src="<?php echo htmlspecialchars($freebie['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($freebie['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        
                        <h4 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($freebie['title']); ?>
                        </h4>
                        
                        <?php if (!empty($freebie['description'])): ?>
                        <p style="color: #9ca3af; font-size: 0.8125rem; line-height: 1.5; margin-bottom: 0.75rem;">
                            <?php echo htmlspecialchars(substr($freebie['description'], 0, 100)) . (strlen($freebie['description']) > 100 ? '...' : ''); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div style="display: flex; align-items: center; justify-content: center; padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 0.5rem; display: none;" data-check-icon></i>
                            <span style="color: #667eea; font-weight: 600; font-size: 0.875rem;">
                                Auswählen
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Empfehlungslink -->
        <div id="referralLinkSection" style="display: none;" class="animate-fade-in-up">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3); margin-bottom: 1.5rem;">
                <h3 class="section-title">
                    <i class="fas fa-link"></i> Dein Empfehlungslink
                </h3>
                <p class="section-text">
                    Teile diesen Link für das ausgewählte Freebie: <strong id="selectedFreebieTitle" style="color: #667eea;"></strong>
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                    <input type="text" 
                           id="referralLinkInput" 
                           readonly
                           style="flex: 1; min-width: 200px; padding: 0.625rem 0.875rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: white; font-family: monospace; font-size: 0.8125rem;">
                    <button onclick="copyReferralLink()" 
                            style="padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                        <i class="fas fa-copy"></i>
                        <span id="copyButtonText">Kopieren</span>
                    </button>
                </div>
                <a href="?page=belohnungsstufen" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem;">
                    <i class="fas fa-trophy"></i>
                    Belohnungen konfigurieren
                </a>
            </div>
        </div>
        
        <!-- Belohnungen -->
        <?php if (!empty($rewards)): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.5s; margin-bottom: 1.5rem;">
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <h3 class="section-title">
                    <i class="fas fa-trophy"></i> Deine konfigurierten Belohnungen (<?php echo count($rewards); ?>)
                </h3>
                <?php foreach ($rewards as $reward): 
                    $icon_class = $reward['reward_icon'] ?? 'fa-gift';
                    $color = $reward['reward_color'] ?? '#667eea';
                ?>
                <div class="reward-card">
                    <div class="icon" style="color: <?php echo $color; ?>;">
                        <i class="fas <?php echo $icon_class; ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="color: <?php echo $color; ?>; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($reward['tier_name']); ?> - Stufe <?php echo $reward['tier_level']; ?>
                        </div>
                        <h4 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($reward['reward_title']); ?>
                        </h4>
                        <?php if ($reward['reward_description']): ?>
                        <p style="color: #9ca3af; font-size: 0.8125rem; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($reward['reward_description']); ?>
                        </p>
                        <?php endif; ?>
                        <div style="color: #10b981; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-check-circle"></i> <?php echo $reward['required_referrals']; ?> Empfehlungen benötigt
                            <?php if ($reward['reward_value']): ?>
                                • <?php echo htmlspecialchars($reward['reward_value']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$reward['is_active']): ?>
                    <div style="padding: 0.5rem 1rem; background: rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600;">
                        Inaktiv
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <!-- Aktivitätsgraph -->
        <?php if (!empty($chart_data)): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.6s; margin-bottom: 1.5rem;">
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
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.7s;">
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
        let selectedFreebieUniqueId = null;
        let referralCode = '<?php echo $referralCode; ?>';
        let baseUrl = '<?php echo $baseUrl; ?>';
        
        // Freebie auswählen
        function selectFreebie(freebieUniqueId, freebieTitle) {
            selectedFreebieUniqueId = freebieUniqueId;
            
            // Alle Karten deselektieren
            document.querySelectorAll('.freebie-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('[data-check-icon]').style.display = 'none';
            });
            
            // Ausgewählte Karte markieren
            const selectedCard = document.querySelector(`.freebie-card[data-freebie-unique-id="${freebieUniqueId}"]`);
            selectedCard.classList.add('selected');
            selectedCard.querySelector('[data-check-icon]').style.display = 'inline';
            
            // Korrekter Freebie-Link
            const referralLink = `${baseUrl}/freebie/index.php?id=${freebieUniqueId}&ref=${referralCode}`;
            document.getElementById('referralLinkInput').value = referralLink;
            document.getElementById('selectedFreebieTitle').textContent = freebieTitle;
            document.getElementById('referralLinkSection').style.display = 'block';
            
            showNotification(`Freebie "${freebieTitle}" ausgewählt!`, 'success');
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
        
        // Toggle Programm
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
        
        // Notification
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
        
        // Chart
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
        
        // Animations
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

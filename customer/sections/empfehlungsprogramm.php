<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * KOMPLETT ÜBERARBEITET mit Slots-Verwaltung und Admin-Hinweisen
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
    
    // EMPFEHLUNGS-SLOTS LADEN
    $stmt_slots = $pdo->prepare("
        SELECT 
            total_slots,
            used_slots,
            product_name,
            source,
            updated_at
        FROM customer_referral_slots 
        WHERE customer_id = ?
    ");
    $stmt_slots->execute([$customer_id]);
    $slots_data = $stmt_slots->fetch(PDO::FETCH_ASSOC);
    
    if (!$slots_data) {
        // Fallback wenn keine Slots-Daten vorhanden
        $slots_data = [
            'total_slots' => 0,
            'used_slots' => 0,
            'product_name' => 'Nicht zugewiesen',
            'source' => 'webhook',
            'updated_at' => null
        ];
    }
    
    $total_slots = (int)$slots_data['total_slots'];
    $used_slots = (int)$slots_data['used_slots'];
    $available_slots = max(0, $total_slots - $used_slots);
    $slots_source = $slots_data['source'];
    $product_name = $slots_data['product_name'] ?? 'Nicht zugewiesen';
    
    // FREEBIE-LIMIT LADEN
    $stmt_freebie_limit = $pdo->prepare("
        SELECT 
            freebie_limit,
            product_name as freebie_product_name,
            source as freebie_source
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt_freebie_limit->execute([$customer_id]);
    $freebie_limit_data = $stmt_freebie_limit->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie_limit_data) {
        $freebie_limit = (int)$freebie_limit_data['freebie_limit'];
        $freebie_source = $freebie_limit_data['freebie_source'];
        $freebie_product_name = $freebie_limit_data['freebie_product_name'] ?? 'Nicht zugewiesen';
    } else {
        $freebie_limit = 0;
        $freebie_source = 'webhook';
        $freebie_product_name = 'Nicht zugewiesen';
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
    
    // FREEBIES LADEN MIT BELOHNUNGS-COUNT
    // 1. Eigene Freebies (custom type aus customer_freebies)
    $stmt_custom = $pdo->prepare("
        SELECT 
            cf.id as customer_freebie_id,
            cf.unique_id,
            cf.headline as title,
            cf.subheadline as description,
            cf.mockup_image_url as image_path,
            cf.created_at,
            'custom' as freebie_source,
            (SELECT COUNT(*) FROM reward_definitions WHERE freebie_id = cf.id AND user_id = ?) as reward_count
        FROM customer_freebies cf
        WHERE cf.customer_id = ? 
        AND cf.freebie_type = 'custom'
    ");
    $stmt_custom->execute([$customer_id, $customer_id]);
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
            f.id as template_id,
            (SELECT COUNT(*) FROM reward_definitions WHERE freebie_id = cf.id AND user_id = ?) as reward_count
        FROM customer_freebies cf
        INNER JOIN freebies f ON cf.template_id = f.id
        WHERE cf.customer_id = ?
        AND (cf.freebie_type = 'template' OR cf.freebie_type IS NULL)
        AND cf.template_id IS NOT NULL
    ");
    $stmt_templates->execute([$customer_id, $customer_id]);
    $template_freebies = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);
    
    // Kombiniere beide Listen
    $freebies = array_merge($custom_freebies, $template_freebies);
    
    // Nach Datum sortieren (neueste zuerst)
    usort($freebies, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Anzahl erstellter Freebies zählen
    $stmt_freebie_count = $pdo->prepare("
        SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?
    ");
    $stmt_freebie_count->execute([$customer_id]);
    $freebies_created = (int)$stmt_freebie_count->fetchColumn();
    
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
    $chart_data = [];
    $total_slots = 0;
    $used_slots = 0;
    $available_slots = 0;
    $slots_source = 'webhook';
    $product_name = 'Nicht zugewiesen';
    $freebie_limit = 0;
    $freebies_created = 0;
    $freebie_source = 'webhook';
    $freebie_product_name = 'Nicht zugewiesen';
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
        
        .admin-notice {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: 2px solid #fbbf24;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
        }
        
        .admin-notice-title {
            color: white;
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-notice-content {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        
        .limits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .limit-box {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 1rem;
        }
        
        .limit-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .limit-value {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
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
        
        .freebie-card-expanded {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
        }
        
        .freebie-header {
            display: flex;
            gap: 1.5rem;
            align-items: start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .freebie-image {
            width: 150px;
            height: 150px;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #111827;
            flex-shrink: 0;
        }
        
        .freebie-info {
            flex: 1;
            min-width: 250px;
        }
        
        .freebie-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .badge-custom {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        .badge-template {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .freebie-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .action-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }
        
        .action-label {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .link-input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .link-input {
            flex: 1;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            padding: 0.5rem;
            font-size: 0.75rem;
            font-family: monospace;
        }
        
        .btn-copy {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .btn-rewards {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .btn-rewards:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.5);
        }
        
        .reward-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
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
            .freebie-header {
                flex-direction: column;
            }
            .freebie-image {
                width: 100%;
                height: 200px;
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
                            Verwalte deine Freebies und Empfehlungslinks
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
        
        <!-- Admin-Hinweis wenn Limits manuell gesetzt wurden -->
        <?php if ($slots_source === 'manual' || $freebie_source === 'manual'): ?>
        <div class="animate-fade-in-up admin-notice" style="opacity: 0; animation-delay: 0.05s;">
            <div class="admin-notice-title">
                <i class="fas fa-user-shield"></i> Hinweis: Limits vom Administrator angepasst
            </div>
            <div class="admin-notice-content">
                <p style="margin-bottom: 0.75rem;">
                    Deine Limits wurden manuell vom Administrator angepasst und werden nicht automatisch durch Tarif-Upgrades überschrieben.
                </p>
                <div class="limits-grid">
                    <?php if ($freebie_source === 'manual'): ?>
                    <div class="limit-box">
                        <div class="limit-label">🎁 Freebie-Limit</div>
                        <div class="limit-value"><?php echo $freebies_created; ?> / <?php echo $freebie_limit; ?></div>
                        <small style="color: rgba(255,255,255,0.7); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                            Manuell vom Admin gesetzt
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($slots_source === 'manual'): ?>
                    <div class="limit-box">
                        <div class="limit-label">🚀 Empfehlungs-Slots</div>
                        <div class="limit-value"><?php echo $used_slots; ?> / <?php echo $total_slots; ?></div>
                        <small style="color: rgba(255,255,255,0.7); font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                            Manuell vom Admin gesetzt • <?php echo $available_slots; ?> verfügbar
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistiken -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo number_format((int)($stats['total_leads'] ?? 0)); ?></div>
                    <div class="stat-label">Gesamt Leads</div>
                </div>
            </div>
            
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo $used_slots; ?> / <?php echo $total_slots; ?></div>
                    <div class="stat-label">Empfehlungs-Slots genutzt</div>
                </div>
            </div>
            
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                <div style="color: white;">
                    <div class="stat-value"><?php echo $available_slots; ?></div>
                    <div class="stat-label">Verfügbare Slots</div>
                </div>
            </div>
        </div>
        
        <!-- Freebies mit individuellen Links und Belohnungen -->
        <?php if ($referralEnabled): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s;">
            <h3 class="section-title">
                <i class="fas fa-gift"></i> Deine Freebies (<?php echo count($freebies); ?>)
            </h3>
            
            <?php if (empty($freebies)): ?>
            <div style="text-align: center; padding: 3rem 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 1rem; border: 1px solid rgba(102, 126, 234, 0.3);">
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
                <?php foreach ($freebies as $freebie): 
                    $referralLink = $baseUrl . '/freebie/index.php?id=' . $freebie['unique_id'] . '&ref=' . $referralCode;
                ?>
                <div class="freebie-card-expanded">
                    <div class="freebie-header">
                        <?php if (!empty($freebie['image_path'])): ?>
                        <div class="freebie-image">
                            <img src="<?php echo htmlspecialchars($freebie['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($freebie['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        
                        <div class="freebie-info">
                            <span class="freebie-badge <?php echo $freebie['freebie_source'] === 'custom' ? 'badge-custom' : 'badge-template'; ?>">
                                <?php echo $freebie['freebie_source'] === 'custom' ? '✨ Eigenes Freebie' : '📚 Template-Freebie'; ?>
                            </span>
                            
                            <h4 style="color: white; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($freebie['title']); ?>
                            </h4>
                            
                            <?php if (!empty($freebie['description'])): ?>
                            <p style="color: #9ca3af; font-size: 0.9375rem; line-height: 1.6; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($freebie['description']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                <span style="color: #3b82f6; font-size: 0.875rem;">
                                    <i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($freebie['created_at'])); ?>
                                </span>
                                <span style="color: #10b981; font-size: 0.875rem;">
                                    <i class="fas fa-trophy"></i> <?php echo $freebie['reward_count']; ?> Belohnung<?php echo $freebie['reward_count'] != 1 ? 'en' : ''; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="freebie-actions">
                        <div class="action-card">
                            <div class="action-label">Empfehlungslink</div>
                            <div class="link-input-group">
                                <input type="text" 
                                       readonly 
                                       value="<?php echo htmlspecialchars($referralLink); ?>" 
                                       class="link-input"
                                       id="link-<?php echo $freebie['customer_freebie_id']; ?>">
                                <button onclick="copyLink('link-<?php echo $freebie['customer_freebie_id']; ?>')" 
                                        class="btn-copy">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small style="color: #6b7280; font-size: 0.75rem;">Teile diesen Link mit deinen Kontakten</small>
                        </div>
                        
                        <div class="action-card">
                            <div class="action-label">Belohnungen</div>
                            <a href="?page=belohnungsstufen&freebie_id=<?php echo $freebie['customer_freebie_id']; ?>" 
                               class="btn-rewards">
                                <i class="fas fa-trophy"></i>
                                <?php if ($freebie['reward_count'] > 0): ?>
                                    Belohnungen verwalten
                                    <span class="reward-count"><?php echo $freebie['reward_count']; ?></span>
                                <?php else: ?>
                                    Belohnungen einrichten
                                <?php endif; ?>
                            </a>
                            <small style="color: #6b7280; font-size: 0.75rem; display: block; margin-top: 0.5rem;">
                                Konfiguriere Belohnungen für Empfehlungen
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Aktivitätsgraph -->
        <?php if (!empty($chart_data)): ?>
        <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.5s; margin-top: 1.5rem; margin-bottom: 1.5rem;">
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
        // Link kopieren
        function copyLink(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            input.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(input.value).then(() => {
                showNotification('Link kopiert!', 'success');
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
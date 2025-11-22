<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * FINAL VERSION: Mailgun-Transparenz + AVV ZUERST
 * 
 * Rechtliche Reihenfolge:
 * 1. Transparenz-Info über Mailgun (EU-Server, Belohnungs-Mails)
 * 2. Zustimmung zu Mailgun + AVV
 * 3. Erst dann: Empfehlungsprogramm nutzbar
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

try {
    $pdo = getDBConnection();
    
    // Benutzer-Details + Mailgun-Zustimmung laden
    // WICHTIG: Prüfe NUR auf 'mailgun_consent' (separat von registration)
    $stmt = $pdo->prepare("
        SELECT 
            u.referral_enabled,
            u.ref_code,
            u.company_name,
            u.company_email,
            u.company_imprint_html,
            (
                SELECT COUNT(*) 
                FROM av_contract_acceptances 
                WHERE user_id = u.id 
                AND acceptance_type = 'mailgun_consent'
            ) as mailgun_consent_given
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User nicht gefunden");
    }
    
    $mailgunConsentGiven = $user['mailgun_consent_given'] > 0;
    
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
    
    // Freebies laden
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
    
    $freebies = array_merge($custom_freebies, $template_freebies);
    usort($freebies, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
} catch (PDOException $e) {
    error_log("Empfehlungsprogramm Error: " . $e->getMessage());
    $user = [
        'referral_enabled' => 0,
        'ref_code' => '',
        'company_name' => '',
        'company_email' => '',
        'company_imprint_html' => '',
        'mailgun_consent_given' => 0
    ];
    $mailgunConsentGiven = false;
    $stats = ['total_leads' => 0, 'referred_leads' => 0, 'total_referrals' => 0, 'successful_referrals' => 0];
    $freebies = [];
    $total_slots = 0;
    $used_slots = 0;
    $available_slots = 0;
}

$referralEnabled = $user['referral_enabled'] ?? 0;
$referralCode = $user['ref_code'] ?? '';
$baseUrl = 'https://app.mehr-infos-jetzt.de';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
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
        
        .toggle-switch input:disabled + .toggle-slider {
            cursor: pointer; /* Macht es klickbar auch wenn disabled */
            opacity: 0.7;
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
        
        /* Mailgun Transparenz Banner */
        .mailgun-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border: 3px solid #d97706;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(251, 191, 36, 0.3);
        }
        
        .mailgun-banner h3 {
            color: #78350f;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .mailgun-banner-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .mailgun-info-box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .mailgun-info-box h4 {
            color: #78350f;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .mailgun-info-list {
            color: #1f2937;
            font-size: 0.9375rem;
            line-height: 1.8;
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .mailgun-info-list li {
            margin-bottom: 0.5rem;
        }
        
        .mailgun-info-list strong {
            color: #78350f;
        }
        
        .consent-button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        
        .consent-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(16, 185, 129, 0.4);
        }
        
        /* Step Indicator */
        .step-indicator {
            background: rgba(251, 191, 36, 0.2);
            border: 2px solid #fbbf24;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: #fbbf24;
            color: #78350f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-text {
            flex: 1;
            color: #78350f;
            font-size: 1rem;
            font-weight: 600;
        }
        
        /* Modal */
        .consent-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .consent-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 1rem 1rem 0 0;
        }
        
        .modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-subtitle {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .consent-section {
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .consent-section h4 {
            color: #1f2937;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .consent-section ul {
            color: #4b5563;
            font-size: 0.9375rem;
            line-height: 1.8;
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .consent-section li {
            margin-bottom: 0.5rem;
        }
        
        .checkbox-group {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: start;
            gap: 1rem;
        }
        
        .checkbox-group:hover {
            border-color: #667eea;
        }
        
        .checkbox-group.checked {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 1.5rem;
            height: 1.5rem;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
        
        .checkbox-label {
            flex: 1;
            color: #1f2937;
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        
        .checkbox-label strong {
            color: #667eea;
            font-size: 1rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9375rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-cancel {
            background: #6b7280;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #4b5563;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-confirm:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.5);
        }
        
        .btn-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Freebie Mockup Image */
        .freebie-mockup {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 2px solid rgba(102, 126, 234, 0.3);
            flex-shrink: 0;
        }
        
        .freebie-card-expanded {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .mailgun-banner {
                padding: 1.5rem;
            }
            
            .mailgun-banner h3 {
                font-size: 1.25rem;
                flex-direction: column;
                text-align: center;
            }
            
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
            
            .consent-button {
                width: 100%;
                justify-content: center;
            }
            
            .freebie-mockup {
                width: 60px;
                height: 60px;
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
                        <h1 style="font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                            <i class="fas fa-rocket"></i> Empfehlungsprogramm
                        </h1>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 1rem;">
                            Verwalte deine Freebies und Empfehlungslinks
                        </p>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="color: white; font-weight: 600; font-size: 0.875rem;">
                            <?php echo $referralEnabled ? 'Aktiviert' : 'Deaktiviert'; ?>
                        </span>
                        <label class="toggle-switch" id="toggleContainer" <?php echo !$mailgunConsentGiven ? 'onclick="handleDisabledToggleClick()"' : ''; ?>>
                            <input type="checkbox" 
                                   id="referralToggle" 
                                   <?php echo $referralEnabled ? 'checked' : ''; ?>
                                   <?php echo !$mailgunConsentGiven ? 'disabled' : ''; ?>
                                   onchange="toggleReferralProgram(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <?php if (!$mailgunConsentGiven): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.5rem; padding: 0.75rem; margin-top: 1rem;">
                    <p style="color: white; font-size: 0.875rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-lock"></i>
                        <strong>Gesperrt:</strong> Um das Empfehlungsprogramm zu nutzen, akzeptiere bitte zuerst die Nutzungsbedingungen unten.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$mailgunConsentGiven): ?>
            
            <!-- STEP INDICATOR -->
            <div class="step-indicator animate-fade-in-up" style="opacity: 0; animation-delay: 0.05s;">
                <div class="step-number animate-pulse">1</div>
                <div class="step-text">
                    Bitte lies die Informationen unten und klicke dann auf "Ich verstehe und stimme zu"
                </div>
            </div>
            
            <!-- ===== MAILGUN TRANSPARENZ BANNER ===== -->
            <div class="mailgun-banner animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s;">
                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; align-items: center; flex-wrap: wrap;">
                    <div class="mailgun-banner-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <h3 style="margin: 0;">
                            Transparenz & Datenschutz
                        </h3>
                        <p style="color: #78350f; font-size: 0.9375rem; margin: 0;">
                            So schützen wir deine Daten bei der Nutzung des Empfehlungsprogramms
                        </p>
                    </div>
                </div>
                
                <div class="mailgun-info-box">
                    <h4>
                        <i class="fas fa-envelope"></i>
                        Automatische Belohnungs-E-Mails via Mailgun
                    </h4>
                    <ul class="mailgun-info-list">
                        <li><strong>Warum Mailgun?</strong> Professioneller E-Mail-Versand für Belohnungs-Benachrichtigungen an deine Leads</li>
                        <li><strong>Server-Standort:</strong> Alle E-Mails werden über <strong>EU-Server (Europa)</strong> versendet - volle DSGVO-Konformität</li>
                        <li><strong>Dein Impressum:</strong> Jede E-Mail enthält automatisch deine Impressumsdaten</li>
                        <li><strong>Deine Kontrolle:</strong> Du bestimmst, welche Belohnungen deine Leads erhalten</li>
                    </ul>
                </div>
                
                <div class="mailgun-info-box">
                    <h4>
                        <i class="fas fa-database"></i>
                        Welche Daten werden verarbeitet?
                    </h4>
                    <ul class="mailgun-info-list">
                        <li><strong>Lead-Daten:</strong> Name, E-Mail-Adresse deiner Leads (die sich freiwillig registriert haben)</li>
                        <li><strong>Belohnungs-Info:</strong> Informationen über erreichte Stufen und freigeschaltete Belohnungen</li>
                        <li><strong>Versand-Statistiken:</strong> Zustellstatus, Öffnungsrate (technisch notwendig für Qualitätssicherung)</li>
                        <li><strong>Keine Weitergabe:</strong> Daten werden ausschließlich für den E-Mail-Versand genutzt, nicht an Dritte verkauft</li>
                    </ul>
                </div>
                
                <div class="mailgun-info-box">
                    <h4>
                        <i class="fas fa-file-contract"></i>
                        Auftragsverarbeitungsvertrag (AVV)
                    </h4>
                    <ul class="mailgun-info-list">
                        <li><strong>Rechtliche Grundlage:</strong> Wir schließen einen AVV nach Art. 28 DSGVO mit dir ab</li>
                        <li><strong>Deine Rolle:</strong> Du bleibst Verantwortlicher für deine Lead-Daten</li>
                        <li><strong>Unsere Rolle:</strong> Wir verarbeiten die Daten nur nach deiner Weisung (E-Mail-Versand)</li>
                        <li><strong>Sicherheit:</strong> Technische und organisatorische Maßnahmen gemäß DSGVO</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <button class="consent-button animate-pulse" onclick="openConsentModal()">
                        <i class="fas fa-check-circle"></i>
                        Ich verstehe und stimme zu
                    </button>
                    <p style="color: #78350f; font-size: 0.875rem; margin-top: 1rem;">
                        Mit deiner Zustimmung akzeptierst du die Nutzung von Mailgun und den AVV
                    </p>
                </div>
            </div>
            
        <?php else: ?>
            
            <!-- ===== EMPFEHLUNGSPROGRAMM CONTENT ===== -->
            
            <!-- Statistiken -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <div style="color: white;">
                        <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['total_leads']; ?></div>
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">Gesamt Leads</div>
                    </div>
                </div>
                
                <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <div style="color: white;">
                        <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $available_slots; ?></div>
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">Verfügbare Slots</div>
                    </div>
                </div>
                
                <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <div style="color: white;">
                        <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['successful_referrals']; ?></div>
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">Erfolgreiche Empfehlungen</div>
                    </div>
                </div>
            </div>
            
            <!-- Freebies Liste -->
            <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.5s;">
                <h2 style="color: white; font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    <i class="fas fa-gift"></i> Deine Freebies (<?php echo count($freebies); ?>)
                </h2>
                
                <?php if (empty($freebies)): ?>
                <div style="text-align: center; padding: 3rem 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 1rem;">
                    <div style="font-size: 3rem; color: #374151; margin-bottom: 1rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h4 style="color: white; font-size: 1.125rem; margin-bottom: 0.5rem;">Keine Freebies verfügbar</h4>
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
                        $mockupImage = $freebie['image_path'] ?? null;
                    ?>
                    <div class="freebie-card-expanded">
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; align-items: start;">
                            <?php if ($mockupImage): ?>
                            <img src="<?php echo htmlspecialchars($mockupImage); ?>" 
                                 alt="<?php echo htmlspecialchars($freebie['title']); ?>"
                                 class="freebie-mockup"
                                 onerror="this.style.display='none'">
                            <?php endif; ?>
                            
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="color: white; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($freebie['title']); ?>
                                </h3>
                                <?php if (!empty($freebie['description'])): ?>
                                <p style="color: #9ca3af; font-size: 0.9375rem;">
                                    <?php echo htmlspecialchars($freebie['description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1rem;">
                                <div style="color: #9ca3af; font-size: 0.75rem; margin-bottom: 0.5rem;">Empfehlungslink</div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" readonly value="<?php echo htmlspecialchars($referralLink); ?>" 
                                           style="flex: 1; background: #111827; border: 1px solid #374151; border-radius: 0.5rem; color: white; padding: 0.5rem; font-size: 0.75rem; font-family: monospace;">
                                    <button onclick="copyLink(this)" class="btn" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1rem;">
                                <div style="color: #9ca3af; font-size: 0.75rem; margin-bottom: 0.5rem;">Belohnungen</div>
                                <a href="?page=belohnungsstufen&freebie_id=<?php echo $freebie['customer_freebie_id']; ?>" 
                                   class="btn" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none;">
                                    <i class="fas fa-trophy"></i>
                                    <?php echo $freebie['reward_count'] > 0 ? 'Verwalten (' . $freebie['reward_count'] . ')' : 'Einrichten'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
        
    </div>
    
    <!-- Consent Modal -->
    <div id="consentModal" class="consent-modal">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-shield-alt"></i>
                    Zustimmung erforderlich
                </div>
                <div class="modal-subtitle">
                    Bitte bestätige die folgenden Punkte, um das Empfehlungsprogramm zu nutzen
                </div>
            </div>
            
            <div class="modal-body">
                <div class="consent-section">
                    <h4>
                        <i class="fas fa-envelope"></i>
                        Mailgun E-Mail-Versand
                    </h4>
                    <ul>
                        <li>Ich verstehe, dass Mailgun (EU-Server) für den Versand von Belohnungs-E-Mails genutzt wird</li>
                        <li>Die Daten meiner Leads (Name, E-Mail) werden nur für den E-Mail-Versand verwendet</li>
                        <li>Mein Impressum wird automatisch in jede E-Mail eingefügt</li>
                        <li>Alle Daten bleiben in Europa und werden DSGVO-konform verarbeitet</li>
                    </ul>
                </div>
                
                <div class="consent-section">
                    <h4>
                        <i class="fas fa-file-contract"></i>
                        Auftragsverarbeitungsvertrag (AVV)
                    </h4>
                    <ul>
                        <li>Ich akzeptiere den AVV nach Art. 28 DSGVO</li>
                        <li>Ich bleibe Verantwortlicher für die Verarbeitung meiner Lead-Daten</li>
                        <li>Die Daten werden nur nach meiner Weisung verarbeitet</li>
                        <li>Technische und organisatorische Maßnahmen werden eingehalten</li>
                    </ul>
                </div>
                
                <label class="checkbox-group" id="mailgunCheckbox" onclick="toggleCheckbox('mailgunCheck')">
                    <input type="checkbox" id="mailgunCheck" onclick="event.stopPropagation(); updateConfirmButton()">
                    <div class="checkbox-label">
                        <strong>Ja, ich stimme zu</strong><br>
                        Ich habe die Informationen gelesen und akzeptiere die Nutzung von Mailgun sowie den Auftragsverarbeitungsvertrag.
                    </div>
                </label>
                
                <div class="modal-actions">
                    <button class="btn btn-cancel" onclick="closeConsentModal()">
                        <i class="fas fa-times"></i>
                        Abbrechen
                    </button>
                    <button class="btn btn-confirm" id="confirmButton" disabled onclick="saveConsent()">
                        <i class="fas fa-check"></i>
                        Zustimmung speichern
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Disabled Toggle Click Handler
        function handleDisabledToggleClick() {
            // Modal direkt öffnen
            openConsentModal();
        }
        
        // Modal öffnen/schließen
        function openConsentModal() {
            document.getElementById('consentModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeConsentModal() {
            document.getElementById('consentModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Checkbox toggle
        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            checkbox.checked = !checkbox.checked;
            updateConfirmButton();
        }
        
        // Confirm Button aktivieren/deaktivieren
        function updateConfirmButton() {
            const mailgunCheck = document.getElementById('mailgunCheck').checked;
            const confirmBtn = document.getElementById('confirmButton');
            const checkboxGroup = document.getElementById('mailgunCheckbox');
            
            confirmBtn.disabled = !mailgunCheck;
            
            if (mailgunCheck) {
                checkboxGroup.classList.add('checked');
            } else {
                checkboxGroup.classList.remove('checked');
            }
        }
        
        // Zustimmung speichern
        async function saveConsent() {
            const confirmBtn = document.getElementById('confirmButton');
            const originalHTML = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichere...';
            confirmBtn.disabled = true;
            
            try {
                const response = await fetch('/api/mailgun/consent.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        consent_given: true,
                        acceptance_type: 'mailgun_consent'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Zustimmung gespeichert! Seite wird neu geladen...', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('❌ Fehler: ' + (result.error || 'Unbekannter Fehler'), 'error');
                    confirmBtn.innerHTML = originalHTML;
                    confirmBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler beim Speichern', 'error');
                confirmBtn.innerHTML = originalHTML;
                confirmBtn.disabled = false;
            }
        }
        
        // Toggle Programm
        function toggleReferralProgram(enabled) {
            const toggle = document.getElementById('referralToggle');
            
            fetch('/api/referral/toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled: enabled })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(
                        enabled ? '✅ Empfehlungsprogramm aktiviert!' : '⏸️ Empfehlungsprogramm deaktiviert',
                        'success'
                    );
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('❌ Fehler: ' + (data.message || 'Unbekannter Fehler'), 'error');
                    // Checkbox zurücksetzen bei Fehler
                    toggle.checked = !enabled;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
                // Checkbox zurücksetzen bei Fehler
                toggle.checked = !enabled;
            });
        }
        
        // Link kopieren
        function copyLink(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.background = '#10b981';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.background = '';
            }, 2000);
            
            showNotification('✅ Link kopiert!', 'success');
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
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                z-index: 99999;
                animation: slideIn 0.3s ease-out;
                max-width: 90%;
                font-size: 0.875rem;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
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
        
        // Close modal on overlay click
        document.getElementById('consentModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeConsentModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeConsentModal();
            }
        });
    </script>
</body>
</html>

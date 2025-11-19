<?php
/**
 * Customer Dashboard - Empfehlungsprogramm Section
 * MIT INTEGRIERTER API-KONFIGURATION
 * Zeigt API-Setup wenn Empfehlungsprogramm aktiviert wird
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// Provider-Klassen laden
require_once __DIR__ . '/../includes/EmailProviders.php';

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
    
    // API-Einstellungen laden
    $stmt_api = $pdo->prepare("
        SELECT * FROM customer_email_api_settings 
        WHERE customer_id = ? AND is_active = TRUE
        LIMIT 1
    ");
    $stmt_api->execute([$customer_id]);
    $api_settings = $stmt_api->fetch(PDO::FETCH_ASSOC);
    
    // API-Key maskieren wenn vorhanden
    if ($api_settings && !empty($api_settings['api_key'])) {
        $api_settings['api_key_masked'] = '••••••••' . substr($api_settings['api_key'], -4);
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
        'company_imprint_html' => ''
    ];
    $stats = ['total_leads' => 0, 'referred_leads' => 0, 'total_referrals' => 0, 'successful_referrals' => 0];
    $freebies = [];
    $total_slots = 0;
    $used_slots = 0;
    $available_slots = 0;
    $api_settings = null;
}

$referralEnabled = $user['referral_enabled'] ?? 0;
$referralCode = $user['ref_code'] ?? '';
$baseUrl = 'https://app.mehr-infos-jetzt.de';

// Verfügbare Provider mit API URL Info
$providers = [
    'quentn' => [
        'name' => 'Quentn',
        'requires_api_url' => true,
        'api_url_placeholder' => 'https://YOUR-ID.quentn.com',
        'api_url_help' => 'Format: https://system_id.server_id.quentn.com',
        'supports_tags' => true,
        'supports_campaigns' => true,
        'supports_direct_email' => true,
    ],
    'activecampaign' => [
        'name' => 'ActiveCampaign',
        'requires_api_url' => true,
        'api_url_placeholder' => 'https://YOUR-ACCOUNT.api-us1.com',
        'api_url_help' => 'Zu finden in Settings → Developer',
        'supports_tags' => true,
        'supports_campaigns' => true,
        'supports_direct_email' => true,
    ],
    'klicktipp' => [
        'name' => 'Klick-Tipp',
        'requires_api_url' => false,
        'default_api_url' => 'http://api.klicktipp.com',
        'supports_tags' => true,
        'supports_campaigns' => false,
        'supports_direct_email' => false,
    ],
    'brevo' => [
        'name' => 'Brevo (Sendinblue)',
        'requires_api_url' => false,
        'default_api_url' => 'https://api.brevo.com/v3',
        'supports_tags' => true,
        'supports_campaigns' => true,
        'supports_direct_email' => true,
    ],
    'getresponse' => [
        'name' => 'GetResponse',
        'requires_api_url' => false,
        'default_api_url' => 'https://api.getresponse.com/v3',
        'supports_tags' => true,
        'supports_campaigns' => true,
        'supports_direct_email' => true,
    ],
];
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
        
        /* Freebie Mockup Image */
        .freebie-mockup {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 2px solid rgba(102, 126, 234, 0.3);
            flex-shrink: 0;
        }
        
        /* API Setup Box */
        .api-setup-box {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 2px solid rgba(251, 191, 36, 0.5);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        .api-setup-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .api-setup-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(251, 191, 36, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #fbbf24;
        }
        
        .provider-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .provider-card {
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.75rem;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .provider-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.3);
        }
        
        .provider-card.selected {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        
        .provider-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        
        .provider-name {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .provider-features {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 0.75rem;
        }
        
        .feature-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
        }
        
        /* Config Form */
        .config-form {
            display: none;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .config-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.9375rem;
            transition: all 0.3s;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-hint {
            color: #6b7280;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        
        .checkbox-group label {
            color: #e5e7eb;
            font-size: 0.9375rem;
            cursor: pointer;
            flex: 1;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn-test {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-secondary {
            background: #374151;
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* API Status Badge */
        .api-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-verified {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-unverified {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        .status-none {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }
        
        /* Existing Config Display */
        .existing-api-config {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .config-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .config-detail:last-child {
            border-bottom: none;
        }
        
        /* Custom Fields Info Box */
        .custom-fields-box {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .custom-fields-box h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .custom-fields-box h3 .title-part {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-template {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-template:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* Email Templates Modal */
        .email-templates-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .email-templates-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: linear-gradient(to bottom right, #1f2937, #111827);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            background: linear-gradient(to bottom right, #1f2937, #111827);
            z-index: 10;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-close {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: #ef4444;
            color: white;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .email-template-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .email-template-card:last-child {
            margin-bottom: 0;
        }
        
        .template-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .template-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .template-copy-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .template-copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px -3px rgba(16, 185, 129, 0.5);
        }
        
        .template-preview {
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            padding: 1rem;
            color: #9ca3af;
            font-size: 0.875rem;
            line-height: 1.6;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
        }
        
        .template-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .template-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .field-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .field-item:last-child {
            margin-bottom: 0;
        }
        
        .field-name {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 0.875rem;
            color: #fbbf24;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .field-type {
            display: inline-block;
            background: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .field-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        /* Lead URLs Box */
        .lead-urls-box {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .lead-urls-box h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .url-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .url-item:last-child {
            margin-bottom: 0;
        }
        
        .url-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        
        .url-link {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .url-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            color: white;
            padding: 0.5rem;
            font-size: 0.875rem;
            font-family: monospace;
        }
        
        .btn-copy-url {
            padding: 0.5rem 1rem;
            background: white;
            color: #3b82f6;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-copy-url:hover {
            background: #f0f9ff;
        }
        
        /* Rest of existing styles ... */
        .freebie-card-expanded {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .provider-grid {
                grid-template-columns: 1fr;
            }
            
            .url-link {
                flex-direction: column;
            }
            
            .btn-copy-url {
                width: 100%;
            }
            
            .freebie-mockup {
                width: 60px;
                height: 60px;
            }
            
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
            
            .template-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .template-copy-btn {
                width: 100%;
                justify-content: center;
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
        
        <?php if ($referralEnabled): ?>
            
            <!-- Custom Fields Infobox -->
            <div class="custom-fields-box animate-fade-in-up" style="opacity: 0; animation-delay: 0.1s;">
                <h3>
                    <div class="title-part">
                        <i class="fas fa-database"></i>
                        <span>Benutzerdefinierte Felder für deinen Autoresponder</span>
                    </div>
                    <button class="btn-template" onclick="openEmailTemplatesModal()">
                        <i class="fas fa-envelope"></i> E-Mail-Vorlagen
                    </button>
                </h3>
                <div style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                    <p style="color: rgba(255, 255, 255, 0.9); font-size: 0.875rem; line-height: 1.6; margin: 0;">
                        <i class="fas fa-info-circle"></i> <strong>Wichtig:</strong> Lege diese benutzerdefinierten Felder in deinem Email-Marketing-System an, bevor du deine API-Zugangsdaten eingibst. Diese Felder werden automatisch bei der Lead-Registrierung übertragen.
                    </p>
                </div>
                
                <div class="field-item">
                    <div class="field-name">
                        referral_code
                        <span class="field-type">Text</span>
                    </div>
                    <div class="field-description">
                        Der eindeutige Empfehlungscode des Leads (z.B. "LEAD12AB34CD"). Wird für die Zuordnung von Sub-Empfehlungen verwendet.
                    </div>
                </div>
                
                <div class="field-item">
                    <div class="field-name">
                        total_referrals
                        <span class="field-type">Zahl</span>
                    </div>
                    <div class="field-description">
                        Gesamtanzahl aller Empfehlungen, die dieser Lead generiert hat (inklusive ausstehender Registrierungen).
                    </div>
                </div>
                
                <div class="field-item">
                    <div class="field-name">
                        successful_referrals
                        <span class="field-type">Zahl</span>
                    </div>
                    <div class="field-description">
                        Anzahl der erfolgreichen, bestätigten Empfehlungen. Wird für die Belohnungsstufen verwendet.
                    </div>
                </div>
                
                <div class="field-item">
                    <div class="field-name">
                        rewards_earned
                        <span class="field-type">Zahl</span>
                    </div>
                    <div class="field-description">
                        Anzahl der bereits erhaltenen Belohnungen. Nützlich für Segmentierung und Follow-up-Kampagnen.
                    </div>
                </div>
                
                <div class="field-item">
                    <div class="field-name">
                        referrer_code
                        <span class="field-type">Text</span>
                    </div>
                    <div class="field-description">
                        Der Code des Empfehlungsgebers (falls vorhanden). Zeigt, wer diesen Lead empfohlen hat.
                    </div>
                </div>
                
                <small style="color: rgba(255, 255, 255, 0.8); font-size: 0.875rem; display: block; margin-top: 1rem;">
                    💡 <strong>Tipp:</strong> Diese Felder ermöglichen dir eine präzise Segmentierung und Automation basierend auf dem Empfehlungsverhalten deiner Leads.
                </small>
            </div>
            
            <!-- Email Templates Modal -->
            <div id="emailTemplatesModal" class="email-templates-modal">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fas fa-envelope-open-text"></i>
                            E-Mail-Vorlagen für Belohnungen
                        </div>
                        <button class="modal-close" onclick="closeEmailTemplatesModal()">
                            <i class="fas fa-times"></i> Schließen
                        </button>
                    </div>
                    <div class="modal-body">
                        
                        <!-- Template 1: Klassisch -->
                        <div class="email-template-card">
                            <div class="template-header">
                                <div class="template-title">
                                    📧 Vorlage 1: Klassisch & Professionell
                                </div>
                                <button class="template-copy-btn" onclick="copyTemplate('template1')">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </div>
                            <div class="template-preview" id="template1">Betreff: 🎉 Glückwunsch! Du hast eine Belohnung freigeschaltet

Hallo!

🎉 Herzlichen Glückwunsch!

Du hast es geschafft und eine neue Belohnung in unserem Empfehlungsprogramm erreicht!

---

📊 Deine aktuelle Statistik:
✅ Erfolgreiche Empfehlungen: {{successful_referrals}}
⭐ Gesammelte Punkte: {{current_points}}
🎁 Dein Empfehlungscode: {{referral_code}}

---

🎁 Deine freigeschaltete Belohnung:

{{reward_title}}

{{reward_warning}}

---

💪 Weiter so!

Du machst das großartig! Teile deinen Empfehlungscode weiterhin mit Freunden und Bekannten.

Viele Grüße
Dein Team</div>
                            <div class="template-tags">
                                <span class="template-tag">Professionell</span>
                                <span class="template-tag">Strukturiert</span>
                                <span class="template-tag">Klar</span>
                            </div>
                        </div>
                        
                        <!-- Template 2: Motivierend -->
                        <div class="email-template-card">
                            <div class="template-header">
                                <div class="template-title">
                                    🚀 Vorlage 2: Motivierend & Dynamisch
                                </div>
                                <button class="template-copy-btn" onclick="copyTemplate('template2')">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </div>
                            <div class="template-preview" id="template2">Betreff: ⭐ Level up! Du hast {{current_points}} Punkte erreicht

Hey! 🌟

WOW - du hast gerade Level up gemacht! 🚀

Mit {{successful_referrals}} erfolgreichen Empfehlungen hast du dir folgende Belohnung verdient:

🎁 {{reward_title}}

{{reward_warning}}

---

📈 DEIN FORTSCHRITT:
Aktuelle Punkte: {{current_points}}
Dein Code: {{referral_code}}

---

🔥 NÄCHSTES ZIEL:
Empfehle weiter und sichere dir noch mehr exklusive Belohnungen!

---

Danke, dass du uns unterstützt! 💙

Dein Team</div>
                            <div class="template-tags">
                                <span class="template-tag">Motivierend</span>
                                <span class="template-tag">Dynamisch</span>
                                <span class="template-tag">Modern</span>
                            </div>
                        </div>
                        
                        <!-- Template 3: Minimalistisch -->
                        <div class="email-template-card">
                            <div class="template-header">
                                <div class="template-title">
                                    ✨ Vorlage 3: Minimalistisch & Elegant
                                </div>
                                <button class="template-copy-btn" onclick="copyTemplate('template3')">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </div>
                            <div class="template-preview" id="template3">Betreff: Deine Belohnung ist bereit

Hallo,

du hast {{current_points}} Punkte erreicht.

Deine Belohnung:
{{reward_title}}

{{reward_warning}}

---

Erfolgreiche Empfehlungen: {{successful_referrals}}
Dein Code: {{referral_code}}

---

Beste Grüße
Dein Team</div>
                            <div class="template-tags">
                                <span class="template-tag">Minimalistisch</span>
                                <span class="template-tag">Elegant</span>
                                <span class="template-tag">Kurz</span>
                            </div>
                        </div>
                        
                        <!-- Platzhalter-Erklärungen -->
                        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 0.75rem; padding: 1.5rem; margin-top: 1.5rem;">
                            <h4 style="color: #3b82f6; font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-info-circle"></i>
                                Verfügbare Platzhalter
                            </h4>
                            <div style="color: #9ca3af; font-size: 0.875rem; line-height: 1.8;">
                                <div style="margin-bottom: 0.5rem;">
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; color: #fbbf24;">{{reward_title}}</code>
                                    - Titel der Belohnung
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; color: #fbbf24;">{{reward_warning}}</code>
                                    - Hinweis/Warnung zur Belohnung
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; color: #fbbf24;">{{successful_referrals}}</code>
                                    - Anzahl erfolgreicher Empfehlungen
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; color: #fbbf24;">{{current_points}}</code>
                                    - Aktuelle Punktzahl
                                </div>
                                <div>
                                    <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; color: #fbbf24;">{{referral_code}}</code>
                                    - Empfehlungscode des Leads
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Lead URLs Box -->
            <div class="lead-urls-box animate-fade-in-up" style="opacity: 0; animation-delay: 0.15s;">
                <h3>
                    <i class="fas fa-users"></i> Lead-Anmeldung & Dashboard
                </h3>
                <div class="url-item">
                    <div class="url-label">📝 Lead-Anmeldeseite (mit Double Opt-in)</div>
                    <div class="url-link">
                        <input type="text" class="url-input" readonly value="<?php echo $baseUrl; ?>/lead_login.php?ref=<?php echo $referralCode; ?>" id="lead-login-url">
                        <button class="btn-copy-url" onclick="copyUrl('lead-login-url')">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                </div>
                <div class="url-item">
                    <div class="url-label">📊 Lead-Dashboard (nach Anmeldung)</div>
                    <div class="url-link">
                        <input type="text" class="url-input" readonly value="<?php echo $baseUrl; ?>/lead_dashboard.php" id="lead-dashboard-url">
                        <button class="btn-copy-url" onclick="copyUrl('lead-dashboard-url')">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                </div>
                <small style="color: rgba(255, 255, 255, 0.8); font-size: 0.875rem; display: block; margin-top: 0.75rem;">
                    💡 Deine Leads können sich über die Anmeldeseite registrieren und dann im Dashboard ihre eigenen Empfehlungslinks verwalten.
                </small>
            </div>
            
            <!-- API Setup / Status -->
            <?php if (!$api_settings): ?>
                <!-- API Setup Required -->
                <div class="api-setup-box animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s;">
                    <div class="api-setup-header">
                        <div class="api-setup-icon">
                            <i class="fas fa-plug"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="color: white; font-size: 1.5rem; font-weight: 600; margin-bottom: 0.25rem;">
                                Email-Marketing Integration
                            </h2>
                            <p style="color: #9ca3af; font-size: 0.875rem;">
                                Verbinde dein Email-Marketing-System für automatische Belohnungs-Emails
                            </p>
                        </div>
                    </div>
                    
                    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: start; gap: 1rem;">
                            <i class="fas fa-info-circle" style="color: #3b82f6; font-size: 1.5rem;"></i>
                            <div style="flex: 1;">
                                <h4 style="color: white; font-size: 0.9375rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Warum API-Integration?
                                </h4>
                                <ul style="color: #9ca3af; font-size: 0.875rem; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                                    <li>Automatische Lead-Eintragung in dein Email-System</li>
                                    <li>Automatischer Versand von Belohnungs-Emails</li>
                                    <li>Tag-Management für bessere Segmentierung</li>
                                    <li>DSGVO-konformes Double Opt-in</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        Wähle deinen Email-Marketing-Anbieter
                    </h3>
                    
                    <div class="provider-grid">
                        <?php foreach ($providers as $key => $provider): ?>
                        <div class="provider-card" 
                             data-provider="<?php echo $key; ?>"
                             onclick="selectProvider('<?php echo $key; ?>')">
                            <div class="provider-icon">📧</div>
                            <div class="provider-name"><?php echo htmlspecialchars($provider['name']); ?></div>
                            <div class="provider-features">
                                <?php if ($provider['supports_direct_email']): ?>
                                <span class="feature-badge">📨 Email</span>
                                <?php endif; ?>
                                <?php if ($provider['supports_tags']): ?>
                                <span class="feature-badge">🏷️ Tags</span>
                                <?php endif; ?>
                                <?php if ($provider['supports_campaigns']): ?>
                                <span class="feature-badge">📣 Kampagnen</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Config Forms for each provider -->
                    <?php foreach ($providers as $key => $provider): ?>
                    <div id="config-<?php echo $key; ?>" class="config-form">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="color: white; font-size: 1.125rem; font-weight: 600;">
                                <?php echo htmlspecialchars($provider['name']); ?> konfigurieren
                            </h3>
                            <button onclick="cancelConfig()" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 0.875rem;">
                                <i class="fas fa-times"></i> Abbrechen
                            </button>
                        </div>
                        
                        <form onsubmit="saveApiConfig(event, '<?php echo $key; ?>')">
                            <!-- API-Zugangsdaten -->
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="color: white; font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                                    <i class="fas fa-key"></i> API-Zugangsdaten
                                </h4>
                                
                                <?php if ($provider['requires_api_url']): ?>
                                <div class="form-group">
                                    <label class="form-label">
                                        API URL <span class="required">*</span>
                                    </label>
                                    <input type="url" name="api_url" class="form-input" required 
                                           placeholder="<?php echo $provider['api_url_placeholder']; ?>">
                                    <div class="form-hint"><?php echo $provider['api_url_help']; ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        API-Key <span class="required">*</span>
                                    </label>
                                    <input type="password" name="api_key" class="form-input" required placeholder="Dein API-Key">
                                    <div class="form-hint">Zu finden in deinen <?php echo $provider['name']; ?> Einstellungen</div>
                                </div>
                            </div>
                            
                            <!-- Listen & Tags -->
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="color: white; font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                                    <i class="fas fa-tags"></i> Listen & Tags
                                </h4>
                                
                                <div class="form-group">
                                    <label class="form-label">Start-Tag</label>
                                    <input type="text" name="start_tag" class="form-input" placeholder="z.B. lead_empfehlung">
                                    <div class="form-hint">Wird jedem neuen Lead zugewiesen</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Listen-ID</label>
                                    <input type="text" name="list_id" class="form-input" placeholder="z.B. 12345">
                                </div>
                                
                                <?php if ($provider['supports_campaigns']): ?>
                                <div class="form-group">
                                    <label class="form-label">Kampagnen-ID</label>
                                    <input type="text" name="campaign_id" class="form-input" placeholder="z.B. 67890">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Double Opt-in -->
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                                <h4 style="color: white; font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
                                    <i class="fas fa-shield-alt"></i> Double Opt-in
                                </h4>
                                
                                <div class="checkbox-group">
                                    <input type="checkbox" name="double_optin_enabled" id="doi-<?php echo $key; ?>" checked>
                                    <label for="doi-<?php echo $key; ?>">
                                        <strong>Double Opt-in aktivieren</strong><br>
                                        <small style="color: #9ca3af;">Empfohlen für DSGVO-Konformität</small>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Buttons -->
                            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                <button type="button" onclick="cancelConfig()" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Abbrechen
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Speichern & Testen
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- API Configured - Show Status -->
                <div class="existing-api-config animate-fade-in-up" style="opacity: 0; animation-delay: 0.2s;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-check-circle"></i> Email-Marketing verbunden
                            </h3>
                            <span class="api-status-badge <?php echo $api_settings['is_verified'] ? 'status-verified' : 'status-unverified'; ?>">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                <?php echo $api_settings['is_verified'] ? 'Verifiziert' : 'Nicht verifiziert'; ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button onclick="testApiConnection()" class="btn btn-test">
                                <i class="fas fa-check-circle"></i> Testen
                            </button>
                            <button onclick="deleteApiConfig()" class="btn" style="background: #ef4444; color: white;">
                                <i class="fas fa-trash"></i> Löschen
                            </button>
                        </div>
                    </div>
                    
                    <div class="config-detail">
                        <span style="color: #9ca3af;">Provider:</span>
                        <span style="color: white; font-weight: 600;">
                            <?php echo ucfirst($api_settings['provider']); ?>
                        </span>
                    </div>
                    
                    <div class="config-detail">
                        <span style="color: #9ca3af;">API Key:</span>
                        <span style="color: white; font-family: monospace; font-size: 0.875rem;">
                            <?php echo $api_settings['api_key_masked'] ?? '••••••••'; ?>
                        </span>
                    </div>
                    
                    <?php if ($api_settings['start_tag']): ?>
                    <div class="config-detail">
                        <span style="color: #9ca3af;">Start-Tag:</span>
                        <span style="color: #10b981; font-weight: 500;">
                            <?php echo htmlspecialchars($api_settings['start_tag']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($api_settings['list_id']): ?>
                    <div class="config-detail">
                        <span style="color: #9ca3af;">Listen-ID:</span>
                        <span style="color: #3b82f6; font-weight: 500;">
                            <?php echo htmlspecialchars($api_settings['list_id']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="config-detail">
                        <span style="color: #9ca3af;">Double Opt-in:</span>
                        <span style="color: white;">
                            <?php echo $api_settings['double_optin_enabled'] ? '✅ Aktiviert' : '❌ Deaktiviert'; ?>
                        </span>
                    </div>
                </div>
                
            <?php endif; ?>
            
            <!-- Statistiken -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.3s; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <div style="color: white;">
                        <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $stats['total_leads']; ?></div>
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">Gesamt Leads</div>
                    </div>
                </div>
                
                <div class="animate-fade-in-up" style="opacity: 0; animation-delay: 0.4s; background: linear-gradient(135deg, #10b981, #059669); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <div style="color: white;">
                        <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $available_slots; ?></div>
                        <div style="color: rgba(255,255,255,0.8); font-size: 0.875rem;">Verfügbare Slots</div>
                    </div>
                </div>
            </div>
            
            <!-- Freebies Liste mit Mockup-Bildern -->
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
                                    <button onclick="copyLink(this)" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1rem;">
                                <div style="color: #9ca3af; font-size: 0.75rem; margin-bottom: 0.5rem;">Belohnungen</div>
                                <a href="?page=belohnungsstufen&freebie_id=<?php echo $freebie['customer_freebie_id']; ?>" 
                                   class="btn btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-trophy"></i>
                                    <?php echo $freebie['reward_count'] > 0 ? 'Verwalten (' . $freebie['reward_count'] . ')' : 'Einrichten'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Empfehlungsprogramm ist deaktiviert -->
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(0, 0, 0, 0.2); border-radius: 1rem; margin-top: 2rem;">
                <div style="font-size: 4rem; color: #374151; margin-bottom: 1.5rem;">
                    <i class="fas fa-power-off"></i>
                </div>
                <h3 style="color: white; font-size: 1.5rem; margin-bottom: 1rem;">
                    Empfehlungsprogramm ist deaktiviert
                </h3>
                <p style="color: #9ca3af; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Aktiviere das Empfehlungsprogramm über den Schieber oben rechts, um deine Freebies zu teilen und Belohnungen zu konfigurieren.
                </p>
                <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 0.75rem; padding: 1.5rem; max-width: 600px; margin: 0 auto;">
                    <h4 style="color: #3b82f6; font-size: 1.125rem; margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i> Was passiert beim Aktivieren?
                    </h4>
                    <ul style="color: #9ca3af; text-align: left; line-height: 1.8;">
                        <li>Du kannst deine Email-Marketing-Integration einrichten</li>
                        <li>Du erhältst Zugriff auf Lead-Anmeldeseiten</li>
                        <li>Deine Freebies werden mit Empfehlungslinks versehen</li>
                        <li>Du kannst Belohnungsstufen konfigurieren</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    
    <script>
        let selectedProvider = null;
        const providerDefaults = <?php echo json_encode($providers); ?>;
        
        // Email Templates Modal
        function openEmailTemplatesModal() {
            document.getElementById('emailTemplatesModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEmailTemplatesModal() {
            document.getElementById('emailTemplatesModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal on overlay click
        document.getElementById('emailTemplatesModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmailTemplatesModal();
            }
        });
        
        // Copy email template
        function copyTemplate(templateId) {
            const template = document.getElementById(templateId);
            const text = template.textContent;
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show success feedback
            const btn = event.target.closest('.template-copy-btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
            }, 2000);
            
            showNotification('✅ E-Mail-Vorlage kopiert!', 'success');
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEmailTemplatesModal();
            }
        });
        
        // Provider auswählen
        function selectProvider(provider) {
            selectedProvider = provider;
            
            // Alle Cards deselektieren
            document.querySelectorAll('.provider-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Gewählte Card selektieren
            document.querySelector(`[data-provider="${provider}"]`).classList.add('selected');
            
            // Alle Forms ausblenden
            document.querySelectorAll('.config-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Gewähltes Form anzeigen
            document.getElementById(`config-${provider}`).classList.add('active');
        }
        
        function cancelConfig() {
            document.querySelectorAll('.config-form').forEach(form => {
                form.classList.remove('active');
            });
            document.querySelectorAll('.provider-card').forEach(card => {
                card.classList.remove('selected');
            });
            selectedProvider = null;
        }
        
        // API Config speichern
        async function saveApiConfig(event, provider) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                provider: provider,
                api_key: formData.get('api_key'),
                start_tag: formData.get('start_tag'),
                list_id: formData.get('list_id'),
                campaign_id: formData.get('campaign_id'),
                double_optin_enabled: formData.get('double_optin_enabled') ? true : false
            };
            
            // API URL - verwende entweder eingegebene URL oder Default
            if (formData.has('api_url') && formData.get('api_url')) {
                data.api_url = formData.get('api_url');
            } else if (providerDefaults[provider].default_api_url) {
                data.api_url = providerDefaults[provider].default_api_url;
            }
            
            try {
                const response = await fetch('/api/email-settings/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Einstellungen gespeichert! Teste jetzt die Verbindung...', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('❌ Fehler: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler beim Speichern', 'error');
            }
        }
        
        // API-Verbindung testen
        async function testApiConnection() {
            showNotification('🔄 Teste Verbindung...', 'info');
            
            try {
                const response = await fetch('/api/email-settings/test.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Verbindung erfolgreich! ' + result.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('❌ Verbindung fehlgeschlagen: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Fehler beim Testen', 'error');
            }
        }
        
        // API-Config löschen
        async function deleteApiConfig() {
            if (!confirm('API-Konfiguration wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) {
                return;
            }
            
            try {
                const response = await fetch('/api/email-settings/delete.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Konfiguration gelöscht', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('❌ Fehler: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
            }
        }
        
        // Toggle Programm
        function toggleReferralProgram(enabled) {
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
                    document.getElementById('referralToggle').checked = !enabled;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
                document.getElementById('referralToggle').checked = !enabled;
            });
        }
        
        // URL kopieren
        function copyUrl(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            document.execCommand('copy');
            
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            btn.style.background = '#10b981';
            
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '';
            }, 2000);
        }
        
        // Link kopieren
        function copyLink(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            button.style.background = '#10b981';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.background = '';
            }, 2000);
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
    </script>
</body>
</html>
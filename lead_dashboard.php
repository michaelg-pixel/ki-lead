<?php
/**
 * Lead Dashboard - Empfehlungsprogramm
 * Leads können Freebies auswählen und ihre Empfehlungslinks teilen
 */

require_once __DIR__ . '/config/database.php';

session_start();

// Login Check
if (!isset($_SESSION['lead_id'])) {
    header('Location: lead_login.php');
    exit;
}

$db = getDBConnection();

// Lead Daten laden
$stmt = $db->prepare("
    SELECT * FROM lead_users 
    WHERE id = ?
");
$stmt->execute([$_SESSION['lead_id']]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    session_destroy();
    header('Location: lead_login.php');
    exit;
}

// Empfohlene Leads laden
$stmt = $db->prepare("
    SELECT referred_name as name, referred_email as email, status, invited_at as registered_at 
    FROM lead_referrals 
    WHERE referrer_id = ?
    ORDER BY invited_at DESC
");
$stmt->execute([$lead['id']]);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Eingelöste Belohnungen laden
$stmt = $db->prepare("
    SELECT reward_id, reward_name, claimed_at 
    FROM referral_claimed_rewards 
    WHERE lead_id = ?
    ORDER BY claimed_at DESC
");
$stmt->execute([$lead['id']]);
$claimed_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Belohnungen aus Datenbank laden (basierend auf user_id des Leads)
$reward_tiers = [];
if ($lead['user_id']) {
    $stmt = $db->prepare("
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
            reward_value
        FROM reward_definitions 
        WHERE user_id = ? AND is_active = 1
        ORDER BY tier_level ASC
    ");
    $stmt->execute([$lead['user_id']]);
    $reward_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Freebies laden - customer_freebies verwendet headline statt name
$freebies = [];
if ($lead['user_id']) {
    try {
        $stmt = $db->prepare("
            SELECT 
                cf.id,
                cf.unique_id,
                COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title,
                COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description,
                COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as image_path,
                cf.customer_id
            FROM customer_freebies cf
            LEFT JOIN freebies f ON cf.template_id = f.id
            WHERE cf.customer_id = ?
            ORDER BY cf.id DESC
        ");
        $stmt->execute([$lead['user_id']]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error loading freebies: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Dashboard - Empfehlungsprogramm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        
        .sidebar-header h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-section {
            margin-bottom: 10px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        
        .menu-item:hover {
            background: #f5f7fa;
            border-left-color: #667eea;
        }
        
        .menu-item.active {
            background: #e7f3ff;
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }
        
        .menu-item i {
            font-size: 18px;
            width: 30px;
            margin-right: 12px;
        }
        
        .menu-item span {
            font-size: 15px;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            margin-top: auto;
        }
        
        .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .sidebar-footer .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        .sidebar-footer .user-details {
            flex: 1;
        }
        
        .sidebar-footer .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .sidebar-footer .user-email {
            font-size: 12px;
            color: #666;
        }
        
        .sidebar-logout {
            width: 100%;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            color: #666;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .sidebar-logout:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: white;
            border: none;
            padding: 12px 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .mobile-toggle:hover {
            background: #667eea;
            color: white;
        }
        
        .mobile-toggle i {
            font-size: 20px;
        }
        
        /* Overlay für Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        /* KI Super Mailer Box */
        .ki-mailer-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .ki-mailer-box h2 {
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ki-mailer-box h2 i {
            font-size: 28px;
        }
        
        .ki-mailer-box p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            opacity: 0.95;
        }
        
        .ki-mailer-features {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .ki-mailer-features h3 {
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .ki-mailer-features ul {
            list-style: none;
            padding: 0;
        }
        
        .ki-mailer-features li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
            line-height: 1.6;
        }
        
        .ki-mailer-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }
        
        .ki-mailer-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .ki-mailer-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: #f8f9fa;
        }
        
        .ki-mailer-btn i {
            font-size: 20px;
        }
        
        .freebie-selection-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .freebie-selection-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .freebie-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .freebie-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .freebie-card.selected {
            border-color: #28a745;
            background: #d4edda;
        }
        .freebie-card .freebie-image {
            width: 100%;
            height: 150px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 60px;
        }
        .freebie-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .freebie-card h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .freebie-card p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        .referral-link-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: none;
        }
        .referral-link-section.show {
            display: block;
        }
        .referral-link-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .link-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .link-input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            background: #f8f9fa;
        }
        .copy-btn {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        /* Template Card Styles */
        .template-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .template-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .template-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .template-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #667eea;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .template-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            font-size: 15px;
            line-height: 1.6;
            color: #333;
            position: relative;
        }
        
        .template-copy-btn {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .template-copy-btn:hover {
            background: #5568d3;
        }
        
        .template-copy-btn.copied {
            background: #28a745;
        }
        
        .rewards-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .rewards-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .reward-tier {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.3s;
        }
        .reward-tier.unlocked {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .reward-tier.claimed {
            border-left-color: #667eea;
            background: #e7f3ff;
        }
        .reward-tier .icon {
            font-size: 40px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: white;
        }
        .reward-tier .info {
            flex: 1;
        }
        .reward-tier .tier-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .reward-tier .title {
            font-weight: 600;
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        .reward-tier .description {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .reward-tier .requirement {
            color: #666;
            font-size: 14px;
        }
        .reward-tier .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .reward-tier .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
        }
        .reward-tier .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }
        .status.locked {
            background: #f0f0f0;
            color: #666;
        }
        .status.unlocked {
            background: #28a745;
            color: white;
        }
        .status.claimed {
            background: #667eea;
            color: white;
        }
        .referrals-list {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .referrals-list h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.converted {
            background: #cfe2ff;
            color: #084298;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .alert-box .icon {
            font-size: 32px;
        }
        .alert-box .content {
            flex: 1;
        }
        .alert-box h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .alert-box p {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
        /* Footer Styles */
        .footer {
            margin-top: 40px;
            padding: 25px;
            text-align: center;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .footer-links a:hover {
            background: #f5f7fa;
            transform: translateY(-2px);
        }
        
        .footer-copyright {
            color: #999;
            font-size: 12px;
            margin-top: 15px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .reward-tier {
                flex-direction: column;
                text-align: center;
            }
            
            .link-input-group {
                flex-direction: column;
            }
            
            .freebie-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>🚀 Dashboard</h3>
            <p>Empfehlungsprogramm</p>
        </div>
        
        <nav class="sidebar-menu">
            <div class="menu-section">
                <a href="#" class="menu-item active" onclick="showSection('dashboard', event)">
                    <i class="fas fa-home"></i>
                    <span>Übersicht</span>
                </a>
                <a href="#" class="menu-item" onclick="showSection('howto', event)">
                    <i class="fas fa-question-circle"></i>
                    <span>So funktioniert's</span>
                </a>
                <a href="#" class="menu-item" onclick="showSection('templates', event)">
                    <i class="fas fa-file-alt"></i>
                    <span>Vorlagen & Templates</span>
                </a>
                <a href="#" class="menu-item" onclick="showSection('social', event)">
                    <i class="fas fa-share-alt"></i>
                    <span>Social Media</span>
                </a>
                <a href="#" class="menu-item" onclick="showSection('tips', event)">
                    <i class="fas fa-lightbulb"></i>
                    <span>Tipps & Tricks</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($lead['name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($lead['name']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($lead['email']); ?></div>
                </div>
            </div>
            <a href="lead_logout.php" class="sidebar-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Section (Default) -->
        <div id="dashboard-section" class="content-section active">
            <div class="header">
                <h1>👋 Willkommen, <?php echo htmlspecialchars($lead['name']); ?>!</h1>
                <p>Dein Empfehlungs-Dashboard</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">🎯</div>
                    <div class="label">Gesamt Empfehlungen</div>
                    <div class="value"><?php echo $lead['total_referrals']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">✅</div>
                    <div class="label">Erfolgreiche Empfehlungen</div>
                    <div class="value"><?php echo $lead['successful_referrals']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">🎁</div>
                    <div class="label">Eingelöste Belohnungen</div>
                    <div class="value"><?php echo count($claimed_rewards); ?></div>
                </div>
            </div>
            
            <!-- KI Super Mailer Box -->
            <div class="ki-mailer-box">
                <h2>
                    <i class="fas fa-robot"></i>
                    KI Super Mailer
                </h2>
                <p>
                    Schreibt verkaufsstarke E-Mails im Stil von einem Profi Online Marketer.
                </p>
                
                <div class="ki-mailer-features">
                    <h3>Beispiel Funktionen:</h3>
                    <ul>
                        <li>Nimm diese E-Mail und erstelle eine neue konvertierende Version</li>
                        <li>Erstelle 3 starke Betreff's und Call-to-Action für dieses Angebot</li>
                        <li>Erstelle 3 aufeinander aufbauende E-Mails für folgendes Produkt</li>
                        <li>Analysiere folgende URL und Erstelle 3 aufeinander aufbauende E-Mails</li>
                    </ul>
                </div>
                
                <a href="https://chatgpt.com/g/g-6894b36839dc81918fbd77eedd74415c-ki-super-mailer" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="ki-mailer-btn">
                    <i class="fas fa-external-link-alt"></i>
                    Jetzt KI Super Mailer nutzen
                </a>
            </div>
            
            <!-- Freebie Auswahl -->
            <?php if (!empty($freebies)): ?>
            <div class="freebie-selection-section">
                <h2><i class="fas fa-gift"></i> Wähle ein Freebie zum Teilen</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Wähle ein Freebie aus, das du mit deinem Empfehlungslink teilen möchtest.
                </p>
                <div class="freebie-grid">
                    <?php foreach ($freebies as $freebie): ?>
                    <div class="freebie-card" 
                         data-unique-id="<?php echo htmlspecialchars($freebie['unique_id']); ?>"
                         data-title="<?php echo htmlspecialchars($freebie['title']); ?>"
                         onclick="selectFreebie(this)">
                        <?php if (!empty($freebie['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($freebie['image_path']); ?>" alt="<?php echo htmlspecialchars($freebie['title']); ?>">
                        <?php else: ?>
                            <div class="freebie-image">🎁</div>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($freebie['title']); ?></h4>
                        <?php if (!empty($freebie['description'])): ?>
                        <p><?php echo htmlspecialchars(substr($freebie['description'], 0, 100)) . (strlen($freebie['description']) > 100 ? '...' : ''); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif ($lead['user_id']): ?>
            <div class="freebie-selection-section">
                <div class="alert-box">
                    <div class="icon">⚠️</div>
                    <div class="content">
                        <h3>Noch keine Freebies verfügbar</h3>
                        <p>Dein Partner hat noch keine Freebies erstellt. Bitte wende dich an deinen Ansprechpartner.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="freebie-selection-section">
                <div class="alert-box">
                    <div class="icon">🔗</div>
                    <div class="content">
                        <h3>Account-Verknüpfung fehlt</h3>
                        <p>Dein Lead-Account ist noch nicht mit einem Partner verknüpft. Bitte kontaktiere den Support.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Empfehlungslink (wird nach Auswahl angezeigt) -->
            <div class="referral-link-section" id="referralLinkSection">
                <h2><i class="fas fa-link"></i> Dein Empfehlungs-Link</h2>
                <p style="color: #666; margin-bottom: 15px;">
                    Teile diesen Link für: <strong id="selectedFreebieTitle" style="color: #667eea;"></strong>
                </p>
                <div class="link-input-group">
                    <input type="text" class="link-input" id="referral-link" readonly>
                    <button class="copy-btn" onclick="copyLink()">
                        <i class="fas fa-copy"></i> Link kopieren
                    </button>
                </div>
                <p style="color: #999; font-size: 13px; margin-top: 10px;">
                    <i class="fas fa-tag"></i> Dein Referral-Code: <strong><?php echo $lead['referral_code']; ?></strong>
                </p>
            </div>
            
            <div class="rewards-section">
                <h2><i class="fas fa-gift"></i> Belohnungs-Stufen</h2>
                <?php if (empty($reward_tiers)): ?>
                    <?php if ($lead['user_id']): ?>
                    <div class="alert-box">
                        <div class="icon">🎁</div>
                        <div class="content">
                            <h3>Noch keine Belohnungen konfiguriert</h3>
                            <p>Dein Partner hat noch keine Belohnungen eingerichtet. Sobald diese verfügbar sind, werden sie hier angezeigt.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert-box">
                        <div class="icon">🔗</div>
                        <div class="content">
                            <h3>Account-Verknüpfung fehlt</h3>
                            <p>Um Belohnungen zu sehen, muss dein Account mit einem Partner verknüpft sein.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php foreach ($reward_tiers as $tier): 
                        $tier_id = $tier['id'];
                        $is_claimed = false;
                        foreach ($claimed_rewards as $claimed) {
                            if ($claimed['reward_id'] == $tier_id) {
                                $is_claimed = true;
                                break;
                            }
                        }
                        $is_unlocked = $lead['successful_referrals'] >= $tier['required_referrals'];
                        $status = $is_claimed ? 'claimed' : ($is_unlocked ? 'unlocked' : 'locked');
                        $progress_percent = min(100, ($lead['successful_referrals'] / $tier['required_referrals']) * 100);
                        
                        $icon_class = 'fa-gift';
                        if (isset($tier['reward_icon']) && strpos($tier['reward_icon'], 'fa-') === 0) {
                            $icon_class = $tier['reward_icon'];
                        }
                        
                        $badge_color = $tier['reward_color'] ?? '#667eea';
                    ?>
                        <div class="reward-tier <?php echo $status; ?>">
                            <div class="icon" style="color: <?php echo $badge_color; ?>">
                                <i class="fas <?php echo $icon_class; ?>"></i>
                            </div>
                            <div class="info">
                                <div class="tier-badge" style="background: <?php echo $badge_color; ?>; color: white;">
                                    <?php echo htmlspecialchars($tier['tier_name'] ?? 'Stufe ' . $tier['tier_level']); ?>
                                </div>
                                <div class="title"><?php echo htmlspecialchars($tier['reward_title']); ?></div>
                                <?php if (!empty($tier['reward_description'])): ?>
                                    <div class="description"><?php echo htmlspecialchars($tier['reward_description']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($tier['reward_value'])): ?>
                                    <div class="description" style="font-weight: 600; color: <?php echo $badge_color; ?>;">
                                        <?php echo htmlspecialchars($tier['reward_value']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="requirement">
                                    <?php echo $tier['required_referrals']; ?> erfolgreiche Empfehlungen benötigt
                                    (<?php echo $lead['successful_referrals']; ?>/<?php echo $tier['required_referrals']; ?>)
                                </div>
                                <?php if (!$is_claimed && !$is_unlocked): ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="status <?php echo $status; ?>">
                                <?php 
                                if ($is_claimed) {
                                    echo '<i class="fas fa-check-circle"></i> Eingelöst';
                                } elseif ($is_unlocked) {
                                    echo '<i class="fas fa-star"></i> Freigeschaltet!';
                                } else {
                                    $remaining = $tier['required_referrals'] - $lead['successful_referrals'];
                                    echo "<i class='fas fa-lock'></i> Noch {$remaining}";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="referrals-list">
                <h2><i class="fas fa-users"></i> Deine Empfehlungen</h2>
                <?php if (empty($referrals)): ?>
                    <div class="empty-state">
                        <div class="icon">📭</div>
                        <p>Noch keine Empfehlungen vorhanden</p>
                        <p style="font-size: 14px; margin-top: 10px;">
                            Teile deinen Link und starte mit dem Empfehlen!
                        </p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Name</th>
                                <th><i class="fas fa-envelope"></i> E-Mail</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-calendar"></i> Registriert am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals as $referral): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['name']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $referral['status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'Ausstehend',
                                                'active' => 'Aktiv',
                                                'converted' => 'Konvertiert',
                                                'cancelled' => 'Abgebrochen'
                                            ];
                                            echo $status_labels[$referral['status']] ?? ucfirst($referral['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($referral['registered_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- So funktioniert's Section -->
        <div id="howto-section" class="content-section">
            <div class="header">
                <h1>❓ So funktioniert's</h1>
                <p>Alles was du wissen musst</p>
            </div>
            
            <div class="freebie-selection-section">
                <h2><i class="fas fa-rocket"></i> In 3 einfachen Schritten zum Erfolg</h2>
                
                <div style="margin-top: 30px;">
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">
                            <i class="fas fa-1" style="background: #667eea; color: white; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 16px;"></i>
                            Wähle dein Freebie
                        </h3>
                        <p style="color: #666; line-height: 1.6;">
                            Suche dir aus der Übersicht ein Freebie aus, das du mit deinem Netzwerk teilen möchtest. 
                            Jedes Freebie bietet wertvollen Content für deine Kontakte.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                        <h3 style="color: #28a745; margin-bottom: 10px;">
                            <i class="fas fa-2" style="background: #28a745; color: white; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 16px;"></i>
                            Teile deinen Link
                        </h3>
                        <p style="color: #666; line-height: 1.6;">
                            Kopiere deinen persönlichen Empfehlungslink und teile ihn mit Freunden, Familie oder in sozialen Medien. 
                            Jeder Klick wird automatisch deinem Account zugeordnet.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                        <h3 style="color: #ffc107; margin-bottom: 10px;">
                            <i class="fas fa-3" style="background: #ffc107; color: white; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 16px;"></i>
                            Erhalte Belohnungen
                        </h3>
                        <p style="color: #666; line-height: 1.6;">
                            Für jede erfolgreiche Empfehlung erhältst du Punkte und schaltest exklusive Belohnungen frei. 
                            Je mehr du empfiehlst, desto besser werden die Prämien!
                        </p>
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; margin-top: 30px; color: white;">
                    <h3 style="margin-bottom: 15px;">
                        <i class="fas fa-lightbulb"></i> Wichtig zu wissen
                    </h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
                            <i class="fas fa-check-circle" style="position: absolute; left: 0; top: 3px;"></i>
                            Dein Link ist einzigartig und wird automatisch getrackt
                        </li>
                        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
                            <i class="fas fa-check-circle" style="position: absolute; left: 0; top: 3px;"></i>
                            Du kannst so viele Freebies teilen wie du möchtest
                        </li>
                        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
                            <i class="fas fa-check-circle" style="position: absolute; left: 0; top: 3px;"></i>
                            Belohnungen werden automatisch freigeschaltet
                        </li>
                        <li style="padding-left: 25px; position: relative;">
                            <i class="fas fa-check-circle" style="position: absolute; left: 0; top: 3px;"></i>
                            Du kannst deinen Fortschritt jederzeit im Dashboard verfolgen
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Vorlagen & Templates Section -->
        <div id="templates-section" class="content-section">
            <div class="header">
                <h1>📝 Vorlagen & Templates</h1>
                <p>Fertige Texte zum Kopieren und Verwenden</p>
            </div>
            
            <!-- E-Mail Vorlagen -->
            <div class="freebie-selection-section">
                <h2><i class="fas fa-envelope"></i> E-Mail Vorlagen</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Nutze diese E-Mail-Vorlagen, um deine Kontakte persönlich anzuschreiben.
                </p>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                            Persönliche Empfehlung
                        </div>
                        <span class="template-badge">Formal</span>
                    </div>
                    <div class="template-content" id="email-template-1">
Hallo [Name],

ich hoffe, es geht dir gut! Ich wollte dir etwas zeigen, das mir kürzlich sehr geholfen hat.

Ich habe ein kostenloses [Freebie-Name] gefunden, das wirklich wertvoll ist. Da ich weiß, dass du dich für [Thema] interessierst, dachte ich, das könnte auch für dich interessant sein.

Du kannst es dir hier kostenlos herunterladen:
[Dein Link]

Falls du Fragen dazu hast, melde dich gerne bei mir!

Viele Grüße
[Dein Name]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('email-template-1', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fas fa-heart" style="color: #e74c3c;"></i>
                            Freundschaftlich
                        </div>
                        <span class="template-badge">Casual</span>
                    </div>
                    <div class="template-content" id="email-template-2">
Hey [Name]!

Rate mal, was ich gerade entdeckt habe! 🎁

Es gibt ein mega cooles kostenloses [Freebie-Name], das perfekt für dich sein könnte. Ich habe es mir selbst angeschaut und finde es echt hilfreich.

Schau's dir mal an, kostet nichts:
[Dein Link]

Lass mich wissen, was du davon hältst!

Liebe Grüße
[Dein Name]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('email-template-2', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fas fa-briefcase" style="color: #0A66C2;"></i>
                            Professionell
                        </div>
                        <span class="template-badge">Business</span>
                    </div>
                    <div class="template-content" id="email-template-3">
Sehr geehrte/r [Name],

ich möchte Sie auf eine wertvolle Ressource aufmerksam machen, die für Ihre Tätigkeit relevant sein könnte.

Es handelt sich um [Freebie-Name] - ein kostenloses Angebot, das [spezifischer Nutzen] bietet. In meiner Erfahrung hat es sich als sehr nützlich erwiesen.

Sie können es hier kostenfrei abrufen:
[Dein Link]

Bei Fragen stehe ich Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen
[Dein Name]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('email-template-3', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <!-- Social Media Posts -->
            <div class="freebie-selection-section">
                <h2><i class="fas fa-share-alt"></i> Social Media Posts</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Fertige Posts für Facebook, Instagram, LinkedIn und Co.
                </p>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fab fa-facebook" style="color: #1877f2;"></i>
                            Facebook Post
                        </div>
                        <span class="template-badge">Social</span>
                    </div>
                    <div class="template-content" id="social-template-1">
🎁 KOSTENLOS für euch!

Ich habe gerade [Freebie-Name] entdeckt und bin total begeistert! 🌟

Das ist perfekt für alle, die [Nutzen/Ziel]. Komplett kostenlos und mega wertvoll!

👉 Hier geht's zum Download: [Dein Link]

#Freebie #Kostenlos #[RellevantesHashtag]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('social-template-1', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fab fa-instagram" style="color: #E4405F;"></i>
                            Instagram Caption
                        </div>
                        <span class="template-badge">Social</span>
                    </div>
                    <div class="template-content" id="social-template-2">
✨ Game Changer Alert! ✨

Hab gerade [Freebie-Name] gefunden und musste es sofort mit euch teilen! 💫

Perfect für alle die [Zielgruppe/Problem]. Und das Beste? Komplett GRATIS! 🎉

🔗 Link in Bio oder DM für direkten Link!

#freebie #kostenlos #tipps #lifehack #[DeinThema]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('social-template-2', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fab fa-linkedin" style="color: #0A66C2;"></i>
                            LinkedIn Post
                        </div>
                        <span class="template-badge">Professional</span>
                    </div>
                    <div class="template-content" id="social-template-3">
💡 Wertvolle Ressource für [Zielgruppe]

Ich möchte eine kostenlose Ressource mit euch teilen, die mir kürzlich sehr weitergeholfen hat: [Freebie-Name]

Was ihr bekommt:
✅ [Benefit 1]
✅ [Benefit 2]
✅ [Benefit 3]

Besonders wertvoll für alle, die [spezifisches Ziel] erreichen möchten.

👉 Link zum kostenlosen Download: [Dein Link]

#ProfessionalDevelopment #Weiterbildung #[BranchenHashtag]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('social-template-3', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <!-- WhatsApp Nachrichten -->
            <div class="freebie-selection-section">
                <h2><i class="fab fa-whatsapp"></i> WhatsApp Nachrichten</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Kurze, persönliche Nachrichten für WhatsApp.
                </p>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fas fa-comments" style="color: #25D366;"></i>
                            Kurz & Knackig
                        </div>
                        <span class="template-badge">WhatsApp</span>
                    </div>
                    <div class="template-content" id="whatsapp-template-1">
Hey! 👋

Hab gerade was Cooles gefunden, das dich interessieren könnte: [Freebie-Name]

Ist komplett kostenlos und echt hilfreich! 🎁

Magst du mal reinschauen? [Dein Link]
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('whatsapp-template-1', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="fas fa-users" style="color: #25D366;"></i>
                            Für Gruppen
                        </div>
                        <span class="template-badge">Group</span>
                    </div>
                    <div class="template-content" id="whatsapp-template-2">
Hey zusammen! 👋

Ich habe ein kostenloses [Freebie-Name] gefunden, das wirklich gut ist!

Falls jemand von euch Interesse an [Thema] hat - das hier könnte mega hilfreich sein! 🚀

Kostenloser Download: [Dein Link]

Viel Spaß damit! 😊
                    </div>
                    <button class="template-copy-btn" onclick="copyTemplate('whatsapp-template-2', this)">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <!-- Betreffzeilen -->
            <div class="freebie-selection-section">
                <h2><i class="fas fa-heading"></i> E-Mail Betreffzeilen</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Aufmerksamkeitsstarke Betreffzeilen für deine E-Mails.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-1" style="margin-bottom: 10px; font-weight: 600;">
🎁 Kostenlos für dich: [Freebie-Name]
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-1', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-2" style="margin-bottom: 10px; font-weight: 600;">
Das musst du dir ansehen! [Freebie-Name]
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-2', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-3" style="margin-bottom: 10px; font-weight: 600;">
[Name], ich habe etwas für dich gefunden
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-3', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-4" style="margin-bottom: 10px; font-weight: 600;">
Gratis Download: [Freebie-Name] 🚀
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-4', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-5" style="margin-bottom: 10px; font-weight: 600;">
Schnell zugreifen: Kostenloses [Freebie-Name]
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-5', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div class="template-card" style="margin-bottom: 0;">
                        <div class="template-content" id="subject-6" style="margin-bottom: 10px; font-weight: 600;">
✨ Empfehlung: Das solltest du dir nicht entgehen lassen
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('subject-6', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Call-to-Actions -->
            <div class="freebie-selection-section">
                <h2><i class="fas fa-mouse-pointer"></i> Call-to-Action Formulierungen</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Wirksame Handlungsaufforderungen für bessere Conversion.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div style="background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-1">
                            👉 Jetzt kostenlos sichern!
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-1', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-2">
                            🎁 Hier gratis downloaden
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-2', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-3">
                            ⚡ Klick hier für deinen Download
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-3', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div style="background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 4px solid #e74c3c; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-4">
                            🚀 Jetzt starten - 100% kostenlos
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-4', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div style="background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-5">
                            💎 Hol dir jetzt dein Gratis-Freebie
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-5', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                    
                    <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px;" id="cta-6">
                            ✨ Sofort verfügbar - Jetzt zugreifen!
                        </div>
                        <button class="template-copy-btn" onclick="copyTemplate('cta-6', this)">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tipps zur Nutzung -->
            <div class="freebie-selection-section">
                <h2><i class="fas fa-info-circle"></i> So nutzt du die Vorlagen optimal</h2>
                
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; color: white; margin-top: 20px;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-magic"></i> Personalisierungs-Tipps</h3>
                    <ul style="line-height: 2; list-style: none; padding-left: 0;">
                        <li>✅ Ersetze [Name] mit dem echten Namen des Empfängers</li>
                        <li>✅ Füge [Freebie-Name] mit dem tatsächlichen Titel ein</li>
                        <li>✅ Verwende [Dein Link] mit deinem persönlichen Empfehlungslink</li>
                        <li>✅ Passe [Thema] an das spezifische Interessengebiet an</li>
                        <li>✅ Ergänze eigene persönliche Erfahrungen für mehr Authentizität</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Social Media Section -->
        <div id="social-section" class="content-section">
            <div class="header">
                <h1>📱 Social Media</h1>
                <p>Teile deine Links optimal</p>
            </div>
            
            <div class="freebie-selection-section">
                <h2><i class="fas fa-share-alt"></i> Social Media Tipps</h2>
                
                <div class="stats-grid" style="margin-top: 30px;">
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #1877f2; margin-bottom: 15px;">
                            <i class="fab fa-facebook"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">Facebook</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Poste in relevanten Gruppen und auf deiner Timeline. 
                            Füge einen persönlichen Kommentar hinzu, warum dir das Freebie gefällt.
                        </p>
                    </div>
                    
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #E4405F; margin-bottom: 15px;">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">Instagram</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Teile deinen Link in der Story oder Bio. 
                            Nutze passende Hashtags und erstelle ansprechende Stories.
                        </p>
                    </div>
                    
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #1DA1F2; margin-bottom: 15px;">
                            <i class="fab fa-twitter"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">Twitter / X</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Teile kurze, prägnante Tweets mit deinem Link. 
                            Nutze relevante Hashtags für mehr Reichweite.
                        </p>
                    </div>
                    
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #0A66C2; margin-bottom: 15px;">
                            <i class="fab fa-linkedin"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">LinkedIn</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Perfekt für professionelle Inhalte. 
                            Schreibe einen wertvollen Beitrag und füge deinen Link hinzu.
                        </p>
                    </div>
                    
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #25D366; margin-bottom: 15px;">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">WhatsApp</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Teile deinen Link direkt mit Freunden und in relevanten Gruppen. 
                            Persönliche Nachrichten haben oft die beste Conversion.
                        </p>
                    </div>
                    
                    <div class="stat-card" style="text-align: center;">
                        <div style="font-size: 50px; color: #0088cc; margin-bottom: 15px;">
                            <i class="fab fa-telegram"></i>
                        </div>
                        <h3 style="color: #333; margin-bottom: 10px;">Telegram</h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6;">
                            Nutze Kanäle und Gruppen für maximale Reichweite. 
                            Telegram-Nutzer sind oft sehr engagiert.
                        </p>
                    </div>
                </div>
                
                <div style="background: #e7f3ff; padding: 25px; border-radius: 10px; margin-top: 30px; border-left: 4px solid #667eea;">
                    <h3 style="color: #667eea; margin-bottom: 15px;">
                        <i class="fas fa-magic"></i> Posting-Vorlagen
                    </h3>
                    <p style="color: #666; margin-bottom: 15px;">
                        Nutze diese Vorlagen für deine Posts:
                    </p>
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <strong>📢 Hey! Ich habe gerade [Freebie-Name] entdeckt und es ist richtig gut!</strong><br>
                        <span style="color: #666; font-size: 14px;">Schau es dir kostenlos an: [Dein Link]</span>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px;">
                        <strong>🎁 Kostenlos für dich:</strong><br>
                        <span style="color: #666; font-size: 14px;">[Freebie-Name] - Klick hier: [Dein Link]</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tipps & Tricks Section -->
        <div id="tips-section" class="content-section">
            <div class="header">
                <h1>💡 Tipps & Tricks</h1>
                <p>So holst du das Maximum raus</p>
            </div>
            
            <div class="freebie-selection-section">
                <h2><i class="fas fa-star"></i> Profi-Tipps für mehr Erfolg</h2>
                
                <div style="margin-top: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; margin-bottom: 20px; color: white;">
                        <h3 style="margin-bottom: 15px;">
                            <i class="fas fa-bullseye"></i> Tipp #1: Zielgruppe kennen
                        </h3>
                        <p style="line-height: 1.6; opacity: 0.95;">
                            Teile nur Freebies, die wirklich zu deiner Zielgruppe passen. 
                            Je relevanter der Content, desto höher die Conversion-Rate. 
                            Überlege dir, welche Probleme deine Kontakte haben und wähle entsprechende Freebies aus.
                        </p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 25px; border-radius: 10px; margin-bottom: 20px; color: white;">
                        <h3 style="margin-bottom: 15px;">
                            <i class="fas fa-clock"></i> Tipp #2: Timing ist alles
                        </h3>
                        <p style="line-height: 1.6; opacity: 0.95;">
                            Poste zu Zeiten, wenn deine Zielgruppe online ist. 
                            Für Facebook und Instagram sind das oft Abends zwischen 19-21 Uhr. 
                            LinkedIn funktioniert besser während der Arbeitszeit (9-17 Uhr).
                        </p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); padding: 25px; border-radius: 10px; margin-bottom: 20px; color: white;">
                        <h3 style="margin-bottom: 15px;">
                            <i class="fas fa-heart"></i> Tipp #3: Authentisch bleiben
                        </h3>
                        <p style="line-height: 1.6; opacity: 0.95;">
                            Teile nur Freebies, die du selbst gut findest. 
                            Deine persönliche Empfehlung ist viel mehr wert als ein nackter Link. 
                            Erzähle, warum DU das Freebie empfiehlst.
                        </p>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 25px; border-radius: 10px; margin-bottom: 20px; color: white;">
                        <h3 style="margin-bottom: 15px;">
                            <i class="fas fa-chart-line"></i> Tipp #4: Mehrwert kommunizieren
                        </h3>
                        <p style="line-height: 1.6; opacity: 0.95;">
                            Erkläre in deinem Post, welchen konkreten Nutzen das Freebie bietet. 
                            Nicht nur "Hol dir das Freebie", sondern "Lerne in 10 Minuten, wie du XYZ machst". 
                            Menschen wollen wissen, was sie davon haben.
                        </p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">🔄</div>
                        <h4 style="color: #333; margin-bottom: 10px;">Regelmäßig posten</h4>
                        <p style="color: #666; font-size: 14px;">
                            Konstanz schlägt Quantität. Lieber einmal pro Woche qualitativ hochwertig posten.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">💬</div>
                        <h4 style="color: #333; margin-bottom: 10px;">Auf Kommentare antworten</h4>
                        <p style="color: #666; font-size: 14px;">
                            Engagement ist der Schlüssel. Beantworte Fragen und bleibe mit deiner Community im Austausch.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
                        <h4 style="color: #333; margin-bottom: 10px;">Performance tracken</h4>
                        <p style="color: #666; font-size: 14px;">
                            Schau regelmäßig ins Dashboard und analysiere, welche Freebies am besten funktionieren.
                        </p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">🎯</div>
                        <h4 style="color: #333; margin-bottom: 10px;">Call-to-Action nutzen</h4>
                        <p style="color: #666; font-size: 14px;">
                            Fordere aktiv zum Klicken auf: "Hol dir jetzt", "Klick hier", "Jetzt kostenlos sichern".
                        </p>
                    </div>
                </div>
                
                <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 25px; border-radius: 10px; margin-top: 30px;">
                    <h3 style="color: #856404; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> Was du vermeiden solltest
                    </h3>
                    <ul style="color: #856404; line-height: 2;">
                        <li>❌ Spam in fremden Gruppen ohne Erlaubnis</li>
                        <li>❌ Nur Links posten ohne Kontext</li>
                        <li>❌ Zu häufig den gleichen Link teilen</li>
                        <li>❌ Irrelevante Freebies für deine Zielgruppe</li>
                        <li>❌ Aggressive Verkaufssprache verwenden</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-links">
                <a href="impressum.php" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-info-circle"></i> Impressum
                </a>
                <a href="datenschutz.php" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-shield-alt"></i> Datenschutz
                </a>
            </div>
            <div class="footer-copyright">
                © <?php echo date('Y'); ?> - Alle Rechte vorbehalten
            </div>
        </footer>
    </main>
    
    <script>
        const leadReferralCode = '<?php echo $lead['referral_code']; ?>';
        const baseUrl = 'https://app.mehr-infos-jetzt.de';
        
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        
        // Section Navigation
        function showSection(sectionName, event) {
            if (event) {
                event.preventDefault();
            }
            
            // Alle Sections ausblenden
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Alle Menu Items deaktivieren
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Gewählte Section anzeigen
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Aktives Menu Item markieren
            if (event) {
                event.target.closest('.menu-item').classList.add('active');
            }
            
            // Auf Mobile: Sidebar schließen
            if (window.innerWidth <= 992) {
                toggleSidebar();
            }
            
            // Nach oben scrollen
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Template Copy Function
        function copyTemplate(templateId, button) {
            const template = document.getElementById(templateId);
            const text = template.textContent.trim();
            
            // Text in die Zwischenablage kopieren
            navigator.clipboard.writeText(text).then(() => {
                // Button Feedback
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Fehler beim Kopieren:', err);
                alert('Bitte kopiere den Text manuell');
            });
        }
        
        function selectFreebie(element) {
            // Alle Karten deselektieren
            document.querySelectorAll('.freebie-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Ausgewählte Karte markieren
            element.classList.add('selected');
            
            // Daten aus data-Attributen holen
            const freebieUniqueId = element.dataset.uniqueId;
            const freebieTitle = element.dataset.title;
            
            console.log('Selected Freebie:', {
                uniqueId: freebieUniqueId,
                title: freebieTitle,
                refCode: leadReferralCode
            });
            
            // Korrekter Freebie-Link mit Lead-Referral-Code
            const referralLink = `${baseUrl}/freebie/index.php?id=${freebieUniqueId}&ref=${leadReferralCode}`;
            
            document.getElementById('referral-link').value = referralLink;
            document.getElementById('selectedFreebieTitle').textContent = freebieTitle;
            document.getElementById('referralLinkSection').classList.add('show');
            
            // Scroll zum Link
            document.getElementById('referralLinkSection').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
        }
        
        function copyLink() {
            const input = document.getElementById('referral-link');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '#667eea';
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
        
        // Overlay click schließt Sidebar auf Mobile
        document.querySelector('.sidebar-overlay').addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                toggleSidebar();
            }
        });
    </script>
</body>
</html>
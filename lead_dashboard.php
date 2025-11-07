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
        
        /* Sidebar Navigation */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.closed {
            transform: translateX(-100%);
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
        
        .menu-item {
            padding: 15px 25px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        
        .menu-item:hover {
            background: #f8f9fa;
            border-left-color: #667eea;
        }
        
        .menu-item.active {
            background: #e7f3ff;
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }
        
        .menu-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        /* Mobile Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }
        
        .menu-toggle:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .menu-toggle i {
            font-size: 20px;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
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
        
        .sidebar-overlay.show {
            display: block;
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
        .logout-btn {
            float: right;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: white;
            color: #667eea;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        /* Info Box für Anleitungen */
        .info-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .info-box h3 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p, .info-box ul, .info-box ol {
            color: #666;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        .info-box ul, .info-box ol {
            padding-left: 25px;
        }
        
        .info-box li {
            margin-bottom: 10px;
        }
        
        .info-box strong {
            color: #333;
        }
        
        .social-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        
        .social-button.facebook {
            background: #1877f2;
            color: white;
        }
        
        .social-button.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
        }
        
        .social-button.whatsapp {
            background: #25D366;
            color: white;
        }
        
        .social-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .menu-toggle {
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
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay für Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>📚 Hilfe & Infos</h3>
            <p>Alles was du wissen musst</p>
        </div>
        <nav class="sidebar-menu">
            <a class="menu-item active" onclick="showSection('dashboard')" data-section="dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a class="menu-item" onclick="showSection('how-it-works')" data-section="how-it-works">
                <i class="fas fa-question-circle"></i>
                <span>So funktioniert's</span>
            </a>
            <a class="menu-item" onclick="showSection('social-media')" data-section="social-media">
                <i class="fas fa-share-alt"></i>
                <span>Social Media</span>
            </a>
            <a class="menu-item" onclick="showSection('tips-tricks')" data-section="tips-tricks">
                <i class="fas fa-lightbulb"></i>
                <span>Tipps & Tricks</span>
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        
        <!-- Dashboard Section -->
        <div class="content-section active" id="dashboard-section">
            <div class="header">
                <a href="lead_logout.php" class="logout-btn">Logout</a>
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
        <div class="content-section" id="how-it-works-section">
            <div class="header">
                <a href="lead_logout.php" class="logout-btn">Logout</a>
                <h1>📚 So funktioniert's</h1>
                <p>Deine Schritt-für-Schritt Anleitung</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-rocket"></i> In 3 einfachen Schritten zum Erfolg</h3>
                
                <p><strong>Schritt 1: Freebie auswählen</strong></p>
                <p>Gehe zum Dashboard und wähle eines der verfügbaren Freebies aus, das du teilen möchtest. Klicke einfach auf die Karte des gewünschten Freebies.</p>
                
                <p><strong>Schritt 2: Link teilen</strong></p>
                <p>Nach der Auswahl erhältst du automatisch deinen persönlichen Empfehlungslink. Dieser Link enthält deinen einzigartigen Referral-Code. Kopiere den Link mit dem Button und teile ihn über:</p>
                <ul>
                    <li>Social Media (Facebook, Instagram, WhatsApp)</li>
                    <li>E-Mail an Freunde und Bekannte</li>
                    <li>In relevanten Online-Communities</li>
                    <li>Persönliche Gespräche</li>
                </ul>
                
                <p><strong>Schritt 3: Belohnungen verdienen</strong></p>
                <p>Wenn sich jemand über deinen Link anmeldet und zum Kunden wird, zählt das als erfolgreiche Empfehlung. Je mehr erfolgreiche Empfehlungen du sammelst, desto mehr Belohnungen schaltest du frei!</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-gift"></i> Das Belohnungssystem</h3>
                <p>Unser Empfehlungsprogramm funktioniert nach einem Stufensystem:</p>
                <ol>
                    <li>Jede erfolgreiche Empfehlung zählt zu deinem Fortschritt</li>
                    <li>Mit jeder Stufe schaltest du neue Belohnungen frei</li>
                    <li>Du kannst alle deine Belohnungen im Dashboard einsehen</li>
                    <li>Freigeschaltete Belohnungen werden automatisch aktiviert</li>
                </ol>
                <p>Tipp: Schau regelmäßig in dein Dashboard, um deinen Fortschritt zu verfolgen!</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-chart-line"></i> Deine Statistiken</h3>
                <p>In deinem Dashboard siehst du immer:</p>
                <ul>
                    <li><strong>Gesamt Empfehlungen:</strong> Alle Personen, die über deinen Link gekommen sind</li>
                    <li><strong>Erfolgreiche Empfehlungen:</strong> Personen, die zu Kunden geworden sind</li>
                    <li><strong>Eingelöste Belohnungen:</strong> Anzahl der bereits erhaltenen Belohnungen</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-question-circle"></i> Häufige Fragen</h3>
                <p><strong>Wann zählt eine Empfehlung als erfolgreich?</strong></p>
                <p>Eine Empfehlung gilt als erfolgreich, wenn die empfohlene Person sich nicht nur registriert, sondern auch zum zahlenden Kunden wird.</p>
                
                <p><strong>Wie lange ist mein Empfehlungslink gültig?</strong></p>
                <p>Dein Empfehlungslink ist dauerhaft gültig. Du kannst ihn jederzeit teilen und nutzen.</p>
                
                <p><strong>Kann ich mehrere Freebies gleichzeitig teilen?</strong></p>
                <p>Ja! Du kannst für jedes Freebie einen eigenen Link generieren und verschiedene Zielgruppen ansprechen.</p>
            </div>
        </div>
        
        <!-- Social Media Section -->
        <div class="content-section" id="social-media-section">
            <div class="header">
                <a href="lead_logout.php" class="logout-btn">Logout</a>
                <h1>📱 Social Media</h1>
                <p>Teile deine Links erfolgreich auf Social Media</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fab fa-facebook"></i> Facebook Tipps</h3>
                <p><strong>Wo teilen?</strong></p>
                <ul>
                    <li>In relevanten Facebook-Gruppen (beachte die Gruppenregeln!)</li>
                    <li>Auf deiner persönlichen Timeline</li>
                    <li>Als Story für 24-Stunden-Sichtbarkeit</li>
                    <li>In Messenger-Chats mit interessierten Freunden</li>
                </ul>
                
                <p><strong>Best Practices:</strong></p>
                <ul>
                    <li>Füge einen persönlichen Text hinzu, warum das Freebie wertvoll ist</li>
                    <li>Nutze ein ansprechendes Vorschaubild</li>
                    <li>Poste zu aktiven Zeiten (abends zwischen 18-21 Uhr)</li>
                    <li>Reagiere auf Kommentare und Fragen</li>
                </ul>
                
                <p><strong>Beispiel-Post:</strong></p>
                <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-style: italic;">
                    "🎁 Hey Leute! Ich habe gerade dieses mega hilfreiche [Freebie-Name] entdeckt und dachte, das könnte für euch auch interessant sein. 
                    Kostenlos und wirklich wertvoll! 💪 Schaut mal rein: [Dein Link]"
                </p>
            </div>
            
            <div class="info-box">
                <h3><i class="fab fa-instagram"></i> Instagram Tipps</h3>
                <p><strong>Wo teilen?</strong></p>
                <ul>
                    <li>In deiner Instagram Story mit Link-Sticker</li>
                    <li>Als Post mit Link in der Bio</li>
                    <li>In Instagram Reels für größere Reichweite</li>
                    <li>Per Direct Message an interessierte Follower</li>
                </ul>
                
                <p><strong>Best Practices:</strong></p>
                <ul>
                    <li>Erstelle eine ansprechende Grafik oder nutze das Freebie-Mockup</li>
                    <li>Verwende relevante Hashtags (#freebie #kostenlos #tipps)</li>
                    <li>Nutze den Link-in-Bio Bereich effektiv</li>
                    <li>Erstelle ein Highlight für dauerhafte Sichtbarkeit</li>
                </ul>
                
                <p><strong>Story-Idee:</strong></p>
                <ul>
                    <li>Bild/Video des Freebies</li>
                    <li>Text: "Swipe up für gratis [Freebie] 🎁"</li>
                    <li>Link-Sticker mit deinem Empfehlungslink</li>
                    <li>Call-to-Action: "Jetzt sichern! 👆"</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3><i class="fab fa-whatsapp"></i> WhatsApp Tipps</h3>
                <p><strong>Wo teilen?</strong></p>
                <ul>
                    <li>In WhatsApp Status für alle Kontakte</li>
                    <li>In relevanten WhatsApp-Gruppen</li>
                    <li>Persönlich an interessierte Kontakte</li>
                    <li>In WhatsApp Broadcast-Listen</li>
                </ul>
                
                <p><strong>Best Practices:</strong></p>
                <ul>
                    <li>Mache es persönlich - schreibe, warum DU das Freebie empfiehlst</li>
                    <li>Halte die Nachricht kurz und prägnant</li>
                    <li>Füge eine Frage hinzu, um Konversation zu starten</li>
                    <li>Respektiere, wenn jemand nicht interessiert ist</li>
                </ul>
                
                <p><strong>Beispiel-Nachricht:</strong></p>
                <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-style: italic;">
                    "Hi [Name]! 👋 Ich habe gerade ein super Freebie gefunden, das perfekt für dich sein könnte, da du dich ja für [Thema] interessierst. 
                    Komplett kostenlos und mega wertvoll! Magst du mal reinschauen? [Dein Link] 🎁"
                </p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-bullhorn"></i> Allgemeine Social Media Tipps</h3>
                <ul>
                    <li><strong>Authentizität:</strong> Sei ehrlich und teile nur, was du selbst wertvoll findest</li>
                    <li><strong>Mehrwert:</strong> Erkläre, welchen Nutzen das Freebie bietet</li>
                    <li><strong>Timing:</strong> Poste zu Zeiten, wenn deine Zielgruppe online ist</li>
                    <li><strong>Konsistenz:</strong> Teile regelmäßig, aber nerve nicht</li>
                    <li><strong>Engagement:</strong> Interagiere mit Kommentaren und Fragen</li>
                    <li><strong>Variation:</strong> Probiere verschiedene Formate aus (Text, Bild, Video)</li>
                </ul>
            </div>
        </div>
        
        <!-- Tipps & Tricks Section -->
        <div class="content-section" id="tips-tricks-section">
            <div class="header">
                <a href="lead_logout.php" class="logout-btn">Logout</a>
                <h1>💡 Tipps & Tricks</h1>
                <p>So wirst du zum Empfehlungs-Profi</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-star"></i> Die besten Empfehlungsstrategien</h3>
                
                <p><strong>1. Zielgruppengerechtes Teilen</strong></p>
                <p>Überlege dir genau, wer von dem Freebie profitieren würde. Teile es gezielt in Communities und mit Personen, die echtes Interesse haben könnten.</p>
                
                <p><strong>2. Persönliche Empfehlung</strong></p>
                <p>Eine persönliche Empfehlung ist viel wertvoller als nur ein geteilter Link. Erkläre, warum DU das Freebie empfiehlst und welchen Nutzen du darin siehst.</p>
                
                <p><strong>3. Mehrfach-Kanäle nutzen</strong></p>
                <p>Beschränke dich nicht auf einen Kanal. Nutze E-Mail, Social Media, persönliche Gespräche und Online-Communities parallel.</p>
                
                <p><strong>4. Timing ist alles</strong></p>
                <p>Teile deine Links zu Zeiten, wenn deine Zielgruppe am aktivsten ist. Für Social Media sind das meist abends und am Wochenende.</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-comments"></i> Erfolgreiche Kommunikation</h3>
                
                <p><strong>Was funktioniert gut:</strong></p>
                <ul>
                    <li>✅ Ehrliche, persönliche Empfehlungen</li>
                    <li>✅ Klare Beschreibung des Nutzens</li>
                    <li>✅ Ansprechende visuelle Darstellung</li>
                    <li>✅ Call-to-Action ("Jetzt sichern!", "Gratis Download")</li>
                    <li>✅ Zeitlich begrenzte Angebote erwähnen (falls zutreffend)</li>
                </ul>
                
                <p><strong>Was du vermeiden solltest:</strong></p>
                <ul>
                    <li>❌ Spam-artiges Teilen ohne Kontext</li>
                    <li>❌ Übertriebene Versprechungen</li>
                    <li>❌ Zu häufiges Posten desselben Links</li>
                    <li>❌ Ignorieren von Gruppenregeln</li>
                    <li>❌ Unaufgefordertes Zusenden an viele Personen</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-chart-line"></i> Optimierung deiner Empfehlungen</h3>
                
                <p><strong>A/B Testing:</strong></p>
                <p>Probiere verschiedene Ansätze aus und beobachte, was am besten funktioniert:</p>
                <ul>
                    <li>Verschiedene Texte und Formulierungen</li>
                    <li>Unterschiedliche Zeiten zum Posten</li>
                    <li>Verschiedene Plattformen</li>
                    <li>Mit und ohne Bilder</li>
                </ul>
                
                <p><strong>Tracking & Analyse:</strong></p>
                <ul>
                    <li>Beobachte deine Statistiken im Dashboard</li>
                    <li>Identifiziere, welche Kanäle am erfolgreichsten sind</li>
                    <li>Fokussiere dich auf das, was funktioniert</li>
                    <li>Lerne aus weniger erfolgreichen Versuchen</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-users"></i> Community aufbauen</h3>
                
                <p><strong>Langfristiger Erfolg durch Beziehungen:</strong></p>
                <ul>
                    <li>Baue echte Beziehungen zu deiner Community auf</li>
                    <li>Biete Mehrwert, nicht nur Links</li>
                    <li>Sei hilfsbereit und beantworte Fragen</li>
                    <li>Teile auch anderen wertvollen Content</li>
                    <li>Sei konsistent und zuverlässig</li>
                </ul>
                
                <p>Eine starke Community wird deine Empfehlungen organisch weiterverbreiten und dir langfristig mehr erfolgreiche Empfehlungen bringen.</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-trophy"></i> Motivation & Mindset</h3>
                
                <p><strong>Bleib dran:</strong></p>
                <ul>
                    <li>🎯 Setze dir realistische Ziele</li>
                    <li>📊 Feiere kleine Erfolge</li>
                    <li>⏱️ Gib dir Zeit - Erfolg kommt nicht über Nacht</li>
                    <li>💪 Lerne aus Rückschlägen</li>
                    <li>🚀 Optimiere kontinuierlich deine Strategie</li>
                </ul>
                
                <p>Denk daran: Jede erfolgreiche Empfehlung bringt dich näher zu deiner nächsten Belohnung. Bleib konsistent und authentisch!</p>
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
        
        // Sidebar Toggle Functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
        
        // Section Navigation
        function showSection(sectionName) {
            // Alle Sections verstecken
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Gewählte Section anzeigen
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Menu Items aktualisieren
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-section') === sectionName) {
                    item.classList.add('active');
                }
            });
            
            // Auf Mobile: Sidebar schließen nach Auswahl
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
            
            // Zum Anfang scrollen
            window.scrollTo({ top: 0, behavior: 'smooth' });
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
        
        // Responsive: Sidebar bei Resize anpassen
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('sidebarOverlay').classList.remove('show');
            }
        });
    </script>
</body>
</html>
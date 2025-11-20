<?php
/**
 * Lead Dashboard - Modern mit Sidebar-Navigation & KI Social Assistant
 * + AUTO-DELIVERY: Automatische Belohnungsauslieferung
 * + SIDEBAR MENÜ: Navigation wie im Customer-Dashboard
 * + KI SOCIAL ASSISTANT: Für Social Media Posting
 * + FREEBIE-SPECIFIC REWARDS: Nur Belohnungen für das aktuelle Freebie
 * + FOOTER: Impressum & Datenschutz
 * FIXED: Alle Weiterleitungen gehen jetzt zu /lead_register.php
 */

require_once __DIR__ . '/config/database.php';

// Session nur starten wenn nicht bereits aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();

// Token-Login
if (isset($_GET['token']) && !isset($_SESSION['lead_id'])) {
    $token = $_GET['token'];
    $freebie_param = isset($_GET['freebie']) ? (int)$_GET['freebie'] : null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM lead_login_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token_data) {
            if ($token_data['used_at'] === null) {
                $stmt = $pdo->prepare("UPDATE lead_login_tokens SET used_at = NOW() WHERE id = ?");
                $stmt->execute([$token_data['id']]);
            }
            
            $stmt = $pdo->prepare("SELECT id FROM lead_users WHERE email = ? AND user_id = ?");
            $stmt->execute([$token_data['email'], $token_data['customer_id']]);
            $existing_lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_lead) {
                $lead_id = $existing_lead['id'];
            } else {
                $referral_code = strtoupper(substr(md5($token_data['email'] . time()), 0, 8));
                $referrer_id = null;
                
                if (!empty($token_data['referral_code'])) {
                    $stmt = $pdo->prepare("SELECT id FROM lead_users WHERE referral_code = ? AND user_id = ?");
                    $stmt->execute([$token_data['referral_code'], $token_data['customer_id']]);
                    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($referrer) $referrer_id = $referrer['id'];
                }
                
                $stmt = $pdo->prepare("INSERT INTO lead_users (name, email, user_id, freebie_id, referral_code, referrer_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$token_data['name'] ?: 'Lead', $token_data['email'], $token_data['customer_id'], $token_data['freebie_id'], $referral_code, $referrer_id]);
                $lead_id = $pdo->lastInsertId();
                
                if ($referrer_id) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO lead_referrals (referrer_id, referred_email, referred_name, freebie_id, status, invited_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                        $stmt->execute([$referrer_id, $token_data['email'], $token_data['name'] ?: 'Lead', $token_data['freebie_id']]);
                    } catch (PDOException $e) {
                        error_log("Fehler beim Erstellen des Referral-Eintrags: " . $e->getMessage());
                    }
                }
            }
            
            $_SESSION['lead_id'] = $lead_id;
            $_SESSION['lead_email'] = $token_data['email'];
            $_SESSION['lead_customer_id'] = $token_data['customer_id'];
            $_SESSION['lead_freebie_id'] = $token_data['freebie_id'];
            
            $redirect_freebie = $freebie_param ?: $token_data['freebie_id'];
            header('Location: /lead_dashboard.php?page=dashboard&freebie=' . $redirect_freebie);
            exit;
        } else {
            header('Location: /lead_token_expired.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login-Token-Fehler: " . $e->getMessage());
        header('Location: /lead_token_expired.php');
        exit;
    }
}

if (!isset($_SESSION['lead_id'])) {
    header('Location: /lead_register.php');
    exit;
}

$lead_id = $_SESSION['lead_id'];

try {
    $stmt = $pdo->prepare("SELECT lu.*, u.referral_enabled, u.ref_code, u.company_name FROM lead_users lu LEFT JOIN users u ON lu.user_id = u.id WHERE lu.id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        session_destroy();
        header('Location: /lead_register.php');
        exit;
    }
} catch (PDOException $e) {
    die('Fehler beim Laden der Lead-Daten: ' . $e->getMessage());
}

$customer_id = $lead['user_id'];
$referral_enabled = (int)($lead['referral_enabled'] ?? 0);
$company_name = $lead['company_name'] ?? 'Dashboard';

// Rechtstexte laden für Footer
$legal_texts = [];
try {
    $stmt = $pdo->prepare("SELECT impressum, datenschutz FROM legal_texts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Rechtstexte: " . $e->getMessage());
}

// Aktive Seite
$current_page = $_GET['page'] ?? 'dashboard';
$selected_freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : null;

// Freebies laden mit lead_freebie_access
$freebies_with_courses = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'lead_freebie_access'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT freebie_id FROM lead_freebie_access WHERE lead_id = ?");
        $stmt->execute([$lead_id]);
        $freebie_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($freebie_ids)) {
            $placeholders = implode(',', array_fill(0, count($freebie_ids), '?'));
            $stmt = $pdo->prepare("
                SELECT 
                    cf.id as freebie_id, 
                    cf.unique_id, 
                    COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, 
                    COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, 
                    COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, 
                    fc.id as course_id 
                FROM customer_freebies cf 
                LEFT JOIN freebies f ON cf.template_id = f.id 
                LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id 
                WHERE cf.customer_id = ? AND cf.id IN ($placeholders)
            ");
            $params = array_merge([$customer_id], $freebie_ids);
            $stmt->execute($params);
            $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        if (!empty($lead['freebie_id'])) {
            $stmt = $pdo->prepare("
                SELECT 
                    cf.id as freebie_id, 
                    cf.unique_id, 
                    COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, 
                    COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, 
                    COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, 
                    fc.id as course_id 
                FROM customer_freebies cf 
                LEFT JOIN freebies f ON cf.template_id = f.id 
                LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id 
                WHERE cf.customer_id = ? AND cf.id = ?
            ");
            $stmt->execute([$customer_id, $lead['freebie_id']]);
            $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Freebies: " . $e->getMessage());
}

if (!$selected_freebie_id && !empty($freebies_with_courses)) {
    $selected_freebie_id = $freebies_with_courses[0]['freebie_id'];
}

$selected_freebie = null;
if ($selected_freebie_id) {
    foreach ($freebies_with_courses as $freebie) {
        if ($freebie['freebie_id'] == $selected_freebie_id) {
            $selected_freebie = $freebie;
            break;
        }
    }
}

// Empfehlungsdaten
$referrals = [];
$delivered_rewards = [];
$total_referrals = 0;
$successful_referrals = 0;

if ($referral_enabled && $selected_freebie_id) {
    try {
        $stmt = $pdo->prepare("SELECT referred_name as name, referred_email as email, status, invited_at as registered_at FROM lead_referrals WHERE referrer_id = ? ORDER BY invited_at DESC");
        $stmt->execute([$lead_id]);
        $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_referrals = count($referrals);
        $successful_referrals = count(array_filter($referrals, function($r) {
            return $r['status'] === 'active' || $r['status'] === 'converted';
        }));
        
        try {
            $stmt = $pdo->prepare("SELECT rd.*, rdef.tier_name, rdef.reward_icon, rdef.reward_color FROM reward_deliveries rd LEFT JOIN reward_definitions rdef ON rd.reward_id = rdef.id WHERE rd.lead_id = ? ORDER BY rd.delivered_at DESC");
            $stmt->execute([$lead_id]);
            $delivered_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("reward_deliveries Tabelle fehlt noch: " . $e->getMessage());
        }
    } catch (PDOException $e) {
        error_log("Fehler beim Laden der Empfehlungen: " . $e->getMessage());
    }
}

// Belohnungsstufen laden - NUR für das spezifische Freebie (keine allgemeinen mehr)
$reward_tiers = [];
if ($referral_enabled && $customer_id && $selected_freebie_id) {
    try {
        // Lade NUR Belohnungen für das spezifische Freebie (KEINE allgemeinen mit freebie_id IS NULL)
        $stmt = $pdo->prepare("SELECT id, tier_level, tier_name, tier_description, required_referrals, reward_type, reward_title, reward_description, reward_icon, reward_color, reward_value FROM reward_definitions WHERE user_id = ? AND is_active = 1 AND freebie_id = ? ORDER BY tier_level ASC");
        $stmt->execute([$customer_id, $selected_freebie_id]);
        $reward_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fehler beim Laden der Belohnungen: " . $e->getMessage());
    }
}

// Variablen für kurse.php Sektion
$course_section_title = "Meine Kurse";

// Navigation Menu Items
$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard', 'show' => true],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse', 'show' => true],
    'belohnungen' => ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen', 'show' => $referral_enabled && !empty($delivered_rewards)],
    'videoanleitung' => ['icon' => 'fa-video', 'label' => 'Videoanleitung', 'show' => $referral_enabled],
    'empfehlen' => ['icon' => 'fa-share-alt', 'label' => 'Empfehlen', 'show' => $referral_enabled],
    'anleitung' => ['icon' => 'fa-book-open', 'label' => 'So funktioniert\'s', 'show' => true],
    'social' => ['icon' => 'fa-robot', 'label' => 'KI Social Assistant', 'show' => $referral_enabled],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company_name); ?> - Lead Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            z-index: 1000;
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
        }
        
        .mobile-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .mobile-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .mobile-logo-text {
            font-size: 16px;
            font-weight: 700;
            color: white;
        }
        
        .mobile-menu-btn {
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.3);
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: none;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            transition: transform 0.3s ease;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .logo-text h1 {
            font-size: 18px;
            color: white;
            font-weight: 700;
        }
        
        .logo-text p {
            font-size: 12px;
            color: #888;
        }
        
        .nav-menu {
            flex: 1;
            padding: 0 16px;
            overflow-y: auto;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: #999;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 2px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-item:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .nav-icon {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .user-section {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            font-size: 12px;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .logout-btn {
            color: #ff6b6b;
            cursor: pointer;
            font-size: 20px;
            text-decoration: none;
            flex-shrink: 0;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
            padding-bottom: 80px;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(180deg, #16213e 0%, #1a1a2e 100%);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 20px 32px;
            text-align: center;
        }
        
        .footer-links {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .footer-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .footer-link:hover {
            color: #8b9bff;
            text-decoration: underline;
        }
        
        .footer-copyright {
            color: #888;
            font-size: 12px;
            margin-top: 12px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 999;
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .main-content {
                margin-top: 60px;
            }
            
            .content-area {
                padding: 20px;
                padding-bottom: 80px;
            }
            
            .footer {
                padding: 16px 20px;
            }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        .sidebar-item { transition: all 0.3s; }
        .sidebar-item:hover { transform: translateX(4px); }
        @keyframes pulse { 0%, 100% { box-shadow: 0 2px 10px rgba(139, 92, 246, 0.2); } 50% { box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); } }
        .reward-new { animation: pulse 2s infinite; }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-logo">
            <div class="mobile-logo-icon">🌟</div>
            <div class="mobile-logo-text"><?php echo htmlspecialchars($company_name); ?></div>
        </div>
        <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon">🌟</div>
            <div class="logo-text">
                <h1><?php echo htmlspecialchars($company_name); ?></h1>
                <p>Lead Portal</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <?php foreach ($menu_items as $page => $item): ?>
                <?php if ($item['show']): ?>
                <a href="?page=<?php echo $page; ?><?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
                   class="nav-item <?php echo $current_page === $page ? 'active' : ''; ?>" 
                   onclick="closeSidebarOnMobile()">
                    <span class="nav-icon"><i class="fas <?php echo $item['icon']; ?>"></i></span>
                    <span><?php echo $item['label']; ?></span>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($lead['name'] ?? 'L', 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($lead['name'] ?? 'Lead'); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($lead['email']); ?></div>
            </div>
            <a href="/lead_logout.php" class="logout-btn" title="Abmelden">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <?php
            // Content laden basierend auf Seite
            $content_file = __DIR__ . '/lead/sections/' . $current_page . '.php';
            if (file_exists($content_file)) {
                include $content_file;
            } else {
                // Fallback: Dashboard-Inline-Content
                echo '<div class="text-center py-16">';
                echo '<h2 class="text-white text-2xl font-bold mb-4">Seite nicht gefunden</h2>';
                echo '<p class="text-gray-400">Die angeforderte Seite existiert noch nicht.</p>';
                echo '<p class="text-gray-500 mt-2 text-sm">Aktuelle Seite: ' . htmlspecialchars($current_page) . '</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <?php if (!empty($legal_texts['impressum'])): ?>
                <a href="#" onclick="window.open('/legal-pages/impressum.php?customer=<?php echo $customer_id; ?>', '_blank'); return false;" class="footer-link">
                    <i class="fas fa-info-circle"></i> Impressum
                </a>
                <?php endif; ?>
                
                <?php if (!empty($legal_texts['datenschutz'])): ?>
                <a href="#" onclick="window.open('/legal-pages/datenschutz.php?customer=<?php echo $customer_id; ?>', '_blank'); return false;" class="footer-link">
                    <i class="fas fa-shield-alt"></i> Datenschutzerklärung
                </a>
                <?php endif; ?>
            </div>
            <div class="footer-copyright">
                © <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name); ?>. Alle Rechte vorbehalten.
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
        
        function closeSidebarOnMobile() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
        
        function copyCode(code, button) {
            navigator.clipboard.writeText(code).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                button.classList.add('bg-green-700');
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('bg-green-700');
                }, 2000);
            }).catch(() => alert('Bitte kopiere den Code manuell'));
        }
        
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            if (!input) return;
            
            input.select();
            try {
                document.execCommand('copy');
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                btn.classList.add('bg-green-600');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-600');
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
        
        // Funktion für Teilen-Button in Kursen
        function scrollToReferralLink(freebieId) {
            window.location.href = '?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>';
        }
        
        function toggleMobileMenu() {
            toggleSidebar();
        }
    </script>
</body>
</html>

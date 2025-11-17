<?php
/**
 * Lead Dashboard - Modern mit Sidebar-Navigation & KI Social Assistant
 * + AUTO-DELIVERY: Automatische Belohnungsauslieferung
 * + SIDEBAR MENÜ: Navigation wie im Customer-Dashboard
 * + KI SOCIAL ASSISTANT: Für Social Media Posting
 */

require_once __DIR__ . '/config/database.php';
session_start();

$pdo = getDBConnection();

// Token-Login (wie gehabt)
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
    header('Location: /lead_login.php');
    exit;
}

$lead_id = $_SESSION['lead_id'];

try {
    $stmt = $pdo->prepare("SELECT lu.*, u.referral_enabled, u.ref_code, u.company_name FROM lead_users lu LEFT JOIN users u ON lu.user_id = u.id WHERE lu.id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        session_destroy();
        header('Location: /lead_login.php');
        exit;
    }
} catch (PDOException $e) {
    die('Fehler beim Laden der Lead-Daten: ' . $e->getMessage());
}

$customer_id = $lead['user_id'];
$referral_enabled = (int)($lead['referral_enabled'] ?? 0);
$company_name = $lead['company_name'] ?? 'Dashboard';

// Aktive Seite
$current_page = $_GET['page'] ?? 'dashboard';
$selected_freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : null;

// Freebies laden
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
            $stmt = $pdo->prepare("SELECT cf.id as freebie_id, cf.unique_id, COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, fc.id as course_id FROM customer_freebies cf LEFT JOIN freebies f ON cf.template_id = f.id LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id WHERE cf.customer_id = ? AND cf.id IN ($placeholders)");
            $params = array_merge([$customer_id], $freebie_ids);
            $stmt->execute($params);
            $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        if (!empty($lead['freebie_id'])) {
            $stmt = $pdo->prepare("SELECT cf.id as freebie_id, cf.unique_id, COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, fc.id as course_id FROM customer_freebies cf LEFT JOIN freebies f ON cf.template_id = f.id LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id WHERE cf.customer_id = ? AND cf.id = ?");
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

// Belohnungen
$reward_tiers = [];
if ($referral_enabled && $customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, tier_level, tier_name, tier_description, required_referrals, reward_type, reward_title, reward_description, reward_icon, reward_color, reward_value FROM reward_definitions WHERE user_id = ? AND is_active = 1 ORDER BY tier_level ASC");
        $stmt->execute([$customer_id]);
        $reward_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fehler beim Laden der Belohnungen: " . $e->getMessage());
    }
}

// Navigation Menu Items
$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard', 'show' => true],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse', 'show' => true],
    'belohnungen' => ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen', 'show' => $referral_enabled && !empty($delivered_rewards)],
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
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        .sidebar-item { transition: all 0.3s; }
        .sidebar-item:hover { transform: translateX(4px); }
        .sidebar-item.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        @keyframes pulse { 0%, 100% { box-shadow: 0 2px 10px rgba(139, 92, 246, 0.2); } 50% { box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); } }
        .reward-new { animation: pulse 2s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="lg:hidden text-white mr-4" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                
                <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-400">
                    <?php echo htmlspecialchars($company_name); ?>
                </h1>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <div class="text-white font-semibold text-sm"><?php echo htmlspecialchars($lead['name']); ?></div>
                        <div class="text-gray-400 text-xs"><?php echo htmlspecialchars($lead['email']); ?></div>
                    </div>
                    <a href="/lead_logout.php" class="bg-gray-700 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="hidden lg:block w-64 bg-gray-800 min-h-screen border-r border-gray-700 fixed left-0" style="top: 73px;">
            <nav class="p-4">
                <div class="space-y-2">
                    <?php foreach ($menu_items as $page => $item): ?>
                        <?php if ($item['show']): ?>
                        <a href="?page=<?php echo $page; ?><?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
                           class="sidebar-item <?php echo $current_page === $page ? 'active' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white block px-4 py-3 rounded-lg">
                            <i class="fas <?php echo $item['icon']; ?> mr-3 w-5"></i>
                            <?php echo $item['label']; ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>
        
        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40" onclick="toggleMobileMenu()">
            <div class="w-64 bg-gray-800 min-h-screen" onclick="event.stopPropagation()">
                <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                    <h2 class="text-white font-bold text-lg">Menü</h2>
                    <button onclick="toggleMobileMenu()" class="text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <nav class="p-4">
                    <div class="space-y-2">
                        <?php foreach ($menu_items as $page => $item): ?>
                            <?php if ($item['show']): ?>
                            <a href="?page=<?php echo $page; ?><?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
                               class="sidebar-item <?php echo $current_page === $page ? 'active' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white block px-4 py-3 rounded-lg">
                                <i class="fas <?php echo $item['icon']; ?> mr-3"></i>
                                <?php echo $item['label']; ?>
                            </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 lg:ml-64 p-4 lg:p-8">
            <?php
            // Content laden basierend auf Seite
            $content_file = __DIR__ . '/lead/sections/' . $current_page . '.php';
            if (file_exists($content_file)) {
                include $content_file;
            } else {
                // Fallback: Dashboard
                include __DIR__ . '/lead/sections/dashboard.php';
            }
            ?>
        </div>
    </div>
    
    <script>
        function toggleMobileMenu() {
            const mobileSidebar = document.getElementById('mobileSidebar');
            mobileSidebar.classList.toggle('hidden');
        }
        
        function copyCode(code, button) {
            navigator.clipboard.writeText(code).then(() => {
                button.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                button.classList.add('bg-green-700');
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy mr-2"></i>Code kopieren';
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
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                btn.classList.add('bg-green-600');
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy mr-2"></i>Kopieren';
                    btn.classList.remove('bg-green-600');
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
    </script>
</body>
</html>
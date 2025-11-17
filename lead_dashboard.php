<?php
/**
 * Lead Dashboard - Modern Dark Mode Design mit Menü-Navigation
 * + AUTO-DELIVERY: Automatische Belohnungsauslieferung mit Email
 * + MENÜ-NAVIGATION: Verschiedene Sektionen ladbar
 * + EMPFEHLUNGSLINK: Über Belohnungen + Auto-Scroll beim Teilen
 */

require_once __DIR__ . '/config/database.php';

session_start();

$pdo = getDBConnection();

// ===== TOKEN-BASIERTE AUTHENTIFIZIERUNG =====
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
                        checkAndTriggerRewardWebhooks($pdo, $referrer_id, $token_data['customer_id']);
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
            header('Location: /lead_dashboard.php?freebie=' . $redirect_freebie);
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

// ===== FREEBIES LADEN =====
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
            $stmt = $pdo->prepare("SELECT cf.id as freebie_id, cf.unique_id, COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, fc.id as course_id, fc.title as course_title FROM customer_freebies cf LEFT JOIN freebies f ON cf.template_id = f.id LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id WHERE cf.customer_id = ? AND cf.id IN ($placeholders)");
            $params = array_merge([$customer_id], $freebie_ids);
            $stmt->execute($params);
            $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        if (!empty($lead['freebie_id'])) {
            $stmt = $pdo->prepare("SELECT cf.id as freebie_id, cf.unique_id, COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title, COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description, COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url, fc.id as course_id, fc.title as course_title FROM customer_freebies cf LEFT JOIN freebies f ON cf.template_id = f.id LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id WHERE cf.customer_id = ? AND cf.id = ?");
            $stmt->execute([$customer_id, $lead['freebie_id']]);
            $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Freebies: " . $e->getMessage());
}

$selected_freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : (!empty($freebies_with_courses) ? $freebies_with_courses[0]['freebie_id'] : null);
$selected_freebie = null;
if ($selected_freebie_id) {
    foreach ($freebies_with_courses as $freebie) {
        if ($freebie['freebie_id'] == $selected_freebie_id) {
            $selected_freebie = $freebie;
            break;
        }
    }
}

// ===== EMPFEHLUNGSDATEN =====
$referrals = [];
$claimed_rewards = [];
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

// ===== BELOHNUNGEN LADEN =====
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

$company_name = $lead['company_name'] ?? 'Dashboard';
$course_section_title = count($freebies_with_courses) > 1 ? 'Deine Kurse' : 'Dein Kurs';

// ===== MENÜ-NAVIGATION =====
$current_page = $_GET['page'] ?? 'dashboard';

$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard'],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse'],
    'anleitung' => ['icon' => 'fa-book-open', 'label' => 'So funktioniert\'s'],
];

if ($referral_enabled) {
    $menu_items['empfehlen'] = ['icon' => 'fa-share-alt', 'label' => 'Empfehlen'];
    if (!empty($delivered_rewards)) {
        $menu_items['belohnungen'] = ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen'];
    }
    $menu_items['social'] = ['icon' => 'fa-robot', 'label' => 'KI Social Assistant'];
}

// Helper Functions
function checkAndTriggerRewardWebhooks($pdo, $lead_id, $customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lead_referrals WHERE referrer_id = ? AND (status = 'active' OR status = 'converted')");
        $stmt->execute([$lead_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $successful_referrals = $result['count'];
        
        $stmt = $pdo->prepare("SELECT rd.* FROM reward_definitions rd WHERE rd.user_id = ? AND rd.is_active = 1 AND rd.required_referrals <= ? AND rd.id NOT IN (SELECT reward_id FROM reward_deliveries WHERE lead_id = ?) ORDER BY rd.tier_level ASC");
        $stmt->execute([$customer_id, $successful_referrals, $lead_id]);
        $unlocked_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($unlocked_rewards as $reward) {
            deliverReward($pdo, $lead_id, $customer_id, $reward);
        }
    } catch (PDOException $e) {
        error_log("Webhook-Check-Fehler: " . $e->getMessage());
    }
}

function deliverReward($pdo, $lead_id, $customer_id, $reward) {
    try {
        $stmt = $pdo->prepare("SELECT lu.*, u.company_name FROM lead_users lu LEFT JOIN users u ON lu.user_id = u.id WHERE lu.id = ?");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lead) return;
        
        $stmt = $pdo->prepare("INSERT INTO reward_deliveries (lead_id, reward_id, user_id, reward_type, reward_title, reward_value, delivery_url, access_code, delivery_instructions, delivered_at, delivery_status, email_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'delivered', 0)");
        $stmt->execute([$lead_id, $reward['id'], $customer_id, $reward['reward_type'], $reward['reward_title'], $reward['reward_value'], $reward['reward_download_url'] ?? null, $reward['reward_access_code'] ?? null, $reward['reward_instructions'] ?? null]);
        $delivery_id = $pdo->lastInsertId();
        
        if ($reward['auto_deliver'] && $delivery_id) {
            $email_sent = sendRewardDeliveryEmail($lead, $reward);
            if ($email_sent) {
                $stmt = $pdo->prepare("UPDATE reward_deliveries SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
                $stmt->execute([$delivery_id]);
            }
        }
    } catch (PDOException $e) {
        error_log("Delivery Error: " . $e->getMessage());
    }
}

function sendRewardDeliveryEmail($lead, $reward) {
    $subject = "🎁 Du hast eine Belohnung freigeschaltet!";
    $reward_details = '';
    
    if (!empty($reward['reward_download_url'])) {
        $reward_details .= "<div style='background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: 6px; margin: 15px 0;'><p style='margin: 0 0 10px 0; font-weight: bold; color: #166534;'>🔗 Download-Link:</p><a href='{$reward['reward_download_url']}' style='display: inline-block; background: #22c55e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Jetzt herunterladen</a></div>";
    }
    
    if (!empty($reward['reward_access_code'])) {
        $reward_details .= "<div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0;'><p style='margin: 0 0 10px 0; font-weight: bold; color: #92400e;'>🔑 Zugriffscode:</p><code style='font-size: 18px; background: white; padding: 8px 16px; border-radius: 6px; display: inline-block; font-family: monospace; color: #92400e;'>{$reward['reward_access_code']}</code></div>";
    }
    
    if (!empty($reward['reward_instructions'])) {
        $reward_details .= "<div style='background: #e0e7ff; border-left: 4px solid #6366f1; padding: 15px; border-radius: 6px; margin: 15px 0;'><p style='margin: 0 0 10px 0; font-weight: bold; color: #3730a3;'>📋 Einlöse-Anweisungen:</p><p style='margin: 0; color: #3730a3;'>" . nl2br(htmlspecialchars($reward['reward_instructions'])) . "</p></div>";
    }
    
    $message = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'><div style='max-width: 600px; margin: 0 auto; padding: 20px;'><div style='background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'><h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Glückwunsch!</h1></div><div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'><p>Hallo " . htmlspecialchars($lead['name']) . ",</p><p>durch deine erfolgreichen Empfehlungen hast du folgende Belohnung freigeschaltet:</p><div style='background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); padding: 24px; border-radius: 12px; margin: 20px 0; text-align: center;'><h2 style='color: white; margin: 0;'>" . htmlspecialchars($reward['reward_title']) . "</h2></div>" . $reward_details . "<div style='text-align: center; margin: 30px 0;'><a href='https://app.mehr-infos-jetzt.de/lead_dashboard.php' style='display: inline-block; background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🎯 Zum Dashboard</a></div></div></div></body></html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . ($lead['company_name'] ?? 'KI Leadsystem') . " <noreply@mehr-infos-jetzt.de>\r\n";
    
    return mail($lead['email'], $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company_name); ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 2px 10px rgba(139, 92, 246, 0.2); } 50% { box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); } }
        .reward-new { animation: pulse 2s infinite; }
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .reward-unlocked {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.2) 50%, rgba(34, 197, 94, 0.1) 100%);
            background-size: 1000px 100%;
            animation: shimmer 3s infinite;
        }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
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
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Navigation Menü -->
        <div class="mb-8">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-4 shadow-xl border border-purple-500/20">
                <div class="flex gap-2 overflow-x-auto">
                    <?php foreach ($menu_items as $page => $item): ?>
                    <a href="?page=<?php echo $page; ?>&freebie=<?php echo $selected_freebie_id; ?>" 
                       class="<?php echo $current_page === $page ? 'bg-purple-600' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white px-4 py-3 rounded-lg font-semibold transition-all whitespace-nowrap">
                        <i class="fas <?php echo $item['icon']; ?> mr-2"></i>
                        <?php echo $item['label']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php
        // Sektion laden
        $section_file = __DIR__ . '/lead/sections/' . $current_page . '.php';
        if (file_exists($section_file) && $current_page !== 'dashboard') {
            include $section_file;
        } else {
            // Dashboard Ansicht
            include __DIR__ . '/lead/sections/dashboard.php';
        }
        ?>
    </div>
    
    <script>
        const leadReferralCode = '<?php echo $lead['referral_code']; ?>';
        
        function scrollToReferralLink(uniqueId) {
            const link = `https://app.mehr-infos-jetzt.de/freebie/index.php?id=${uniqueId}&ref=${leadReferralCode}`;
            navigator.clipboard.writeText(link).then(() => {
                const section = document.getElementById('referralLinkSection');
                if (section) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    section.style.transform = 'scale(1.02)';
                    section.style.transition = 'transform 0.3s';
                    setTimeout(() => { section.style.transform = 'scale(1)'; }, 300);
                }
            }).catch(() => alert('Bitte kopiere den Link manuell'));
        }
        
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            input.select();
            try {
                document.execCommand('copy');
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-green-700');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-700');
                    btn.classList.add('bg-green-600');
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
        
        function copyCode(code, button) {
            navigator.clipboard.writeText(code).then(() => {
                button.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
                button.classList.add('bg-green-700');
                button.classList.remove('bg-yellow-600');
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy mr-2"></i>Code kopieren';
                    button.classList.remove('bg-green-700');
                    button.classList.add('bg-yellow-600');
                }, 2000);
            }).catch(() => alert('Bitte kopiere den Code manuell'));
        }
    </script>
</body>
</html>

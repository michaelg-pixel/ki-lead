<?php
/**
 * Lead Dashboard - Modern Dark Mode Design
 * + AUTO-DELIVERY: Automatische Belohnungsauslieferung mit Email
 * + REDESIGN: Gleiches Design wie Customer Dashboard
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

// Helper Functions bleiben gleich...
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
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 2px 10px rgba(139, 92, 246, 0.2); } 50% { box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4); } }
        .reward-new { animation: pulse 2s infinite; }
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
                    <div class="text-right">
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
        
        <!-- Willkommen -->
        <div class="mb-8 animate-fade-in-up opacity-0">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 shadow-2xl">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-2">
                    Willkommen zurück, <?php echo htmlspecialchars($lead['name']); ?>! 👋
                </h2>
                <p class="text-purple-100 text-lg">
                    Deine Kurse und Belohnungen warten auf dich.
                </p>
            </div>
        </div>
        
        <!-- Stats -->
        <?php if ($referral_enabled): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2"><?php echo $total_referrals; ?></div>
                    <div class="text-green-100 text-sm font-medium">Gesamt Empfehlungen</div>
                </div>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2"><?php echo $successful_referrals; ?></div>
                    <div class="text-blue-100 text-sm font-medium">Erfolgreiche Empfehlungen</div>
                </div>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-gift text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2"><?php echo count($delivered_rewards); ?></div>
                    <div class="text-purple-100 text-sm font-medium">Erhaltene Belohnungen</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Kurse -->
        <div class="mb-8 animate-fade-in-up opacity-0" style="animation-delay: 0.3s;">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
                <h3 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-graduation-cap text-purple-400 mr-2"></i>
                    <?php echo $course_section_title; ?>
                </h3>
                
                <?php if (empty($freebies_with_courses)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📭</div>
                    <p class="text-gray-400 text-lg">Noch keine Kurse verfügbar</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($freebies_with_courses as $freebie): ?>
                    <div class="bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700 hover:border-purple-500 transition-all group">
                        <div class="h-48 bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center">
                            <?php if (!empty($freebie['mockup_url'])): ?>
                            <img src="<?php echo htmlspecialchars($freebie['mockup_url']); ?>" class="w-full h-full object-cover" alt="">
                            <?php else: ?>
                            <i class="fas fa-graduation-cap text-white text-6xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <h4 class="text-white font-bold text-lg mb-2"><?php echo htmlspecialchars($freebie['title']); ?></h4>
                            <?php if (!empty($freebie['description'])): ?>
                            <p class="text-gray-400 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars(substr($freebie['description'], 0, 120)); ?></p>
                            <?php endif; ?>
                            <div class="flex gap-2">
                                <?php if (!empty($freebie['course_id'])): ?>
                                <a href="/customer/freebie-course-player.php?id=<?php echo $freebie['course_id']; ?>&email=<?php echo urlencode($lead['email']); ?>" 
                                   class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white text-center px-4 py-3 rounded-lg font-semibold transition-all">
                                    <i class="fas fa-play-circle mr-2"></i>Kurs starten
                                </a>
                                <?php endif; ?>
                                <?php if ($referral_enabled): ?>
                                <button onclick="shareAndScroll('<?php echo $freebie['freebie_id']; ?>', '<?php echo htmlspecialchars($freebie['unique_id']); ?>')" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Meine Belohnungen -->
        <?php if ($referral_enabled && !empty($delivered_rewards)): ?>
        <div class="mb-8 animate-fade-in-up opacity-0" style="animation-delay: 0.4s;" id="myRewardsSection">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
                <h3 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-gift text-purple-400 mr-2"></i>
                    Meine Belohnungen
                    <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm ml-3"><?php echo count($delivered_rewards); ?></span>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($delivered_rewards as $reward): 
                        $is_new = (time() - strtotime($reward['delivered_at'])) < 86400;
                    ?>
                    <div class="bg-gray-800/50 rounded-xl p-6 border border-purple-500/30 <?php echo $is_new ? 'reward-new' : ''; ?>">
                        <?php if ($is_new): ?>
                        <div class="inline-block bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold mb-4">
                            ✨ NEU
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 mb-4">
                            <div style="color: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>;" class="text-4xl">
                                <i class="fas <?php echo htmlspecialchars($reward['reward_icon'] ?? 'fa-gift'); ?>"></i>
                            </div>
                            <div>
                                <?php if (!empty($reward['tier_name'])): ?>
                                <div class="inline-block px-3 py-1 rounded-full text-xs font-bold mb-2" style="background: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>20; color: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>;">
                                    <?php echo htmlspecialchars($reward['tier_name']); ?>
                                </div>
                                <?php endif; ?>
                                <h4 class="text-white font-bold text-lg"><?php echo htmlspecialchars($reward['reward_title']); ?></h4>
                            </div>
                        </div>
                        
                        <?php if (!empty($reward['delivery_url'])): ?>
                        <div class="bg-green-500/10 border-l-4 border-green-500 p-4 rounded mb-4">
                            <p class="text-green-300 font-semibold mb-2">🔗 Download-Link:</p>
                            <a href="<?php echo htmlspecialchars($reward['delivery_url']); ?>" target="_blank" 
                               class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-all">
                                <i class="fas fa-download mr-2"></i>Jetzt herunterladen
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($reward['access_code'])): ?>
                        <div class="bg-yellow-500/10 border-l-4 border-yellow-500 p-4 rounded mb-4">
                            <p class="text-yellow-300 font-semibold mb-2">🔑 Zugriffscode:</p>
                            <div class="bg-gray-900 p-3 rounded font-mono text-yellow-300 text-center text-lg font-bold">
                                <?php echo htmlspecialchars($reward['access_code']); ?>
                            </div>
                            <button onclick="copyCode('<?php echo htmlspecialchars($reward['access_code']); ?>', this)" 
                                    class="w-full mt-2 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold transition-all">
                                <i class="fas fa-copy mr-2"></i>Code kopieren
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($reward['delivery_instructions'])): ?>
                        <div class="bg-blue-500/10 border-l-4 border-blue-500 p-4 rounded">
                            <p class="text-blue-300 font-semibold mb-2">📋 Einlöse-Anweisungen:</p>
                            <p class="text-gray-300 text-sm"><?php echo nl2br(htmlspecialchars($reward['delivery_instructions'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-gray-500 text-xs mt-4 pt-4 border-t border-gray-700">
                            <i class="fas fa-clock mr-1"></i>
                            Erhalten am <?php echo date('d.m.Y \u\m H:i', strtotime($reward['delivered_at'])); ?> Uhr
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Empfehlungsprogramm -->
        <?php if ($referral_enabled && $selected_freebie): ?>
        <div class="mb-8 animate-fade-in-up opacity-0" style="animation-delay: 0.5s;">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
                <h3 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-link text-purple-400 mr-2"></i>
                    Dein Empfehlungs-Link
                </h3>
                
                <div class="bg-purple-500/10 rounded-xl p-6 mb-6">
                    <p class="text-purple-300 font-semibold mb-3">
                        Empfehlungs-Link für: <strong><?php echo htmlspecialchars($selected_freebie['title']); ?></strong>
                    </p>
                    <div class="flex gap-3">
                        <input type="text" 
                               id="referralLink" 
                               value="<?php echo htmlspecialchars('https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . $selected_freebie['unique_id'] . '&ref=' . $lead['referral_code']); ?>" 
                               readonly
                               class="flex-1 bg-gray-900 text-white px-4 py-3 rounded-lg border border-purple-500/50">
                        <button onclick="copyReferralLink()" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-all whitespace-nowrap">
                            <i class="fas fa-copy mr-2"></i>Kopieren
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($reward_tiers)): ?>
                <h4 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-trophy text-yellow-400 mr-2"></i>
                    Verfügbare Belohnungen
                </h4>
                <div class="space-y-4">
                    <?php foreach ($reward_tiers as $tier): 
                        $is_unlocked = $successful_referrals >= $tier['required_referrals'];
                        $progress_percent = min(100, ($successful_referrals / $tier['required_referrals']) * 100);
                    ?>
                    <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 <?php echo $is_unlocked ? 'border-green-500' : ''; ?>">
                        <div class="flex items-center gap-4">
                            <div class="text-4xl" style="color: <?php echo $tier['reward_color'] ?? '#8B5CF6'; ?>">
                                <i class="fas <?php echo $tier['reward_icon'] ?? 'fa-gift'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="inline-block px-3 py-1 rounded-full text-xs font-bold mb-2" style="background: <?php echo $tier['reward_color'] ?? '#8B5CF6'; ?>; color: white;">
                                    <?php echo htmlspecialchars($tier['tier_name']); ?>
                                </div>
                                <h5 class="text-white font-bold text-lg"><?php echo htmlspecialchars($tier['reward_title']); ?></h5>
                                <p class="text-gray-400 text-sm"><?php echo $tier['required_referrals']; ?> Empfehlungen benötigt (<?php echo $successful_referrals; ?>/<?php echo $tier['required_referrals']; ?>)</p>
                                <?php if (!$is_unlocked): ?>
                                <div class="w-full bg-gray-700 rounded-full h-2 mt-2">
                                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 h-2 rounded-full transition-all" style="width: <?php echo $progress_percent; ?>%"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($is_unlocked): ?>
                            <span class="bg-green-500 text-white px-4 py-2 rounded-full font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>Freigeschaltet
                            </span>
                            <?php else: ?>
                            <span class="bg-gray-700 text-gray-400 px-4 py-2 rounded-full">
                                <i class="fas fa-lock mr-1"></i>Noch <?php echo $tier['required_referrals'] - $successful_referrals; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const leadReferralCode = '<?php echo $lead['referral_code']; ?>';
        
        function shareAndScroll(freebieId, uniqueId) {
            const link = `https://app.mehr-infos-jetzt.de/freebie/index.php?id=${uniqueId}&ref=${leadReferralCode}`;
            navigator.clipboard.writeText(link).then(() => {
                setTimeout(() => {
                    window.location.href = window.location.pathname + '?freebie=' + freebieId;
                }, 600);
            }).catch(() => alert('Bitte kopiere den Link manuell'));
        }
        
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
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
    </script>
</body>
</html>
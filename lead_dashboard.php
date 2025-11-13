<?php
/**
 * Lead Dashboard - Vereintes System
 * Zeigt: Freebie-Kurse + Videoplayer + Empfehlungsprogramm PRO FREEBIE
 * Mit One-Click-Login Support
 */

require_once __DIR__ . '/config/database.php';

session_start();

$pdo = getDBConnection();

// ===== ONE-CLICK-LOGIN-SUPPORT =====
if (isset($_GET['token']) && !isset($_SESSION['lead_id'])) {
    $token = $_GET['token'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM lead_login_tokens 
            WHERE token = ? AND expires_at > NOW() AND used_at IS NULL
        ");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token_data) {
            // Token als verwendet markieren
            $stmt = $pdo->prepare("UPDATE lead_login_tokens SET used_at = NOW() WHERE id = ?");
            $stmt->execute([$token_data['id']]);
            
            // Lead-User erstellen oder laden
            $stmt = $pdo->prepare("
                SELECT id FROM lead_users 
                WHERE email = ? AND user_id = ?
            ");
            $stmt->execute([$token_data['email'], $token_data['customer_id']]);
            $existing_lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_lead) {
                $lead_id = $existing_lead['id'];
            } else {
                // Neuen Lead-User erstellen
                $referral_code = strtoupper(substr(md5($token_data['email'] . time()), 0, 8));
                
                $stmt = $pdo->prepare("
                    INSERT INTO lead_users 
                    (name, email, user_id, referral_code, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $token_data['name'] ?: 'Lead',
                    $token_data['email'],
                    $token_data['customer_id'],
                    $referral_code
                ]);
                $lead_id = $pdo->lastInsertId();
            }
            
            // Session setzen
            $_SESSION['lead_id'] = $lead_id;
            $_SESSION['lead_email'] = $token_data['email'];
            $_SESSION['lead_customer_id'] = $token_data['customer_id'];
            
            // Redirect ohne Token
            header('Location: /lead_dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login-Token-Fehler: " . $e->getMessage());
    }
}

// Login Check
if (!isset($_SESSION['lead_id'])) {
    header('Location: /lead_login.php');
    exit;
}

$lead_id = $_SESSION['lead_id'];

// Lead-Daten laden
try {
    $stmt = $pdo->prepare("
        SELECT lu.*, u.referral_enabled, u.ref_code, u.company_name
        FROM lead_users lu
        LEFT JOIN users u ON lu.user_id = u.id
        WHERE lu.id = ?
    ");
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

// ===== FREEBIES MIT KURSEN LADEN =====
$freebies_with_courses = [];

try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id as freebie_id,
            cf.unique_id,
            COALESCE(NULLIF(cf.headline, ''), f.name, 'Freebie') as title,
            COALESCE(NULLIF(cf.subheadline, ''), f.description, '') as description,
            COALESCE(NULLIF(cf.mockup_image_url, ''), f.mockup_image_url) as mockup_url,
            fc.id as course_id,
            fc.title as course_title,
            fc.description as course_description
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id
        WHERE cf.customer_id = ?
        ORDER BY cf.id DESC
    ");
    $stmt->execute([$customer_id]);
    $freebies_with_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Freebies: " . $e->getMessage());
}

// Gewähltes Freebie für Empfehlungsprogramm (aus URL oder erstes Freebie)
$selected_freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : null;
if (!$selected_freebie_id && !empty($freebies_with_courses)) {
    $selected_freebie_id = $freebies_with_courses[0]['freebie_id'];
}

// Gewähltes Freebie Details laden
$selected_freebie = null;
if ($selected_freebie_id) {
    foreach ($freebies_with_courses as $freebie) {
        if ($freebie['freebie_id'] == $selected_freebie_id) {
            $selected_freebie = $freebie;
            break;
        }
    }
}

// ===== EMPFEHLUNGSDATEN FÜR GEWÄHLTES FREEBIE =====
$referrals = [];
$claimed_rewards = [];
$total_referrals = 0;
$successful_referrals = 0;

if ($referral_enabled && $selected_freebie_id) {
    try {
        // Empfehlungen für dieses spezifische Freebie
        // Wir nutzen den referral_code in der URL des Freebies
        $stmt = $pdo->prepare("
            SELECT referred_name as name, referred_email as email, status, invited_at as registered_at 
            FROM lead_referrals 
            WHERE referrer_id = ?
            ORDER BY invited_at DESC
        ");
        $stmt->execute([$lead_id]);
        $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_referrals = count($referrals);
        $successful_referrals = count(array_filter($referrals, function($r) {
            return $r['status'] === 'active' || $r['status'] === 'converted';
        }));
        
        // Eingelöste Belohnungen
        $stmt = $pdo->prepare("
            SELECT reward_id, reward_name, claimed_at 
            FROM referral_claimed_rewards 
            WHERE lead_id = ?
            ORDER BY claimed_at DESC
        ");
        $stmt->execute([$lead_id]);
        $claimed_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Fehler beim Laden der Empfehlungen: " . $e->getMessage());
    }
}

// ===== BELOHNUNGEN LADEN =====
$reward_tiers = [];

if ($referral_enabled && $customer_id) {
    try {
        $stmt = $pdo->prepare("
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
        $stmt->execute([$customer_id]);
        $reward_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fehler beim Laden der Belohnungen: " . $e->getMessage());
    }
}

$primary_color = '#8B5CF6';
$company_name = $lead['company_name'] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company_name); ?> - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: <?php echo $primary_color; ?>;
            --primary-dark: color-mix(in srgb, <?php echo $primary_color; ?> 80%, black);
            --primary-light: color-mix(in srgb, <?php echo $primary_color; ?> 20%, white);
            --bg: #f5f7fa;
            --text: #1a1a1a;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        
        /* ===== HEADER ===== */
        .header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: var(--bg);
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        /* ===== MAIN CONTAINER ===== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 900;
            color: var(--primary);
        }
        
        /* ===== SECTIONS ===== */
        .section {
            background: white;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-icon {
            font-size: 28px;
        }
        
        /* ===== FREEBIE GRID ===== */
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .freebie-card {
            background: var(--bg);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .freebie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }
        
        .freebie-mockup {
            width: 100%;
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }
        
        .freebie-mockup img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .freebie-content {
            padding: 20px;
        }
        
        .freebie-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        .freebie-description {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 16px;
        }
        
        .freebie-actions {
            display: flex;
            gap: 8px;
        }
        
        .freebie-button {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-align: center;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .freebie-button:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }
        
        .share-button {
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .share-button:hover {
            background: #059669;
            transform: scale(1.02);
        }
        
        .share-button.copied {
            background: #3b82f6;
        }
        
        /* ===== FREEBIE SELECTOR ===== */
        .freebie-selector {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        
        .freebie-selector-btn {
            padding: 12px 24px;
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: var(--text);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .freebie-selector-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        
        .freebie-selector-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        /* ===== EMPFEHLUNGSPROGRAMM ===== */
        .referral-link-box {
            background: var(--primary-light);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .referral-link-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary-dark);
        }
        
        .referral-link-input {
            display: flex;
            gap: 12px;
        }
        
        .referral-link-input input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        
        .copy-btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .copy-btn:hover {
            background: var(--primary-dark);
        }
        
        /* ===== REWARD TIERS ===== */
        .reward-tier {
            background: var(--bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 4px solid var(--border);
            transition: all 0.3s;
        }
        
        .reward-tier.unlocked {
            border-left-color: #10b981;
            background: #d1fae5;
        }
        
        .reward-tier.claimed {
            border-left-color: var(--primary);
            background: var(--primary-light);
        }
        
        .reward-icon {
            font-size: 48px;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 16px;
        }
        
        .reward-info {
            flex: 1;
        }
        
        .reward-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .reward-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .reward-description {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .reward-requirement {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .reward-progress {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .reward-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transition: width 0.3s;
        }
        
        .reward-status {
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }
        
        .reward-status.locked {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .reward-status.unlocked {
            background: #10b981;
            color: white;
        }
        
        .reward-status.claimed {
            background: var(--primary);
            color: white;
        }
        
        /* ===== REFERRALS TABLE ===== */
        .referrals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .referrals-table th {
            background: var(--bg);
            padding: 12px;
            text-align: left;
            font-weight: 700;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .referrals-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.converted {
            background: #dbeafe;
            color: #1e3a8a;
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-text {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .empty-subtext {
            font-size: 14px;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
            
            .freebie-grid {
                grid-template-columns: 1fr;
            }
            
            .freebie-actions {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .reward-tier {
                flex-direction: column;
                text-align: center;
            }
            
            .referrals-table {
                font-size: 12px;
            }
            
            .referrals-table th,
            .referrals-table td {
                padding: 8px;
            }
            
            .referral-link-input {
                flex-direction: column;
            }
            
            .copy-btn {
                width: 100%;
            }
            
            .freebie-selector {
                flex-direction: column;
            }
            
            .freebie-selector-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo"><?php echo htmlspecialchars($company_name); ?></div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($lead['name']); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($lead['email']); ?></div>
                </div>
                <a href="/lead_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="container">
        
        <!-- Freebie Kurse -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">📚</span>
                    Deine Kurse
                </h2>
            </div>
            
            <?php if (empty($freebies_with_courses)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div class="empty-text">Noch keine Kurse verfügbar</div>
                    <div class="empty-subtext">Kurse werden hier angezeigt, sobald sie verfügbar sind</div>
                </div>
            <?php else: ?>
                <div class="freebie-grid">
                    <?php foreach ($freebies_with_courses as $freebie): ?>
                        <div class="freebie-card">
                            <div class="freebie-mockup">
                                <?php if (!empty($freebie['mockup_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($freebie['title']); ?>">
                                <?php else: ?>
                                    🎓
                                <?php endif; ?>
                            </div>
                            <div class="freebie-content">
                                <h3 class="freebie-title"><?php echo htmlspecialchars($freebie['title']); ?></h3>
                                <?php if (!empty($freebie['description'])): ?>
                                    <p class="freebie-description">
                                        <?php echo htmlspecialchars(substr($freebie['description'], 0, 120)); ?>
                                        <?php echo strlen($freebie['description']) > 120 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="freebie-actions">
                                    <?php if (!empty($freebie['course_id'])): ?>
                                        <a href="/customer/freebie-course-player.php?id=<?php echo $freebie['course_id']; ?>&email=<?php echo urlencode($lead['email']); ?>" 
                                           class="freebie-button">
                                            <i class="fas fa-play-circle"></i> Kurs starten
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($referral_enabled): ?>
                                        <button class="share-button" 
                                                onclick="shareFreebieLink('<?php echo htmlspecialchars($freebie['unique_id']); ?>', '<?php echo htmlspecialchars($freebie['title']); ?>', this)">
                                            <i class="fas fa-share-alt"></i> Teilen
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Empfehlungsprogramm (nur wenn aktiv UND Freebies vorhanden) -->
        <?php if ($referral_enabled && !empty($freebies_with_courses)): ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-label">Gesamt Empfehlungen</div>
                <div class="stat-value"><?php echo $total_referrals; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Erfolgreiche Empfehlungen</div>
                <div class="stat-value"><?php echo $successful_referrals; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-label">Eingelöste Belohnungen</div>
                <div class="stat-value"><?php echo count($claimed_rewards); ?></div>
            </div>
        </div>
        
        <!-- Freebie Auswahl für Empfehlungsprogramm -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">🎯</span>
                    Wähle dein Freebie zum Bewerben
                </h2>
            </div>
            
            <div class="freebie-selector">
                <?php foreach ($freebies_with_courses as $freebie): ?>
                    <a href="?freebie=<?php echo $freebie['freebie_id']; ?>" 
                       class="freebie-selector-btn <?php echo ($selected_freebie_id == $freebie['freebie_id']) ? 'active' : ''; ?>">
                        <i class="fas fa-gift"></i>
                        <?php echo htmlspecialchars($freebie['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($selected_freebie): ?>
            <!-- Empfehlungslink für gewähltes Freebie -->
            <div class="referral-link-box">
                <div class="referral-link-label">
                    Dein Empfehlungs-Link für: <strong><?php echo htmlspecialchars($selected_freebie['title']); ?></strong>
                </div>
                <div class="referral-link-input">
                    <input type="text" 
                           id="referralLink" 
                           value="<?php echo htmlspecialchars('https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . $selected_freebie['unique_id'] . '&ref=' . $lead['referral_code']); ?>" 
                           readonly>
                    <button class="copy-btn" onclick="copyReferralLink()">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
            
            <p style="color: var(--text-light); font-size: 14px;">
                <i class="fas fa-info-circle"></i> 
                Teile diesen Link für "<strong><?php echo htmlspecialchars($selected_freebie['title']); ?></strong>" und verdiene Belohnungen!
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Belohnungen für gewähltes Freebie -->
        <?php if (!empty($reward_tiers) && $selected_freebie): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">🎁</span>
                    Deine Belohnungen für "<?php echo htmlspecialchars($selected_freebie['title']); ?>"
                </h2>
            </div>
            
            <?php foreach ($reward_tiers as $tier): 
                $tier_id = $tier['id'];
                $is_claimed = false;
                foreach ($claimed_rewards as $claimed) {
                    if ($claimed['reward_id'] == $tier_id) {
                        $is_claimed = true;
                        break;
                    }
                }
                $is_unlocked = $successful_referrals >= $tier['required_referrals'];
                $status = $is_claimed ? 'claimed' : ($is_unlocked ? 'unlocked' : 'locked');
                $progress_percent = min(100, ($successful_referrals / $tier['required_referrals']) * 100);
                
                $icon_class = $tier['reward_icon'] ?? 'fa-gift';
                if (strpos($icon_class, 'fa-') !== 0) {
                    $icon_class = 'fa-gift';
                }
                
                $badge_color = $tier['reward_color'] ?? $primary_color;
            ?>
                <div class="reward-tier <?php echo $status; ?>">
                    <div class="reward-icon" style="color: <?php echo $badge_color; ?>">
                        <i class="fas <?php echo $icon_class; ?>"></i>
                    </div>
                    <div class="reward-info">
                        <div class="reward-badge" style="background: <?php echo $badge_color; ?>; color: white;">
                            <?php echo htmlspecialchars($tier['tier_name'] ?? 'Stufe ' . $tier['tier_level']); ?>
                        </div>
                        <div class="reward-title"><?php echo htmlspecialchars($tier['reward_title']); ?></div>
                        <?php if (!empty($tier['reward_description'])): ?>
                            <div class="reward-description"><?php echo htmlspecialchars($tier['reward_description']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($tier['reward_value'])): ?>
                            <div class="reward-description" style="font-weight: 600; color: <?php echo $badge_color; ?>;">
                                <?php echo htmlspecialchars($tier['reward_value']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="reward-requirement">
                            <?php echo $tier['required_referrals']; ?> erfolgreiche Empfehlungen benötigt
                            (<?php echo $successful_referrals; ?>/<?php echo $tier['required_referrals']; ?>)
                        </div>
                        <?php if (!$is_claimed && !$is_unlocked): ?>
                            <div class="reward-progress">
                                <div class="reward-progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="reward-status <?php echo $status; ?>">
                        <?php 
                        if ($is_claimed) {
                            echo '<i class="fas fa-check-circle"></i> Eingelöst';
                        } elseif ($is_unlocked) {
                            echo '<i class="fas fa-star"></i> Freigeschaltet!';
                        } else {
                            $remaining = $tier['required_referrals'] - $successful_referrals;
                            echo "<i class='fas fa-lock'></i> Noch {$remaining}";
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Deine Empfehlungen -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">👥</span>
                    Deine Empfehlungen
                </h2>
            </div>
            
            <?php if (empty($referrals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div class="empty-text">Noch keine Empfehlungen</div>
                    <div class="empty-subtext">Teile deinen Link und starte mit dem Empfehlen!</div>
                </div>
            <?php else: ?>
                <table class="referrals-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Status</th>
                            <th>Datum</th>
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
                                <td><?php echo date('d.m.Y', strtotime($referral['registered_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php endif; // Ende Empfehlungsprogramm ?>
        
    </div>
    
    <script>
        const leadReferralCode = '<?php echo $lead['referral_code']; ?>';
        
        function shareFreebieLink(uniqueId, title, button) {
            const link = `https://app.mehr-infos-jetzt.de/freebie/index.php?id=${uniqueId}&ref=${leadReferralCode}`;
            
            // In Zwischenablage kopieren
            navigator.clipboard.writeText(link).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                alert('Bitte kopiere den Link manuell');
            });
        }
        
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                btn.style.background = '#10b981';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
    </script>
</body>
</html>
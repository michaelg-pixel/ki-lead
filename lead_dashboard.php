<?php
session_start();

// Debugging (später entfernen)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// KONFIGURATION
require_once 'config.php';

// Sicherheitsprüfung
if (!isset($_SESSION['lead_id'])) {
    header('Location: login.php');
    exit();
}

$lead_id = $_SESSION['lead_id'];

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Lead-Daten abrufen
$stmt = $conn->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Referral-Daten laden (wenn aktiviert)
$referral_enabled = false;
$referral_link = '';
$referral_stats = ['total' => 0, 'active' => 0, 'pending' => 0];
$delivered_rewards = [];

if (file_exists('referral_config.php')) {
    require_once 'referral_config.php';
    if (REFERRAL_SYSTEM_ENABLED) {
        $referral_enabled = true;
        $referral_link = SITE_URL . '/register.php?ref=' . $lead['referral_code'];
        
        // Statistiken abrufen
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM leads 
            WHERE referred_by = ?
        ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $referral_stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Ausgelieferte Belohnungen abrufen
        $stmt = $conn->prepare("
            SELECT r.*, rt.name, rt.description, rt.delivery_type, rt.reward_value
            FROM referral_rewards r
            JOIN referral_tiers rt ON r.tier_id = rt.id
            WHERE r.user_id = ? AND r.status = 'delivered'
            ORDER BY r.delivered_at DESC
        ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $delivered_rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ===== MENÜ-NAVIGATION =====
$current_page = $_GET['page'] ?? 'dashboard';

$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard'],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse'],
];

if ($referral_enabled) {
    $menu_items['anleitung'] = ['icon' => 'fa-book-open', 'label' => 'So funktioniert\'s'];
    $menu_items['empfehlen'] = ['icon' => 'fa-share-alt', 'label' => 'Empfehlen'];
    if (!empty($delivered_rewards)) {
        $menu_items['belohnungen'] = ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen'];
    }
    $menu_items['social'] = ['icon' => 'fa-robot', 'label' => 'KI Social Assistant'];
}

// ===== FREEBIES ABRUFEN =====
$freebies = [];
$stmt = $conn->prepare("
    SELECT f.*, 
           COALESCE(flp.progress, 0) as user_progress,
           flp.last_accessed,
           flp.completed_at,
           (SELECT COUNT(*) FROM freebie_lessons WHERE freebie_id = f.id) as total_lessons
    FROM freebies f
    LEFT JOIN freebie_lead_progress flp ON f.id = flp.freebie_id AND flp.lead_id = ?
    WHERE f.status = 'active'
    ORDER BY f.sort_order ASC, f.created_at DESC
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $freebies[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KI Leadsystem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .logout-btn {
            float: right;
            background: #ff4757;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #ee5a6f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,71,87,0.3);
        }
        
        /* Navigation Tabs */
        .nav-tabs {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            padding: 12px 24px;
            border: none;
            background: #f8f9fa;
            color: #666;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .nav-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .nav-tab i {
            font-size: 16px;
        }
        
        /* Content Area */
        .content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .page-content {
            display: none;
        }
        
        .page-content.active {
            display: block;
        }
        
        /* Freebie Cards */
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .freebie-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .freebie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .freebie-mockup {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .freebie-content {
            padding: 25px;
        }
        
        .freebie-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .freebie-description {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .freebie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .lesson-count {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .lesson-count i {
            color: #667eea;
        }
        
        .progress-indicator {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .start-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* Referral Sections */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .referral-link-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .referral-link-box h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .link-input-group {
            display: flex;
            gap: 10px;
        }
        
        .link-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            font-family: monospace;
        }
        
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #5568d3;
        }
        
        .copy-btn.copied {
            background: #2ecc71;
        }
        
        /* Rewards */
        .reward-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .reward-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .reward-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .reward-date {
            font-size: 12px;
            color: #999;
        }
        
        .reward-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .reward-value {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .logout-btn {
                float: none;
                display: block;
                margin-top: 15px;
                text-align: center;
            }
            
            .nav-tabs {
                padding: 15px;
            }
            
            .nav-tab {
                flex: 1;
                min-width: calc(50% - 5px);
                justify-content: center;
            }
            
            .content {
                padding: 20px;
            }
            
            .freebie-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .link-input-group {
                flex-direction: column;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Abmelden
            </a>
            <h1>Willkommen zurück, <?php echo htmlspecialchars($lead['vorname']); ?>!</h1>
            <p>Schön, dass du da bist. Hier ist dein persönliches Dashboard.</p>
        </div>
        
        <!-- Navigation -->
        <div class="nav-tabs">
            <?php foreach ($menu_items as $page => $item): ?>
                <a href="?page=<?php echo $page; ?>" 
                   class="nav-tab <?php echo $current_page === $page ? 'active' : ''; ?>">
                    <i class="fas <?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Dashboard Page -->
            <div class="page-content <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <h2>Dein Dashboard</h2>
                <p style="color: #666; margin-top: 10px;">Hier findest du eine Übersicht über deine Aktivitäten.</p>
                
                <?php if ($referral_enabled): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['active']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($delivered_rewards); ?></div>
                        <div class="stat-label">Erhaltene Belohnungen</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 40px;">
                    <h3 style="margin-bottom: 20px;">Deine Kurse im Überblick</h3>
                    <?php if (empty($freebies)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Noch keine Kurse verfügbar</h3>
                            <p>Neue Kurse werden hier erscheinen, sobald sie verfügbar sind.</p>
                        </div>
                    <?php else: ?>
                        <div class="freebie-grid">
                            <?php foreach ($freebies as $freebie): 
                                $progress_percent = $freebie['total_lessons'] > 0 
                                    ? round(($freebie['user_progress'] / $freebie['total_lessons']) * 100) 
                                    : 0;
                            ?>
                                <div class="freebie-card" onclick="window.location.href='?page=kurse&freebie=<?php echo $freebie['id']; ?>'">
                                    <?php if ($freebie['mockup_image']): ?>
                                        <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($freebie['name']); ?>"
                                             class="freebie-mockup">
                                    <?php else: ?>
                                        <div class="freebie-mockup"></div>
                                    <?php endif; ?>
                                    
                                    <div class="freebie-content">
                                        <div class="freebie-title"><?php echo htmlspecialchars($freebie['name']); ?></div>
                                        <div class="freebie-description"><?php echo htmlspecialchars($freebie['description']); ?></div>
                                        
                                        <?php if ($progress_percent > 0): ?>
                                            <div class="progress-bar-container">
                                                <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%"></div>
                                            </div>
                                            <div class="progress-indicator"><?php echo $progress_percent; ?>% abgeschlossen</div>
                                        <?php endif; ?>
                                        
                                        <div class="freebie-meta">
                                            <span class="lesson-count">
                                                <i class="fas fa-play-circle"></i>
                                                <?php echo $freebie['total_lessons']; ?> Lektionen
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Kurse Page -->
            <div class="page-content <?php echo $current_page === 'kurse' ? 'active' : ''; ?>">
                <?php
                $freebie_id = isset($_GET['freebie']) ? intval($_GET['freebie']) : null;
                
                if ($freebie_id) {
                    // Einzelnen Kurs anzeigen
                    include 'freebie_view.php';
                } else {
                    // Alle Kurse anzeigen
                    ?>
                    <h2>Meine Kurse</h2>
                    <p style="color: #666; margin-top: 10px; margin-bottom: 30px;">Hier findest du alle deine verfügbaren Kurse und Trainings.</p>
                    
                    <?php if (empty($freebies)): ?>
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Noch keine Kurse verfügbar</h3>
                            <p>Neue Kurse werden hier erscheinen, sobald sie verfügbar sind.</p>
                        </div>
                    <?php else: ?>
                        <div class="freebie-grid">
                            <?php foreach ($freebies as $freebie): 
                                $progress_percent = $freebie['total_lessons'] > 0 
                                    ? round(($freebie['user_progress'] / $freebie['total_lessons']) * 100) 
                                    : 0;
                            ?>
                                <div class="freebie-card">
                                    <?php if ($freebie['mockup_image']): ?>
                                        <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($freebie['name']); ?>"
                                             class="freebie-mockup">
                                    <?php else: ?>
                                        <div class="freebie-mockup"></div>
                                    <?php endif; ?>
                                    
                                    <div class="freebie-content">
                                        <div class="freebie-title"><?php echo htmlspecialchars($freebie['name']); ?></div>
                                        <div class="freebie-description"><?php echo htmlspecialchars($freebie['description']); ?></div>
                                        
                                        <?php if ($progress_percent > 0): ?>
                                            <div class="progress-bar-container">
                                                <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%"></div>
                                            </div>
                                            <div class="progress-indicator"><?php echo $progress_percent; ?>% abgeschlossen</div>
                                        <?php endif; ?>
                                        
                                        <div class="freebie-meta">
                                            <span class="lesson-count">
                                                <i class="fas fa-play-circle"></i>
                                                <?php echo $freebie['total_lessons']; ?> Lektionen
                                            </span>
                                        </div>
                                        
                                        <button class="start-btn" onclick="window.location.href='?page=kurse&freebie=<?php echo $freebie['id']; ?>'">
                                            <?php echo $progress_percent > 0 ? 'Fortsetzen' : 'Starten'; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php } ?>
            </div>
            
            <?php if ($referral_enabled): ?>
            
            <!-- Anleitung Page -->
            <div class="page-content <?php echo $current_page === 'anleitung' ? 'active' : ''; ?>">
                <h2><i class="fas fa-book-open"></i> So funktioniert das Empfehlungsprogramm</h2>
                <p style="color: #666; margin-top: 10px; margin-bottom: 30px;">Verdiene attraktive Belohnungen, indem du Freunde einlädst!</p>
                
                <div style="background: #f8f9fa; border-radius: 12px; padding: 30px; margin: 20px 0;">
                    <h3 style="margin-bottom: 20px; color: #333;">📋 Die 3 einfachen Schritte:</h3>
                    
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #667eea; margin-bottom: 10px;">1. Teile deinen Link</h4>
                        <p style="color: #666; line-height: 1.6;">Nutze deinen persönlichen Empfehlungslink, um Freunde, Familie oder Bekannte einzuladen.</p>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #667eea; margin-bottom: 10px;">2. Deine Kontakte registrieren sich</h4>
                        <p style="color: #666; line-height: 1.6;">Sobald sich jemand über deinen Link registriert, wird die Empfehlung automatisch erfasst.</p>
                    </div>
                    
                    <div>
                        <h4 style="color: #667eea; margin-bottom: 10px;">3. Erhalte deine Belohnungen</h4>
                        <p style="color: #666; line-height: 1.6;">Je mehr Personen du erfolgreich empfiehlst, desto bessere Belohnungen erhältst du!</p>
                    </div>
                </div>
                
                <?php
                // Belohnungsstufen anzeigen
                $stmt = $conn->prepare("SELECT * FROM referral_tiers ORDER BY required_referrals ASC");
                $stmt->execute();
                $tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                if (!empty($tiers)):
                ?>
                <div style="margin-top: 40px;">
                    <h3 style="margin-bottom: 20px;">🎁 Deine Belohnungsstufen:</h3>
                    
                    <?php foreach ($tiers as $tier): ?>
                    <div class="reward-card">
                        <div class="reward-header">
                            <div>
                                <div class="reward-name"><?php echo htmlspecialchars($tier['name']); ?></div>
                                <p style="color: #999; font-size: 14px; margin-top: 5px;">
                                    Ab <?php echo $tier['required_referrals']; ?> erfolgreichen Empfehlungen
                                </p>
                            </div>
                            <div class="reward-value"><?php echo htmlspecialchars($tier['reward_value']); ?></div>
                        </div>
                        <div class="reward-description"><?php echo nl2br(htmlspecialchars($tier['description'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Empfehlen Page -->
            <div class="page-content <?php echo $current_page === 'empfehlen' ? 'active' : ''; ?>">
                <h2><i class="fas fa-share-alt"></i> Freunde empfehlen</h2>
                <p style="color: #666; margin-top: 10px;">Teile deinen persönlichen Link und verdiene Belohnungen!</p>
                
                <div class="referral-link-box">
                    <h3>Dein persönlicher Empfehlungslink:</h3>
                    <div class="link-input-group">
                        <input type="text" 
                               class="link-input" 
                               value="<?php echo htmlspecialchars($referral_link); ?>" 
                               id="referralLink" 
                               readonly>
                        <button class="copy-btn" onclick="copyReferralLink()">
                            <i class="fas fa-copy"></i> Kopieren
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['active']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['pending']; ?></div>
                        <div class="stat-label">Ausstehende Empfehlungen</div>
                    </div>
                </div>
                
                <?php
                // Referral-Liste abrufen
                $stmt = $conn->prepare("
                    SELECT vorname, nachname, email, status, created_at
                    FROM leads 
                    WHERE referred_by = ?
                    ORDER BY created_at DESC
                ");
                $stmt->bind_param("i", $lead_id);
                $stmt->execute();
                $referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                if (!empty($referrals)):
                ?>
                <div style="margin-top: 40px;">
                    <h3 style="margin-bottom: 20px;">Deine Empfehlungen:</h3>
                    <?php foreach ($referrals as $ref): 
                        $status_colors = [
                            'pending' => '#ffc107',
                            'active' => '#28a745',
                            'inactive' => '#dc3545'
                        ];
                        $status_labels = [
                            'pending' => 'Ausstehend',
                            'active' => 'Aktiv',
                            'inactive' => 'Inaktiv'
                        ];
                    ?>
                    <div class="reward-card">
                        <div class="reward-header">
                            <div>
                                <div class="reward-name">
                                    <?php echo htmlspecialchars($ref['vorname'] . ' ' . $ref['nachname']); ?>
                                </div>
                                <p style="color: #999; font-size: 14px; margin-top: 5px;">
                                    <?php echo htmlspecialchars($ref['email']); ?>
                                </p>
                            </div>
                            <div>
                                <span style="background: <?php echo $status_colors[$ref['status']]; ?>; color: white; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    <?php echo $status_labels[$ref['status']]; ?>
                                </span>
                            </div>
                        </div>
                        <p style="color: #999; font-size: 13px; margin-top: 10px;">
                            Registriert am <?php echo date('d.m.Y', strtotime($ref['created_at'])); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Belohnungen Page -->
            <?php if (!empty($delivered_rewards)): ?>
            <div class="page-content <?php echo $current_page === 'belohnungen' ? 'active' : ''; ?>">
                <h2><i class="fas fa-gift"></i> Meine Belohnungen</h2>
                <p style="color: #666; margin-top: 10px; margin-bottom: 30px;">Hier findest du alle Belohnungen, die du bereits erhalten hast.</p>
                
                <?php foreach ($delivered_rewards as $reward): ?>
                <div class="reward-card">
                    <div class="reward-header">
                        <div>
                            <div class="reward-name"><?php echo htmlspecialchars($reward['name']); ?></div>
                            <p class="reward-date">
                                Erhalten am <?php echo date('d.m.Y', strtotime($reward['delivered_at'])); ?>
                            </p>
                        </div>
                        <div class="reward-value"><?php echo htmlspecialchars($reward['reward_value']); ?></div>
                    </div>
                    <div class="reward-description"><?php echo nl2br(htmlspecialchars($reward['description'])); ?></div>
                    
                    <?php if ($reward['delivery_content']): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong style="color: #667eea;">Details:</strong>
                        <p style="margin-top: 8px; color: #666;"><?php echo nl2br(htmlspecialchars($reward['delivery_content'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- KI Social Assistant Page -->
            <div class="page-content <?php echo $current_page === 'social' ? 'active' : ''; ?>">
                <h2><i class="fas fa-robot"></i> KI Social Assistant</h2>
                <p style="color: #666; margin-top: 10px; margin-bottom: 30px;">Erstelle automatisch Posts für deine Social-Media-Kanäle.</p>
                
                <div class="empty-state">
                    <i class="fas fa-magic"></i>
                    <h3>Bald verfügbar!</h3>
                    <p>Der KI Social Assistant wird in Kürze freigeschaltet und hilft dir dabei,<br>automatisch ansprechende Posts für deine Empfehlungen zu erstellen.</p>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyReferralLink() {
            const input = document.getElementById('referralLink');
            const button = event.target.closest('.copy-btn');
            
            input.select();
            document.execCommand('copy');
            
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            button.classList.add('copied');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('copied');
            }, 2000);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>

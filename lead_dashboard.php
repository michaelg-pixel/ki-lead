<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['lead_id'])) {
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Session abgelaufen</title></head><body style="font-family:sans-serif;text-align:center;padding:100px"><h1>🔒 Session abgelaufen</h1><p>Bitte registriere dich erneut.</p></body></html>';
    exit();
}

$lead_id = $_SESSION['lead_id'];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Verbindung fehlgeschlagen");
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT * FROM lead_users WHERE id = ?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    session_destroy();
    die("Lead nicht gefunden.");
}

// Freebies mit KORREKTEN Spaltennamen
$freebies = [];
$stmt = $conn->prepare("
    SELECT 
        cf.id,
        cf.headline as name,
        cf.subheadline as description,
        cf.mockup_image_url as mockup_image,
        cf.customer_id,
        cf.unique_id,
        lfa.granted_at,
        (
            SELECT COUNT(*) 
            FROM freebie_course_lessons fcl
            INNER JOIN freebie_course_modules fcm ON fcl.module_id = fcm.id
            INNER JOIN freebie_courses fc ON fcm.course_id = fc.id
            WHERE fc.freebie_id = cf.id
        ) as total_lessons
    FROM customer_freebies cf
    INNER JOIN lead_freebie_access lfa ON cf.id = lfa.freebie_id
    WHERE lfa.lead_id = ?
    ORDER BY lfa.granted_at DESC
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $freebies[] = $row;
}
$stmt->close();

$referral_enabled = false;
$referral_link = '';
$referral_stats = ['total' => 0, 'pending' => 0];

if (!empty($lead['user_id'])) {
    $stmt = $conn->prepare("SELECT referral_enabled FROM users WHERE id = ?");
    $stmt->bind_param("i", $lead['user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user_data && $user_data['referral_enabled']) {
        $referral_enabled = true;
        
        // Link zur Freebie-Landingpage statt direkt zur Registrierung
        if (!empty($freebies) && !empty($freebies[0]['unique_id'])) {
            $referral_link = SITE_URL . '/freebie/?id=' . $freebies[0]['unique_id'] . '&ref=' . $lead['referral_code'];
        } else {
            $referral_link = SITE_URL . '/lead_register.php?freebie=' . $lead['freebie_id'] . '&customer=' . $lead['user_id'] . '&ref=' . $lead['referral_code'];
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending FROM lead_referrals WHERE referrer_id = ?");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $referral_stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$current_page = $_GET['page'] ?? 'dashboard';
$menu_items = ['dashboard' => ['icon' => '📊', 'label' => 'Dashboard'], 'kurse' => ['icon' => '🎓', 'label' => 'Meine Kurse']];
if ($referral_enabled) $menu_items['empfehlen'] = ['icon' => '🚀', 'label' => 'Empfehlen'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Dashboard</title>
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
            -webkit-overflow-scrolling: touch;
        }
        
        /* Content Styles */
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-header h2 {
            font-size: 28px;
            color: white;
            margin-bottom: 8px;
        }
        
        .page-header p {
            font-size: 14px;
            color: #888;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.2);
            padding: 24px;
            border-radius: 16px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #888;
        }
        
        /* Freebie Grid */
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .freebie-card {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .freebie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .freebie-mockup {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .freebie-content {
            padding: 24px;
        }
        
        .freebie-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .freebie-description {
            font-size: 14px;
            color: #888;
            line-height: 1.6;
            margin-bottom: 16px;
            min-height: 60px;
        }
        
        .freebie-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            font-size: 14px;
        }
        
        .start-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: all 0.3s;
        }
        
        .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Referral Box */
        .referral-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px dashed rgba(102, 126, 234, 0.3);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .referral-box h3 {
            color: white;
            margin-bottom: 16px;
            font-size: 18px;
        }
        
        .link-input-group {
            display: flex;
            gap: 12px;
        }
        
        .link-input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            font-size: 13px;
            font-family: monospace;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .copy-btn.copied {
            background: #2ecc71;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: white;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 16px;
            color: #888;
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
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-logo">
            <div class="mobile-logo-icon">🌟</div>
            <div class="mobile-logo-text">Mein Dashboard</div>
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
                <h1>Mein Dashboard</h1>
                <p>Lead Portal</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <?php foreach ($menu_items as $page => $item): ?>
                <a href="?page=<?php echo $page; ?>" class="nav-item <?php echo $current_page === $page ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($lead['name'] ?? 'L', 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($lead['name'] ?? 'Lead'); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($lead['email']); ?></div>
            </div>
            <a href="lead_logout.php" class="logout-btn" title="Abmelden">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <?php if ($current_page === 'dashboard'): ?>
                <div class="page-header">
                    <h2>Willkommen zurück<?php echo !empty($lead['name']) ? ', ' . htmlspecialchars($lead['name']) : ''; ?>! 👋</h2>
                    <p>Hier ist deine Übersicht über deine Kurse und Empfehlungen</p>
                </div>
                
                <?php if ($referral_enabled): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total'] - $referral_stats['pending']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($freebies); ?></div>
                        <div class="stat-label">Verfügbare Kurse</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="page-header" style="margin-top: 40px;">
                    <h2>Deine Kurse 🎓</h2>
                </div>
                
                <?php if (empty($freebies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Noch keine Kurse verfügbar</h3>
                        <p>Neue Kurse werden hier erscheinen, sobald sie verfügbar sind.</p>
                    </div>
                <?php else: ?>
                    <div class="freebie-grid">
                        <?php foreach ($freebies as $freebie): ?>
                            <div class="freebie-card" onclick="window.location.href='customer/view_freebie.php?id=<?php echo $freebie['id']; ?>'">
                                <?php if (!empty($freebie['mockup_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" alt="<?php echo htmlspecialchars($freebie['name'] ?? 'Kurs'); ?>" class="freebie-mockup">
                                <?php else: ?>
                                    <div class="freebie-mockup"><i class="fas fa-graduation-cap"></i></div>
                                <?php endif; ?>
                                
                                <div class="freebie-content">
                                    <div class="freebie-title"><?php echo htmlspecialchars($freebie['name'] ?? 'Unbenannter Kurs'); ?></div>
                                    <div class="freebie-description"><?php echo htmlspecialchars($freebie['description'] ?? 'Keine Beschreibung'); ?></div>
                                    <div class="freebie-meta">
                                        <i class="fas fa-play-circle"></i>
                                        <?php echo $freebie['total_lessons']; ?> Lektionen
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($current_page === 'kurse'): ?>
                <div class="page-header">
                    <h2>Meine Kurse 🎓</h2>
                    <p>Alle deine verfügbaren Kurse im Überblick</p>
                </div>
                
                <?php if (empty($freebies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Noch keine Kurse verfügbar</h3>
                        <p>Neue Kurse werden hier erscheinen.</p>
                    </div>
                <?php else: ?>
                    <div class="freebie-grid">
                        <?php foreach ($freebies as $freebie): ?>
                            <div class="freebie-card">
                                <?php if (!empty($freebie['mockup_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" alt="Kurs" class="freebie-mockup">
                                <?php else: ?>
                                    <div class="freebie-mockup"><i class="fas fa-graduation-cap"></i></div>
                                <?php endif; ?>
                                
                                <div class="freebie-content">
                                    <div class="freebie-title"><?php echo htmlspecialchars($freebie['name'] ?? 'Kurs'); ?></div>
                                    <div class="freebie-description"><?php echo htmlspecialchars($freebie['description'] ?? ''); ?></div>
                                    <div class="freebie-meta">
                                        <i class="fas fa-play-circle"></i>
                                        <?php echo $freebie['total_lessons']; ?> Lektionen
                                    </div>
                                    <button class="start-btn" onclick="window.location.href='customer/view_freebie.php?id=<?php echo $freebie['id']; ?>'">
                                        Kurs starten
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($current_page === 'empfehlen' && $referral_enabled): ?>
                <div class="page-header">
                    <h2>Freunde empfehlen 🚀</h2>
                    <p>Teile deinen persönlichen Link und zeige deinen Freunden dieses großartige Angebot!</p>
                </div>
                
                <div class="referral-box">
                    <h3>Dein Empfehlungslink:</h3>
                    <div class="link-input-group">
                        <input type="text" class="link-input" value="<?php echo htmlspecialchars($referral_link); ?>" id="referralLink" readonly>
                        <button class="copy-btn" onclick="copyReferralLink()"><i class="fas fa-copy"></i> Kopieren</button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total'] - $referral_stats['pending']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['pending']; ?></div>
                        <div class="stat-label">Ausstehend</div>
                    </div>
                </div>
            <?php endif; ?>
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

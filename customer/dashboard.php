<?php
session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Kunde';
$customer_email = $_SESSION['email'] ?? '';

// Aktuelle Seite bestimmen
$page = $_GET['page'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opt in Pilot - Kunden Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <?php if ($page === 'empfehlungsprogramm'): ?>
    <!-- Tailwind CSS für Empfehlungsprogramm-Seite -->
    <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
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
        
        /* Menüpunkte kleiner gemacht */
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
            -webkit-overflow-scrolling: touch;
        }
        
        /* Responsive Styles */
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
        }
        
        @media (max-width: 480px) {
            .mobile-logo-text {
                font-size: 14px;
            }
            
            .logo-text h1 {
                font-size: 16px;
            }
            
            .logo-text p {
                font-size: 11px;
            }
            
            .nav-item {
                padding: 9px 12px;
                font-size: 13px;
            }
            
            .user-section {
                padding: 12px 16px;
            }
        }
        
        @media (hover: none) and (pointer: coarse) {
            .nav-item:active {
                background: rgba(102, 126, 234, 0.2);
            }
        }
    </style>
    
    <!-- Chatling.ai Chatbot -->
    <script>
        window.chtlConfig = { chatbotId: "1774412542" }
    </script>
    <script async data-id="1774412542" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="mobile-logo">
            <div class="mobile-logo-icon">🌟</div>
            <div class="mobile-logo-text">Opt in Pilot</div>
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
                <h1>Opt in Pilot</h1>
                <p>Kunden Portal</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="?page=overview" class="nav-item <?php echo $page === 'overview' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">📊</span>
                <span>Übersicht</span>
            </a>
            <a href="?page=tutorials" class="nav-item <?php echo $page === 'tutorials' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">📖</span>
                <span>Anleitungen & Tutorials</span>
            </a>
            <a href="legal-texts.php" class="nav-item" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">⚖️</span>
                <span>Rechtstexte</span>
            </a>
            <a href="?page=kurse" class="nav-item <?php echo $page === 'kurse' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🎓</span>
                <span>Freebies / Kurse</span>
            </a>
            <a href="?page=freebies" class="nav-item <?php echo $page === 'freebies' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🎁</span>
                <span>Landingpages</span>
            </a>
            <a href="?page=empfehlungsprogramm" class="nav-item <?php echo $page === 'empfehlungsprogramm' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🚀</span>
                <span>Empfehlungsprogramm</span>
            </a>
            <a href="?page=belohnungsstufen" class="nav-item <?php echo $page === 'belohnungsstufen' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🏆</span>
                <span>Belohnungsstufen</span>
            </a>
            <a href="?page=marktplatz-browse" class="nav-item <?php echo $page === 'marktplatz-browse' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🛍️</span>
                <span>Marktplatz durchsuchen</span>
            </a>
            <a href="?page=marktplatz" class="nav-item <?php echo $page === 'marktplatz' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🏪</span>
                <span>Meine Marktplatz-Angebote</span>
            </a>
            <a href="?page=ki-prompt" class="nav-item <?php echo $page === 'ki-prompt' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">🤖</span>
                <span>KI Agent</span>
            </a>
            <a href="?page=einstellungen" class="nav-item <?php echo $page === 'einstellungen' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">
                <span class="nav-icon">⚙️</span>
                <span>Einstellungen</span>
            </a>
        </nav>
        
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($customer_name, 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($customer_name); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($customer_email); ?></div>
            </div>
            <a href="/public/logout.php" class="logout-btn" title="Abmelden">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <?php if ($page === 'overview'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/overview.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Dashboard wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'tutorials'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/tutorials.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Tutorials werden geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'kurse'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/kurse.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Seite wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'freebies'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/freebies.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Seite wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'empfehlungsprogramm'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/empfehlungsprogramm.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Empfehlungsprogramm wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'belohnungsstufen'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/belohnungsstufen.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Belohnungsstufen werden geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'marktplatz-browse'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/marktplatz-browse.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Marktplatz wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'marktplatz'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/marktplatz.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Marktplatz wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'ki-prompt'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/ki-prompt.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Seite wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'einstellungen'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/einstellungen.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Seite wird geladen...</h3></div>';
                }
                ?>
            
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
        
        // Close sidebar on window resize if it's open
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>
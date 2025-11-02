<?php
session_start();

// WICHTIG: Korrekte Session-Variable-Namen verwenden!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once '../config/database.php';
$pdo = getDBConnection();

// Statistiken holen
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'tutorials' => $pdo->query("SELECT COUNT(*) FROM tutorials")->fetchColumn(),
    'freebies' => $pdo->query("SELECT COUNT(*) FROM freebies")->fetchColumn(),
];

$admin_name = $_SESSION['name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? '';

// Aktuelle Seite bestimmen
$page = $_GET['page'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KI Leadsystem</title>
    
    <!-- Tailwind CSS vorab laden für freebie-edit -->
    <?php if ($page === 'freebie-edit' || $page === 'freebie-create'): ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Loading Screen für Editor-Seiten */
        .page-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0a16;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            opacity: 1;
            transition: opacity 0.3s ease-out;
        }
        
        .page-loading.loaded {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(168, 85, 247, 0.2);
            border-top-color: #a855f7;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <?php endif; ?>
    
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <!-- Loading Screen für Editor-Seiten -->
    <?php if ($page === 'freebie-edit' || $page === 'freebie-create'): ?>
    <div class="page-loading" id="pageLoading">
        <div class="spinner"></div>
    </div>
    <script>
        // Loading Screen nach DOM-Ready ausblenden
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loader = document.getElementById('pageLoading');
                if (loader) {
                    loader.classList.add('loaded');
                    setTimeout(() => loader.remove(), 300);
                }
            }, 100);
        });
    </script>
    <?php endif; ?>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
    <!-- Sidebar Overlay für Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon">⭐</div>
            <div class="logo-text">
                <h1>Admin Panel</h1>
                <p>Management Dashboard</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="?page=overview" class="nav-item <?php echo $page === 'overview' ? 'active' : ''; ?>">
                <span class="nav-icon">▦</span>
                <span>Übersicht</span>
            </a>
            <a href="?page=users" class="nav-item <?php echo $page === 'users' ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span>Kunden</span>
            </a>
            <a href="?page=tutorials" class="nav-item <?php echo $page === 'tutorials' ? 'active' : ''; ?>">
                <span class="nav-icon">📖</span>
                <span>Anleitungen & Tutorials</span>
            </a>
            <a href="?page=freebies" class="nav-item <?php echo $page === 'freebies' ? 'active' : ''; ?>">
                <span class="nav-icon">🔗</span>
                <span>Kurs-Freebies</span>
            </a>
            <a href="?page=templates" class="nav-item <?php echo $page === 'templates' ? 'active' : ''; ?>">
                <span class="nav-icon">📚</span>
                <span>Kursverwaltung</span>
            </a>
            <a href="?page=social" class="nav-item <?php echo $page === 'social' ? 'active' : ''; ?>">
                <span class="nav-icon">📱</span>
                <span>Social Media</span>
            </a>
            <a href="?page=digistore" class="nav-item <?php echo $page === 'digistore' ? 'active' : ''; ?>">
                <span class="nav-icon">🛒</span>
                <span>Digistore24</span>
            </a>
            <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span>Einstellungen</span>
            </a>
            <a href="?page=profile" class="nav-item <?php echo $page === 'profile' ? 'active' : ''; ?>">
                <span class="nav-icon">👤</span>
                <span>Admin-Profil</span>
            </a>
        </nav>
        
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($admin_email); ?></div>
            </div>
            <a href="/logout.php" class="logout-btn" title="Abmelden">↪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><?php 
                    $titles = [
                        'overview' => 'Dashboard Übersicht',
                        'users' => 'Kundenverwaltung',
                        'courses' => 'Kurse verwalten',
                        'freebies' => 'Freebie Templates',
                        'freebie-create' => 'Neues Freebie Template',
                        'freebie-edit' => 'Template bearbeiten',
                        'templates' => 'Kursverwaltung',
                        'course-edit' => 'Kurs bearbeiten',
                        'tutorials' => 'Anleitungen & Tutorials verwalten',
                        'settings' => 'Einstellungen',
                        'profile' => 'Admin-Profil'
                    ];
                    echo $titles[$page] ?? 'Dashboard';
                ?></h2>
                <p class="topbar-subtitle">Willkommen zurück, <?php echo htmlspecialchars($admin_name); ?></p>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn" title="Einstellungen">⚙️</button>
                <button class="icon-btn profile-btn" title="Profil">
                    <span class="profile-avatar">M</span>
                    <span class="profile-info">
                        <span class="profile-name"><?php echo htmlspecialchars($admin_name); ?></span>
                        <span class="profile-email"><?php echo htmlspecialchars($admin_email); ?></span>
                    </span>
                    <span>↪</span>
                </button>
            </div>
        </div>
        
        <div class="content-area">
            <?php if ($page === 'overview'): ?>
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">👥</div>
                            <div class="stat-change">+12%</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['users']; ?></div>
                        <div class="stat-label">Registrierte Kunden</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📚</div>
                            <div class="stat-change">+8%</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['courses']; ?></div>
                        <div class="stat-label">Aktive Kurse</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">🎁</div>
                            <div class="stat-change">+15%</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['freebies']; ?></div>
                        <div class="stat-label">Freebie Templates</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📖</div>
                            <div class="stat-change">+5%</div>
                        </div>
                        <div class="stat-value"><?php echo $stats['tutorials']; ?></div>
                        <div class="stat-label">Tutorials</div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">Neueste Kunden</h3>
                        <a href="?page=users" class="btn">Alle ansehen</a>
                    </div>
                    <?php
                    $recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                    if (count($recent_users) > 0):
                    ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>E-Mail</th>
                                    <th>Rolle</th>
                                    <th>Registriert</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td data-label="Name"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td data-label="E-Mail"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="Rolle"><span class="badge badge-<?php echo $user['role']; ?>"><?php echo strtoupper($user['role']); ?></span></td>
                                    <td data-label="Registriert"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <p>Noch keine Kunden registriert</p>
                    </div>
                    <?php endif; ?>
                </div>
            
            <?php elseif ($page === 'users'): ?>
                <?php include 'sections/users.php'; ?>
            
            <?php elseif ($page === 'courses'): ?>
                <?php include 'sections/courses.php'; ?>
            
            <?php elseif ($page === 'freebies'): ?>
                <?php include 'sections/freebies.php'; ?>
            
            <?php elseif ($page === 'freebie-create'): ?>
                <?php include 'sections/freebie-create.php'; ?>
            
            <?php elseif ($page === 'freebie-edit'): ?>
                <?php include 'sections/freebie-edit.php'; ?>
            
            <?php elseif ($page === 'templates'): ?>
                <?php include 'sections/templates.php'; ?>
            
            <?php elseif ($page === 'course-edit'): ?>
                <?php include 'sections/course-edit.php'; ?>
            
            <?php elseif ($page === 'tutorials'): ?>
                <?php include 'sections/tutorials.php'; ?>
            
            <?php elseif ($page === 'settings'): ?>
                <?php include 'sections/settings.php'; ?>
            
            <?php elseif ($page === 'profile'): ?>
                <?php include 'sections/profile.php'; ?>
            
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* ANGEPASSTE FARBEN - Basierend auf Screenshot */
        :root {
            /* Hintergrundfarben - Dunklere violett-blaue Töne */
            --bg-primary: #0a0a16;
            --bg-secondary: #1a1532;
            --bg-tertiary: #252041;
            --bg-card: #2a2550;
            
            /* Primärfarben - Violett/Lila Töne */
            --primary: #a855f7;
            --primary-dark: #8b40d1;
            --primary-light: #c084fc;
            
            /* Akzentfarben */
            --accent: #f59e0b;
            --success: #4ade80;
            --success-dark: #22c55e;
            --danger: #fb7185;
            --danger-dark: #f43f5e;
            --warning: #fbbf24;
            
            /* Text-Farben */
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            
            /* Borders */
            --border: rgba(168, 85, 247, 0.2);
            --border-light: rgba(255, 255, 255, 0.05);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* MOBILE MENU TOGGLE */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            width: 44px;
            height: 44px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 0;
            transition: all 0.3s;
        }
        
        .mobile-menu-toggle span {
            width: 20px;
            height: 2px;
            background: var(--text-primary);
            transition: all 0.3s;
            display: block;
        }
        
        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        
        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }
        
        /* SIDEBAR OVERLAY */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
        }
        
        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px 24px;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 24px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .logo-text h1 {
            font-size: 16px;
            color: white;
            font-weight: 700;
        }
        
        .logo-text p {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .nav-menu {
            flex: 1;
            padding: 0 12px;
            overflow-y: auto;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 2px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-item:hover {
            background: rgba(168, 85, 247, 0.1);
            color: var(--primary-light);
        }
        
        .nav-item.active {
            background: rgba(168, 85, 247, 0.15);
            color: white;
            position: relative;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--primary);
            border-radius: 0 2px 2px 0;
        }
        
        .nav-icon {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .user-section {
            padding: 16px 20px;
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: white;
        }
        
        .user-email {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .logout-btn {
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 18px;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .logout-btn:hover {
            color: var(--danger);
        }
        
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-light);
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar-left h2 {
            font-size: 24px;
            color: white;
            margin-bottom: 4px;
        }
        
        .topbar-subtitle {
            font-size: 13px;
            color: var(--text-muted);
        }
        
        .topbar-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .icon-btn {
            width: 40px;
            height: 40px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
            color: var(--text-secondary);
        }
        
        .icon-btn:hover {
            background: rgba(168, 85, 247, 0.2);
            color: var(--primary-light);
        }
        
        .profile-btn {
            width: auto;
            padding: 0 12px;
            gap: 10px;
        }
        
        .profile-avatar {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .profile-name {
            font-size: 13px;
            font-weight: 600;
            color: white;
        }
        
        .profile-email {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
            background: var(--bg-primary);
        }
        
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(168, 85, 247, 0.2);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-change {
            font-size: 14px;
            font-weight: 600;
            color: var(--success);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        /* SECTIONS */
        .section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }
        
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }
        
        /* TABLE CONTAINER für horizontales Scrollen */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* TABLES */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        
        th {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: var(--text-primary);
            font-size: 14px;
        }
        
        tbody tr {
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: rgba(168, 85, 247, 0.05);
        }
        
        /* BADGES */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-admin {
            background: rgba(168, 85, 247, 0.2);
            color: var(--primary-light);
        }
        
        .badge-customer {
            background: rgba(255,255,255,0.1);
            color: var(--text-secondary);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* ============================================
           RESPONSIVE DESIGN - MOBILE OPTIMIERUNG
           ============================================ */
        
        /* Tablets und kleine Desktops */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            
            .content-area {
                padding: 24px;
            }
            
            .topbar {
                padding: 16px 24px;
            }
        }
        
        /* Mobile Geräte */
        @media (max-width: 768px) {
            /* Mobile Menu Toggle anzeigen */
            .mobile-menu-toggle {
                display: flex;
            }
            
            /* Sidebar Overlay anzeigen wenn aktiv */
            .sidebar-overlay.active {
                display: block;
            }
            
            /* Sidebar für Mobile */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            /* Main Content nimmt volle Breite */
            .main-content {
                width: 100%;
            }
            
            /* Topbar anpassen */
            .topbar {
                padding: 16px;
                padding-left: 70px; /* Platz für Menu Button */
            }
            
            .topbar-left h2 {
                font-size: 18px;
            }
            
            .topbar-subtitle {
                display: none; /* Subtitle auf Mobile ausblenden */
            }
            
            /* Profile Button vereinfachen */
            .profile-btn .profile-info {
                display: none;
            }
            
            .profile-btn > span:last-child {
                display: none;
            }
            
            .icon-btn {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
            
            /* Content Area */
            .content-area {
                padding: 16px;
            }
            
            /* Stats Grid - Eine Spalte auf sehr kleinen Geräten */
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            /* Sections */
            .section {
                padding: 16px;
                margin-bottom: 16px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-title {
                font-size: 16px;
            }
            
            /* Buttons */
            .btn {
                width: 100%;
                text-align: center;
                padding: 12px 16px;
            }
            
            /* Tables - Responsive Ansicht */
            table {
                min-width: 100%;
                font-size: 13px;
            }
            
            th, td {
                padding: 8px;
            }
            
            /* Optional: Card-Style für Tabellen auf kleinen Geräten */
            @media (max-width: 600px) {
                .table-container table,
                .table-container thead,
                .table-container tbody,
                .table-container th,
                .table-container td,
                .table-container tr {
                    display: block;
                }
                
                .table-container thead tr {
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                }
                
                .table-container tr {
                    margin-bottom: 12px;
                    border: 1px solid var(--border);
                    border-radius: 8px;
                    padding: 12px;
                    background: var(--bg-tertiary);
                }
                
                .table-container td {
                    border: none;
                    position: relative;
                    padding: 8px 8px 8px 120px;
                    text-align: right;
                }
                
                .table-container td:before {
                    content: attr(data-label);
                    position: absolute;
                    left: 8px;
                    width: 100px;
                    padding-right: 10px;
                    white-space: nowrap;
                    font-weight: 600;
                    color: var(--text-secondary);
                    text-align: left;
                }
                
                .badge {
                    display: inline-block;
                }
            }
        }
        
        /* Sehr kleine Mobile Geräte */
        @media (max-width: 480px) {
            .topbar-left h2 {
                font-size: 16px;
            }
            
            .mobile-menu-toggle {
                width: 40px;
                height: 40px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .stat-label {
                font-size: 12px;
            }
            
            /* Stats auf zwei Spalten bei sehr kleinen Geräten optional */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    
    <script>
        // Mobile Menu Toggle Funktionalität
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            function toggleMenu() {
                menuToggle.classList.toggle('active');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // Body Scroll verhindern wenn Menu offen
                if (sidebar.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
            
            // Menu Toggle Button
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleMenu);
            }
            
            // Overlay Click zum Schließen
            if (overlay) {
                overlay.addEventListener('click', toggleMenu);
            }
            
            // Navigation Items schließen Menu auf Mobile
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleMenu();
                    }
                });
            });
            
            // Bei Resize über 768px Menu zurücksetzen
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    menuToggle.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>

<?php
// Sichere Session-Konfiguration laden - MUSS vor session_start() sein!
require_once __DIR__ . '/../config/security.php';

// Starte sichere Session mit 90-Tage Konfiguration
startSecureSession();

// Login-Check mit sicherer Funktion
requireLogin('/public/login.php');

// WICHTIG: Admin-Rollen-Prüfung
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

// 🔥 CSV EXPORT HANDLER - GANZ OBEN, VOR JEGLICHEM HTML OUTPUT!
if (isset($_GET['page']) && $_GET['page'] === 'referrals' && isset($_GET['export']) && isset($_GET['customer']) && !empty($_GET['customer'])) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    $customer_id = (int)$_GET['customer'];
    
    // Customer-Daten holen
    $stmt = $pdo->prepare("SELECT name, company_name FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lead Users für diesen Customer
    $stmt = $pdo->prepare("
        SELECT 
            lu.id,
            lu.email,
            lu.name,
            lu.referral_code,
            lu.successful_referrals,
            lu.total_referrals,
            lu.rewards_earned,
            lu.status,
            lu.created_at,
            (SELECT COUNT(*) FROM lead_users WHERE referrer_id = lu.id) as referred_count
        FROM lead_users lu
        WHERE lu.user_id = ?
        ORDER BY lu.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CSV generieren
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads_customer_' . $customer_id . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM für Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, [
        'ID',
        'E-Mail',
        'Name',
        'Referral Code',
        'Erfolgreiche Empfehlungen',
        'Gesamt Empfehlungen',
        'Belohnungen',
        'Hat empfohlen',
        'Status',
        'Registriert am'
    ], ';');
    
    // Daten
    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['id'],
            $lead['email'],
            $lead['name'] ?? 'Lead',
            $lead['referral_code'],
            $lead['successful_referrals'],
            $lead['total_referrals'],
            $lead['rewards_earned'],
            $lead['referred_count'],
            $lead['status'],
            date('d.m.Y H:i', strtotime($lead['created_at']))
        ], ';');
    }
    
    fclose($output);
    exit; // WICHTIG: Sofort beenden, KEIN HTML!
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
    
    <!-- CSS mit Cache-Buster v3.0 -->
    <link rel="stylesheet" href="styles/dashboard.css?v=3.0">
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
            <a href="?page=referrals" class="nav-item <?php echo $page === 'referrals' ? 'active' : ''; ?>">
                <span class="nav-icon">🚀</span>
                <span>Empfehlungen</span>
            </a>
            <a href="?page=jv-check" class="nav-item <?php echo $page === 'jv-check' ? 'active' : ''; ?>">
                <span class="nav-icon">✅</span>
                <span>JV Check Verifizierung</span>
            </a>
            <a href="?page=offers" class="nav-item <?php echo $page === 'offers' ? 'active' : ''; ?>">
                <span class="nav-icon">🎯</span>
                <span>Angebote</span>
            </a>
            <a href="/admin/av-contract-acceptances.php" class="nav-item">
                <span class="nav-icon">🔒</span>
                <span>AV-Zustimmungen</span>
            </a>
            <?php /* AUSGEBLENDET: Digistore24 Menüpunkt - kann bei Bedarf wieder aktiviert werden
            <a href="?page=digistore" class="nav-item <?php echo $page === 'digistore' ? 'active' : ''; ?>">
                <span class="nav-icon">🛒</span>
                <span>Digistore24</span>
            </a>
            */ ?>
            <a href="?page=webhooks" class="nav-item <?php echo $page === 'webhooks' ? 'active' : ''; ?>">
                <span class="nav-icon">🔗</span>
                <span>Webhooks</span>
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
            <a href="/public/logout.php" class="logout-btn" title="Abmelden">↪</a>
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
                        'referrals' => 'Empfehlungsprogramm-Übersicht',
                        'jv-check' => 'JV Check Verifizierung',
                        'offers' => 'Angebote verwalten',
                        'digistore' => 'Digistore24 Integration',
                        'webhooks' => 'Flexible Webhook-Verwaltung',
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
                
                <!-- DSGVO / Compliance Section -->
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">🔒 DSGVO & Compliance</h3>
                    </div>
                    <div class="table-container">
                        <div style="padding: 20px;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                                <a href="/admin/av-contract-acceptances.php" style="text-decoration: none; color: inherit;">
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; transition: transform 0.2s;">
                                        <div style="font-size: 32px; margin-bottom: 12px;">🔒</div>
                                        <h4 style="font-size: 18px; margin-bottom: 8px;">AV-Vertrags-Zustimmungen</h4>
                                        <p style="font-size: 14px; opacity: 0.9;">DSGVO-konforme Nachweispflicht gem. Art. 28 DSGVO</p>
                                        <div style="margin-top: 12px; font-size: 12px; opacity: 0.8;">
                                            Alle Zustimmungen anzeigen →
                                        </div>
                                    </div>
                                </a>
                                
                                <a href="/migrations/migrate-av-contract.html" style="text-decoration: none; color: inherit;">
                                    <div style="background: #f9fafb; border: 2px solid #e5e7eb; padding: 20px; border-radius: 12px; transition: all 0.2s;">
                                        <div style="font-size: 32px; margin-bottom: 12px;">⚙️</div>
                                        <h4 style="font-size: 18px; margin-bottom: 8px; color: #1f2937;">Migration durchführen</h4>
                                        <p style="font-size: 14px; color: #6b7280;">Erstelle die Datenbanktabelle für AV-Zustimmungen</p>
                                        <div style="margin-top: 12px; font-size: 12px; color: #8b5cf6;">
                                            Zur Migration →
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
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
            
            <?php elseif ($page === 'referrals'): ?>
                <?php include 'sections/referrals.php'; ?>
            
            <?php elseif ($page === 'jv-check'): ?>
                <?php include 'sections/jv-check.php'; ?>
            
            <?php elseif ($page === 'offers'): ?>
                <?php include 'sections/offers.php'; ?>
            
            <?php elseif ($page === 'digistore'): ?>
                <?php include 'sections/digistore.php'; ?>
            
            <?php elseif ($page === 'webhooks'): ?>
                <?php include 'sections/webhooks.php'; ?>
            
            <?php elseif ($page === 'settings'): ?>
                <?php include 'sections/settings.php'; ?>
            
            <?php elseif ($page === 'profile'): ?>
                <?php include 'sections/profile.php'; ?>
            
            <?php endif; ?>
        </div>
    </div>
    
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

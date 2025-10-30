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
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">🌟</div>
            <div class="logo-text">
                <h1>KI Leadsystem</h1>
                <p>Admin Panel</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="?page=overview" class="nav-item <?php echo $page === 'overview' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span>Übersicht</span>
            </a>
            <a href="?page=users" class="nav-item <?php echo $page === 'users' ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span>Kunden</span>
            </a>
            <a href="?page=courses" class="nav-item <?php echo $page === 'courses' ? 'active' : ''; ?>">
                <span class="nav-icon">📚</span>
                <span>Kurse</span>
            </a>
            <a href="?page=freebies" class="nav-item <?php echo $page === 'freebies' ? 'active' : ''; ?>">
                <span class="nav-icon">🎁</span>
                <span>Freebie Templates</span>
            </a>
            <a href="?page=tutorials" class="nav-item <?php echo $page === 'tutorials' ? 'active' : ''; ?>">
                <span class="nav-icon">📖</span>
                <span>Tutorials</span>
            </a>
            <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span>Einstellungen</span>
            </a>
        </nav>
        
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($admin_email); ?></div>
            </div>
            <a href="/public/logout.php" class="logout-btn" title="Logout">🚪</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h2><?php 
                $titles = [
                    'overview' => 'Dashboard Übersicht',
                    'users' => 'Kunden verwalten',
                    'courses' => 'Kurse verwalten',
                    'freebies' => 'Freebie Templates',
                    'freebie-create' => 'Neues Freebie Template',
                    'freebie-edit' => 'Template bearbeiten',
                    'tutorials' => 'Tutorials verwalten',
                    'settings' => 'Einstellungen'
                ];
                echo $titles[$page] ?? 'Dashboard';
            ?></h2>
            <p>Willkommen zurück, <?php echo htmlspecialchars($admin_name); ?>!</p>
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
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo strtoupper($user['role']); ?></span></td>
                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            
            <?php elseif ($page === 'tutorials'): ?>
                <?php include 'sections/tutorials.php'; ?>
            
            <?php elseif ($page === 'settings'): ?>
                <?php include 'sections/settings.php'; ?>
            
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Alle Styles hier inline, da styles/dashboard.css nicht existiert */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
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
            font-size: 20px;
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
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #999;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
            cursor: pointer;
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
            font-size: 20px;
            width: 24px;
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
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .user-email {
            font-size: 12px;
            color: #888;
        }
        
        .logout-btn {
            color: #ff6b6b;
            cursor: pointer;
            font-size: 20px;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .topbar {
            background: #1a1a2e;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px 32px;
        }
        
        .topbar h2 {
            font-size: 24px;
            color: white;
        }
        
        .topbar p {
            font-size: 14px;
            color: #888;
            margin-top: 4px;
        }
        
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 16px;
            padding: 24px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-change {
            font-size: 14px;
            color: #4ade80;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #888;
        }
        
        .section {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }
        
        .section-subtitle {
            font-size: 14px;
            color: #888;
            margin-top: 4px;
        }
        
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        th {
            color: #888;
            font-weight: 500;
            font-size: 14px;
        }
        
        td {
            color: #e0e0e0;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .badge-customer {
            background: rgba(255,255,255,0.1);
            color: #888;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
    </style>
</body>
</html>
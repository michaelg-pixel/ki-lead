<?php
/**
 * Quick Fix Script: Fügt CSS zum Admin Dashboard hinzu
 * Rufen Sie diese Datei einmalig auf: https://app.mehr-infos-jetzt.de/fix-admin-css.php
 */

$dashboardFile = __DIR__ . '/admin/dashboard.php';

if (!file_exists($dashboardFile)) {
    die("❌ admin/dashboard.php nicht gefunden!");
}

$content = file_get_contents($dashboardFile);

// Prüfe ob CSS-Link vorhanden ist
if (strpos($content, '<link rel="stylesheet" href="styles/dashboard.css">') !== false) {
    // Ersetze CSS-Link durch inline CSS
    $css = <<<'CSS'
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
        
        /* TABLE CONTAINER */
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
        
        /* RESPONSIVE */
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
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
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
            
            .main-content {
                width: 100%;
            }
            
            .topbar {
                padding: 16px;
                padding-left: 70px;
            }
            
            .topbar-left h2 {
                font-size: 18px;
            }
            
            .topbar-subtitle {
                display: none;
            }
            
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
            
            .content-area {
                padding: 16px;
            }
            
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
            
            .btn {
                width: 100%;
                text-align: center;
                padding: 12px 16px;
            }
            
            table {
                min-width: 100%;
                font-size: 13px;
            }
            
            th, td {
                padding: 8px;
            }
            
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
CSS;
    
    $content = str_replace('<link rel="stylesheet" href="styles/dashboard.css">', $css, $content);
    
    // Backup erstellen
    $backup = __DIR__ . '/admin/dashboard-backup-' . date('YmdHis') . '.php';
    copy($dashboardFile, $backup);
    
    // Schreiben
    file_put_contents($dashboardFile, $content);
    
    echo "✅ CSS zum Admin Dashboard hinzugefügt!<br>";
    echo "✅ Backup erstellt: " . basename($backup) . "<br>";
    echo "<br>Testen Sie: <a href='/admin/dashboard.php'>Admin Dashboard</a>";
    
} else {
    echo "ℹ️ CSS ist bereits inline vorhanden oder wurde schon gefixt.";
}
?>
<?php
/**
 * Customer Dashboard - Overview Section
 * KORRIGIERT: Verwendet die richtigen Tabellen (customer_freebies, freebie_click_analytics, etc.)
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// ===== ECHTE STATISTIKEN ABRUFEN =====
try {
    // Freigeschaltete Freebies - KORRIGIERT: customer_freebies Tabelle (template-basierte Freebies)
    $stmt_freebies = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customer_freebies 
        WHERE customer_id = ? 
        AND (freebie_type = 'template' OR freebie_type IS NULL)
    ");
    $stmt_freebies->execute([$customer_id]);
    $freebies_unlocked = $stmt_freebies->fetchColumn();
    
    // Videokurse
    $stmt_courses = $pdo->prepare("
        SELECT COUNT(*) FROM course_access 
        WHERE user_id = ?
    ");
    $stmt_courses->execute([$customer_id]);
    $courses_count = $stmt_courses->fetchColumn();
    
    // ECHTE KLICKS aus freebie_click_analytics - KORRIGIERT
    try {
        $stmt_clicks = $pdo->prepare("
            SELECT COALESCE(SUM(click_count), 0) 
            FROM freebie_click_analytics 
            WHERE customer_id = ?
            AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt_clicks->execute([$customer_id]);
        $total_clicks = $stmt_clicks->fetchColumn();
    } catch (PDOException $e) {
        // Fallback: customer_tracking
        try {
            $stmt_clicks = $pdo->prepare("
                SELECT COUNT(*) FROM customer_tracking 
                WHERE user_id = ? 
                AND type = 'click'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt_clicks->execute([$customer_id]);
            $total_clicks = $stmt_clicks->fetchColumn();
        } catch (PDOException $e2) {
            $total_clicks = 0;
        }
    }
    
    // ECHTE SEITENAUFRUFE
    try {
        $stmt_page_views = $pdo->prepare("
            SELECT COUNT(*) FROM customer_tracking 
            WHERE user_id = ? 
            AND type = 'page_view'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt_page_views->execute([$customer_id]);
        $total_page_views = $stmt_page_views->fetchColumn();
    } catch (PDOException $e) {
        $total_page_views = 0;
    }
    
    // Durchschnittliche Verweildauer
    try {
        $stmt_avg_time = $pdo->prepare("
            SELECT AVG(duration) FROM customer_tracking 
            WHERE user_id = ? 
            AND type = 'time_spent'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt_avg_time->execute([$customer_id]);
        $avg_time_spent = round($stmt_avg_time->fetchColumn() ?? 0);
    } catch (PDOException $e) {
        $avg_time_spent = 0;
    }
    
    // Heute's Aktivitäten
    try {
        $stmt_today = $pdo->prepare("
            SELECT COUNT(*) FROM customer_tracking 
            WHERE user_id = ? 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt_today->execute([$customer_id]);
        $today_activities = $stmt_today->fetchColumn();
    } catch (PDOException $e) {
        $today_activities = 0;
    }
    
    // Top 5 meistbesuchte Seiten
    try {
        $stmt_top_pages = $pdo->prepare("
            SELECT page, COUNT(*) as visits 
            FROM customer_tracking 
            WHERE user_id = ? 
            AND type = 'page_view'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY page 
            ORDER BY visits DESC 
            LIMIT 5
        ");
        $stmt_top_pages->execute([$customer_id]);
        $top_pages = $stmt_top_pages->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $top_pages = [];
    }
    
    // Aktivitätsverlauf (Letzte 7 Tage)
    try {
        $stmt_activity_chart = $pdo->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM customer_tracking 
            WHERE user_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt_activity_chart->execute([$customer_id]);
        $activity_chart_data = $stmt_activity_chart->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $activity_chart_data = [];
    }
    
    // Neue Kurse prüfen
    $stmt_new_courses = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.thumbnail, c.is_premium 
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.is_active = 1 
        AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND ca.user_id IS NULL
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $stmt_new_courses->execute([$customer_id]);
    $new_courses = $stmt_new_courses->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
    $freebies_unlocked = 0;
    $courses_count = 0;
    $total_clicks = 0;
    $total_page_views = 0;
    $avg_time_spent = 0;
    $today_activities = 0;
    $top_pages = [];
    $activity_chart_data = [];
    $new_courses = [];
}

// ===== MOTIVATIONSSPRÜCHE =====
$motivational_quotes = [
    "Erfolg entsteht durch Tun – starte jetzt deinen nächsten Schritt! 🚀",
    "Jeder Klick bringt dich deinem Ziel näher. 🎯",
    "Kleine Schritte – große Ergebnisse. 💪",
    "Dein Erfolg beginnt genau hier, genau jetzt. ⭐",
    "Konstanz schlägt Perfektion – bleib dran! 🔥",
    "Die besten Leads entstehen aus echtem Mehrwert. 💎",
    "Heute ist der perfekte Tag, um durchzustarten! 🌟",
    "Deine Vision ist größer als jede Herausforderung. 🏆",
    "Jedes Freebie ist eine Chance für neue Kunden. 🎁",
    "Glaube an dich – deine Leads warten schon! 💫"
];

$day_of_year = date('z');
$daily_quote = $motivational_quotes[$day_of_year % count($motivational_quotes)];

// Format für Chart-Daten
$chart_labels = [];
$chart_values = [];
foreach ($activity_chart_data as $day) {
    $chart_labels[] = date('d.m', strtotime($day['date']));
    $chart_values[] = $day['count'];
}

// Tracking verfügbar?
$tracking_available = !empty($activity_chart_data) || $total_page_views > 0;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .count-animation {
            animation: countUp 0.5s ease-out;
        }
        
        .progress-bar-fill {
            transition: width 1s ease-out;
        }
        
        .checkbox-custom {
            appearance: none;
            width: 24px;
            height: 24px;
            border: 2px solid #667eea;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }
        
        .checkbox-custom:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- ===== LIVE TRACKING INDICATOR ===== -->
        <?php if ($tracking_available): ?>
        <div class="mb-4 animate-fade-in-up">
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <span class="live-indicator"></span>
                <span>Live Tracking aktiv • Heute <?php echo $today_activities; ?> Aktivitäten</span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ===== WILLKOMMENSBEREICH ===== -->
        <div class="mb-8 animate-fade-in-up">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 shadow-2xl">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                    Willkommen zurück, <?php echo htmlspecialchars($customer_name); ?>! 👋
                </h1>
                <p class="text-purple-100 text-lg mb-6">
                    <?php if ($tracking_available): ?>
                    Deine Aktivitäten der letzten 30 Tage werden in Echtzeit erfasst.
                    <?php else: ?>
                    Hier siehst du deine Erfolge und nächsten Schritte.
                    <?php endif; ?>
                </p>
                <a href="?page=tutorials" 
                   class="inline-flex items-center gap-2 bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-all transform hover:scale-105 shadow-lg"
                   data-track="button-tutorials">
                    <i class="fas fa-rocket"></i>
                    Jetzt starten
                </a>
            </div>
        </div>
        
        <!-- ===== ECHTZEIT STATISTIKÜBERSICHT ===== -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Freebies -->
            <div class="stat-card bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-gift text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($freebies_unlocked); ?>
                    </div>
                    <div class="text-green-100 text-sm font-medium">
                        Freigeschaltete Freebies
                    </div>
                </div>
            </div>
            
            <!-- Kurse -->
            <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($courses_count); ?>
                    </div>
                    <div class="text-purple-100 text-sm font-medium">
                        Deine Videokurse
                    </div>
                </div>
            </div>
            
            <!-- Seitenaufrufe oder Klicks -->
            <?php if ($tracking_available): ?>
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-eye text-white text-2xl"></i>
                    </div>
                    <span class="text-xs text-blue-100 font-medium">30 Tage</span>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($total_page_views); ?>
                    </div>
                    <div class="text-blue-100 text-sm font-medium">
                        Seitenaufrufe
                    </div>
                </div>
            </div>
            
            <!-- Klicks -->
            <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-mouse-pointer text-white text-2xl"></i>
                    </div>
                    <span class="text-xs text-pink-100 font-medium">30 Tage</span>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($total_clicks); ?>
                    </div>
                    <div class="text-pink-100 text-sm font-medium">
                        Freebie Klicks
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Fallback wenn kein Tracking -->
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-mouse-pointer text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        <?php echo number_format($total_clicks); ?>
                    </div>
                    <div class="text-blue-100 text-sm font-medium">
                        Freebie Klicks
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 animate-fade-in-up opacity-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                </div>
                <div class="text-white">
                    <div class="text-5xl font-bold mb-2 count-animation">
                        Start
                    </div>
                    <div class="text-pink-100 text-sm font-medium">
                        Tracking läuft an
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- REST DES CODES BLEIBT GLEICH... -->
        <?php include __DIR__ . '/../sections/overview_rest.php'; ?>
        
    </div>
</body>
</html>

<?php
/**
 * Customer Dashboard - Overview Section - IMPROVED WITH ERROR DISPLAY
 * Zeigt Fehler an statt sie zu verstecken
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// Debug-Modus aktivieren (kann später auskommentiert werden)
$debug_mode = true;
$debug_messages = [];

// ===== ECHTE STATISTIKEN ABRUFEN =====
$freebies_unlocked = 0;
$courses_count = 0;
$total_clicks = 0;
$total_page_views = 0;
$avg_time_spent = 0;
$today_activities = 0;
$top_pages = [];
$activity_chart_data = [];
$new_courses = [];

try {
    // PDO prüfen
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("❌ PDO Verbindung nicht verfügbar!");
    }
    
    if ($debug_mode) {
        $debug_messages[] = "✅ PDO Verbindung OK";
        $debug_messages[] = "👤 Customer ID: " . $customer_id;
    }
    
    // 1. Freigeschaltete Freebies - NUR die noch existieren!
    try {
        $stmt_freebies = $pdo->prepare("
            SELECT COUNT(*) 
            FROM customer_freebies cf
            INNER JOIN freebies f ON cf.freebie_id = f.id
            WHERE cf.customer_id = ?
        ");
        $stmt_freebies->execute([$customer_id]);
        $freebies_unlocked = $stmt_freebies->fetchColumn();
        
        if ($debug_mode) {
            $debug_messages[] = "✅ Freebies Query: $freebies_unlocked gefunden";
        }
    } catch (PDOException $e) {
        if ($debug_mode) {
            $debug_messages[] = "❌ Freebies Query Fehler: " . $e->getMessage();
        }
        error_log("Freebies Query Error: " . $e->getMessage());
    }
    
    // 2. Videokurse
    try {
        $stmt_courses = $pdo->prepare("
            SELECT COUNT(*) FROM course_access 
            WHERE user_id = ?
        ");
        $stmt_courses->execute([$customer_id]);
        $courses_count = $stmt_courses->fetchColumn();
        
        if ($debug_mode) {
            $debug_messages[] = "✅ Kurse Query: $courses_count gefunden";
        }
    } catch (PDOException $e) {
        if ($debug_mode) {
            $debug_messages[] = "❌ Kurse Query Fehler: " . $e->getMessage();
        }
        error_log("Courses Query Error: " . $e->getMessage());
    }
    
    // 3. ECHTE KLICKS aus freebie_click_analytics
    try {
        $stmt_clicks = $pdo->prepare("
            SELECT COALESCE(SUM(click_count), 0) 
            FROM freebie_click_analytics 
            WHERE customer_id = ?
            AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt_clicks->execute([$customer_id]);
        $total_clicks = $stmt_clicks->fetchColumn();
        
        if ($debug_mode) {
            $debug_messages[] = "✅ Klicks Query: $total_clicks gefunden";
        }
    } catch (PDOException $e) {
        if ($debug_mode) {
            $debug_messages[] = "⚠️ Klicks Query Fehler, versuche Fallback: " . $e->getMessage();
        }
        
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
            
            if ($debug_mode) {
                $debug_messages[] = "✅ Klicks Fallback: $total_clicks gefunden";
            }
        } catch (PDOException $e2) {
            if ($debug_mode) {
                $debug_messages[] = "❌ Klicks Fallback Fehler: " . $e2->getMessage();
            }
            error_log("Clicks Fallback Error: " . $e2->getMessage());
        }
    }
    
    // 4. ECHTE SEITENAUFRUFE
    try {
        $stmt_page_views = $pdo->prepare("
            SELECT COUNT(*) FROM customer_tracking 
            WHERE user_id = ? 
            AND type = 'page_view'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt_page_views->execute([$customer_id]);
        $total_page_views = $stmt_page_views->fetchColumn();
        
        if ($debug_mode) {
            $debug_messages[] = "✅ Seitenaufrufe Query: $total_page_views gefunden";
        }
    } catch (PDOException $e) {
        if ($debug_mode) {
            $debug_messages[] = "❌ Seitenaufrufe Query Fehler: " . $e->getMessage();
        }
        error_log("Page Views Query Error: " . $e->getMessage());
    }
    
    // 5. Durchschnittliche Verweildauer
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
        if ($debug_mode) {
            $debug_messages[] = "⚠️ Verweildauer Query Fehler: " . $e->getMessage();
        }
    }
    
    // 6. Heute's Aktivitäten
    try {
        $stmt_today = $pdo->prepare("
            SELECT COUNT(*) FROM customer_tracking 
            WHERE user_id = ? 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt_today->execute([$customer_id]);
        $today_activities = $stmt_today->fetchColumn();
    } catch (PDOException $e) {
        if ($debug_mode) {
            $debug_messages[] = "⚠️ Heute Aktivitäten Query Fehler: " . $e->getMessage();
        }
    }
    
    // 7. Top 5 meistbesuchte Seiten
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
        if ($debug_mode) {
            $debug_messages[] = "⚠️ Top Pages Query Fehler: " . $e->getMessage();
        }
    }
    
    // 8. Aktivitätsverlauf (Letzte 7 Tage)
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
        if ($debug_mode) {
            $debug_messages[] = "⚠️ Activity Chart Query Fehler: " . $e->getMessage();
        }
    }
    
    // 9. Neue Kurse prüfen
    try {
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
        if ($debug_mode) {
            $debug_messages[] = "⚠️ New Courses Query Fehler: " . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    if ($debug_mode) {
        $debug_messages[] = "❌ CRITICAL ERROR: " . $e->getMessage();
    }
    error_log("Dashboard Stats Critical Error: " . $e->getMessage());
}

// Finale Statistik-Zusammenfassung
if ($debug_mode) {
    $debug_messages[] = "📊 FINALE WERTE:";
    $debug_messages[] = "   - Freebies: $freebies_unlocked";
    $debug_messages[] = "   - Kurse: $courses_count";
    $debug_messages[] = "   - Klicks: $total_clicks";
    $debug_messages[] = "   - Seitenaufrufe: $total_page_views";
}

// Rest des Codes bleibt gleich...
// (Motivationssprüche, Chart-Daten, etc.)

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

$chart_labels = [];
$chart_values = [];
foreach ($activity_chart_data as $day) {
    $chart_labels[] = date('d.m', strtotime($day['date']));
    $chart_values[] = $day['count'];
}

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
        /* Alle Styles bleiben gleich wie in der Original-Datei */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
        @keyframes buttonPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7), 0 0 20px rgba(255, 255, 255, 0.3);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(255, 255, 255, 0), 0 0 30px rgba(255, 255, 255, 0.5);
                transform: scale(1.05);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0), 0 0 20px rgba(255, 255, 255, 0.3);
                transform: scale(1);
            }
        }
        @keyframes buttonGlow {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }
        .cta-button {
            animation: buttonPulse 2s ease-in-out infinite, buttonGlow 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        .cta-button::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        .cta-button:hover {
            animation: none;
            transform: scale(1.08) !important;
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.6), 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }
        
        /* DEBUG BOX STYLE */
        .debug-box {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.95);
            border: 2px solid #00ff00;
            border-radius: 8px;
            padding: 15px;
            max-width: 400px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 9999;
            font-family: monospace;
            font-size: 11px;
            color: #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        .debug-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
        }
        .debug-close {
            cursor: pointer;
            color: #ff0000;
            font-weight: bold;
        }
        .debug-message {
            margin: 5px 0;
            line-height: 1.4;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen">
    
    <?php if ($debug_mode && !empty($debug_messages)): ?>
    <!-- DEBUG BOX -->
    <div class="debug-box" id="debugBox">
        <div class="debug-box-header">
            <strong>🔍 DEBUG INFO</strong>
            <span class="debug-close" onclick="document.getElementById('debugBox').style.display='none'">✕</span>
        </div>
        <?php foreach ($debug_messages as $msg): ?>
        <div class="debug-message"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Rest des HTML bleibt genau gleich wie in der Original-Datei -->
    <!-- Ich kopiere hier nur die wichtigsten Teile für die Kürze -->
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($tracking_available): ?>
        <div class="mb-4 animate-fade-in-up">
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <span class="live-indicator"></span>
                <span>Live Tracking aktiv • Heute <?php echo $today_activities; ?> Aktivitäten</span>
            </div>
        </div>
        <?php endif; ?>
        
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
                   class="cta-button inline-flex items-center gap-3 bg-white text-purple-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-purple-50 transition-all shadow-2xl"
                   data-track="button-tutorials">
                    <i class="fas fa-rocket text-2xl"></i>
                    Jetzt starten
                </a>
            </div>
        </div>
        
        <!-- STATISTIK-KARTEN -->
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
                        Aktive Freebies
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
        
        <!-- REST DES HTML CODES WIE IN DER ORIGINAL-DATEI -->
        <!-- (Activity Chart, Top Pages, Neue Kurse, Checkliste, etc.) -->
        
    </div>
    
    <!-- Alle JavaScript-Funktionen bleiben gleich -->
    <script>
        // Tracking System und alle anderen Funktionen wie im Original
        const TrackingSystem = {
            apiUrl: '/customer/api/tracking.php',
            pageStartTime: Date.now(),
            trackPageView: function() {
                this.sendTrackingData({ type: 'page_view', data: { page: 'overview', referrer: document.referrer }});
            },
            trackClick: function(element, target = '') {
                this.sendTrackingData({ type: 'click', data: { page: 'overview', element: element, target: target }});
            },
            trackEvent: function(eventName, eventData = {}) {
                this.sendTrackingData({ type: 'event', data: { page: 'overview', event_name: eventName, event_data: eventData }});
            },
            trackTimeSpent: function() {
                const duration = Math.floor((Date.now() - this.pageStartTime) / 1000);
                this.sendTrackingData({ type: 'time_spent', data: { page: 'overview', duration: duration }});
            },
            sendTrackingData: function(data) {
                fetch(this.apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).catch(err => console.error('Tracking error:', err));
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            TrackingSystem.trackPageView();
            document.querySelectorAll('[data-track]').forEach(element => {
                element.addEventListener('click', function(e) {
                    const trackId = this.getAttribute('data-track');
                    const href = this.getAttribute('href') || '';
                    TrackingSystem.trackClick(trackId, href);
                });
            });
            document.querySelectorAll('.checkbox-custom').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const task = this.getAttribute('data-task');
                    const completed = this.checked;
                    TrackingSystem.trackEvent('checklist_update', { task: task, checked: completed });
                    fetch('/customer/api/checklist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ task_id: task, completed: completed })
                    }).catch(err => console.error('Checklist save error:', err));
                });
            });
            window.addEventListener('beforeunload', function() {
                TrackingSystem.trackTimeSpent();
            });
            setInterval(function() {
                TrackingSystem.trackTimeSpent();
            }, 30000);
            loadProgress();
            <?php if (!empty($activity_chart_data)): ?>
            initActivityChart();
            <?php endif; ?>
        });
        
        function initActivityChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Aktivitäten',
                        data: <?php echo json_encode($chart_values); ?>,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }},
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#9ca3af' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        x: {
                            ticks: { color: '#9ca3af' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });
        }
        
        function loadProgress() {
            fetch('/customer/api/checklist.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.progress) {
                        Object.keys(data.progress).forEach(task => {
                            const checkbox = document.querySelector(`[data-task="${task}"]`);
                            if (checkbox) checkbox.checked = data.progress[task];
                        });
                    }
                    updateProgress();
                })
                .catch(err => {
                    console.error('Load progress error:', err);
                    updateProgress();
                });
        }
        
        function updateProgress() {
            const checkboxes = document.querySelectorAll('#checklist input[type="checkbox"]');
            const total = checkboxes.length;
            let checked = 0;
            checkboxes.forEach(checkbox => { if (checkbox.checked) checked++; });
            const percentage = Math.round((checked / total) * 100);
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-percentage');
            if (progressBar) progressBar.style.width = percentage + '%';
            if (progressText) progressText.textContent = percentage + '%';
        }
    </script>
</body>
</html>
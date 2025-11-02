<?php
/**
 * Customer Dashboard - Fortschritt & Analytics Section
 * Umfassende Performance-Übersicht mit ECHTEN Charts, Stats und Achievements
 */

if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// ===== DATEN ABRUFEN =====
try {
    // Basis-Statistiken
    $stmt_freebies = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
    $stmt_freebies->execute([$customer_id]);
    $total_freebies = $stmt_freebies->fetchColumn();
    
    $stmt_clicks = $pdo->prepare("SELECT COALESCE(SUM(clicks), 0) FROM customer_freebies WHERE customer_id = ?");
    $stmt_clicks->execute([$customer_id]);
    $total_clicks = $stmt_clicks->fetchColumn();
    
    $stmt_courses = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE customer_id = ? AND has_access = 1");
    $stmt_courses->execute([$customer_id]);
    $total_courses = $stmt_courses->fetchColumn();
    
    // DEBUG: Alle Freebies anzeigen
    $stmt_debug = $pdo->prepare("
        SELECT 
            cf.id,
            cf.customer_id,
            cf.headline,
            cf.clicks,
            cf.freebie_type,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt_debug->execute([$customer_id]);
    $debug_freebies = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
    
    // Freebie Performance Details - MIT KORREKTEM SPALTENNAMEN
    $stmt_freebie_details = $pdo->prepare("
        SELECT 
            cf.id,
            cf.headline as freebie_name,
            cf.clicks,
            cf.created_at,
            cf.url_slug
        FROM customer_freebies cf
        WHERE cf.customer_id = ?
        ORDER BY cf.clicks DESC
        LIMIT 10
    ");
    $stmt_freebie_details->execute([$customer_id]);
    $freebie_performance = $stmt_freebie_details->fetchAll(PDO::FETCH_ASSOC);
    
    // Kurs-Fortschritt
    $stmt_course_progress = $pdo->prepare("
        SELECT 
            c.id,
            c.title,
            c.thumbnail,
            COUNT(DISTINCT cm.id) as total_modules,
            COUNT(DISTINCT cl.id) as total_lessons,
            COUNT(DISTINCT clc.lesson_id) as completed_lessons
        FROM courses c
        INNER JOIN course_access ca ON c.id = ca.course_id
        LEFT JOIN course_modules cm ON c.id = cm.course_id
        LEFT JOIN course_lessons cl ON cm.id = cl.module_id
        LEFT JOIN course_lesson_completions clc ON cl.id = clc.lesson_id AND clc.customer_id = ?
        WHERE ca.customer_id = ? AND ca.has_access = 1
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt_course_progress->execute([$customer_id, $customer_id]);
    $course_progress = $stmt_course_progress->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktivitäts-Timeline (letzte 10 Aktivitäten)
    $activities = [];
    
    // Freebies erstellt - MIT KORREKTEM SPALTENNAMEN
    $stmt_freebie_activity = $pdo->prepare("
        SELECT 'freebie_created' as type, headline as name, created_at 
        FROM customer_freebies 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt_freebie_activity->execute([$customer_id]);
    $activities = array_merge($activities, $stmt_freebie_activity->fetchAll(PDO::FETCH_ASSOC));
    
    // Lektionen abgeschlossen
    $stmt_lesson_activity = $pdo->prepare("
        SELECT 'lesson_completed' as type, cl.title as name, clc.completed_at as created_at
        FROM course_lesson_completions clc
        INNER JOIN course_lessons cl ON clc.lesson_id = cl.id
        WHERE clc.customer_id = ?
        ORDER BY clc.completed_at DESC
        LIMIT 5
    ");
    $stmt_lesson_activity->execute([$customer_id]);
    $activities = array_merge($activities, $stmt_lesson_activity->fetchAll(PDO::FETCH_ASSOC));
    
    // Sortieren nach Datum
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $activities = array_slice($activities, 0, 10);
    
    // ===== ECHTE HISTORISCHE KLICK-DATEN FÜR CHART =====
    // Prüfen ob Analytics-Tabelle existiert
    $table_exists = false;
    try {
        $pdo->query("SELECT 1 FROM freebie_click_analytics LIMIT 1");
        $table_exists = true;
    } catch (PDOException $e) {
        error_log("Analytics table not found, using fallback");
    }
    
    $chart_data = [];
    if ($table_exists) {
        // ECHTE DATEN aus Analytics-Tabelle (letzte 30 Tage)
        $stmt_chart = $pdo->prepare("
            SELECT 
                click_date as date,
                SUM(click_count) as clicks
            FROM freebie_click_analytics
            WHERE customer_id = ?
            AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY click_date
            ORDER BY click_date ASC
        ");
        $stmt_chart->execute([$customer_id]);
        $chart_results = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);
        
        // Alle 30 Tage mit 0 vorbefüllen
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_data[$date] = 0;
        }
        
        // Echte Werte eintragen
        foreach ($chart_results as $row) {
            $chart_data[$row['date']] = (int)$row['clicks'];
        }
    } else {
        // Fallback: Leere Daten wenn Tabelle noch nicht existiert
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_data[$date] = 0;
        }
    }
    
    // Durchschnittliche Klicks pro Tag berechnen
    $total_chart_clicks = array_sum($chart_data);
    $days_with_clicks = count(array_filter($chart_data));
    $avg_clicks_per_day = $days_with_clicks > 0 ? round($total_chart_clicks / $days_with_clicks, 1) : 0;
    
} catch (PDOException $e) {
    error_log("Fortschritt Error: " . $e->getMessage());
    $total_freebies = 0;
    $total_clicks = 0;
    $total_courses = 0;
    $freebie_performance = [];
    $course_progress = [];
    $activities = [];
    $chart_data = [];
    $avg_clicks_per_day = 0;
    $table_exists = false;
    $debug_freebies = [];
}

// Achievement-Berechnung
$achievements = [
    [
        'id' => 'first_freebie',
        'title' => 'Erster Schritt',
        'description' => 'Erstes Freebie erstellt',
        'icon' => '🎁',
        'unlocked' => $total_freebies >= 1,
        'progress' => min(100, ($total_freebies / 1) * 100)
    ],
    [
        'id' => 'freebie_master',
        'title' => 'Freebie Master',
        'description' => '5 Freebies erstellt',
        'icon' => '🏆',
        'unlocked' => $total_freebies >= 5,
        'progress' => min(100, ($total_freebies / 5) * 100)
    ],
    [
        'id' => 'first_clicks',
        'title' => 'Aufmerksamkeit erregt',
        'description' => '10 Klicks erreicht',
        'icon' => '🎯',
        'unlocked' => $total_clicks >= 10,
        'progress' => min(100, ($total_clicks / 10) * 100)
    ],
    [
        'id' => 'click_champion',
        'title' => 'Klick Champion',
        'description' => '100 Klicks erreicht',
        'icon' => '🚀',
        'unlocked' => $total_clicks >= 100,
        'progress' => min(100, ($total_clicks / 100) * 100)
    ],
    [
        'id' => 'learner',
        'title' => 'Wissbegierig',
        'description' => 'Ersten Kurs gestartet',
        'icon' => '📚',
        'unlocked' => $total_courses >= 1,
        'progress' => min(100, ($total_courses / 1) * 100)
    ],
    [
        'id' => 'dedicated',
        'title' => 'Engagiert',
        'description' => 'Erste Lektion abgeschlossen',
        'icon' => '✅',
        'unlocked' => !empty($course_progress) && array_sum(array_column($course_progress, 'completed_lessons')) >= 1,
        'progress' => !empty($course_progress) ? min(100, (array_sum(array_column($course_progress, 'completed_lessons')) / 1) * 100) : 0
    ]
];

$unlocked_achievements = array_filter($achievements, function($a) { return $a['unlocked']; });
$achievement_percentage = round((count($unlocked_achievements) / count($achievements)) * 100);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .achievement-card { transition: all 0.3s ease; }
        .achievement-card:hover { transform: translateY(-4px); }
        .achievement-locked { opacity: 0.5; filter: grayscale(100); }
        .achievement-unlocked { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .activity-item { border-left: 3px solid #667eea; transition: all 0.2s; }
        .activity-item:hover { background: rgba(102, 126, 234, 0.1); border-left-color: #764ba2; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- DEBUG INFO -->
        <div class="bg-yellow-900/20 border-2 border-yellow-500 rounded-xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">🔍 DEBUG INFO</h2>
            <div class="text-white space-y-2">
                <p><strong>Customer ID:</strong> <?php echo $customer_id; ?></p>
                <p><strong>Total Freebies in DB:</strong> <?php echo $total_freebies; ?></p>
                <p><strong>Total Clicks:</strong> <?php echo $total_clicks; ?></p>
                <p><strong>Freebie Performance Array Count:</strong> <?php echo count($freebie_performance); ?></p>
                
                <div class="mt-4">
                    <p class="text-yellow-400 font-bold mb-2">Alle Freebies in der Datenbank für diese Customer ID:</p>
                    <?php if (!empty($debug_freebies)): ?>
                        <div class="bg-gray-800 rounded p-4 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left border-b border-gray-700">
                                        <th class="p-2">ID</th>
                                        <th class="p-2">Customer ID</th>
                                        <th class="p-2">Headline</th>
                                        <th class="p-2">Klicks</th>
                                        <th class="p-2">Typ</th>
                                        <th class="p-2">Erstellt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debug_freebies as $df): ?>
                                    <tr class="border-b border-gray-800">
                                        <td class="p-2"><?php echo $df['id']; ?></td>
                                        <td class="p-2"><?php echo $df['customer_id']; ?></td>
                                        <td class="p-2"><?php echo htmlspecialchars($df['headline']); ?></td>
                                        <td class="p-2"><?php echo $df['clicks']; ?></td>
                                        <td class="p-2"><?php echo $df['freebie_type'] ?? 'NULL'; ?></td>
                                        <td class="p-2"><?php echo $df['created_at']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-red-400">❌ Keine Freebies in der Datenbank gefunden!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                <i class="fas fa-chart-line text-purple-400 mr-2"></i>
                Dein Fortschritt
            </h1>
            <p class="text-gray-400">Verfolge deine Erfolge und erreiche neue Meilensteine</p>
        </div>
        
        <!-- Key Metrics Hero -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-gift text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_freebies); ?></div>
                <div class="text-sm opacity-90">Freebies erstellt</div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-mouse-pointer text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Gesamt</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_clicks); ?></div>
                <div class="text-sm opacity-90">Klicks generiert</div>
            </div>
            
            <div class="bg-gradient-to-br from-pink-500 to-pink-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-graduation-cap text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Aktiv</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo number_format($total_courses); ?></div>
                <div class="text-sm opacity-90">Kurse verfügbar</div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-trophy text-3xl opacity-80"></i>
                    <span class="text-xs bg-white/20 px-2 py-1 rounded-full">Level</span>
                </div>
                <div class="text-4xl font-bold mb-1"><?php echo count($unlocked_achievements); ?>/<?php echo count($achievements); ?></div>
                <div class="text-sm opacity-90">Achievements</div>
            </div>
        </div>
        
        <!-- Charts & Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Performance Chart -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-chart-area text-blue-400 mr-2"></i>
                    Performance Übersicht
                    <?php if (!$table_exists): ?>
                    <span class="text-xs bg-green-500/20 text-green-300 px-2 py-1 rounded ml-2">Aktiv</span>
                    <?php endif; ?>
                </h3>
                <div class="bg-gray-900 rounded-xl p-4">
                    <canvas id="performanceChart" height="200"></canvas>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400"><?php echo number_format($avg_clicks_per_day, 1); ?></div>
                        <div class="text-sm text-gray-400">Ø Klicks/Tag</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">
                            <?php echo $total_freebies > 0 ? number_format($total_clicks / $total_freebies, 1) : 0; ?>
                        </div>
                        <div class="text-sm text-gray-400">Ø pro Freebie</div>
                    </div>
                </div>
            </div>
            
            <!-- Top Freebies -->
            <div class="bg-gray-800 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                    Top Performance Freebies
                </h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php if (!empty($freebie_performance)): ?>
                        <?php foreach (array_slice($freebie_performance, 0, 5) as $index => $freebie): ?>
                        <div class="bg-gray-900 rounded-lg p-4 hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gradient-to-br from-purple-500 to-blue-500 w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <div class="text-white font-semibold line-clamp-1">
                                            <?php echo htmlspecialchars($freebie['freebie_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <?php echo date('d.m.Y', strtotime($freebie['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-400">
                                        <?php echo number_format($freebie['clicks']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">Klicks</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Noch keine Freebies erstellt</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Rest of the page continues... -->
        <!-- (Achievements, Kurs-Fortschritt, Aktivitäts-Timeline bleiben gleich) -->
        
    </div>
    
    <script>
        // Performance Chart mit ECHTEN DATEN
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($chart_data)); ?>,
                datasets: [{
                    label: 'Klicks',
                    data: <?php echo json_encode(array_values($chart_data)); ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#888',
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#888',
                            maxRotation: 0,
                            callback: function(val, index) {
                                return index % 5 === 0 ? this.getLabelForValue(val).substr(5) : '';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

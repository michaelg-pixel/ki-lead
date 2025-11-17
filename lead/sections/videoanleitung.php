<?php
/**
 * Lead Dashboard - Videoanleitung Sektion
 * Zeigt GLOBALE Videos (freebie_id = 0) für ALLE Leads
 * Videos werden in Kategorien angezeigt
 */
if (!isset($lead)) {
    die('Unauthorized');
}

$effective_customer_id = $lead['user_id'] ?? null;

if (!$effective_customer_id) {
    echo '<div class="bg-red-600/20 border border-red-600/30 rounded-xl p-6 text-center">';
    echo '<p class="text-red-300">Fehler: Kunden-ID fehlt</p>';
    echo '</div>';
    return;
}

// DEBUG: Zeige die customer_id
$debug_mode = true; // Setze auf false wenn Problem gelöst ist

// GLOBALE Videos laden - freebie_id = 0 bedeutet für ALLE Leads sichtbar
$videos = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM video_tutorials 
        WHERE customer_id = ? AND freebie_id = 0 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([$effective_customer_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Zeige Abfrage-Ergebnis
    if ($debug_mode) {
        error_log("DEBUG Videoanleitung: customer_id = " . $effective_customer_id);
        error_log("DEBUG Videoanleitung: Videos gefunden = " . count($videos));
        
        // Zeige ALLE Videos in der Datenbank
        $stmt_all = $pdo->query("SELECT id, customer_id, freebie_id, category_name FROM video_tutorials");
        $all_videos = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
        error_log("DEBUG Videoanleitung: ALLE Videos in DB = " . count($all_videos));
        foreach ($all_videos as $v) {
            error_log("  - Video ID {$v['id']}: customer_id={$v['customer_id']}, freebie_id={$v['freebie_id']}, name={$v['category_name']}");
        }
    }
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Videos: " . $e->getMessage());
    $videos = [];
}

// Vimeo URL in Embed-URL konvertieren
function getVimeoEmbedUrl($url) {
    preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
    if (isset($matches[1])) {
        return 'https://player.vimeo.com/video/' . $matches[1] . '?title=0&byline=0&portrait=0';
    }
    return $url;
}

// Farb-Klassen Mapping
$color_classes = [
    'purple' => 'from-purple-600 to-blue-600',
    'blue' => 'from-blue-600 to-cyan-600',
    'green' => 'from-green-600 to-emerald-600',
    'yellow' => 'from-yellow-600 to-orange-600',
    'red' => 'from-red-600 to-pink-600',
    'pink' => 'from-pink-600 to-purple-600',
];
?>

<div class="animate-fade-in-up opacity-0">
    <!-- DEBUG INFO (nur im Debug-Modus) -->
    <?php if ($debug_mode): ?>
    <div class="bg-yellow-600/20 border border-yellow-600/30 rounded-xl p-4 mb-6">
        <h4 class="text-yellow-300 font-bold mb-2">🐛 DEBUG INFO</h4>
        <p class="text-yellow-200 text-sm">Customer ID: <?php echo $effective_customer_id; ?></p>
        <p class="text-yellow-200 text-sm">Videos gefunden: <?php echo count($videos); ?></p>
        <p class="text-yellow-200 text-sm">Lead Email: <?php echo htmlspecialchars($lead['email'] ?? 'N/A'); ?></p>
        <?php
        // Zeige alle Videos in der DB
        try {
            $stmt_debug = $pdo->query("SELECT id, customer_id, freebie_id, category_name FROM video_tutorials ORDER BY id DESC LIMIT 5");
            $debug_videos = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($debug_videos)) {
                echo '<p class="text-yellow-200 text-sm mt-2 font-bold">Alle Videos in DB:</p>';
                echo '<ul class="text-yellow-200 text-xs">';
                foreach ($debug_videos as $dv) {
                    echo '<li>ID ' . $dv['id'] . ': customer_id=' . $dv['customer_id'] . ', freebie_id=' . $dv['freebie_id'] . ', ' . htmlspecialchars($dv['category_name']) . '</li>';
                }
                echo '</ul>';
            }
        } catch (Exception $e) {
            echo '<p class="text-red-300 text-xs">Debug-Fehler: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-white mb-2">
            <i class="fas fa-video text-purple-400 mr-3"></i>
            Videoanleitung
        </h2>
        <p class="text-gray-400">
            <i class="fas fa-graduation-cap mr-2"></i>
            Schau dir unsere Video-Tutorials an und werde zum Experten
        </p>
    </div>
    
    <?php if (empty($videos)): ?>
    <!-- Keine Videos vorhanden -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-12 text-center shadow-xl border border-purple-500/20">
        <div class="text-6xl mb-4">🎥</div>
        <h3 class="text-white text-2xl font-bold mb-2">Noch keine Videos verfügbar</h3>
        <p class="text-gray-400 mb-6">Videos werden hier angezeigt, sobald sie hinzugefügt wurden</p>
        <a href="?page=dashboard<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all">
            <i class="fas fa-home"></i>
            Zurück zum Dashboard
        </a>
    </div>
    
    <?php else: ?>
    <!-- Video Player Bereich -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 shadow-xl border border-purple-500/20 mb-8">
        <div id="currentVideoTitle" class="mb-6">
            <h3 class="text-white text-2xl font-bold mb-2">
                <i id="currentVideoIcon" class="fas <?php echo htmlspecialchars($videos[0]['category_icon']); ?> text-purple-400 mr-2"></i>
                <span id="currentVideoName"><?php echo htmlspecialchars($videos[0]['category_name']); ?></span>
            </h3>
            <p id="currentVideoDescription" class="text-gray-400">
                <?php echo htmlspecialchars($videos[0]['description'] ?: 'Schau dir dieses Tutorial an'); ?>
            </p>
        </div>
        
        <!-- Video Player -->
        <div id="videoPlayerContainer" class="aspect-video bg-gray-900 rounded-xl overflow-hidden mb-4 border border-purple-500/20">
            <iframe 
                id="vimeoPlayer"
                src="<?php echo getVimeoEmbedUrl($videos[0]['vimeo_url']); ?>" 
                class="w-full h-full"
                frameborder="0" 
                allow="autoplay; fullscreen; picture-in-picture" 
                allowfullscreen>
            </iframe>
        </div>
    </div>
    
    <!-- Video-Kategorien Grid -->
    <div class="mb-8">
        <h3 class="text-white text-xl font-bold mb-4">
            <i class="fas fa-th-large mr-2"></i>
            Alle Video-Kategorien (<?php echo count($videos); ?>)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($videos as $index => $video): ?>
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl cursor-pointer video-category"
                 data-video-id="<?php echo $video['id']; ?>"
                 data-video-url="<?php echo htmlspecialchars(getVimeoEmbedUrl($video['vimeo_url'])); ?>"
                 data-video-name="<?php echo htmlspecialchars($video['category_name']); ?>"
                 data-video-icon="<?php echo htmlspecialchars($video['category_icon']); ?>"
                 data-video-description="<?php echo htmlspecialchars($video['description'] ?: ''); ?>"
                 onclick="loadVideo(this)">
                
                <!-- Icon Header -->
                <div class="h-48 bg-gradient-to-br <?php echo $color_classes[$video['category_color']] ?? $color_classes['purple']; ?> flex items-center justify-center relative overflow-hidden">
                    <i class="fas <?php echo htmlspecialchars($video['category_icon']); ?> text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
                    
                    <!-- Video Nummer Badge -->
                    <div class="absolute top-3 left-3 bg-black/30 backdrop-blur-sm text-white px-3 py-1 rounded-lg text-sm font-semibold">
                        #<?php echo $index + 1; ?>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <h3 class="text-white font-bold text-xl mb-2 line-clamp-2">
                        <?php echo htmlspecialchars($video['category_name']); ?>
                    </h3>
                    <p class="text-gray-400 text-sm mb-4 line-clamp-3">
                        <?php echo htmlspecialchars($video['description'] ?: 'Klicke hier, um das Video anzusehen und mehr zu erfahren'); ?>
                    </p>
                    <div class="bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white px-4 py-3 rounded-xl text-center font-semibold transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-play-circle text-lg"></i>
                        <span>Video ansehen</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Zum Empfehlen -->
        <?php if ($referral_enabled): ?>
        <a href="?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="bg-gradient-to-br from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 rounded-2xl p-8 text-center transition-all shadow-xl border border-purple-500/20 group">
            <div class="text-5xl mb-4">🚀</div>
            <h3 class="text-white text-xl font-bold mb-2 group-hover:scale-105 transition-transform">Jetzt loslegen</h3>
            <p class="text-purple-200">Starte mit dem Empfehlen und sichere dir Belohnungen</p>
        </a>
        <?php endif; ?>
        
        <!-- Zu den Kursen -->
        <a href="?page=kurse<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="bg-gradient-to-br from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-2xl p-8 text-center transition-all shadow-xl border border-green-500/20 group">
            <div class="text-5xl mb-4">📚</div>
            <h3 class="text-white text-xl font-bold mb-2 group-hover:scale-105 transition-transform">Meine Kurse</h3>
            <p class="text-green-200">Entdecke alle verfügbaren Kurse und lerne mehr</p>
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Video-Player laden
function loadVideo(element) {
    const videoUrl = element.getAttribute('data-video-url');
    const videoName = element.getAttribute('data-video-name');
    const videoIcon = element.getAttribute('data-video-icon');
    const videoDescription = element.getAttribute('data-video-description');
    
    // Update Player
    document.getElementById('vimeoPlayer').src = videoUrl;
    document.getElementById('currentVideoName').textContent = videoName;
    document.getElementById('currentVideoIcon').className = 'fas ' + videoIcon + ' text-purple-400 mr-2';
    document.getElementById('currentVideoDescription').textContent = videoDescription || 'Schau dir dieses Tutorial an';
    
    // Scroll zum Video-Player mit smooth animation
    document.getElementById('videoPlayerContainer').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
    
    // Kurze Animation für besseres Feedback
    const container = document.getElementById('videoPlayerContainer');
    container.style.transform = 'scale(0.98)';
    setTimeout(() => {
        container.style.transform = 'scale(1)';
    }, 150);
}

// Smooth Animation beim Laden
document.addEventListener('DOMContentLoaded', function() {
    const fadeElement = document.querySelector('.animate-fade-in-up');
    if (fadeElement) {
        setTimeout(() => {
            fadeElement.style.opacity = '1';
        }, 100);
    }
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

#videoPlayerContainer {
    transition: transform 0.15s ease-out;
}
</style>

<?php
/**
 * Lead Dashboard - Kurse Sektion
 * + 4er-Grid Layout für Freebies
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<div class="animate-fade-in-up opacity-0">
    <h2 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-graduation-cap text-purple-400 mr-3"></i>
        <?php echo $course_section_title; ?>
    </h2>
    
    <?php if (empty($freebies_with_courses)): ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-12 text-center shadow-xl border border-purple-500/20">
        <div class="text-6xl mb-4">📭</div>
        <h3 class="text-white text-2xl font-bold mb-2">Noch keine Kurse verfügbar</h3>
        <p class="text-gray-400">Kurse werden hier angezeigt, sobald sie verfügbar sind</p>
    </div>
    <?php else: ?>
    <!-- 4er-Grid Layout: 1 Spalte (mobile), 2 Spalten (tablet), 4 Spalten (desktop) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($freebies_with_courses as $freebie): ?>
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden border border-purple-500/20 hover:border-purple-500 transition-all group shadow-xl">
            <div class="h-48 bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center relative overflow-hidden">
                <?php if (!empty($freebie['mockup_url'])): ?>
                <img src="<?php echo htmlspecialchars($freebie['mockup_url']); ?>" 
                     class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-300" 
                     alt="<?php echo htmlspecialchars($freebie['title']); ?>">
                <?php else: ?>
                <i class="fas fa-graduation-cap text-white text-6xl group-hover:scale-110 transition-transform duration-300"></i>
                <?php endif; ?>
                <div class="absolute top-3 right-3 bg-purple-600 text-white px-2 py-1 rounded-full text-xs font-bold">
                    <i class="fas fa-video mr-1"></i>Kurs
                </div>
            </div>
            <div class="p-5">
                <h3 class="text-white font-bold text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($freebie['title']); ?></h3>
                
                <?php if (!empty($freebie['course_title'])): ?>
                <p class="text-purple-400 text-xs mb-2">
                    <i class="fas fa-book mr-1"></i><?php echo htmlspecialchars($freebie['course_title']); ?>
                </p>
                <?php endif; ?>
                
                <?php if (!empty($freebie['description'])): ?>
                <p class="text-gray-400 text-xs mb-4 line-clamp-2">
                    <?php echo htmlspecialchars($freebie['description']); ?>
                </p>
                <?php endif; ?>
                
                <div class="flex gap-2">
                    <?php if (!empty($freebie['course_id'])): ?>
                    <a href="/customer/freebie-course-player.php?id=<?php echo $freebie['course_id']; ?>&email=<?php echo urlencode($lead['email']); ?>" 
                       class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white text-center px-3 py-2.5 rounded-xl text-sm font-semibold transition-all">
                        <i class="fas fa-play-circle mr-1"></i>Start
                    </a>
                    <?php else: ?>
                    <div class="flex-1 bg-yellow-600/20 text-yellow-300 text-center px-3 py-2.5 rounded-xl text-sm font-semibold border border-yellow-600/30">
                        <i class="fas fa-clock mr-1"></i>Bald
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($referral_enabled): ?>
                    <button onclick="scrollToReferralLink('<?php echo htmlspecialchars($freebie['unique_id']); ?>')" 
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-2.5 rounded-xl text-sm font-semibold transition-all"
                            title="Teilen">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

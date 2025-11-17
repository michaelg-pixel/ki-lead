<?php
/**
 * Lead Dashboard - Übersicht
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<!-- Willkommen -->
<div class="mb-8 animate-fade-in-up opacity-0">
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-2">
            Willkommen zurück, <?php echo htmlspecialchars($lead['name']); ?>! 👋
        </h2>
        <p class="text-purple-100 text-lg">
            Deine Kurse und Belohnungen warten auf dich.
        </p>
    </div>
</div>

<!-- Stats -->
<?php if ($referral_enabled): ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                <i class="fas fa-users text-white text-2xl"></i>
            </div>
        </div>
        <div class="text-white">
            <div class="text-5xl font-bold mb-2"><?php echo $total_referrals; ?></div>
            <div class="text-green-100 text-sm font-medium">Gesamt Empfehlungen</div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0" style="animation-delay: 0.2s;">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                <i class="fas fa-check-circle text-white text-2xl"></i>
            </div>
        </div>
        <div class="text-white">
            <div class="text-5xl font-bold mb-2"><?php echo $successful_referrals; ?></div>
            <div class="text-blue-100 text-sm font-medium">Erfolgreiche Empfehlungen</div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 shadow-xl animate-fade-in-up opacity-0" style="animation-delay: 0.3s;">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                <i class="fas fa-gift text-white text-2xl"></i>
            </div>
        </div>
        <div class="text-white">
            <div class="text-5xl font-bold mb-2"><?php echo count($delivered_rewards); ?></div>
            <div class="text-purple-100 text-sm font-medium">Erhaltene Belohnungen</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in-up opacity-0" style="animation-delay: 0.4s;">
    
    <!-- Kurse starten -->
    <a href="?page=kurse<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
       class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20 hover:border-purple-500 transition-all group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-purple-600 rounded-xl p-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-graduation-cap text-white text-3xl"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-xl">Kurse starten</h3>
                <p class="text-gray-400 text-sm">Lerne jetzt weiter</p>
            </div>
        </div>
        <div class="text-gray-400 text-sm">
            <?php echo count($freebies_with_courses); ?> Kurs<?php echo count($freebies_with_courses) != 1 ? 'e' : ''; ?> verfügbar
        </div>
    </a>
    
    <!-- Empfehlen -->
    <?php if ($referral_enabled): ?>
    <a href="?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
       class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-green-500/20 hover:border-green-500 transition-all group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-green-600 rounded-xl p-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-share-alt text-white text-3xl"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-xl">Empfehlen</h3>
                <p class="text-gray-400 text-sm">Verdiene Belohnungen</p>
            </div>
        </div>
        <div class="text-gray-400 text-sm">
            Nächste Belohnung bei <?php echo !empty($reward_tiers) ? $reward_tiers[0]['required_referrals'] : '3'; ?> Empfehlungen
        </div>
    </a>
    <?php endif; ?>
    
    <!-- KI Social Assistant -->
    <?php if ($referral_enabled): ?>
    <a href="?page=social<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
       class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-blue-500/20 hover:border-blue-500 transition-all group relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-purple-600/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-blue-600 rounded-xl p-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-robot text-white text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-xl">KI Social Assistant</h3>
                    <p class="text-gray-400 text-sm">Erstelle Posts für Social Media</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-blue-400 text-sm font-semibold">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                </span>
                KI-Powered
            </div>
        </div>
    </a>
    <?php endif; ?>
    
    <!-- Anleitung -->
    <a href="?page=anleitung<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
       class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-yellow-500/20 hover:border-yellow-500 transition-all group">
        <div class="flex items-center gap-4 mb-4">
            <div class="bg-yellow-600 rounded-xl p-4 group-hover:scale-110 transition-transform">
                <i class="fas fa-book-open text-white text-3xl"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-xl">So funktioniert's</h3>
                <p class="text-gray-400 text-sm">Schritt-für-Schritt Anleitung</p>
            </div>
        </div>
        <div class="text-gray-400 text-sm">
            Lerne, wie du am besten empfiehlst
        </div>
    </a>
    
</div>
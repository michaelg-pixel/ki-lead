<?php
/**
 * Lead Dashboard - So funktioniert's (Anleitung)
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<div class="animate-fade-in-up opacity-0">
    <h2 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-book-open text-yellow-400 mr-3"></i>
        So funktioniert's
    </h2>
    
    <!-- Intro -->
    <div class="bg-gradient-to-r from-yellow-600 to-orange-600 rounded-2xl p-8 mb-8 shadow-xl">
        <h3 class="text-white text-2xl font-bold mb-3">
            🎯 Verdiene Belohnungen durch Empfehlungen!
        </h3>
        <p class="text-yellow-100 text-lg">
            Teile dein Freebie mit Freunden und Familie und erhalte wertvolle Belohnungen für jede erfolgreiche Empfehlung.
        </p>
    </div>
    
    <!-- Schritt-für-Schritt Anleitung -->
    <div class="space-y-6">
        
        <!-- Schritt 1 -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">1</span>
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-bold text-xl mb-2">
                        <i class="fas fa-link text-purple-400 mr-2"></i>
                        Kopiere deinen persönlichen Empfehlungslink
                    </h4>
                    <p class="text-gray-400 mb-3">
                        Dein einzigartiger Empfehlungslink ist bereits für dich erstellt. Kopiere ihn einfach mit einem Klick!
                    </p>
                    <?php if ($referral_enabled && $selected_freebie): ?>
                    <div class="bg-gray-900/50 rounded-lg p-3 font-mono text-sm text-purple-300 break-all">
                        <?php echo htmlspecialchars('https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . $selected_freebie['unique_id'] . '&ref=' . $lead['referral_code']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Schritt 2 -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-green-500/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">2</span>
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-bold text-xl mb-2">
                        <i class="fas fa-share-alt text-green-400 mr-2"></i>
                        Teile den Link mit deinen Kontakten
                    </h4>
                    <p class="text-gray-400 mb-3">
                        Versende den Link per WhatsApp, E-Mail, Social Media oder nutze unseren KI Social Assistant für professionelle Posts!
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <div class="bg-green-600/20 text-green-300 px-4 py-2 rounded-lg border border-green-600/30">
                            <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                        </div>
                        <div class="bg-blue-600/20 text-blue-300 px-4 py-2 rounded-lg border border-blue-600/30">
                            <i class="fab fa-facebook mr-2"></i>Facebook
                        </div>
                        <div class="bg-blue-500/20 text-blue-300 px-4 py-2 rounded-lg border border-blue-500/30">
                            <i class="fab fa-twitter mr-2"></i>Twitter
                        </div>
                        <div class="bg-pink-600/20 text-pink-300 px-4 py-2 rounded-lg border border-pink-600/30">
                            <i class="fab fa-instagram mr-2"></i>Instagram
                        </div>
                        <div class="bg-red-600/20 text-red-300 px-4 py-2 rounded-lg border border-red-600/30">
                            <i class="fas fa-envelope mr-2"></i>E-Mail
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schritt 3 -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-blue-500/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">3</span>
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-bold text-xl mb-2">
                        <i class="fas fa-user-plus text-blue-400 mr-2"></i>
                        Deine Kontakte melden sich an
                    </h4>
                    <p class="text-gray-400 mb-3">
                        Wenn jemand über deinen Link das Freebie herunterlädt und sich anmeldet, wird die Empfehlung automatisch verfolgt.
                    </p>
                    <div class="bg-blue-600/10 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-blue-300 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Wichtig:</strong> Die Empfehlung gilt nur, wenn dein Kontakt über deinen persönlichen Link kommt!
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schritt 4 -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-yellow-500/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-yellow-600 to-orange-600 rounded-xl flex items-center justify-center">
                    <span class="text-white text-2xl font-bold">4</span>
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-bold text-xl mb-2">
                        <i class="fas fa-gift text-yellow-400 mr-2"></i>
                        Erhalte automatisch deine Belohnungen
                    </h4>
                    <p class="text-gray-400 mb-3">
                        Sobald du die erforderliche Anzahl an Empfehlungen erreichst, erhältst du deine Belohnung automatisch per E-Mail!
                    </p>
                    <div class="bg-yellow-600/10 border-l-4 border-yellow-500 p-4 rounded">
                        <p class="text-yellow-300 text-sm mb-2">
                            <i class="fas fa-trophy mr-2"></i>
                            <strong>Deine Belohnungen:</strong>
                        </p>
                        <?php if (!empty($reward_tiers)): ?>
                        <ul class="space-y-1">
                            <?php foreach (array_slice($reward_tiers, 0, 3) as $tier): ?>
                            <li class="text-gray-300 text-sm">
                                ✨ <strong><?php echo $tier['required_referrals']; ?> Empfehlungen:</strong> 
                                <?php echo htmlspecialchars($tier['reward_title']); ?>
                            </li>
                            <?php endforeach; ?>
                            <?php if (count($reward_tiers) > 3): ?>
                            <li class="text-gray-400 text-sm">... und noch mehr!</li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Tipps & Tricks -->
    <div class="mt-8 bg-gradient-to-br from-purple-900/50 to-blue-900/50 rounded-2xl p-6 shadow-xl border border-purple-500/30">
        <h3 class="text-white text-xl font-bold mb-4">
            <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
            Profi-Tipps für mehr Erfolg
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-800/50 rounded-lg p-4">
                <h4 class="text-purple-400 font-semibold mb-2">
                    <i class="fas fa-comments mr-2"></i>Persönliche Nachricht
                </h4>
                <p class="text-gray-400 text-sm">
                    Füge eine persönliche Nachricht hinzu, warum das Freebie wertvoll ist - das erhöht die Conversion!
                </p>
            </div>
            
            <div class="bg-gray-800/50 rounded-lg p-4">
                <h4 class="text-blue-400 font-semibold mb-2">
                    <i class="fas fa-clock mr-2"></i>Bester Zeitpunkt
                </h4>
                <p class="text-gray-400 text-sm">
                    Poste vormittags oder abends, wenn die meisten Menschen online sind.
                </p>
            </div>
            
            <div class="bg-gray-800/50 rounded-lg p-4">
                <h4 class="text-green-400 font-semibold mb-2">
                    <i class="fas fa-users mr-2"></i>Zielgruppe
                </h4>
                <p class="text-gray-400 text-sm">
                    Teile mit Menschen, die wirklich von dem Freebie profitieren können.
                </p>
            </div>
            
            <div class="bg-gray-800/50 rounded-lg p-4">
                <h4 class="text-yellow-400 font-semibold mb-2">
                    <i class="fas fa-robot mr-2"></i>KI nutzen
                </h4>
                <p class="text-gray-400 text-sm">
                    Nutze unseren KI Social Assistant für professionelle, ansprechende Posts!
                </p>
            </div>
        </div>
    </div>
    
    <!-- CTA Buttons -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white text-center px-6 py-4 rounded-xl font-bold text-lg transition-all shadow-xl">
            <i class="fas fa-share-alt mr-2"></i>
            Jetzt empfehlen!
        </a>
        
        <?php if ($referral_enabled): ?>
        <a href="?page=social<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-center px-6 py-4 rounded-xl font-bold text-lg transition-all shadow-xl">
            <i class="fas fa-robot mr-2"></i>
            KI Social Assistant nutzen
        </a>
        <?php endif; ?>
    </div>
    
</div>

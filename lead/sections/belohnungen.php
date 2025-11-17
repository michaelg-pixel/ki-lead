<?php
/**
 * Lead Dashboard - Belohnungen Sektion
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<div class="animate-fade-in-up opacity-0">
    <h2 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-gift text-purple-400 mr-3"></i>
        Meine Belohnungen
    </h2>
    
    <!-- Erhaltene Belohnungen -->
    <?php if (!empty($delivered_rewards)): ?>
    <div class="mb-8">
        <h3 class="text-white text-xl font-bold mb-4">
            <i class="fas fa-check-circle text-green-400 mr-2"></i>
            Erhaltene Belohnungen
            <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm ml-3"><?php echo count($delivered_rewards); ?></span>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($delivered_rewards as $reward): 
                $is_new = (time() - strtotime($reward['delivered_at'])) < 86400;
            ?>
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl p-6 border border-purple-500/30 shadow-xl <?php echo $is_new ? 'reward-new' : ''; ?>">
                <?php if ($is_new): ?>
                <div class="inline-block bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold mb-4">
                    ✨ NEU
                </div>
                <?php endif; ?>
                
                <div class="flex items-center gap-4 mb-4">
                    <div style="color: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>;" class="text-4xl">
                        <i class="fas <?php echo htmlspecialchars($reward['reward_icon'] ?? 'fa-gift'); ?>"></i>
                    </div>
                    <div>
                        <?php if (!empty($reward['tier_name'])): ?>
                        <div class="inline-block px-3 py-1 rounded-full text-xs font-bold mb-2" 
                             style="background: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>20; color: <?php echo htmlspecialchars($reward['reward_color'] ?? '#8B5CF6'); ?>;">
                            <?php echo htmlspecialchars($reward['tier_name']); ?>
                        </div>
                        <?php endif; ?>
                        <h4 class="text-white font-bold text-lg"><?php echo htmlspecialchars($reward['reward_title']); ?></h4>
                    </div>
                </div>
                
                <?php if (!empty($reward['delivery_url'])): ?>
                <div class="bg-green-500/10 border-l-4 border-green-500 p-4 rounded mb-4">
                    <p class="text-green-300 font-semibold mb-2">🔗 Download-Link:</p>
                    <a href="<?php echo htmlspecialchars($reward['delivery_url']); ?>" target="_blank" 
                       class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-all">
                        <i class="fas fa-download mr-2"></i>Jetzt herunterladen
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($reward['access_code'])): ?>
                <div class="bg-yellow-500/10 border-l-4 border-yellow-500 p-4 rounded mb-4">
                    <p class="text-yellow-300 font-semibold mb-2">🔑 Zugriffscode:</p>
                    <div class="bg-gray-900 p-3 rounded font-mono text-yellow-300 text-center text-lg font-bold">
                        <?php echo htmlspecialchars($reward['access_code']); ?>
                    </div>
                    <button onclick="copyCode('<?php echo htmlspecialchars($reward['access_code']); ?>', this)" 
                            class="w-full mt-2 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold transition-all">
                        <i class="fas fa-copy mr-2"></i>Code kopieren
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($reward['delivery_instructions'])): ?>
                <div class="bg-blue-500/10 border-l-4 border-blue-500 p-4 rounded">
                    <p class="text-blue-300 font-semibold mb-2">📋 Einlöse-Anweisungen:</p>
                    <p class="text-gray-300 text-sm"><?php echo nl2br(htmlspecialchars($reward['delivery_instructions'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="text-gray-500 text-xs mt-4 pt-4 border-t border-gray-700">
                    <i class="fas fa-clock mr-1"></i>
                    Erhalten am <?php echo date('d.m.Y \u\m H:i', strtotime($reward['delivered_at'])); ?> Uhr
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Verfügbare Belohnungsstufen -->
    <?php if (!empty($reward_tiers)): ?>
    <div>
        <h3 class="text-white text-xl font-bold mb-4">
            <i class="fas fa-trophy text-yellow-400 mr-2"></i>
            Verfügbare Belohnungsstufen
        </h3>
        
        <div class="space-y-4">
            <?php 
            $delivered_reward_ids = array_column($delivered_rewards, 'reward_id');
            foreach ($reward_tiers as $tier): 
                $is_unlocked = $successful_referrals >= $tier['required_referrals'];
                $is_delivered = in_array($tier['id'], $delivered_reward_ids);
                $progress_percent = min(100, ($successful_referrals / $tier['required_referrals']) * 100);
                $color = $tier['reward_color'] ?? '#8B5CF6';
            ?>
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl p-6 border-2 <?php echo $is_unlocked ? 'border-green-500 reward-unlocked' : 'border-gray-700'; ?> shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center" style="background: <?php echo $color; ?>20;">
                        <i class="fas <?php echo $tier['reward_icon'] ?? 'fa-gift'; ?> text-3xl" style="color: <?php echo $color; ?>;"></i>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex items-center flex-wrap gap-2 mb-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold" style="background: <?php echo $color; ?>; color: white;">
                                <?php echo htmlspecialchars($tier['tier_name']); ?>
                            </span>
                            <?php if ($is_delivered): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-600 text-white">
                                <i class="fas fa-check-circle mr-1"></i>Erhalten
                            </span>
                            <?php elseif ($is_unlocked): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-600 text-white animate-pulse">
                                <i class="fas fa-star mr-1"></i>Freigeschaltet!
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="text-white font-bold text-xl mb-1">
                            <?php echo htmlspecialchars($tier['reward_title']); ?>
                        </h4>
                        
                        <?php if (!empty($tier['reward_description'])): ?>
                        <p class="text-gray-400 text-sm mb-3">
                            <?php echo htmlspecialchars($tier['reward_description']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($tier['reward_value'])): ?>
                        <p class="text-sm font-semibold mb-3" style="color: <?php echo $color; ?>;">
                            💎 <?php echo htmlspecialchars($tier['reward_value']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-400">
                                    <?php echo $tier['required_referrals']; ?> Empfehlungen erforderlich
                                </span>
                                <span class="font-bold" style="color: <?php echo $color; ?>;">
                                    <?php echo $successful_referrals; ?> / <?php echo $tier['required_referrals']; ?>
                                </span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full transition-all duration-500" 
                                     style="width: <?php echo $progress_percent; ?>%; background: linear-gradient(90deg, <?php echo $color; ?>, <?php echo $color; ?>cc);"></div>
                            </div>
                        </div>
                        
                        <?php if (!$is_unlocked): ?>
                        <p class="text-gray-500 text-xs">
                            <i class="fas fa-info-circle mr-1"></i>
                            Noch <?php echo $tier['required_referrals'] - $successful_referrals; ?> Empfehlung<?php echo ($tier['required_referrals'] - $successful_referrals) != 1 ? 'en' : ''; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($delivered_rewards) && empty($reward_tiers)): ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-12 text-center shadow-xl border border-purple-500/20">
        <div class="text-6xl mb-4">🎁</div>
        <h3 class="text-white text-2xl font-bold mb-2">Noch keine Belohnungen</h3>
        <p class="text-gray-400 mb-6">Empfiehl dein Freebie und erhalte wertvolle Belohnungen!</p>
        <a href="?page=empfehlen<?php echo $selected_freebie_id ? '&freebie=' . $selected_freebie_id : ''; ?>" 
           class="inline-block bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-8 py-3 rounded-xl font-bold transition-all">
            <i class="fas fa-share-alt mr-2"></i>Jetzt empfehlen
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function copyCode(code, button) {
    navigator.clipboard.writeText(code).then(() => {
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
        button.classList.add('bg-green-700');
        button.classList.remove('bg-yellow-600');
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-copy mr-2"></i>Code kopieren';
            button.classList.remove('bg-green-700');
            button.classList.add('bg-yellow-600');
        }, 2000);
    });
}
</script>

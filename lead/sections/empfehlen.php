<?php
/**
 * Lead Dashboard - Empfehlen Sektion
 * + BELOHNUNGSSTUFEN: Anzeige der verfügbaren Belohnungen mit Progress
 */
if (!isset($lead) || !isset($pdo)) {
    die('Unauthorized');
}
?>

<div class="animate-fade-in-up opacity-0">
    <h2 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-share-alt text-green-400 mr-3"></i>
        Empfehlen & Belohnungen verdienen
    </h2>
    
    <!-- Empfehlungslink -->
    <?php if ($referral_enabled && $selected_freebie): ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-green-500/20 mb-8">
        <h3 class="text-white text-xl font-bold mb-4">
            <i class="fas fa-link text-green-400 mr-2"></i>
            Dein Empfehlungs-Link
        </h3>
        
        <div class="bg-green-500/10 rounded-xl p-6">
            <p class="text-green-300 font-semibold mb-3">
                📢 Teile diesen Link und verdiene Belohnungen!
            </p>
            <p class="text-gray-400 text-sm mb-4">
                Für: <strong class="text-white"><?php echo htmlspecialchars($selected_freebie['title']); ?></strong>
            </p>
            <div class="flex gap-3 flex-col sm:flex-row">
                <input type="text" 
                       id="refLink" 
                       value="<?php echo htmlspecialchars('https://app.mehr-infos-jetzt.de/freebie/index.php?id=' . $selected_freebie['unique_id'] . '&ref=' . $lead['referral_code']); ?>" 
                       readonly
                       class="flex-1 bg-gray-900 text-white px-4 py-3 rounded-lg border border-green-500/50">
                <button onclick="copyRefLink()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-copy mr-2"></i>Kopieren
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 shadow-xl">
            <div class="text-white">
                <div class="text-5xl font-bold mb-2"><?php echo $total_referrals; ?></div>
                <div class="text-green-100 text-sm font-medium">Gesamt Empfehlungen</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 shadow-xl">
            <div class="text-white">
                <div class="text-5xl font-bold mb-2"><?php echo $successful_referrals; ?></div>
                <div class="text-blue-100 text-sm font-medium">Erfolgreiche</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 shadow-xl">
            <div class="text-white">
                <div class="text-5xl font-bold mb-2"><?php echo count($delivered_rewards); ?></div>
                <div class="text-purple-100 text-sm font-medium">Erhaltene Belohnungen</div>
            </div>
        </div>
    </div>
    
    <!-- Belohnungsstufen -->
    <?php if (!empty($reward_tiers)): ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20 mb-8">
        <h3 class="text-white text-2xl font-bold mb-6">
            <i class="fas fa-trophy text-yellow-400 mr-2"></i>
            Deine Belohnungsstufen
        </h3>
        
        <div class="space-y-6">
            <?php foreach ($reward_tiers as $index => $tier): 
                $is_achieved = $successful_referrals >= $tier['required_referrals'];
                $progress_percent = min(100, ($successful_referrals / $tier['required_referrals']) * 100);
                $is_next = !$is_achieved && ($index == 0 || $successful_referrals >= $reward_tiers[$index-1]['required_referrals']);
                
                // Prüfen ob Belohnung bereits ausgeliefert wurde
                $is_delivered = false;
                foreach ($delivered_rewards as $delivered) {
                    if ($delivered['reward_id'] == $tier['id']) {
                        $is_delivered = true;
                        break;
                    }
                }
            ?>
            <div class="bg-gray-800/50 rounded-xl p-6 border-2 <?php echo $is_achieved ? 'border-green-500' : ($is_next ? 'border-purple-500' : 'border-gray-700'); ?> relative overflow-hidden">
                <!-- Background Gradient -->
                <?php if ($is_achieved): ?>
                <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 to-transparent"></div>
                <?php elseif ($is_next): ?>
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-transparent"></div>
                <?php endif; ?>
                
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-4 flex-wrap gap-4">
                        <div class="flex items-center gap-4">
                            <!-- Tier Badge -->
                            <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold text-white <?php echo $is_achieved ? 'bg-gradient-to-br from-green-500 to-green-700' : 'bg-gradient-to-br from-gray-600 to-gray-700'; ?> flex-shrink-0">
                                <?php echo $tier['tier_level']; ?>
                            </div>
                            
                            <div>
                                <h4 class="text-white text-xl font-bold mb-1">
                                    <?php echo htmlspecialchars($tier['tier_name']); ?>
                                    <?php if ($is_delivered): ?>
                                    <span class="text-green-400 text-sm ml-2">
                                        <i class="fas fa-check-circle"></i> Erhalten
                                    </span>
                                    <?php endif; ?>
                                </h4>
                                <?php if (!empty($tier['tier_description'])): ?>
                                <p class="text-gray-400 text-sm">
                                    <?php echo htmlspecialchars($tier['tier_description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <?php if ($is_achieved): ?>
                        <span class="px-4 py-2 bg-green-600 text-white rounded-full text-sm font-semibold whitespace-nowrap">
                            <i class="fas fa-check mr-1"></i>Erreicht!
                        </span>
                        <?php elseif ($is_next): ?>
                        <span class="px-4 py-2 bg-purple-600 text-white rounded-full text-sm font-semibold whitespace-nowrap">
                            <i class="fas fa-rocket mr-1"></i>Nächstes Ziel
                        </span>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-600 text-gray-300 rounded-full text-sm font-semibold whitespace-nowrap">
                            <i class="fas fa-lock mr-1"></i>Gesperrt
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Reward Info -->
                    <div class="bg-black/30 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-4">
                            <div class="text-3xl flex-shrink-0" style="color: <?php echo htmlspecialchars($tier['reward_color'] ?? '#667eea'); ?>">
                                <i class="fas <?php echo htmlspecialchars($tier['reward_icon'] ?? 'fa-gift'); ?>"></i>
                            </div>
                            <div class="flex-1">
                                <h5 class="text-white font-semibold text-lg mb-1">
                                    <?php echo htmlspecialchars($tier['reward_title']); ?>
                                </h5>
                                <?php if (!empty($tier['reward_value'])): ?>
                                <div class="text-green-400 font-medium mb-2">
                                    <i class="fas fa-tag"></i> Wert: <?php echo htmlspecialchars($tier['reward_value']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($tier['reward_description'])): ?>
                                <p class="text-gray-400 text-sm">
                                    <?php echo htmlspecialchars($tier['reward_description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-400">
                                <?php if ($is_achieved): ?>
                                    <i class="fas fa-check text-green-400"></i> Stufe freigeschaltet!
                                <?php else: ?>
                                    <i class="fas fa-users"></i> <?php echo $successful_referrals; ?> / <?php echo $tier['required_referrals']; ?> Empfehlungen
                                <?php endif; ?>
                            </span>
                            <span class="<?php echo $is_achieved ? 'text-green-400' : 'text-purple-400'; ?> font-semibold">
                                <?php echo round($progress_percent); ?>%
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="h-full <?php echo $is_achieved ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-purple-500 to-purple-600'; ?> transition-all duration-500 rounded-full" 
                                 style="width: <?php echo $progress_percent; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if (!$is_achieved && $is_next): ?>
                    <div class="text-center text-sm text-gray-400 mt-3">
                        <i class="fas fa-info-circle text-blue-400"></i>
                        Noch <strong class="text-white"><?php echo ($tier['required_referrals'] - $successful_referrals); ?> <?php echo ($tier['required_referrals'] - $successful_referrals) == 1 ? 'Empfehlung' : 'Empfehlungen'; ?></strong> bis zur nächsten Belohnung!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Keine Belohnungen definiert -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-8 text-center shadow-xl border border-gray-700 mb-8">
        <div class="text-6xl mb-4">🎁</div>
        <h3 class="text-white text-2xl font-bold mb-2">Belohnungen werden vorbereitet</h3>
        <p class="text-gray-400">
            Dein Anbieter konfiguriert gerade die Belohnungsstufen. Bald kannst du hier sehen, welche tollen Prämien auf dich warten!
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Empfehlungen Liste -->
    <?php if (!empty($referrals)): ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
        <h3 class="text-white text-xl font-bold mb-6">
            <i class="fas fa-users text-purple-400 mr-2"></i>
            Deine Empfehlungen
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left text-gray-400 font-semibold text-sm py-3 px-4">Name</th>
                        <th class="text-left text-gray-400 font-semibold text-sm py-3 px-4">E-Mail</th>
                        <th class="text-left text-gray-400 font-semibold text-sm py-3 px-4">Status</th>
                        <th class="text-left text-gray-400 font-semibold text-sm py-3 px-4">Datum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral): ?>
                    <tr class="border-b border-gray-800 hover:bg-gray-800/50 transition-colors">
                        <td class="text-white py-4 px-4"><?php echo htmlspecialchars($referral['name']); ?></td>
                        <td class="text-gray-400 py-4 px-4"><?php echo htmlspecialchars($referral['email']); ?></td>
                        <td class="py-4 px-4">
                            <?php
                            $status_colors = [
                                'active' => 'bg-green-600 text-white',
                                'pending' => 'bg-yellow-600 text-white',
                                'converted' => 'bg-blue-600 text-white'
                            ];
                            $status_labels = [
                                'active' => 'Aktiv',
                                'pending' => 'Ausstehend',
                                'converted' => 'Konvertiert'
                            ];
                            $color = $status_colors[$referral['status']] ?? 'bg-gray-600 text-white';
                            $label = $status_labels[$referral['status']] ?? ucfirst($referral['status']);
                            ?>
                            <span class="<?php echo $color; ?> px-3 py-1 rounded-full text-xs font-semibold">
                                <?php echo $label; ?>
                            </span>
                        </td>
                        <td class="text-gray-400 py-4 px-4"><?php echo date('d.m.Y', strtotime($referral['registered_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-12 text-center shadow-xl border border-purple-500/20">
        <div class="text-6xl mb-4">📭</div>
        <h3 class="text-white text-2xl font-bold mb-2">Noch keine Empfehlungen</h3>
        <p class="text-gray-400 mb-6">Teile deinen Link und verdiene Belohnungen!</p>
        <button onclick="copyRefLink()" 
                class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-8 py-3 rounded-xl font-bold transition-all">
            <i class="fas fa-copy mr-2"></i>Link kopieren
        </button>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<script>
function copyRefLink() {
    const input = document.getElementById('refLink');
    if (!input) return;
    input.select();
    try {
        document.execCommand('copy');
        const btn = event.target.closest('button');
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Kopiert!';
        btn.classList.add('bg-green-700');
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy mr-2"></i>Kopieren';
            btn.classList.remove('bg-green-700');
        }, 2000);
    } catch (err) {
        alert('Bitte kopiere den Link manuell');
    }
}
</script>

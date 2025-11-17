<?php
/**
 * Lead Dashboard - Empfehlen Sektion
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
                    <tr class="border-b border-gray-800">
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

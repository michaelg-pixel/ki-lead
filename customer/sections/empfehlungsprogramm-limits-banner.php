<?php
/**
 * Customer Dashboard - Empfehlungsprogramm mit Slots-Anzeige
 * Zeigt an: "Du hast X von Y Empfehlungsprogrammen genutzt"
 */

// Empfehlungs-Slots laden
$stmt = $pdo->prepare("
    SELECT total_slots, used_slots
    FROM customer_referral_slots 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$slotsData = $stmt->fetch();

$totalSlots = $slotsData ? (int)$slotsData['total_slots'] : 0;
$usedSlots = $slotsData ? (int)$slotsData['used_slots'] : 0;
$availableSlots = max(0, $totalSlots - $usedSlots);

// Freebie-Limit laden
$stmt = $pdo->prepare("
    SELECT freebie_limit
    FROM customer_freebie_limits 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$limitData = $stmt->fetch();
$freebieLimit = $limitData ? (int)$limitData['freebie_limit'] : 0;

// Anzahl erstellter Freebies
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM customer_freebies 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$freebiesCreated = (int)$stmt->fetchColumn();

$freebiesAvailable = max(0, $freebieLimit - $freebiesCreated);
?>

<!-- Limits-Anzeige Banner -->
<div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);">
    <h3 style="color: white; font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.5rem;">📊</span>
        Deine Limits
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <!-- Freebie-Limit -->
        <div style="background: rgba(255, 255, 255, 0.15); border-radius: 0.75rem; padding: 1rem; backdrop-filter: blur(10px);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: rgba(255, 255, 255, 0.9); font-size: 0.875rem; font-weight: 600;">
                    🎁 Eigene Freebies
                </span>
                <span style="background: rgba(255, 255, 255, 0.2); padding: 4px 8px; border-radius: 12px; color: white; font-size: 0.75rem; font-weight: 700;">
                    <?php echo $freebiesCreated; ?> / <?php echo $freebieLimit; ?>
                </span>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 999px; height: 8px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #10b981, #059669); height: 100%; width: <?php echo $freebieLimit > 0 ? min(100, ($freebiesCreated / $freebieLimit) * 100) : 0; ?>%; transition: width 0.3s;"></div>
            </div>
            <?php if ($freebiesAvailable > 0): ?>
            <p style="color: rgba(255, 255, 255, 0.9); font-size: 0.75rem; margin-top: 0.5rem; margin-bottom: 0;">
                ✅ Noch <strong><?php echo $freebiesAvailable; ?></strong> verfügbar
            </p>
            <?php else: ?>
            <p style="color: #fbbf24; font-size: 0.75rem; margin-top: 0.5rem; margin-bottom: 0;">
                ⚠️ Limit erreicht
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Empfehlungs-Slots -->
        <div style="background: rgba(255, 255, 255, 0.15); border-radius: 0.75rem; padding: 1rem; backdrop-filter: blur(10px);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: rgba(255, 255, 255, 0.9); font-size: 0.875rem; font-weight: 600;">
                    🚀 Empfehlungsprogramme
                </span>
                <span style="background: rgba(255, 255, 255, 0.2); padding: 4px 8px; border-radius: 12px; color: white; font-size: 0.75rem; font-weight: 700;">
                    <?php echo $usedSlots; ?> / <?php echo $totalSlots; ?>
                </span>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); border-radius: 999px; height: 8px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #8b5cf6, #7c3aed); height: 100%; width: <?php echo $totalSlots > 0 ? min(100, ($usedSlots / $totalSlots) * 100) : 0; %>%; transition: width 0.3s;"></div>
            </div>
            <?php if ($availableSlots > 0): ?>
            <p style="color: rgba(255, 255, 255, 0.9); font-size: 0.75rem; margin-top: 0.5rem; margin-bottom: 0;">
                ✅ Noch <strong><?php echo $availableSlots; ?></strong> verfügbar
            </p>
            <?php else: ?>
            <p style="color: #fbbf24; font-size: 0.75rem; margin-top: 0.5rem; margin-bottom: 0;">
                ⚠️ Limit erreicht
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($availableSlots == 0 && count($freebies) > 0): ?>
    <div style="background: rgba(251, 191, 36, 0.2); border: 1px solid rgba(251, 191, 36, 0.4); border-radius: 0.75rem; padding: 1rem; margin-top: 1rem;">
        <div style="display: flex; align-items: start; gap: 0.75rem;">
            <span style="font-size: 1.5rem;">⚠️</span>
            <div>
                <strong style="color: white; display: block; margin-bottom: 0.25rem;">
                    Empfehlungsprogramm-Limit erreicht
                </strong>
                <p style="color: rgba(255, 255, 255, 0.9); font-size: 0.875rem; margin: 0; line-height: 1.5;">
                    Du hast alle deine Empfehlungsprogramm-Slots genutzt (<?php echo $totalSlots; ?> von <?php echo $totalSlots; ?>). 
                    Um weitere Empfehlungsprogramme zu erstellen, wähle ein höheres Paket oder kontaktiere den Support.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Limits-Check beim Erstellen neuer Empfehlungsprogramme
function checkLimitsBeforeCreate() {
    const availableSlots = <?php echo $availableSlots; ?>;
    const availableFreebies = <?php echo $freebiesAvailable; ?>;
    
    if (availableSlots === 0) {
        showNotification('⚠️ Empfehlungsprogramm-Limit erreicht! Du hast alle <?php echo $totalSlots; ?> Slots genutzt.', 'error');
        return false;
    }
    
    if (availableFreebies === 0) {
        showNotification('⚠️ Freebie-Limit erreicht! Du hast alle <?php echo $freebieLimit; ?> Freebies erstellt.', 'error');
        return false;
    }
    
    return true;
}
</script>

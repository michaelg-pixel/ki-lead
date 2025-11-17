<?php
/**
 * Lead Dashboard - Meine Belohnungen Sektion
 * Zeigt alle erhaltenen Belohnungen mit Download-Links, Codes etc.
 */

// Diese Datei kann in lead_dashboard.php eingebunden werden

function renderMyRewardsSection($pdo, $lead_id) {
    try {
        // Alle ausgelieferten Belohnungen laden
        $stmt = $pdo->prepare("
            SELECT 
                rd.*,
                rdef.tier_name,
                rdef.tier_description,
                rdef.reward_icon,
                rdef.reward_color
            FROM reward_deliveries rd
            LEFT JOIN reward_definitions rdef ON rd.reward_id = rdef.id
            WHERE rd.lead_id = ?
            ORDER BY rd.delivered_at DESC
        ");
        $stmt->execute([$lead_id]);
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rewards)) {
            return '
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">🎁</span>
                        Meine Belohnungen
                    </h2>
                </div>
                
                <div class="empty-state">
                    <div class="empty-icon">🎁</div>
                    <div class="empty-text">Noch keine Belohnungen erhalten</div>
                    <div class="empty-subtext">Empfiehl weiter und schalte tolle Belohnungen frei!</div>
                </div>
            </div>
            ';
        }
        
        $html = '
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">🎁</span>
                    Meine Belohnungen
                    <span style="background: #8B5CF6; color: white; padding: 4px 12px; border-radius: 12px; 
                                 font-size: 14px; margin-left: 12px;">
                        ' . count($rewards) . '
                    </span>
                </h2>
            </div>
            
            <div class="rewards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
                                             gap: 20px;">
        ';
        
        foreach ($rewards as $reward) {
            $icon = $reward['reward_icon'] ?? 'fa-gift';
            if (strpos($icon, 'fa-') !== 0) {
                $icon = 'fa-gift';
            }
            
            $color = $reward['reward_color'] ?? '#8B5CF6';
            $is_new = (time() - strtotime($reward['delivered_at'])) < 86400; // Neu wenn < 24h
            
            $html .= '
            <div class="reward-delivery-card" style="background: white; border-radius: 12px; padding: 24px; 
                                                       box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
                                                       border-left: 4px solid ' . htmlspecialchars($color) . ';
                                                       position: relative;">
                ' . ($is_new ? '
                <div style="position: absolute; top: 12px; right: 12px; background: #22c55e; color: white; 
                            padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                    ✨ NEU
                </div>
                ' : '') . '
                
                <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px;">
                    <div style="font-size: 48px; color: ' . htmlspecialchars($color) . ';">
                        <i class="fas ' . htmlspecialchars($icon) . '"></i>
                    </div>
                    <div style="flex: 1;">
                        ' . (!empty($reward['tier_name']) ? '
                        <div style="display: inline-block; background: ' . htmlspecialchars($color) . '; 
                                    color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; 
                                    font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">
                            ' . htmlspecialchars($reward['tier_name']) . '
                        </div>
                        ' : '') . '
                        <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700;">
                            ' . htmlspecialchars($reward['reward_title']) . '
                        </h3>
                        ' . (!empty($reward['reward_value']) ? '
                        <p style="margin: 0; color: ' . htmlspecialchars($color) . '; font-weight: 600; font-size: 16px;">
                            ' . htmlspecialchars($reward['reward_value']) . '
                        </p>
                        ' : '') . '
                    </div>
                </div>
                
                ' . (!empty($reward['delivery_url']) ? '
                <div style="background: #f0fdf4; border: 2px solid #22c55e; padding: 16px; border-radius: 8px; 
                            margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #166534;">
                        🔗 Download-Link:
                    </div>
                    <a href="' . htmlspecialchars($reward['delivery_url']) . '" 
                       target="_blank"
                       style="display: inline-block; background: #22c55e; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.2s;"
                       onmouseover="this.style.background=\'#16a34a\'"
                       onmouseout="this.style.background=\'#22c55e\'">
                        <i class="fas fa-download"></i> Jetzt herunterladen
                    </a>
                </div>
                ' : '') . '
                
                ' . (!empty($reward['access_code']) ? '
                <div style="background: #fef3c7; border: 2px solid #f59e0b; padding: 16px; border-radius: 8px; 
                            margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #92400e;">
                        🔑 Zugriffscode:
                    </div>
                    <div style="background: white; padding: 12px; border-radius: 6px; font-family: monospace; 
                                font-size: 18px; font-weight: bold; color: #92400e; text-align: center; 
                                border: 2px dashed #f59e0b;">
                        ' . htmlspecialchars($reward['access_code']) . '
                    </div>
                    <button onclick="copyCode(\'' . htmlspecialchars($reward['access_code']) . '\', this)" 
                            style="margin-top: 8px; width: 100%; padding: 8px; background: #f59e0b; color: white; 
                                   border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
                        <i class="fas fa-copy"></i> Code kopieren
                    </button>
                </div>
                ' : '') . '
                
                ' . (!empty($reward['delivery_instructions']) ? '
                <div style="background: #e0e7ff; border: 2px solid #6366f1; padding: 16px; border-radius: 8px; 
                            margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #3730a3;">
                        📋 Einlöse-Anweisungen:
                    </div>
                    <div style="font-size: 14px; color: #3730a3; line-height: 1.6;">
                        ' . nl2br(htmlspecialchars($reward['delivery_instructions'])) . '
                    </div>
                </div>
                ' : '') . '
                
                <div style="font-size: 12px; color: #9ca3af; margin-top: 16px; padding-top: 16px; 
                            border-top: 1px solid #e5e7eb;">
                    <i class="fas fa-clock"></i> 
                    Erhalten am ' . date('d.m.Y \u\m H:i', strtotime($reward['delivered_at'])) . ' Uhr
                </div>
            </div>
            ';
        }
        
        $html .= '
            </div>
        </div>
        
        <script>
        function copyCode(code, button) {
            navigator.clipboard.writeText(code).then(() => {
                const originalHTML = button.innerHTML;
                button.innerHTML = \'<i class="fas fa-check"></i> Kopiert!\';
                button.style.background = \'#22c55e\';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.style.background = \'#f59e0b\';
                }, 2000);
            }).catch(err => {
                alert(\'Bitte kopiere den Code manuell\');
            });
        }
        </script>
        ';
        
        return $html;
        
    } catch (Exception $e) {
        error_log("Render My Rewards Error: " . $e->getMessage());
        return '<div class="error">Fehler beim Laden der Belohnungen</div>';
    }
}

// Responsive Styles für Mobile
?>
<style>
@media (max-width: 768px) {
    .rewards-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

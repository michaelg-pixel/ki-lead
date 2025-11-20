<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * FIXED VERSION - Mit Bildern im Marktplatz + besseres Design
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// Freebie-ID aus URL Parameter holen (optional)
$freebie_id = isset($_GET['freebie_id']) ? (int)$_GET['freebie_id'] : null;

// Freebie-Details laden wenn ID vorhanden
$selected_freebie = null;
if ($freebie_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cf.id as customer_freebie_id,
                cf.unique_id,
                COALESCE(cf.headline, f.headline, f.name) as title,
                COALESCE(cf.subheadline, f.subheadline) as description,
                COALESCE(cf.mockup_image_url, f.mockup_image_url) as image_path,
                cf.freebie_type,
                cf.created_at
            FROM customer_freebies cf
            LEFT JOIN freebies f ON cf.template_id = f.id
            WHERE cf.id = ? AND cf.customer_id = ?
        ");
        $stmt->execute([$freebie_id, $customer_id]);
        $selected_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Freebie Load Error: " . $e->getMessage());
    }
}

// Alle Freebies laden für Dropdown-Auswahl
$all_freebies = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id as customer_freebie_id,
            cf.unique_id,
            COALESCE(cf.headline, f.headline, f.name) as title,
            COALESCE(cf.mockup_image_url, f.mockup_image_url) as image_path
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("All Freebies Load Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        
        .freebie-selector {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        .freebie-select {
            width: 100%;
            padding: 0.75rem;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.9375rem;
            margin-top: 0.5rem;
        }
        
        .reward-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .reward-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .reward-tier-badge {
            position: absolute;
            top: -10px;
            left: -10px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        .no-freebie-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            z-index: 9999;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        
        .modal-content {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        /* MARKETPLACE MODAL STYLES */
        .marketplace-modal-content {
            max-width: 1200px;
        }
        
        .marketplace-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .template-card {
            background: linear-gradient(to bottom right, #111827, #1f2937);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 0;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .template-card:hover {
            transform: translateY(-4px);
            border-color: rgba(102, 126, 234, 0.6);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        
        .template-card.imported {
            border-color: rgba(16, 185, 129, 0.5);
            background: linear-gradient(to bottom right, #064e3b, #1f2937);
        }
        
        .template-card-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .template-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .template-card-content {
            padding: 1.5rem;
        }
        
        .btn-import {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            width: 100%;
        }
        
        .btn-import:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }
        
        .btn-import:disabled {
            background: #374151;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: #9ca3af;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.9375rem;
            transition: all 0.3s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9375rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-secondary {
            background: #374151;
            color: white;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid #3b82f6;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .freebie-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        @media (max-width: 640px) {
            .reward-card {
                padding: 1rem;
            }
            
            .modal-content {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }
            
            .freebie-info-card {
                flex-direction: column;
                text-align: center;
            }
            
            .marketplace-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(to bottom right, #1f2937, #111827, #1f2937); min-height: 100vh;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 1rem;">
        
        <!-- Header -->
        <div class="animate-fade-in" style="opacity: 0; margin-bottom: 2rem;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1rem; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                            <i class="fas fa-trophy"></i> Belohnungsstufen
                        </h1>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 1rem;">
                            Konfiguriere die Belohnungen für dein Empfehlungsprogramm
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <button onclick="openMarketplace()" class="btn" style="background: rgba(255, 255, 255, 0.2); color: white;">
                            <i class="fas fa-shopping-bag"></i>
                            Marktplatz durchstöbern
                        </button>
                        <button onclick="openRewardModal()" class="btn btn-primary" id="createBtn">
                            <i class="fas fa-plus"></i>
                            Neue Belohnungsstufe
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Freebie-Auswahl (wenn nicht aus Empfehlungsprogramm) -->
        <?php if (!$selected_freebie && !empty($all_freebies)): ?>
        <div class="freebie-selector animate-fade-in" style="opacity: 0; animation-delay: 0.1s;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="color: #3b82f6; font-size: 2rem;">
                    <i class="fas fa-filter"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">
                        Belohnungen filtern (optional)
                    </h3>
                    <p style="color: #9ca3af; font-size: 0.875rem;">
                        Wähle ein Freebie um nur dessen Belohnungen zu sehen, oder lasse das Feld leer um alle zu sehen.
                    </p>
                </div>
            </div>
            <select class="freebie-select" onchange="filterByFreebie(this.value)">
                <option value="">Alle Belohnungen anzeigen</option>
                <?php foreach ($all_freebies as $f): ?>
                <option value="<?php echo $f['customer_freebie_id']; ?>">
                    <?php echo htmlspecialchars($f['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <!-- Freebie-Info wenn ausgewählt -->
        <?php if ($selected_freebie): ?>
        <div class="freebie-info-card animate-fade-in" style="opacity: 0; animation-delay: 0.1s;">
            <?php if (!empty($selected_freebie['image_path'])): ?>
            <div style="width: 60px; height: 60px; border-radius: 0.5rem; overflow: hidden; background: #111827; flex-shrink: 0;">
                <img src="<?php echo htmlspecialchars($selected_freebie['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($selected_freebie['title']); ?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <?php else: ?>
            <div style="width: 60px; height: 60px; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas fa-gift"></i>
            </div>
            <?php endif; ?>
            
            <div style="flex: 1;">
                <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.8125rem; margin-bottom: 0.25rem;">
                    Belohnungen für Freebie
                </div>
                <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">
                    <?php echo htmlspecialchars($selected_freebie['title']); ?>
                </h3>
                <?php if (!empty($selected_freebie['description'])): ?>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.8125rem;">
                    <?php echo htmlspecialchars(substr($selected_freebie['description'], 0, 80)) . (strlen($selected_freebie['description']) > 80 ? '...' : ''); ?>
                </p>
                <?php endif; ?>
            </div>
            
            <a href="?page=belohnungsstufen" style="color: white; background: rgba(255, 255, 255, 0.2); padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem; white-space: nowrap;">
                <i class="fas fa-arrow-left"></i> Zurück
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Info: Alte Belohnungen -->
        <?php if (!$selected_freebie): ?>
        <div class="info-box animate-fade-in" style="opacity: 0; animation-delay: 0.2s;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <div style="color: #3b82f6; font-size: 2rem; flex-shrink: 0;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                        Allgemeine und Freebie-spezifische Belohnungen
                    </h3>
                    <p style="color: #9ca3af; font-size: 0.875rem; line-height: 1.6;">
                        Du siehst hier alle deine Belohnungsstufen. Belohnungen mit <span class="no-freebie-tag" style="display: inline;">⚠️ Allgemein</span> gelten für alle Freebies. 
                        Du kannst über das Empfehlungsprogramm neue freebie-spezifische Belohnungen erstellen oder fertige Templates aus dem Marktplatz importieren.
                    </p>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                        <a href="?page=empfehlungsprogramm" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-rocket"></i>
                            Zum Empfehlungsprogramm
                        </a>
                        <button onclick="openMarketplace()" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                            <i class="fas fa-shopping-bag"></i>
                            Marktplatz durchstöbern
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Loading State -->
        <div id="loadingState" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p style="color: #9ca3af; font-size: 1.125rem;">
                Lade Belohnungsstufen...
            </p>
        </div>
        
        <!-- Error State -->
        <div id="errorState" style="display: none;">
            <div style="background: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; border-radius: 1rem; padding: 2rem; text-align: center;">
                <div style="font-size: 4rem; color: #ef4444; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 style="color: white; font-size: 1.5rem; margin-bottom: 1rem;">
                    Fehler beim Laden
                </h3>
                <p id="errorMessage" style="color: #9ca3af; margin-bottom: 2rem; font-size: 1rem;">
                </p>
            </div>
        </div>
        
        <!-- Rewards Grid -->
        <div id="rewardsGrid" style="display: none;">
            <!-- Wird dynamisch gefüllt -->
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" style="display: none; text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; color: #374151; margin-bottom: 1rem;">
                <i class="fas fa-trophy"></i>
            </div>
            <h3 style="color: white; font-size: 1.5rem; margin-bottom: 0.5rem;">
                Noch keine Belohnungsstufen
            </h3>
            <p style="color: #9ca3af; margin-bottom: 2rem;">
                <?php if ($selected_freebie): ?>
                    Erstelle deine erste Belohnungsstufe für dieses Freebie
                <?php else: ?>
                    Erstelle deine erste Belohnungsstufe oder importiere fertige Templates aus dem Marktplatz
                <?php endif; ?>
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button onclick="openRewardModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Jetzt erstellen
                </button>
                <button onclick="openMarketplace()" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                    <i class="fas fa-shopping-bag"></i>
                    Marktplatz durchstöbern
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal: Belohnung erstellen/bearbeiten -->
    <div id="rewardModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 id="modalTitle" style="color: white; font-size: 1.5rem; font-weight: 700;">
                    Neue Belohnungsstufe
                </h2>
                <button onclick="closeRewardModal()" style="background: none; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="rewardForm" onsubmit="saveReward(event)">
                <input type="hidden" id="rewardId" name="id">
                <input type="hidden" id="rewardFreebieId" name="freebie_id" value="<?php echo $freebie_id ?? ''; ?>">
                
                <?php if (!$selected_freebie && !empty($all_freebies)): ?>
                <div class="form-group">
                    <label class="form-label">Freebie zuordnen (optional)</label>
                    <select name="freebie_id_select" class="form-select">
                        <option value="">Allgemeine Belohnung (alle Freebies)</option>
                        <?php foreach ($all_freebies as $f): ?>
                        <option value="<?php echo $f['customer_freebie_id']; ?>">
                            <?php echo htmlspecialchars($f['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #6b7280; font-size: 0.75rem;">Leer lassen für allgemeine Belohnung</small>
                </div>
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    
                    <!-- Linke Spalte: Grunddaten -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Stufen-Level *</label>
                            <input type="number" name="tier_level" class="form-input" required min="1" max="50" placeholder="z.B. 1">
                            <small style="color: #6b7280; font-size: 0.75rem;">Nummer der Stufe (1-50)</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Stufen-Name *</label>
                            <input type="text" name="tier_name" class="form-input" required placeholder="z.B. Bronze, Silber, Gold">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="tier_description" class="form-textarea" rows="3" placeholder="Optionale Beschreibung der Stufe..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Erforderliche Empfehlungen *</label>
                            <input type="number" name="required_referrals" class="form-input" required min="1" placeholder="z.B. 3">
                            <small style="color: #6b7280; font-size: 0.75rem;">Anzahl erfolgreicher Empfehlungen</small>
                        </div>
                    </div>
                    
                    <!-- Rechte Spalte: Belohnung -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Belohnungstyp *</label>
                            <select name="reward_type" class="form-select" required>
                                <option value="">-- Auswählen --</option>
                                <option value="ebook">E-Book</option>
                                <option value="pdf">PDF-Download</option>
                                <option value="consultation">Beratung</option>
                                <option value="course">Kurs-Zugang</option>
                                <option value="voucher">Gutschein</option>
                                <option value="discount">Rabatt</option>
                                <option value="freebie">Zusätzliches Freebie</option>
                                <option value="other">Sonstiges</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Belohnungs-Titel *</label>
                            <input type="text" name="reward_title" class="form-input" required placeholder="z.B. Kostenloses E-Book">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Belohnungs-Beschreibung</label>
                            <textarea name="reward_description" class="form-textarea" rows="3" placeholder="Beschreibe die Belohnung..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Wert</label>
                            <input type="text" name="reward_value" class="form-input" placeholder="z.B. 50€, 1h Beratung, 20% Rabatt">
                        </div>
                    </div>
                </div>
                
                <!-- Erweiterte Einstellungen -->
                <details style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                    <summary style="color: #9ca3af; cursor: pointer; padding: 0.75rem; background: rgba(255,255,255,0.05); border-radius: 0.5rem; user-select: none;">
                        <i class="fas fa-cog"></i> Erweiterte Einstellungen
                    </summary>
                    
                    <div style="margin-top: 1rem; display: grid; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Download-URL</label>
                            <input type="url" name="reward_download_url" class="form-input" placeholder="https://...">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Zugriffscode</label>
                            <input type="text" name="reward_access_code" class="form-input" placeholder="Optional: Code für Zugriff">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Einlöse-Anweisungen</label>
                            <textarea name="reward_instructions" class="form-textarea" rows="3" placeholder="Anweisungen, wie die Belohnung eingelöst wird..."></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Icon (Font Awesome)</label>
                                <input type="text" name="reward_icon" class="form-input" value="fa-gift" placeholder="fa-gift">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Farbe</label>
                                <input type="color" name="reward_color" class="form-input" value="#667eea">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; cursor: pointer;">
                                <input type="checkbox" name="is_active" checked style="width: 1.25rem; height: 1.25rem;">
                                Aktiv
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; cursor: pointer;">
                                <input type="checkbox" name="is_featured" style="width: 1.25rem; height: 1.25rem;">
                                Hervorgehoben
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #9ca3af; cursor: pointer;">
                                <input type="checkbox" name="auto_deliver" style="width: 1.25rem; height: 1.25rem;">
                                Auto-Zusendung
                            </label>
                        </div>
                    </div>
                </details>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1.5rem; border-top: 1px solid #374151;">
                    <button type="button" onclick="closeRewardModal()" class="btn btn-secondary">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Marktplatz -->
    <div id="marketplaceModal" class="modal">
        <div class="modal-content marketplace-modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h2 style="color: white; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-shopping-bag"></i> Belohnungen-Marktplatz
                    </h2>
                    <p style="color: #9ca3af; font-size: 0.875rem;">
                        Importiere fertige Belohnungs-Templates von anderen Vendors
                    </p>
                </div>
                <button onclick="closeMarketplace()" style="background: none; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Filters -->
            <div class="marketplace-filters">
                <div style="flex: 1; min-width: 200px;">
                    <input 
                        type="text" 
                        id="marketplaceSearch" 
                        class="form-input" 
                        placeholder="🔍 Suche..." 
                        onkeyup="filterMarketplace()"
                        style="margin-bottom: 0;"
                    >
                </div>
                <div style="min-width: 200px;">
                    <select id="marketplaceCategory" class="form-select" onchange="filterMarketplace()" style="margin-bottom: 0;">
                        <option value="">Alle Kategorien</option>
                        <option value="ebook">E-Books</option>
                        <option value="pdf">PDFs</option>
                        <option value="consultation">Beratungen</option>
                        <option value="course">Kurse</option>
                        <option value="voucher">Gutscheine</option>
                        <option value="discount">Rabatte</option>
                        <option value="other">Sonstiges</option>
                    </select>
                </div>
                <div style="min-width: 200px;">
                    <select id="marketplaceNiche" class="form-select" onchange="filterMarketplace()" style="margin-bottom: 0;">
                        <option value="">Alle Nischen</option>
                        <option value="online-business">Online Business</option>
                        <option value="fitness">Fitness</option>
                        <option value="health">Gesundheit</option>
                        <option value="marketing">Marketing</option>
                        <option value="coaching">Coaching</option>
                        <option value="other">Sonstiges</option>
                    </select>
                </div>
            </div>
            
            <!-- Loading -->
            <div id="marketplaceLoading" style="text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <p style="color: #9ca3af; font-size: 1.125rem;">
                    Lade Marktplatz...
                </p>
            </div>
            
            <!-- Grid -->
            <div id="marketplaceGrid" class="marketplace-grid" style="display: none;">
                <!-- Wird dynamisch gefüllt -->
            </div>
            
            <!-- Empty -->
            <div id="marketplaceEmpty" style="display: none; text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 4rem; color: #374151; margin-bottom: 1rem;">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 style="color: white; font-size: 1.5rem; margin-bottom: 0.5rem;">
                    Keine Templates gefunden
                </h3>
                <p style="color: #9ca3af;">
                    Passe deine Filter an oder werde selbst zum Vendor und erstelle Templates!
                </p>
            </div>
        </div>
    </div>
    
    <script>
        let rewards = [];
        let freebieId = <?php echo $freebie_id ?? 'null'; ?>;
        let marketplaceTemplates = [];
        let allMarketplaceTemplates = [];
        
        // Seite laden
        document.addEventListener('DOMContentLoaded', function() {
            loadRewards();
        });
        
        // Belohnungen laden
        function loadRewards() {
            const url = freebieId 
                ? `/api/rewards/list.php?freebie_id=${freebieId}`
                : `/api/rewards/list.php`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingState').style.display = 'none';
                    
                    if (data.success) {
                        rewards = data.data;
                        renderRewards();
                    } else {
                        document.getElementById('errorMessage').textContent = data.error || 'Unbekannter Fehler';
                        document.getElementById('errorState').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('errorMessage').textContent = 'Verbindungsfehler beim Laden der Belohnungsstufen.';
                    document.getElementById('errorState').style.display = 'block';
                });
        }
        
        // Filter by Freebie
        function filterByFreebie(selectedFreebieId) {
            if (selectedFreebieId) {
                window.location.href = `?page=belohnungsstufen&freebie_id=${selectedFreebieId}`;
            } else {
                window.location.href = `?page=belohnungsstufen`;
            }
        }
        
        // Belohnungen anzeigen
        function renderRewards() {
            const grid = document.getElementById('rewardsGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (rewards.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            grid.style.display = 'block';
            emptyState.style.display = 'none';
            
            grid.innerHTML = rewards.map((reward, index) => {
                const hasNoFreebie = !reward.freebie_id;
                
                return `
                <div class="reward-card animate-fade-in" style="opacity: 0; animation-delay: ${index * 0.1}s;">
                    <div class="reward-tier-badge" style="background: ${reward.reward_color || '#667eea'};">
                        ${reward.tier_level}
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem; flex-wrap: wrap;">
                            <div style="flex: 1;">
                                <h3 style="color: white; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">
                                    ${escapeHtml(reward.tier_name)}
                                    ${hasNoFreebie ? '<span class="no-freebie-tag">⚠️ Allgemein</span>' : ''}
                                    ${reward.is_imported ? '<span class="no-freebie-tag" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">📥 Importiert</span>' : ''}
                                </h3>
                                ${reward.tier_description ? `
                                    <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                        ${escapeHtml(reward.tier_description)}
                                    </p>
                                ` : ''}
                            </div>
                            <span class="status-badge ${reward.is_active ? 'status-active' : 'status-inactive'}">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                ${reward.is_active ? 'Aktiv' : 'Inaktiv'}
                            </span>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="color: ${reward.reward_color || '#667eea'}; font-size: 1.5rem;">
                                <i class="fas ${reward.reward_icon || 'fa-gift'}"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="color: white; font-weight: 600; margin-bottom: 0.25rem;">
                                    ${escapeHtml(reward.reward_title)}
                                </div>
                                ${reward.reward_value ? `
                                    <div style="color: #10b981; font-size: 0.875rem; font-weight: 500;">
                                        <i class="fas fa-tag"></i> ${escapeHtml(reward.reward_value)}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        ${reward.reward_description ? `
                            <p style="color: #9ca3af; font-size: 0.8125rem; line-height: 1.5;">
                                ${escapeHtml(reward.reward_description)}
                            </p>
                        ` : ''}
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; padding: 0.75rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.5rem; margin-bottom: 1rem;">
                        <div style="flex: 1; text-align: center;">
                            <div style="color: #667eea; font-size: 1.5rem; font-weight: 700;">
                                ${reward.required_referrals}
                            </div>
                            <div style="color: #9ca3af; font-size: 0.75rem;">
                                Empfehlungen
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center; border-left: 1px solid rgba(255,255,255,0.1);">
                            <div style="color: #10b981; font-size: 1.5rem; font-weight: 700;">
                                ${reward.leads_achieved || 0}
                            </div>
                            <div style="color: #9ca3af; font-size: 0.75rem;">
                                Erreicht
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center; border-left: 1px solid rgba(255,255,255,0.1);">
                            <div style="color: #f59e0b; font-size: 1.5rem; font-weight: 700;">
                                ${reward.times_claimed || 0}
                            </div>
                            <div style="color: #9ca3af; font-size: 0.75rem;">
                                Eingelöst
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="editReward(${reward.id})" class="btn btn-secondary" style="flex: 1; font-size: 0.875rem; padding: 0.625rem;">
                            <i class="fas fa-edit"></i> Bearbeiten
                        </button>
                        <button onclick="deleteReward(${reward.id}, '${escapeHtml(reward.tier_name)}')" class="btn btn-danger" style="flex: 1; font-size: 0.875rem; padding: 0.625rem;">
                            <i class="fas fa-trash"></i> Löschen
                        </button>
                    </div>
                </div>
            `}).join('');
        }
        
        // ===========================================
        // MARKTPLATZ FUNKTIONEN - MIT BILDERN!
        // ===========================================
        
        function openMarketplace() {
            document.getElementById('marketplaceModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            loadMarketplace();
        }
        
        function closeMarketplace() {
            document.getElementById('marketplaceModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function loadMarketplace() {
            document.getElementById('marketplaceLoading').style.display = 'block';
            document.getElementById('marketplaceGrid').style.display = 'none';
            document.getElementById('marketplaceEmpty').style.display = 'none';
            
            fetch('/api/vendor/marketplace/list.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('marketplaceLoading').style.display = 'none';
                    
                    if (data.success) {
                        allMarketplaceTemplates = data.templates;
                        marketplaceTemplates = data.templates;
                        renderMarketplace();
                    } else {
                        document.getElementById('marketplaceEmpty').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('marketplaceLoading').style.display = 'none';
                    document.getElementById('marketplaceEmpty').style.display = 'block';
                });
        }
        
        function filterMarketplace() {
            const search = document.getElementById('marketplaceSearch').value.toLowerCase();
            const category = document.getElementById('marketplaceCategory').value;
            const niche = document.getElementById('marketplaceNiche').value;
            
            marketplaceTemplates = allMarketplaceTemplates.filter(template => {
                const matchesSearch = !search || 
                    template.template_name.toLowerCase().includes(search) ||
                    template.template_description?.toLowerCase().includes(search);
                const matchesCategory = !category || template.category === category;
                const matchesNiche = !niche || template.niche === niche;
                
                return matchesSearch && matchesCategory && matchesNiche;
            });
            
            renderMarketplace();
        }
        
        function renderMarketplace() {
            const grid = document.getElementById('marketplaceGrid');
            const empty = document.getElementById('marketplaceEmpty');
            
            if (marketplaceTemplates.length === 0) {
                grid.style.display = 'none';
                empty.style.display = 'block';
                return;
            }
            
            grid.style.display = 'grid';
            empty.style.display = 'none';
            
            grid.innerHTML = marketplaceTemplates.map(template => {
                const isImported = template.is_imported_by_me;
                const hasImage = template.preview_image && template.preview_image.trim() !== '';
                
                return `
                <div class="template-card ${isImported ? 'imported' : ''}">
                    ${isImported ? `
                        <div style="background: rgba(16, 185, 129, 0.9); color: white; padding: 0.5rem; text-align: center; font-size: 0.875rem; font-weight: 600;">
                            ✓ Bereits importiert
                        </div>
                    ` : ''}
                    
                    <!-- BILD OBEN -->
                    <div class="template-card-image">
                        ${hasImage ? `
                            <img src="${escapeHtml(template.preview_image)}" 
                                 alt="${escapeHtml(template.template_name)}"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\\'color: white; font-size: 3rem;\\'><i class=\\'fas ${template.reward_icon || 'fa-gift'}\\'></i></div>';">
                        ` : `
                            <div style="color: white; font-size: 3rem;">
                                <i class="fas ${template.reward_icon || 'fa-gift'}"></i>
                            </div>
                        `}
                    </div>
                    
                    <!-- INHALT -->
                    <div class="template-card-content">
                        <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                            <div style="color: ${template.reward_color || '#667eea'}; font-size: 1.5rem; flex-shrink: 0;">
                                <i class="fas ${template.reward_icon || 'fa-gift'}"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="color: white; font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis;">
                                    ${escapeHtml(template.template_name)}
                                </h3>
                                <div style="color: #9ca3af; font-size: 0.75rem;">
                                    von ${escapeHtml(template.vendor_name || 'Unbekannt')}
                                </div>
                            </div>
                        </div>
                        
                        ${template.template_description ? `
                            <p style="color: #9ca3af; font-size: 0.875rem; line-height: 1.5; margin-bottom: 1rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                ${escapeHtml(template.template_description)}
                            </p>
                        ` : ''}
                        
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                            ${template.category ? `
                                <span style="background: rgba(102, 126, 234, 0.2); color: #667eea; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                                    ${getCategoryLabel(template.category)}
                                </span>
                            ` : ''}
                            ${template.niche ? `
                                <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                                    ${getNicheLabel(template.niche)}
                                </span>
                            ` : ''}
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; padding: 0.75rem; background: rgba(0, 0, 0, 0.3); border-radius: 0.5rem; margin-bottom: 1rem;">
                            <div style="text-align: center;">
                                <div style="color: white; font-size: 1.25rem; font-weight: 700;">
                                    ${template.times_imported || 0}
                                </div>
                                <div style="color: #9ca3af; font-size: 0.75rem;">
                                    Imports
                                </div>
                            </div>
                            <div style="text-align: center; border-left: 1px solid rgba(255,255,255,0.1);">
                                <div style="color: white; font-size: 1.25rem; font-weight: 700;">
                                    ${template.suggested_referrals_required || 3}
                                </div>
                                <div style="color: #9ca3af; font-size: 0.75rem;">
                                    Empfehlungen
                                </div>
                            </div>
                        </div>
                        
                        <button 
                            onclick="importTemplate(${template.id})" 
                            class="btn btn-import"
                            ${isImported ? 'disabled' : ''}
                        >
                            ${isImported ? '<i class="fas fa-check"></i> Importiert' : '<i class="fas fa-download"></i> Importieren'}
                        </button>
                    </div>
                </div>
            `}).join('');
        }
        
        function importTemplate(templateId) {
            if (!confirm('Dieses Template in deine Belohnungsstufen importieren?')) {
                return;
            }
            
            fetch('/api/vendor/marketplace/import.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    template_id: templateId,
                    freebie_id: freebieId || null
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('✓ Template erfolgreich importiert!', 'success');
                    closeMarketplace();
                    loadRewards();
                } else {
                    showNotification('Fehler: ' + (result.error || 'Unbekannter Fehler'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
            });
        }
        
        function getCategoryLabel(category) {
            const labels = {
                'ebook': 'E-Book',
                'pdf': 'PDF',
                'consultation': 'Beratung',
                'course': 'Kurs',
                'voucher': 'Gutschein',
                'discount': 'Rabatt',
                'other': 'Sonstiges'
            };
            return labels[category] || category;
        }
        
        function getNicheLabel(niche) {
            const labels = {
                'online-business': 'Online Business',
                'fitness': 'Fitness',
                'health': 'Gesundheit',
                'marketing': 'Marketing',
                'coaching': 'Coaching',
                'other': 'Sonstiges'
            };
            return labels[niche] || niche;
        }
        
        // ===========================================
        // BESTEHENDE FUNKTIONEN
        // ===========================================
        
        function openRewardModal() {
            document.getElementById('modalTitle').textContent = 'Neue Belohnungsstufe';
            document.getElementById('rewardForm').reset();
            document.getElementById('rewardId').value = '';
            document.getElementById('rewardFreebieId').value = freebieId || '';
            document.querySelector('[name="reward_icon"]').value = 'fa-gift';
            document.querySelector('[name="reward_color"]').value = '#667eea';
            document.querySelector('[name="is_active"]').checked = true;
            document.getElementById('rewardModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeRewardModal() {
            document.getElementById('rewardModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function editReward(id) {
            const reward = rewards.find(r => r.id == id);
            if (!reward) return;
            
            document.getElementById('modalTitle').textContent = 'Belohnungsstufe bearbeiten';
            document.getElementById('rewardId').value = reward.id;
            
            const form = document.getElementById('rewardForm');
            Object.keys(reward).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = reward[key] == 1;
                    } else {
                        field.value = reward[key] || '';
                    }
                }
            });
            
            const freebieSelect = form.querySelector('[name="freebie_id_select"]');
            if (freebieSelect) {
                freebieSelect.value = reward.freebie_id || '';
            }
            
            document.getElementById('rewardModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function saveReward(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {};
            
            formData.forEach((value, key) => {
                if (key === 'is_active' || key === 'is_featured' || key === 'auto_deliver') {
                    data[key] = formData.has(key);
                } else if (key === 'tier_level' || key === 'required_referrals') {
                    data[key] = parseInt(value);
                } else if (key === 'freebie_id_select') {
                    if (value) data.freebie_id = parseInt(value);
                } else if (key !== 'freebie_id') {
                    data[key] = value;
                }
            });
            
            const id = document.getElementById('rewardId').value;
            if (id) data.id = parseInt(id);
            
            const hiddenFreebieId = document.getElementById('rewardFreebieId').value;
            if (hiddenFreebieId && !data.freebie_id) {
                data.freebie_id = parseInt(hiddenFreebieId);
            }
            
            fetch('/api/rewards/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    closeRewardModal();
                    loadRewards();
                } else {
                    showNotification('Fehler: ' + result.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
            });
        }
        
        function deleteReward(id, name) {
            if (!confirm(`Belohnungsstufe "${name}" wirklich löschen?`)) {
                return;
            }
            
            fetch('/api/rewards/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification(result.message, 'success');
                    loadRewards();
                } else {
                    showNotification('Fehler: ' + result.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
            });
        }
        
        function showNotification(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                z-index: 99999;
                animation: slideIn 0.3s ease-out;
                max-width: 90%;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

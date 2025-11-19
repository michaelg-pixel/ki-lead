<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * ÜBERARBEITET: Unterstützt alte Belohnungen ohne freebie_id UND neue mit freebie_id
 * - Zeigt alle Belohnungen an
 * - Erlaubt Zugriff ohne Freebie-Parameter (zeigt dann alle)
 * - Option: Freebie nachträglich zuordnen
 * + PHASE 5: Marktplatz Integration
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .template-card {
            background: linear-gradient(to bottom right, #111827, #1f2937);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
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
                            Marktplatz
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

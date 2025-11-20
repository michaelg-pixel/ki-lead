<?php
/**
 * Admin Section: Angebote-Verwaltung
 * Erstellen und Bearbeiten von Angebots-Laufschriften für das Customer Dashboard
 */

if (!defined('INCLUDED')) {
    die('Direct access not allowed');
}

// Erfolgs- und Fehlermeldungen
$success_message = '';
$error_message = '';

// POST-Handler für Erstellen/Bearbeiten/Löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $button_text = trim($_POST['button_text'] ?? 'Jetzt ansehen');
                $button_link = trim($_POST['button_link'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (!empty($title) && !empty($description) && !empty($button_link)) {
                    $stmt = $pdo->prepare("INSERT INTO offers (title, description, button_text, button_link, is_active) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$title, $description, $button_text, $button_link, $is_active])) {
                        $success_message = 'Angebot erfolgreich erstellt!';
                    } else {
                        $error_message = 'Fehler beim Erstellen des Angebots.';
                    }
                } else {
                    $error_message = 'Bitte alle Pflichtfelder ausfüllen.';
                }
                break;
            
            case 'update':
                $id = intval($_POST['offer_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $button_text = trim($_POST['button_text'] ?? 'Jetzt ansehen');
                $button_link = trim($_POST['button_link'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if ($id > 0 && !empty($title) && !empty($description) && !empty($button_link)) {
                    $stmt = $pdo->prepare("UPDATE offers SET title = ?, description = ?, button_text = ?, button_link = ?, is_active = ? WHERE id = ?");
                    if ($stmt->execute([$title, $description, $button_text, $button_link, $is_active, $id])) {
                        $success_message = 'Angebot erfolgreich aktualisiert!';
                    } else {
                        $error_message = 'Fehler beim Aktualisieren des Angebots.';
                    }
                } else {
                    $error_message = 'Ungültige Daten.';
                }
                break;
            
            case 'delete':
                $id = intval($_POST['offer_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM offers WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success_message = 'Angebot erfolgreich gelöscht!';
                    } else {
                        $error_message = 'Fehler beim Löschen des Angebots.';
                    }
                }
                break;
            
            case 'toggle':
                $id = intval($_POST['offer_id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE offers SET is_active = NOT is_active WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success_message = 'Status erfolgreich geändert!';
                    } else {
                        $error_message = 'Fehler beim Ändern des Status.';
                    }
                }
                break;
        }
    }
}

// Alle Angebote abrufen
$stmt = $pdo->query("SELECT * FROM offers ORDER BY created_at DESC");
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bearbeitungsmodus
$edit_mode = false;
$edit_offer = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_offer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_offer) {
        $edit_mode = true;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Erfolgs-/Fehlermeldungen -->
        <?php if ($success_message): ?>
        <div class="mb-6 bg-green-500/20 border border-green-500 text-green-300 px-6 py-4 rounded-xl flex items-center gap-3">
            <i class="fas fa-check-circle text-2xl"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="mb-6 bg-red-500/20 border border-red-500 text-red-300 px-6 py-4 rounded-xl flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Formular zum Erstellen/Bearbeiten -->
        <div class="mb-8 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-purple-500/20">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?> text-purple-400 mr-2"></i>
                <?php echo $edit_mode ? 'Angebot bearbeiten' : 'Neues Angebot erstellen'; ?>
            </h2>
            
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'create'; ?>">
                <?php if ($edit_mode): ?>
                <input type="hidden" name="offer_id" value="<?php echo $edit_offer['id']; ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Titel <span class="text-red-400">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           value="<?php echo $edit_mode ? htmlspecialchars($edit_offer['title']) : ''; ?>"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors"
                           placeholder="z.B. Neu: KI Avatar Business Masterclass"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Beschreibung (Lauftext) <span class="text-red-400">*</span>
                    </label>
                    <textarea name="description" 
                              rows="3"
                              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors"
                              placeholder="z.B. Lerne, wie du mit KI-Avataren automatisierte Geschäfte aufbaust. Jetzt 50% Rabatt für Mitglieder!"
                              required><?php echo $edit_mode ? htmlspecialchars($edit_offer['description']) : ''; ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Dieser Text wird als Laufschrift im Customer Dashboard angezeigt</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Button-Text <span class="text-red-400">*</span>
                        </label>
                        <input type="text" 
                               name="button_text" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_offer['button_text']) : 'Jetzt ansehen'; ?>"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors"
                               placeholder="Jetzt ansehen"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Button-Link (URL) <span class="text-red-400">*</span>
                        </label>
                        <input type="url" 
                               name="button_link" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_offer['button_link']) : ''; ?>"
                               class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors"
                               placeholder="https://..."
                               required>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active"
                           <?php echo ($edit_mode && $edit_offer['is_active']) || !$edit_mode ? 'checked' : ''; ?>
                           class="w-5 h-5 text-purple-600 bg-gray-800 border-gray-600 rounded focus:ring-purple-500">
                    <label for="is_active" class="ml-3 text-sm text-gray-300">
                        Angebot aktiv anzeigen
                    </label>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" 
                            class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                        <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> mr-2"></i>
                        <?php echo $edit_mode ? 'Speichern' : 'Angebot erstellen'; ?>
                    </button>
                    
                    <?php if ($edit_mode): ?>
                    <a href="?page=offers" 
                       class="bg-gray-700 text-white px-6 py-3 rounded-xl font-bold hover:bg-gray-600 transition-all">
                        <i class="fas fa-times mr-2"></i>
                        Abbrechen
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Liste der Angebote -->
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-blue-500/20">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-list text-blue-400 mr-2"></i>
                Alle Angebote (<?php echo count($offers); ?>)
            </h2>
            
            <?php if (empty($offers)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">Noch keine Angebote vorhanden</p>
                <p class="text-gray-500 text-sm">Erstelle dein erstes Angebot oben</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($offers as $offer): ?>
                <div class="bg-gray-800/50 rounded-xl p-6 border <?php echo $offer['is_active'] ? 'border-green-500/30' : 'border-gray-700'; ?> hover:border-purple-500/50 transition-all">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white">
                                    <?php echo htmlspecialchars($offer['title']); ?>
                                </h3>
                                <?php if ($offer['is_active']): ?>
                                <span class="bg-green-500/20 text-green-300 text-xs px-3 py-1 rounded-full font-semibold">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    Aktiv
                                </span>
                                <?php else: ?>
                                <span class="bg-gray-600/50 text-gray-400 text-xs px-3 py-1 rounded-full">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    Inaktiv
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-gray-300 mb-3">
                                <?php echo htmlspecialchars($offer['description']); ?>
                            </p>
                            
                            <div class="flex items-center gap-4 text-sm text-gray-400">
                                <span>
                                    <i class="fas fa-link mr-1"></i>
                                    <a href="<?php echo htmlspecialchars($offer['button_link']); ?>" 
                                       target="_blank" 
                                       class="text-blue-400 hover:text-blue-300">
                                        <?php echo htmlspecialchars($offer['button_text']); ?>
                                    </a>
                                </span>
                                <span>
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($offer['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                                        title="Status ändern">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>
                            
                            <a href="?page=offers&edit=<?php echo $offer['id']; ?>" 
                               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
                               title="Bearbeiten">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <form method="POST" 
                                  class="inline"
                                  onsubmit="return confirm('Angebot wirklich löschen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                <button type="submit" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
                                        title="Löschen">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Vorschau -->
        <?php if (!empty($offers)): ?>
        <?php $preview_offer = array_values(array_filter($offers, fn($o) => $o['is_active']))[0] ?? $offers[0]; ?>
        <div class="mt-8 bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl p-6 shadow-xl border border-yellow-500/20">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-eye text-yellow-400 mr-2"></i>
                Vorschau im Customer Dashboard
            </h2>
            <p class="text-gray-400 mb-6">So wird die Laufschrift für die Kunden angezeigt:</p>
            
            <!-- Live-Vorschau -->
            <div class="bg-gray-900/50 rounded-xl p-4 border border-gray-700">
                <div class="flex items-center gap-4">
                    <a href="<?php echo htmlspecialchars($preview_offer['button_link']); ?>" 
                       target="_blank"
                       class="flex-shrink-0 bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                        <?php echo htmlspecialchars($preview_offer['button_text']); ?>
                    </a>
                    
                    <div class="flex-1 overflow-hidden">
                        <div class="marquee-container">
                            <div class="marquee-content">
                                <span class="text-white font-semibold text-lg">
                                    <?php echo htmlspecialchars($preview_offer['title']); ?> • 
                                    <?php echo htmlspecialchars($preview_offer['description']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
        .marquee-container {
            position: relative;
            overflow: hidden;
            height: 40px;
            display: flex;
            align-items: center;
        }
        
        .marquee-container::before,
        .marquee-container::after {
            content: '';
            position: absolute;
            top: 0;
            width: 50px;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }
        
        .marquee-container::before {
            left: 0;
            background: linear-gradient(to right, rgba(17, 24, 39, 1), transparent);
        }
        
        .marquee-container::after {
            right: 0;
            background: linear-gradient(to left, rgba(17, 24, 39, 1), transparent);
        }
        
        .marquee-content {
            display: flex;
            white-space: nowrap;
            animation: marquee 20s linear infinite;
        }
        
        @keyframes marquee {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
    </style>
</body>
</html>

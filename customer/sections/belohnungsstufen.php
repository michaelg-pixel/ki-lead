<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * Sektion für customer/dashboard.php
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
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
        
        .reward-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            position: relative;
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
        
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
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
                    <button onclick="openRewardModal()" class="btn btn-primary" id="createBtn">
                        <i class="fas fa-plus"></i>
                        Neue Belohnungsstufe
                    </button>
                </div>
            </div>
        </div>
        
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
            <div class="error-box animate-fade-in">
                <div style="font-size: 4rem; color: #ef4444; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 style="color: white; font-size: 1.5rem; margin-bottom: 1rem;">
                    Setup erforderlich
                </h3>
                <p id="errorMessage" style="color: #9ca3af; margin-bottom: 2rem; font-size: 1rem;">
                </p>
                <a href="/setup-reward-definitions.php" target="_blank" class="btn btn-primary">
                    <i class="fas fa-wrench"></i>
                    Setup jetzt ausführen
                </a>
            </div>
        </div>
        
        <!-- Rewards Grid -->
        <div id="rewardsGrid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
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
                Erstelle deine erste Belohnungsstufe für dein Empfehlungsprogramm
            </p>
            <button onclick="openRewardModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Jetzt erstellen
            </button>
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
                                <option value="freebie">Freebie</option>
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
    
    <script>
        let rewards = [];
        
        // Seite laden
        document.addEventListener('DOMContentLoaded', function() {
            loadRewards();
        });
        
        // Belohnungen laden
        function loadRewards() {
            fetch('/api/rewards/list.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingState').style.display = 'none';
                    
                    if (data.success) {
                        rewards = data.data;
                        renderRewards();
                    } else {
                        // Fehler anzeigen
                        document.getElementById('errorMessage').textContent = data.error;
                        document.getElementById('errorState').style.display = 'block';
                        document.getElementById('createBtn').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('errorMessage').textContent = 'Verbindungsfehler beim Laden der Belohnungsstufen.';
                    document.getElementById('errorState').style.display = 'block';
                    document.getElementById('createBtn').style.display = 'none';
                });
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
            
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
            
            grid.innerHTML = rewards.map((reward, index) => `
                <div class="reward-card animate-fade-in" style="opacity: 0; animation-delay: ${index * 0.1}s;">
                    <div class="reward-tier-badge" style="background: ${reward.reward_color || '#667eea'};">
                        ${reward.tier_level}
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                            <div>
                                <h3 style="color: white; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem;">
                                    ${escapeHtml(reward.tier_name)}
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
            `).join('');
        }
        
        // Modal öffnen (neu)
        function openRewardModal() {
            document.getElementById('modalTitle').textContent = 'Neue Belohnungsstufe';
            document.getElementById('rewardForm').reset();
            document.getElementById('rewardId').value = '';
            document.querySelector('[name="reward_icon"]').value = 'fa-gift';
            document.querySelector('[name="reward_color"]').value = '#667eea';
            document.querySelector('[name="is_active"]').checked = true;
            document.getElementById('rewardModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Modal schließen
        function closeRewardModal() {
            document.getElementById('rewardModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Belohnung bearbeiten
        function editReward(id) {
            const reward = rewards.find(r => r.id == id);
            if (!reward) return;
            
            document.getElementById('modalTitle').textContent = 'Belohnungsstufe bearbeiten';
            document.getElementById('rewardId').value = reward.id;
            
            // Alle Felder füllen
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
            
            document.getElementById('rewardModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // Belohnung speichern
        function saveReward(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {};
            
            formData.forEach((value, key) => {
                if (key === 'is_active' || key === 'is_featured' || key === 'auto_deliver') {
                    data[key] = formData.has(key);
                } else if (key === 'tier_level' || key === 'required_referrals') {
                    data[key] = parseInt(value);
                } else {
                    data[key] = value;
                }
            });
            
            // ID hinzufügen wenn vorhanden
            const id = document.getElementById('rewardId').value;
            if (id) data.id = parseInt(id);
            
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
        
        // Belohnung löschen
        function deleteReward(id, name) {
            if (!confirm(`Belohnungsstufe "${name}" wirklich löschen?\n\nHinweis: Wenn sie bereits vergeben wurde, wird sie nur deaktiviert.`)) {
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
                    showNotification(result.message, result.deactivated ? 'info' : 'success');
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
        
        // Notification anzeigen
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
        
        // HTML Escape
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Animation Styles
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

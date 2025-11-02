<?php
// Admin-Check wurde bereits im dashboard.php durchgeführt
$admin_id = $_SESSION['user_id'];

// Admin-Daten aus Datenbank holen
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$admin_name = $admin['name'] ?? 'Admin';
$admin_email = $admin['email'] ?? '';
$profile_image = $admin['profile_image'] ?? null;
$created_at = $admin['created_at'] ?? '';

// Berechne wie lange Admin ist
$admin_since_days = 0;
if ($created_at) {
    $created_date = new DateTime($created_at);
    $now = new DateTime();
    $admin_since_days = $now->diff($created_date)->days;
}
?>

<div class="profile-container">
    <!-- Benachrichtigungen -->
    <div id="notification" class="notification" style="display: none;">
        <span id="notification-message"></span>
        <button onclick="closeNotification()" class="notification-close">✕</button>
    </div>
    
    <!-- 1. PROFIL BEARBEITEN -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">👤 Profil bearbeiten</h3>
            <p class="section-description">Verwalten Sie Ihre persönlichen Informationen</p>
        </div>
        
        <div class="profile-content">
            <!-- Profilbild Upload -->
            <div class="profile-image-section">
                <div class="current-profile-image">
                    <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                        <img src="/<?php echo htmlspecialchars($profile_image); ?>?v=<?php echo time(); ?>" alt="Profilbild" id="profileImagePreview">
                    <?php else: ?>
                        <div class="profile-avatar-large" id="profileImagePreview">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-image-actions">
                    <input type="file" id="profileImageInput" accept="image/*" style="display: none;">
                    <button class="btn btn-secondary" onclick="document.getElementById('profileImageInput').click()">
                        📷 Bild ändern
                    </button>
                    <p class="help-text">JPG, PNG, GIF oder WEBP. Max. 5MB</p>
                </div>
            </div>
            
            <!-- Profil-Formular -->
            <form id="profileForm" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="profile_name">Name</label>
                        <input 
                            type="text" 
                            id="profile_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($admin_name); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_email">E-Mail</label>
                        <input 
                            type="email" 
                            id="profile_email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($admin_email); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span id="profileBtnText">Profil speichern</span>
                        <span id="profileBtnLoader" class="btn-loader" style="display: none;">
                            <span class="spinner-small"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Admin-Statistiken -->
        <div class="stats-mini-grid">
            <div class="stat-mini-card">
                <div class="stat-mini-icon">👑</div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value"><?php echo $admin_since_days; ?> Tage</div>
                    <div class="stat-mini-label">Als Admin aktiv</div>
                </div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-icon">📝</div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value" id="totalActivities">-</div>
                    <div class="stat-mini-label">Aktionen durchgeführt</div>
                </div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-icon">🕐</div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value" id="lastActivity">-</div>
                    <div class="stat-mini-label">Letzte Aktivität</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 2. SICHERHEIT -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">🔐 Sicherheit & Sessions</h3>
            <p class="section-description">Überwachen Sie Ihre Login-Aktivitäten</p>
        </div>
        
        <!-- Aktive Sessions -->
        <div class="subsection">
            <div class="subsection-header">
                <h4>Aktive Sessions</h4>
                <button class="btn btn-danger-outline btn-small" onclick="terminateAllSessions()">
                    🚪 Alle anderen abmelden
                </button>
            </div>
            
            <div id="activeSessions" class="sessions-list">
                <div class="loading-spinner">
                    <span class="spinner"></span>
                    <p>Lade Sessions...</p>
                </div>
            </div>
        </div>
        
        <!-- Letzte Logins -->
        <div class="subsection">
            <h4 class="subsection-header">Letzte Login-Aktivitäten</h4>
            <div id="lastLogins" class="activity-list">
                <div class="loading-spinner">
                    <span class="spinner"></span>
                    <p>Lade Aktivitäten...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 3. AKTIVITÄTSPROTOKOLL -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">📊 Aktivitätsprotokoll</h3>
            <p class="section-description">Ihre letzten 20 Admin-Aktionen im System</p>
        </div>
        
        <div id="activityLog" class="activity-list">
            <div class="loading-spinner">
                <span class="spinner"></span>
                <p>Lade Aktivitäten...</p>
            </div>
        </div>
    </div>
    
    <!-- 4. PRÄFERENZEN -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">⚙️ Präferenzen & Einstellungen</h3>
            <p class="section-description">Passen Sie Ihr Admin-Erlebnis an</p>
        </div>
        
        <form id="preferencesForm" class="preferences-form">
            <!-- Benachrichtigungen -->
            <div class="preferences-group">
                <h4 class="preferences-group-title">🔔 E-Mail-Benachrichtigungen</h4>
                
                <label class="toggle-switch">
                    <input type="checkbox" id="notifications_new_users" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Bei neuen Benutzer-Registrierungen</span>
                </label>
                
                <label class="toggle-switch">
                    <input type="checkbox" id="notifications_course_purchases" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Bei Kurskäufen über Digistore24</span>
                </label>
                
                <label class="toggle-switch">
                    <input type="checkbox" id="notifications_weekly_summary" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Wöchentliche Zusammenfassung</span>
                </label>
            </div>
            
            <!-- Interface-Einstellungen -->
            <div class="preferences-group">
                <h4 class="preferences-group-title">🎨 Interface</h4>
                
                <div class="form-group">
                    <label>Theme (Coming Soon)</label>
                    <select id="theme" class="form-input" disabled>
                        <option value="dark">Dark Mode</option>
                        <option value="light">Light Mode</option>
                    </select>
                    <p class="help-text">Dark/Light Mode wird in einem zukünftigen Update verfügbar sein</p>
                </div>
                
                <div class="form-group">
                    <label>Sprache</label>
                    <select id="language" class="form-input">
                        <option value="de">Deutsch</option>
                        <option value="en">English (Coming Soon)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span id="prefBtnText">Einstellungen speichern</span>
                    <span id="prefBtnLoader" class="btn-loader" style="display: none;">
                        <span class="spinner-small"></span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 16px 48px 16px 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
    min-width: 300px;
    max-width: 500px;
}

.notification.success {
    border-left: 4px solid var(--success);
}

.notification.error {
    border-left: 4px solid var(--danger);
}

.notification-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 18px;
    padding: 0;
    width: 24px;
    height: 24px;
}

@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Section Description */
.section-description {
    font-size: 14px;
    color: var(--text-secondary);
    margin-top: 8px;
}

/* Profile Content */
.profile-content {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 32px;
    margin-top: 24px;
    align-items: start;
}

.profile-image-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.current-profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--border);
    background: var(--bg-tertiary);
}

.current-profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar-large {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
}

.profile-image-actions {
    text-align: center;
}

.help-text {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 8px;
}

/* Profile Form */
.profile-form {
    width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.form-input, select.form-input {
    width: 100%;
    padding: 12px 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.form-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Stats Mini Grid */
.stats-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border-light);
}

.stat-mini-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
}

.stat-mini-icon {
    font-size: 32px;
}

.stat-mini-value {
    font-size: 20px;
    font-weight: 700;
    color: white;
}

.stat-mini-label {
    font-size: 12px;
    color: var(--text-secondary);
}

/* Subsection */
.subsection {
    margin-top: 24px;
}

.subsection-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 16px;
    font-weight: 600;
    color: white;
}

.subsection-header h4 {
    font-size: 16px;
    font-weight: 600;
    color: white;
    margin: 0;
}

/* Sessions List */
.sessions-list, .activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.session-item, .activity-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.session-info, .activity-info {
    flex: 1;
}

.session-title {
    font-size: 14px;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.session-details, .activity-details {
    font-size: 13px;
    color: var(--text-secondary);
}

.session-badge {
    display: inline-block;
    padding: 4px 8px;
    background: rgba(74, 222, 128, 0.2);
    color: var(--success);
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

/* Activity Item */
.activity-item {
    align-items: start;
}

.activity-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.activity-info {
    flex: 1;
}

.activity-title {
    font-size: 14px;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
    color: var(--text-muted);
}

/* Preferences */
.preferences-form {
    margin-top: 24px;
}

.preferences-group {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.preferences-group-title {
    font-size: 16px;
    font-weight: 600;
    color: white;
    margin-bottom: 16px;
}

/* Toggle Switch */
.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    cursor: pointer;
    position: relative;
}

.toggle-switch input[type="checkbox"] {
    position: absolute;
    opacity: 0;
}

.toggle-slider {
    width: 48px;
    height: 24px;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 24px;
    position: relative;
    transition: all 0.3s;
    flex-shrink: 0;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    background: var(--text-secondary);
    border-radius: 50%;
    top: 2px;
    left: 3px;
    transition: all 0.3s;
}

.toggle-switch input[type="checkbox"]:checked + .toggle-slider {
    background: var(--primary);
    border-color: var(--primary);
}

.toggle-switch input[type="checkbox"]:checked + .toggle-slider::before {
    background: white;
    transform: translateX(23px);
}

.toggle-label {
    font-size: 14px;
    color: var(--text-primary);
}

/* Buttons */
.btn-secondary {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--bg-card);
    border-color: var(--primary);
}

.btn-danger-outline {
    background: transparent;
    border: 1px solid var(--danger);
    color: var(--danger);
}

.btn-danger-outline:hover {
    background: var(--danger);
    color: white;
}

.btn-small {
    padding: 8px 16px;
    font-size: 13px;
}

.form-actions {
    margin-top: 24px;
}

.btn-loader {
    display: inline-flex;
    align-items: center;
}

.spinner-small {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* Mobile */
@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .profile-image-section {
        margin: 0 auto;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-mini-grid {
        grid-template-columns: 1fr;
    }
    
    .subsection-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .btn-small {
        width: 100%;
    }
    
    .notification {
        left: 20px;
        right: 20px;
        min-width: auto;
    }
}
</style>

<script>
// === 1. PROFIL BEARBEITEN ===

// Profilbild Upload
document.getElementById('profileImageInput').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('profile_image', file);
    
    try {
        const response = await fetch('/admin/api/upload-profile-image.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            // Bild aktualisieren
            const preview = document.getElementById('profileImagePreview');
            if (preview.tagName === 'IMG') {
                preview.src = data.data.profile_image_url + '?v=' + Date.now();
            } else {
                preview.outerHTML = '<img src="' + data.data.profile_image_url + '?v=' + Date.now() + '" alt="Profilbild" id="profileImagePreview">';
            }
            // Reload nach 1 Sekunde für Sidebar-Update
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Fehler beim Hochladen des Bildes', 'error');
    }
});

// Profil-Formular
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('profileBtnText');
    const loader = document.getElementById('profileBtnLoader');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    btn.style.display = 'none';
    loader.style.display = 'inline-flex';
    
    const name = document.getElementById('profile_name').value;
    const email = document.getElementById('profile_email').value;
    
    try {
        const response = await fetch('/admin/api/update-profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            // Reload nach 1 Sekunde für Sidebar-Update
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Ein Fehler ist aufgetreten', 'error');
    } finally {
        submitBtn.disabled = false;
        btn.style.display = 'inline';
        loader.style.display = 'none';
    }
});

// === 2. SICHERHEIT ===

// Aktive Sessions laden
async function loadActiveSessions() {
    try {
        const response = await fetch('/admin/api/session-management.php?action=get_sessions');
        const data = await response.json();
        
        const container = document.getElementById('activeSessions');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(session => `
                <div class="session-item">
                    <div class="session-info">
                        <div class="session-title">
                            ${session.device || 'Unbekanntes Gerät'} - ${session.browser || 'Unbekannter Browser'}
                            <span class="session-badge">AKTIV</span>
                        </div>
                        <div class="session-details">
                            IP: ${session.ip_address || 'N/A'} • 
                            Standort: ${session.location || 'Unbekannt'} • 
                            Letzte Aktivität: ${formatDate(session.last_activity)}
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔒</div><p>Keine aktiven Sessions</p></div>';
        }
    } catch (error) {
        document.getElementById('activeSessions').innerHTML = '<div class="empty-state"><p>Fehler beim Laden</p></div>';
    }
}

// Letzte Logins laden
async function loadLastLogins() {
    try {
        const response = await fetch('/admin/api/session-management.php?action=get_last_logins');
        const data = await response.json();
        
        const container = document.getElementById('lastLogins');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(login => `
                <div class="activity-item">
                    <div class="activity-icon">🔐</div>
                    <div class="activity-info">
                        <div class="activity-title">Login von ${login.device || 'Unbekanntes Gerät'}</div>
                        <div class="activity-details">
                            ${login.ip_address || 'N/A'} • ${login.location || 'Unbekannt'}
                        </div>
                        <div class="activity-time">${formatDate(login.created_at)}</div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📝</div><p>Keine Login-Aktivitäten</p></div>';
        }
    } catch (error) {
        document.getElementById('lastLogins').innerHTML = '<div class="empty-state"><p>Fehler beim Laden</p></div>';
    }
}

// Alle Sessions beenden
async function terminateAllSessions() {
    if (!confirm('Möchten Sie wirklich alle anderen Sessions beenden?')) return;
    
    try {
        const response = await fetch('/admin/api/session-management.php?action=terminate_all_sessions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            loadActiveSessions();
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Ein Fehler ist aufgetreten', 'error');
    }
}

// === 3. AKTIVITÄTSPROTOKOLL ===

async function loadActivityLog() {
    try {
        const response = await fetch('/admin/api/activity-log.php?limit=20');
        const data = await response.json();
        
        const container = document.getElementById('activityLog');
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-info">
                        <div class="activity-title">${activity.label}</div>
                        <div class="activity-details">${activity.action_description}</div>
                        <div class="activity-time">
                            ${formatDate(activity.created_at)} • IP: ${activity.ip_address || 'N/A'}
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Update Stats
            document.getElementById('totalActivities').textContent = data.data.length;
            if (data.data[0]) {
                document.getElementById('lastActivity').textContent = formatRelativeTime(data.data[0].created_at);
            }
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><p>Noch keine Aktivitäten aufgezeichnet</p></div>';
        }
    } catch (error) {
        document.getElementById('activityLog').innerHTML = '<div class="empty-state"><p>Fehler beim Laden</p></div>';
    }
}

// === 4. PRÄFERENZEN ===

// Präferenzen laden
async function loadPreferences() {
    try {
        const response = await fetch('/admin/api/preferences.php?action=get');
        const data = await response.json();
        
        if (data.success && data.data) {
            const prefs = data.data;
            document.getElementById('notifications_new_users').checked = prefs.notifications_new_users;
            document.getElementById('notifications_course_purchases').checked = prefs.notifications_course_purchases;
            document.getElementById('notifications_weekly_summary').checked = prefs.notifications_weekly_summary;
            document.getElementById('theme').value = prefs.theme || 'dark';
            document.getElementById('language').value = prefs.language || 'de';
        }
    } catch (error) {
        console.error('Fehler beim Laden der Präferenzen:', error);
    }
}

// Präferenzen speichern
document.getElementById('preferencesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('prefBtnText');
    const loader = document.getElementById('prefBtnLoader');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    btn.style.display = 'none';
    loader.style.display = 'inline-flex';
    
    const preferences = {
        notifications_new_users: document.getElementById('notifications_new_users').checked,
        notifications_course_purchases: document.getElementById('notifications_course_purchases').checked,
        notifications_weekly_summary: document.getElementById('notifications_weekly_summary').checked,
        theme: document.getElementById('theme').value,
        language: document.getElementById('language').value
    };
    
    try {
        const response = await fetch('/admin/api/preferences.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(preferences)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Ein Fehler ist aufgetreten', 'error');
    } finally {
        submitBtn.disabled = false;
        btn.style.display = 'inline';
        loader.style.display = 'none';
    }
});

// === HELPER FUNCTIONS ===

function showNotification(message, type) {
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notification-message');
    
    notification.className = 'notification ' + type;
    notificationMessage.textContent = message;
    notification.style.display = 'block';
    
    setTimeout(() => closeNotification(), 5000);
}

function closeNotification() {
    document.getElementById('notification').style.display = 'none';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('de-DE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatRelativeTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffMins < 1) return 'Gerade eben';
    if (diffMins < 60) return `vor ${diffMins} Min.`;
    if (diffHours < 24) return `vor ${diffHours} Std.`;
    return `vor ${diffDays} Tag${diffDays > 1 ? 'en' : ''}`;
}

// === INIT ===
document.addEventListener('DOMContentLoaded', function() {
    loadActiveSessions();
    loadLastLogins();
    loadActivityLog();
    loadPreferences();
});
</script>

<?php
// Admin-Check wurde bereits im dashboard.php durchgeführt
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? '';
?>

<div class="settings-container">
    <!-- Benachrichtigungen -->
    <div id="notification" class="notification" style="display: none;">
        <span id="notification-message"></span>
        <button onclick="closeNotification()" class="notification-close">✕</button>
    </div>
    
    <!-- Account-Einstellungen -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">👤 Account-Einstellungen</h3>
        </div>
        
        <div class="settings-grid">
            <!-- Admin-Profil Info -->
            <div class="info-card">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($admin_name); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-label">E-Mail</div>
                <div class="info-value"><?php echo htmlspecialchars($admin_email); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-label">Rolle</div>
                <div class="info-value">
                    <span class="badge badge-admin">ADMINISTRATOR</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Passwort ändern -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">🔒 Passwort ändern</h3>
            <p class="section-description">Ändern Sie Ihr Admin-Passwort für erhöhte Sicherheit</p>
        </div>
        
        <form id="passwordForm" class="password-form">
            <div class="form-group">
                <label for="current_password">Aktuelles Passwort</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-input"
                        placeholder="Geben Sie Ihr aktuelles Passwort ein"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                        <span class="eye-icon">👁️</span>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">Neues Passwort</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-input"
                        placeholder="Mindestens 8 Zeichen"
                        required
                        minlength="8"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                        <span class="eye-icon">👁️</span>
                    </button>
                </div>
                <div class="password-strength" id="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Passwort bestätigen</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        placeholder="Neues Passwort wiederholen"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <span class="eye-icon">👁️</span>
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="btnText">Passwort ändern</span>
                    <span id="btnLoader" class="btn-loader" style="display: none;">
                        <span class="spinner-small"></span>
                    </span>
                </button>
            </div>
        </form>
    </div>
    
    <!-- System-Informationen -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">⚙️ System-Informationen</h3>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-icon">🖥️</span>
                <div class="info-content">
                    <div class="info-label">PHP Version</div>
                    <div class="info-value"><?php echo phpversion(); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <span class="info-icon">🗄️</span>
                <div class="info-content">
                    <div class="info-label">Datenbank</div>
                    <div class="info-value">MySQL</div>
                </div>
            </div>
            
            <div class="info-item">
                <span class="info-icon">🌐</span>
                <div class="info-content">
                    <div class="info-label">Server</div>
                    <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <span class="info-icon">📅</span>
                <div class="info-content">
                    <div class="info-label">Zeitzone</div>
                    <div class="info-value"><?php echo date_default_timezone_get(); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1000px;
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
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: var(--text-primary);
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Section Description */
.section-description {
    font-size: 14px;
    color: var(--text-secondary);
    margin-top: 8px;
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.info-card {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
}

.info-label {
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.info-value {
    font-size: 16px;
    color: var(--text-primary);
    font-weight: 500;
}

/* Password Form */
.password-form {
    max-width: 500px;
    margin-top: 20px;
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

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.form-input {
    width: 100%;
    padding: 12px 48px 12px 16px;
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

.form-input::placeholder {
    color: var(--text-muted);
}

.toggle-password {
    position: absolute;
    right: 12px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eye-icon {
    font-size: 18px;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.toggle-password:hover .eye-icon {
    opacity: 1;
}

/* Password Strength */
.password-strength {
    margin-top: 8px;
    height: 4px;
    background: var(--bg-tertiary);
    border-radius: 2px;
    overflow: hidden;
    display: none;
}

.password-strength.active {
    display: block;
}

.password-strength-bar {
    height: 100%;
    transition: all 0.3s;
    border-radius: 2px;
}

.password-strength.weak .password-strength-bar {
    width: 33%;
    background: var(--danger);
}

.password-strength.medium .password-strength-bar {
    width: 66%;
    background: var(--warning);
}

.password-strength.strong .password-strength-bar {
    width: 100%;
    background: var(--success);
}

/* Form Actions */
.form-actions {
    margin-top: 24px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
}

.info-icon {
    font-size: 24px;
}

.info-content {
    flex: 1;
}

/* Mobile Anpassungen */
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .password-form {
        max-width: 100%;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .notification {
        left: 20px;
        right: 20px;
        min-width: auto;
    }
}
</style>

<script>
// Passwort-Sichtbarkeit umschalten
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.type === 'password' ? 'text' : 'password';
    field.type = type;
}

// Passwort-Stärke prüfen
document.getElementById('new_password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.classList.remove('active', 'weak', 'medium', 'strong');
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strength = 0;
    
    // Länge
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Komplexität
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    strengthDiv.classList.add('active');
    strengthDiv.classList.remove('weak', 'medium', 'strong');
    
    if (strength <= 2) {
        strengthDiv.classList.add('weak');
        strengthDiv.innerHTML = '<div class="password-strength-bar"></div>';
    } else if (strength <= 4) {
        strengthDiv.classList.add('medium');
        strengthDiv.innerHTML = '<div class="password-strength-bar"></div>';
    } else {
        strengthDiv.classList.add('strong');
        strengthDiv.innerHTML = '<div class="password-strength-bar"></div>';
    }
});

// Formular absenden
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');
    
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Client-seitige Validierung
    if (newPassword !== confirmPassword) {
        showNotification('Die neuen Passwörter stimmen nicht überein', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showNotification('Das neue Passwort muss mindestens 8 Zeichen lang sein', 'error');
        return;
    }
    
    // Button deaktivieren
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    
    try {
        const response = await fetch('/admin/api/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            // Formular zurücksetzen
            document.getElementById('passwordForm').reset();
            document.getElementById('password-strength').classList.remove('active', 'weak', 'medium', 'strong');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
    } finally {
        // Button wieder aktivieren
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

// Benachrichtigung anzeigen
function showNotification(message, type) {
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notification-message');
    
    notification.className = 'notification ' + type;
    notificationMessage.textContent = message;
    notification.style.display = 'block';
    
    // Nach 5 Sekunden automatisch ausblenden
    setTimeout(() => {
        closeNotification();
    }, 5000);
}

// Benachrichtigung schließen
function closeNotification() {
    const notification = document.getElementById('notification');
    notification.style.display = 'none';
}
</script>

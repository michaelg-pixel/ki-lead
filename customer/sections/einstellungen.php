<?php
// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

// Auth-Funktionen werden benötigt
if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/../../includes/auth.php';
}

$message = '';
$error = '';

/**
 * Passwort-Stärke validieren
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Mindestens 8 Zeichen';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Mindestens 1 Großbuchstabe';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Mindestens 1 Kleinbuchstabe';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Mindestens 1 Zahl';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Mindestens 1 Sonderzeichen (!@#$%^&*)';
    }
    
    return $errors;
}

// Passwort ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validierung
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Bitte füllen Sie alle Felder aus.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Die neuen Passwörter stimmen nicht überein.';
    } else {
        // Passwort-Stärke prüfen
        $password_errors = validatePasswordStrength($new_password);
        if (!empty($password_errors)) {
            $error = 'Das Passwort erfüllt nicht alle Anforderungen: ' . implode(', ', $password_errors);
        } else {
            try {
                // Aktuelles Passwort prüfen
                $user = getCurrentUser();
                if (!$user) {
                    $error = 'Benutzer nicht gefunden.';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = 'Das aktuelle Passwort ist falsch.';
                } else {
                    // Passwort ändern
                    if (resetPassword($_SESSION['user_id'], $new_password)) {
                        $message = 'Passwort erfolgreich geändert!';
                    } else {
                        $error = 'Fehler beim Ändern des Passworts.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
            }
        }
    }
}
?>

<style>
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 32px;
    }
    
    .settings-header {
        margin-bottom: 32px;
    }
    
    .settings-title {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
    }
    
    .settings-subtitle {
        font-size: 14px;
        color: #888;
    }
    
    .settings-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #ccc;
        margin-bottom: 8px;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: white;
        font-size: 14px;
        transition: all 0.2s;
        -webkit-appearance: none;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #667eea;
        background: rgba(0, 0, 0, 0.5);
    }
    
    .form-help {
        font-size: 12px;
        color: #888;
        margin-top: 6px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        line-height: 1.5;
    }
    
    .alert-success {
        background: rgba(52, 211, 153, 0.1);
        border: 1px solid rgba(52, 211, 153, 0.3);
        color: #34d399;
    }
    
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
    
    .password-requirements {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
        padding: 16px;
        margin-top: 24px;
    }
    
    .password-requirements h4 {
        font-size: 14px;
        color: #667eea;
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    .password-requirements ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .password-requirements li {
        font-size: 13px;
        color: #aaa;
        padding: 6px 0;
        padding-left: 24px;
        position: relative;
        transition: color 0.2s;
    }
    
    .password-requirements li:before {
        content: "○";
        position: absolute;
        left: 0;
        color: #667eea;
        font-weight: bold;
    }
    
    .password-requirements li.valid {
        color: #34d399;
    }
    
    .password-requirements li.valid:before {
        content: "✓";
        color: #34d399;
    }
    
    .user-info-card {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    
    .info-item {
        background: rgba(0, 0, 0, 0.3);
        padding: 16px;
        border-radius: 8px;
    }
    
    .info-label {
        font-size: 12px;
        color: #888;
        margin-bottom: 6px;
    }
    
    .info-value {
        font-size: 14px;
        color: white;
        font-weight: 500;
        word-break: break-word;
    }
    
    /* Passwort-Stärke-Anzeige */
    .password-strength {
        margin-top: 12px;
    }
    
    .strength-label {
        font-size: 12px;
        color: #888;
        margin-bottom: 6px;
    }
    
    .strength-bar {
        height: 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    
    .strength-fill {
        height: 100%;
        width: 0%;
        transition: all 0.3s;
        border-radius: 3px;
    }
    
    .strength-text {
        font-size: 12px;
        font-weight: 600;
    }
    
    .strength-weak .strength-fill {
        width: 33%;
        background: #ef4444;
    }
    
    .strength-weak .strength-text {
        color: #ef4444;
    }
    
    .strength-medium .strength-fill {
        width: 66%;
        background: #f59e0b;
    }
    
    .strength-medium .strength-text {
        color: #f59e0b;
    }
    
    .strength-strong .strength-fill {
        width: 100%;
        background: #34d399;
    }
    
    .strength-strong .strength-text {
        color: #34d399;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .settings-container {
            padding: 24px 16px;
        }
        
        .settings-title {
            font-size: 24px;
        }
        
        .settings-card {
            padding: 24px 20px;
            border-radius: 12px;
        }
        
        .card-title {
            font-size: 18px;
        }
        
        .user-info-card {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }
    
    @media (max-width: 480px) {
        .settings-container {
            padding: 16px 12px;
        }
        
        .settings-header {
            margin-bottom: 24px;
        }
        
        .settings-title {
            font-size: 20px;
        }
        
        .settings-subtitle {
            font-size: 13px;
        }
        
        .settings-card {
            padding: 20px 16px;
            margin-bottom: 16px;
        }
        
        .card-title {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-input {
            padding: 10px 14px;
            font-size: 16px; /* Verhindert Zoom auf iOS */
        }
        
        .btn-primary {
            padding: 14px 20px;
            font-size: 15px;
        }
        
        .password-requirements {
            padding: 12px;
        }
        
        .password-requirements li {
            font-size: 12px;
            padding: 4px 0;
        }
        
        .alert {
            padding: 12px 16px;
            font-size: 13px;
        }
    }
    
    @media (hover: none) and (pointer: coarse) {
        .btn-primary:hover {
            transform: none;
            box-shadow: none;
        }
        
        .btn-primary:active {
            transform: scale(0.98);
        }
    }
</style>

<div class="settings-container">
    <div class="settings-header">
        <h1 class="settings-title">⚙️ Einstellungen</h1>
        <p class="settings-subtitle">Verwalten Sie Ihre Kontoeinstellungen</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <span>✅</span>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <span>❌</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Benutzer-Informationen -->
    <div class="settings-card">
        <h2 class="card-title">
            <span>👤</span>
            <span>Ihre Informationen</span>
        </h2>
        <div class="user-info-card">
            <div class="info-item">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Nicht angegeben'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">E-Mail</div>
                <div class="info-value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Nicht angegeben'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Rolle</div>
                <div class="info-value">
                    <?php 
                    $role = $_SESSION['role'] ?? 'customer';
                    echo $role === 'admin' ? 'Administrator' : 'Kunde';
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Passwort ändern -->
    <div class="settings-card">
        <h2 class="card-title">
            <span>🔒</span>
            <span>Passwort ändern</span>
        </h2>
        
        <form method="POST" id="passwordForm">
            <div class="form-group">
                <label class="form-label" for="current_password">Aktuelles Passwort</label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    class="form-input" 
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="new_password">Neues Passwort</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-input" 
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
                
                <!-- Passwort-Stärke-Anzeige -->
                <div class="password-strength" id="passwordStrength" style="display:none;">
                    <div class="strength-label">Passwort-Stärke:</div>
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <div class="strength-text"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password">Neues Passwort bestätigen</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-input" 
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>
            
            <button type="submit" name="change_password" class="btn-primary" id="submitBtn">
                Passwort ändern
            </button>
            
            <div class="password-requirements">
                <h4>Passwort-Anforderungen:</h4>
                <ul id="requirements">
                    <li id="req-length">Mindestens 8 Zeichen</li>
                    <li id="req-uppercase">Mindestens 1 Großbuchstabe (A-Z)</li>
                    <li id="req-lowercase">Mindestens 1 Kleinbuchstabe (a-z)</li>
                    <li id="req-number">Mindestens 1 Zahl (0-9)</li>
                    <li id="req-special">Mindestens 1 Sonderzeichen (!@#$%^&*)</li>
                </ul>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    // Anforderungen
    const requirements = {
        length: { regex: /.{8,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }
    };
    
    // Passwort-Stärke berechnen
    function checkPasswordStrength(password) {
        if (!password) {
            strengthIndicator.style.display = 'none';
            return 0;
        }
        
        strengthIndicator.style.display = 'block';
        let strength = 0;
        
        // Prüfe jede Anforderung
        for (let key in requirements) {
            const req = requirements[key];
            if (req.regex.test(password)) {
                req.element.classList.add('valid');
                strength++;
            } else {
                req.element.classList.remove('valid');
            }
        }
        
        // Stärke-Anzeige aktualisieren
        strengthIndicator.className = 'password-strength';
        const strengthText = strengthIndicator.querySelector('.strength-text');
        
        if (strength <= 2) {
            strengthIndicator.classList.add('strength-weak');
            strengthText.textContent = 'Schwach';
        } else if (strength <= 4) {
            strengthIndicator.classList.add('strength-medium');
            strengthText.textContent = 'Mittel';
        } else {
            strengthIndicator.classList.add('strength-strong');
            strengthText.textContent = 'Stark';
        }
        
        return strength;
    }
    
    // Live-Validierung
    newPassword.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        
        // Button nur aktivieren, wenn alle Anforderungen erfüllt sind
        if (strength === 5 && confirmPassword.value && this.value === confirmPassword.value) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    });
    
    confirmPassword.addEventListener('input', function() {
        if (newPassword.value === this.value && checkPasswordStrength(newPassword.value) === 5) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    });
    
    // Initial Button deaktivieren
    submitBtn.disabled = true;
});
</script>
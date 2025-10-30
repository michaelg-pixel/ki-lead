<?php
// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once '../includes/auth.php';

$message = '';
$error = '';

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
    } elseif (strlen($new_password) < 8) {
        $error = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
    } else {
        // Aktuelles Passwort prüfen
        $user = getCurrentUser();
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Das aktuelle Passwort ist falsch.';
        } else {
            // Passwort ändern
            if (resetPassword($_SESSION['user_id'], $new_password)) {
                $message = 'Passwort erfolgreich geändert!';
            } else {
                $error = 'Fehler beim Ändern des Passworts.';
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
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
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
    }
    
    .password-requirements li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #667eea;
        font-weight: bold;
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
        
        <form method="POST">
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
                <p class="form-help">Mindestens 8 Zeichen</p>
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
            
            <button type="submit" name="change_password" class="btn-primary">
                Passwort ändern
            </button>
            
            <div class="password-requirements">
                <h4>Passwort-Anforderungen:</h4>
                <ul>
                    <li>Mindestens 8 Zeichen lang</li>
                    <li>Verwenden Sie eine Kombination aus Buchstaben und Zahlen</li>
                    <li>Vermeiden Sie einfache Passwörter wie "12345678"</li>
                </ul>
            </div>
        </form>
    </div>
</div>

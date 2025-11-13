<?php
/**
 * Customer Dashboard - API-Einstellungen für Empfehlungsprogramm
 * Konfiguration von Email-Marketing-Anbietern
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// Provider-Klassen laden
require_once __DIR__ . '/../includes/EmailProviders.php';

// Bestehende API-Einstellungen laden
$api_settings = null;
try {
    $stmt = $pdo->prepare("
        SELECT * FROM customer_email_api_settings 
        WHERE customer_id = ? AND is_active = TRUE
        LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    $api_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // API-Key entschlüsseln wenn vorhanden
    if ($api_settings && !empty($api_settings['api_key'])) {
        // Hier könnte eine Verschlüsselung implementiert werden
        // Für jetzt zeigen wir nur Sternchen
        $api_settings['api_key_masked'] = '••••••••' . substr($api_settings['api_key'], -4);
    }
} catch (PDOException $e) {
    error_log("API Settings Load Error: " . $e->getMessage());
}

// Verfügbare Provider laden
$providers = EmailProviderFactory::getSupportedProviders();
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
        
        .provider-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .provider-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .provider-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }
        
        .provider-card.selected {
            border-color: #10b981;
            background: linear-gradient(to bottom right, #064e3b, #065f46);
        }
        
        .provider-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .provider-card.selected .provider-icon {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .provider-features {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .feature-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border-radius: 8px;
            font-size: 11px;
            margin: 2px;
        }
        
        .config-section {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            display: none;
        }
        
        .config-section.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            background: #111827;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 0.9375rem;
            transition: all 0.3s;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-hint {
            color: #6b7280;
            font-size: 0.75rem;
            margin-top: 0.25rem;
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
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn-test {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-verified {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-unverified {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        .status-error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        
        .checkbox-group label {
            color: #e5e7eb;
            font-size: 0.9375rem;
            cursor: pointer;
            flex: 1;
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid #3b82f6;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box-icon {
            color: #3b82f6;
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
        
        .existing-config {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .config-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .config-detail:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .provider-grid {
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
                            <i class="fas fa-plug"></i> API-Einstellungen
                        </h1>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 1rem;">
                            Verbinde dein Email-Marketing-System mit dem Empfehlungsprogramm
                        </p>
                    </div>
                    <a href="?page=empfehlungsprogramm" style="color: white; background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-arrow-left"></i> Zurück
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Bestehende Konfiguration -->
        <?php if ($api_settings): ?>
        <div class="existing-config animate-fade-in" style="opacity: 0; animation-delay: 0.1s;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">
                        Aktuelle Konfiguration
                    </h3>
                    <span class="status-badge <?php echo $api_settings['is_verified'] ? 'status-verified' : 'status-unverified'; ?>">
                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                        <?php echo $api_settings['is_verified'] ? 'Verifiziert' : 'Nicht verifiziert'; ?>
                    </span>
                </div>
                <button onclick="deleteConfig()" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Konfiguration löschen
                </button>
            </div>
            
            <div class="config-detail">
                <span style="color: #9ca3af;">Provider:</span>
                <span style="color: white; font-weight: 600;">
                    <?php echo ucfirst($api_settings['provider']); ?>
                </span>
            </div>
            
            <div class="config-detail">
                <span style="color: #9ca3af;">API Key:</span>
                <span style="color: white; font-family: monospace; font-size: 0.875rem;">
                    <?php echo $api_settings['api_key_masked'] ?? '••••••••'; ?>
                </span>
            </div>
            
            <?php if ($api_settings['start_tag']): ?>
            <div class="config-detail">
                <span style="color: #9ca3af;">Start-Tag:</span>
                <span style="color: #10b981; font-weight: 500;">
                    <?php echo htmlspecialchars($api_settings['start_tag']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if ($api_settings['list_id']): ?>
            <div class="config-detail">
                <span style="color: #9ca3af;">Listen-ID:</span>
                <span style="color: #3b82f6; font-weight: 500;">
                    <?php echo htmlspecialchars($api_settings['list_id']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if ($api_settings['campaign_id']): ?>
            <div class="config-detail">
                <span style="color: #9ca3af;">Kampagnen-ID:</span>
                <span style="color: #8b5cf6; font-weight: 500;">
                    <?php echo htmlspecialchars($api_settings['campaign_id']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="config-detail">
                <span style="color: #9ca3af;">Double Opt-in:</span>
                <span style="color: white;">
                    <?php echo $api_settings['double_optin_enabled'] ? '✅ Aktiviert' : '❌ Deaktiviert'; ?>
                </span>
            </div>
            
            <?php if ($api_settings['last_verified_at']): ?>
            <div class="config-detail">
                <span style="color: #9ca3af;">Zuletzt verifiziert:</span>
                <span style="color: white;">
                    <?php echo date('d.m.Y H:i', strtotime($api_settings['last_verified_at'])); ?> Uhr
                </span>
            </div>
            <?php endif; ?>
            
            <?php if ($api_settings['verification_error']): ?>
            <div style="margin-top: 1rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 0.5rem;">
                <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.25rem;">
                    <i class="fas fa-exclamation-triangle"></i> Verifizierungsfehler
                </div>
                <div style="color: #fca5a5; font-size: 0.875rem;">
                    <?php echo htmlspecialchars($api_settings['verification_error']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <button onclick="testConnection()" class="btn btn-test">
                    <i class="fas fa-check-circle"></i> Verbindung testen
                </button>
                <button onclick="showEditForm()" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Bearbeiten
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Info-Box -->
        <div class="info-box animate-fade-in" style="opacity: 0; animation-delay: 0.2s;">
            <div style="display: flex; align-items: start;">
                <i class="fas fa-info-circle info-box-icon"></i>
                <div style="flex: 1;">
                    <h4 style="color: white; font-weight: 600; margin-bottom: 0.5rem;">
                        Wie funktioniert die Integration?
                    </h4>
                    <ul style="color: #9ca3af; font-size: 0.875rem; line-height: 1.6; margin: 0; padding-left: 1.25rem;">
                        <li>Wähle deinen Email-Marketing-Anbieter aus</li>
                        <li>Trage deine API-Zugangsdaten ein</li>
                        <li>Konfiguriere Tags, Listen und Kampagnen</li>
                        <li>Leads werden automatisch in dein System eingetragen</li>
                        <li>Belohnungen werden automatisch per Email versendet</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Provider-Auswahl -->
        <div class="animate-fade-in" style="opacity: 0; animation-delay: 0.3s;" id="providerSelection">
            <h2 style="color: white; font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                Wähle deinen Email-Marketing-Anbieter
            </h2>
            
            <div class="provider-grid">
                <?php foreach ($providers as $key => $provider): ?>
                <div class="provider-card" 
                     data-provider="<?php echo $key; ?>"
                     onclick="selectProvider('<?php echo $key; ?>')">
                    <div class="provider-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($provider['name']); ?>
                    </h3>
                    <div class="provider-features">
                        <?php if ($provider['supports_direct_email']): ?>
                        <span class="feature-badge">
                            <i class="fas fa-paper-plane"></i> Direkt-Email
                        </span>
                        <?php endif; ?>
                        <?php if ($provider['supports_tags']): ?>
                        <span class="feature-badge">
                            <i class="fas fa-tag"></i> Tags
                        </span>
                        <?php endif; ?>
                        <?php if ($provider['supports_campaigns']): ?>
                        <span class="feature-badge">
                            <i class="fas fa-bullhorn"></i> Kampagnen
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Konfigurations-Formulare für jeden Provider -->
        <?php foreach ($providers as $key => $provider): ?>
        <div id="config-<?php echo $key; ?>" class="config-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <h2 style="color: white; font-size: 1.5rem; font-weight: 600;">
                    <?php echo htmlspecialchars($provider['name']); ?> konfigurieren
                </h2>
                <button onclick="cancelConfig()" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 0.875rem;">
                    <i class="fas fa-times"></i> Abbrechen
                </button>
            </div>
            
            <form onsubmit="saveConfig(event, '<?php echo $key; ?>')">
                <!-- API-Zugangsdaten -->
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-key"></i> API-Zugangsdaten
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            API-Key <span class="required">*</span>
                        </label>
                        <input type="password" 
                               name="api_key" 
                               class="form-input" 
                               required 
                               placeholder="Dein API-Key von <?php echo $provider['name']; ?>">
                        <div class="form-hint">
                            Zu finden in deinen <?php echo $provider['name']; ?> Einstellungen unter "API" oder "Integrationen"
                        </div>
                    </div>
                    
                    <?php if (in_array('username', $provider['config_fields'])): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Benutzername <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="username" 
                               class="form-input" 
                               required 
                               placeholder="Dein <?php echo $provider['name']; ?> Benutzername">
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('password', $provider['config_fields'])): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Passwort <span class="required">*</span>
                        </label>
                        <input type="password" 
                               name="password" 
                               class="form-input" 
                               required 
                               placeholder="Dein <?php echo $provider['name']; ?> Passwort">
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('account_url', $provider['config_fields'])): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Account-URL <span class="required">*</span>
                        </label>
                        <input type="url" 
                               name="account_url" 
                               class="form-input" 
                               required 
                               placeholder="https://dein-account.api-us1.com">
                        <div class="form-hint">
                            Deine individuelle API-URL von <?php echo $provider['name']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('base_url', $provider['config_fields'])): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Basis-URL
                        </label>
                        <input type="url" 
                               name="base_url" 
                               class="form-input" 
                               placeholder="https://api.quentn.com/public/v1">
                        <div class="form-hint">
                            Optional: Nur ändern wenn du eine andere API-Version nutzt
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Listen & Tags -->
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-tags"></i> Listen & Tags Konfiguration
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Start-Tag
                        </label>
                        <input type="text" 
                               name="start_tag" 
                               class="form-input" 
                               placeholder="z.B. lead_empfehlungsprogramm">
                        <div class="form-hint">
                            Dieser Tag wird jedem neuen Lead automatisch zugewiesen
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Listen-ID
                        </label>
                        <input type="text" 
                               name="list_id" 
                               class="form-input" 
                               placeholder="z.B. 12345">
                        <div class="form-hint">
                            ID der Liste, in die neue Leads eingetragen werden sollen
                        </div>
                    </div>
                    
                    <?php if ($provider['supports_campaigns']): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Kampagnen-ID
                        </label>
                        <input type="text" 
                               name="campaign_id" 
                               class="form-input" 
                               placeholder="z.B. 67890">
                        <div class="form-hint">
                            ID der Kampagne für automatische Follow-ups
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Email-Versand -->
                <?php if ($provider['supports_direct_email']): ?>
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-paper-plane"></i> Email-Versand Einstellungen
                    </h3>
                    
                    <?php if (in_array('sender_email', $provider['config_fields'])): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Absender-Email <span class="required">*</span>
                        </label>
                        <input type="email" 
                               name="sender_email" 
                               class="form-input" 
                               required 
                               placeholder="deine@email.de">
                        <div class="form-hint">
                            Muss in <?php echo $provider['name']; ?> verifiziert sein
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Absender-Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="sender_name" 
                               class="form-input" 
                               required 
                               placeholder="Dein Name / Firma">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Double Opt-in -->
                <div style="background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: white; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                        <i class="fas fa-shield-alt"></i> Double Opt-in Einstellungen
                    </h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" 
                               name="double_optin_enabled" 
                               id="doi-<?php echo $key; ?>" 
                               checked>
                        <label for="doi-<?php echo $key; ?>">
                            <strong>Double Opt-in aktivieren</strong><br>
                            <small style="color: #9ca3af;">Empfohlen für DSGVO-Konformität</small>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Double Opt-in Formular-ID (optional)
                        </label>
                        <input type="text" 
                               name="double_optin_form_id" 
                               class="form-input" 
                               placeholder="ID deines DOI-Formulars">
                    </div>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; flex-wrap: wrap;">
                    <button type="button" onclick="cancelConfig()" class="btn" style="background: #374151; color: white;">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Speichern & Testen
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
        
    </div>
    
    <script>
        let selectedProvider = null;
        let hasExistingConfig = <?php echo $api_settings ? 'true' : 'false'; ?>;
        
        // Provider auswählen
        function selectProvider(provider) {
            selectedProvider = provider;
            
            // Alle Cards deselektieren
            document.querySelectorAll('.provider-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Gewählte Card selektieren
            document.querySelector(`[data-provider="${provider}"]`).classList.add('selected');
            
            // Konfig-Form anzeigen
            showConfigForm(provider);
        }
        
        function showConfigForm(provider) {
            // Provider-Auswahl ausblenden
            document.getElementById('providerSelection').style.display = 'none';
            
            // Alle Config-Sections ausblenden
            document.querySelectorAll('.config-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Gewählte Config-Section anzeigen
            document.getElementById(`config-${provider}`).classList.add('active');
            
            // Smooth scroll nach oben
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function cancelConfig() {
            // Alle Config-Sections ausblenden
            document.querySelectorAll('.config-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Provider-Auswahl wieder anzeigen
            document.getElementById('providerSelection').style.display = 'block';
            
            selectedProvider = null;
        }
        
        function showEditForm() {
            <?php if ($api_settings): ?>
            selectProvider('<?php echo $api_settings['provider']; ?>');
            <?php endif; ?>
        }
        
        // Konfiguration speichern
        async function saveConfig(event, provider) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                provider: provider,
                api_key: formData.get('api_key'),
                start_tag: formData.get('start_tag'),
                list_id: formData.get('list_id'),
                campaign_id: formData.get('campaign_id'),
                double_optin_enabled: formData.get('double_optin_enabled') ? true : false,
                double_optin_form_id: formData.get('double_optin_form_id')
            };
            
            // Provider-spezifische Felder
            const additionalFields = ['username', 'password', 'account_url', 'base_url', 'sender_email', 'sender_name'];
            additionalFields.forEach(field => {
                if (formData.has(field)) {
                    data[field] = formData.get(field);
                }
            });
            
            try {
                const response = await fetch('/api/email-settings/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Einstellungen gespeichert! Teste jetzt die Verbindung.', 'success');
                    
                    // Nach kurzem Timeout neu laden
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('Fehler: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Verbindungsfehler beim Speichern', 'error');
            }
        }
        
        // Verbindung testen
        async function testConnection() {
            showNotification('Teste Verbindung...', 'info');
            
            try {
                const response = await fetch('/api/email-settings/test.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Verbindung erfolgreich! ' + result.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('❌ Verbindung fehlgeschlagen: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Fehler beim Testen der Verbindung', 'error');
            }
        }
        
        // Konfiguration löschen
        async function deleteConfig() {
            if (!confirm('API-Konfiguration wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) {
                return;
            }
            
            try {
                const response = await fetch('/api/email-settings/delete.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Konfiguration gelöscht', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Fehler: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Verbindungsfehler', 'error');
            }
        }
        
        // Notification
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
                font-size: 0.875rem;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
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
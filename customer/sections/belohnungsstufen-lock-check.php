<?php
/**
 * WICHTIG: Sperre für Belohnungsstufen
 * Prüft ob Empfehlungsprogramm aktiviert ist
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// PRÜFUNG: Ist Empfehlungsprogramm aktiviert?
try {
    $stmt = $pdo->prepare("SELECT referral_enabled FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $referral_enabled = (bool)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Referral Check Error: " . $e->getMessage());
    $referral_enabled = false;
}

// Wenn NICHT aktiviert → Sperrbildschirm anzeigen
if (!$referral_enabled) {
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
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            body {
                background: linear-gradient(to bottom right, #1f2937, #111827, #1f2937);
                min-height: 100vh;
                margin: 0;
                padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .locked-screen {
                max-width: 800px;
                margin: 0 auto;
                padding: 2rem 1rem;
                text-align: center;
            }
            .lock-icon {
                width: 120px;
                height: 120px;
                margin: 0 auto 2rem;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 4rem;
                color: white;
                animation: pulse 2s ease-in-out infinite;
                box-shadow: 0 20px 40px -10px rgba(239, 68, 68, 0.5);
            }
            .lock-title {
                color: white;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                animation: fadeInUp 0.6s ease-out;
            }
            .lock-subtitle {
                color: #9ca3af;
                font-size: 1.25rem;
                margin-bottom: 3rem;
                line-height: 1.6;
                animation: fadeInUp 0.6s ease-out 0.1s both;
            }
            .steps-container {
                background: linear-gradient(to bottom right, #1f2937, #374151);
                border: 2px solid rgba(102, 126, 234, 0.3);
                border-radius: 1rem;
                padding: 2rem;
                margin-bottom: 2rem;
                text-align: left;
                animation: fadeInUp 0.6s ease-out 0.2s both;
                box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
            }
            .steps-title {
                color: white;
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .step {
                display: flex;
                gap: 1.5rem;
                padding: 1.5rem;
                background: rgba(0, 0, 0, 0.2);
                border-radius: 0.75rem;
                margin-bottom: 1rem;
                transition: all 0.3s;
            }
            .step:hover {
                background: rgba(102, 126, 234, 0.1);
                transform: translateX(5px);
            }
            .step:last-child {
                margin-bottom: 0;
            }
            .step-number {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: 700;
                color: white;
                flex-shrink: 0;
                box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            }
            .step-content h3 {
                color: white;
                font-size: 1.125rem;
                font-weight: 600;
                margin: 0 0 0.5rem 0;
            }
            .step-content p {
                color: #9ca3af;
                font-size: 0.9375rem;
                margin: 0;
                line-height: 1.6;
            }
            .action-button {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                text-decoration: none;
                border-radius: 0.75rem;
                font-size: 1.125rem;
                font-weight: 600;
                transition: all 0.3s;
                animation: fadeInUp 0.6s ease-out 0.3s both;
                box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
            }
            .action-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.5);
            }
            .feature-box {
                background: rgba(59, 130, 246, 0.1);
                border: 2px solid #3b82f6;
                border-radius: 1rem;
                padding: 1.5rem;
                margin-top: 2rem;
                animation: fadeInUp 0.6s ease-out 0.4s both;
            }
            .feature-box h3 {
                color: white;
                font-size: 1.125rem;
                font-weight: 600;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .feature-list {
                color: #9ca3af;
                font-size: 0.9375rem;
                line-height: 1.8;
                margin: 0;
                padding-left: 1.5rem;
            }
            .feature-list li {
                margin-bottom: 0.5rem;
            }
            @media (max-width: 640px) {
                .lock-icon {
                    width: 80px;
                    height: 80px;
                    font-size: 2.5rem;
                }
                .lock-title {
                    font-size: 1.75rem;
                }
                .lock-subtitle {
                    font-size: 1rem;
                }
                .step {
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                }
                .step-number {
                    width: 50px;
                    height: 50px;
                    font-size: 1.25rem;
                }
                .action-button {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    </head>
    <body>
        <div class="locked-screen">
            <div class="lock-icon">
                <i class="fas fa-lock"></i>
            </div>
            
            <h1 class="lock-title">
                Empfehlungsprogramm nicht aktiviert
            </h1>
            
            <p class="lock-subtitle">
                Um Belohnungsstufen zu erstellen und zu verwalten, musst du erst dein<br>
                Empfehlungsprogramm aktivieren. Das dauert nur wenige Minuten!
            </p>
            
            <div class="steps-container">
                <div class="steps-title">
                    <i class="fas fa-list-check"></i>
                    So aktivierst du dein Empfehlungsprogramm:
                </div>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Gehe zum Empfehlungsprogramm</h3>
                        <p>Klicke auf den grünen Button unten, um zur Empfehlungsprogramm-Seite zu gelangen.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Lies die Transparenz-Informationen</h3>
                        <p>Du erfährst, wie wir deine Daten schützen (EU-Server, DSGVO-konform) und wie das Mailgun E-Mail-System funktioniert.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Akzeptiere die Nutzungsbedingungen</h3>
                        <p>Stimme der Nutzung von Mailgun und dem Auftragsverarbeitungsvertrag (AVV) zu. Deine Daten bleiben sicher in Europa.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Aktiviere das Programm</h3>
                        <p>Schalte den Toggle oben rechts auf "Aktiviert" um. Fertig! Jetzt kannst du Belohnungsstufen erstellen.</p>
                    </div>
                </div>
            </div>
            
            <a href="?page=empfehlungsprogramm" class="action-button">
                <i class="fas fa-rocket"></i>
                Jetzt Empfehlungsprogramm aktivieren
            </a>
            
            <div class="feature-box">
                <h3>
                    <i class="fas fa-gift"></i>
                    Was du danach tun kannst:
                </h3>
                <ul class="feature-list">
                    <li>✅ Unbegrenzt viele Belohnungsstufen erstellen</li>
                    <li>✅ Fertige Templates aus dem Marktplatz importieren</li>
                    <li>✅ Belohnungen an deine Freebies koppeln</li>
                    <li>✅ Automatische E-Mail-Benachrichtigungen an deine Leads</li>
                    <li>✅ Empfehlungslinks generieren und teilen</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit; // Beende das Script hier
}

// AB HIER: Normaler Code für aktivierte Programme
// (Rest der Datei bleibt unverändert...)
?>
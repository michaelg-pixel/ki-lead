<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// ========================================
// FREEBIE LADEN
// ========================================
$freebie_id = $_GET['id'] ?? null;

if (!$freebie_id) {
    die('Ungültige Anfrage. Freebie-ID fehlt.');
}

$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
$stmt->execute([$freebie_id]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    die('Freebie nicht gefunden.');
}

// ========================================
// VIDEOKURS PRÜFEN
// ========================================
$has_videokurs = ($freebie['has_course'] == 1);
$videokurs_url = null;

if ($has_videokurs) {
    // Token für sicheren Zugang generieren
    $token = hash('sha256', $freebie['id'] . $freebie['unique_id']);
    $videokurs_url = "/public/videokurs-player.php?id={$freebie['id']}&token={$token}";
}

// ========================================
// KUNDEN-DATEN LADEN (für Rechtstexte)
// ========================================
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$freebie['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

$imprint_url = $customer['imprint_url'] ?? '#';
$privacy_url = $customer['privacy_url'] ?? '#';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danke! - <?php echo htmlspecialchars($freebie['headline']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 48px 32px;
            text-align: center;
            color: white;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 16px;
            animation: bounce 1s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .success-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        
        .success-subtitle {
            font-size: 16px;
            opacity: 0.95;
            line-height: 1.6;
        }
        
        .success-content {
            padding: 40px 32px;
        }
        
        .message-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .message-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message-text {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.8;
        }
        
        /* VIDEOKURS CARD */
        .videokurs-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            text-align: center;
            color: white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        .videokurs-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .videokurs-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .videokurs-description {
            font-size: 15px;
            color: #ddd;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .videokurs-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .videokurs-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.6);
        }
        
        /* NEXT STEPS */
        .next-steps {
            margin-bottom: 32px;
        }
        
        .next-steps-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 20px;
        }
        
        .step {
            display: flex;
            align-items: start;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
            padding-top: 4px;
        }
        
        .step-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .step-text {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        /* FOOTER */
        .success-footer {
            background: #f9fafb;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .footer-links {
            margin-top: 12px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }
        
        .footer-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 640px) {
            .success-container {
                border-radius: 16px;
            }
            
            .success-header {
                padding: 32px 24px;
            }
            
            .success-icon {
                font-size: 60px;
            }
            
            .success-title {
                font-size: 24px;
            }
            
            .success-content {
                padding: 24px 20px;
            }
            
            .videokurs-card {
                padding: 24px;
            }
            
            .videokurs-title {
                font-size: 20px;
            }
            
            .videokurs-button {
                padding: 14px 32px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <!-- Header -->
        <div class="success-header">
            <div class="success-icon">🎉</div>
            <h1 class="success-title">Vielen Dank!</h1>
            <p class="success-subtitle">
                Du hast dich erfolgreich eingetragen.<br>
                Prüfe jetzt dein E-Mail-Postfach.
            </p>
        </div>
        
        <!-- Content -->
        <div class="success-content">
            <!-- Info Box -->
            <div class="message-box">
                <div class="message-title">
                    📧 E-Mail-Bestätigung erforderlich
                </div>
                <div class="message-text">
                    Wir haben dir gerade eine Bestätigungs-E-Mail geschickt. 
                    Bitte klicke auf den Link in der E-Mail, um deine Anmeldung zu bestätigen 
                    und sofortigen Zugang zu erhalten.
                </div>
            </div>
            
            <!-- VIDEOKURS CARD (nur wenn vorhanden) -->
            <?php if ($has_videokurs): ?>
            <div class="videokurs-card">
                <div class="videokurs-icon">🎓</div>
                <h2 class="videokurs-title">Dein Videokurs ist bereit!</h2>
                <p class="videokurs-description">
                    Als Bonus hast du jetzt Zugang zu einem exklusiven Videokurs. 
                    Starte sofort und lerne in deinem eigenen Tempo.
                </p>
                <a href="<?php echo htmlspecialchars($videokurs_url); ?>" class="videokurs-button">
                    🎥 Zum Videokurs starten
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h3 class="next-steps-title">📋 Nächste Schritte</h3>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">E-Mail prüfen</div>
                        <div class="step-text">
                            Schau in deinem Postfach nach unserer Bestätigungs-E-Mail. 
                            Prüfe auch deinen Spam-Ordner!
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Link bestätigen</div>
                        <div class="step-text">
                            Klicke auf den Bestätigungslink in der E-Mail, um deine Anmeldung 
                            abzuschließen.
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title"><?php echo $has_videokurs ? 'Videokurs starten' : 'Zugang erhalten'; ?></div>
                        <div class="step-text">
                            <?php if ($has_videokurs): ?>
                                Nach der Bestätigung kannst du direkt mit dem Videokurs loslegen!
                            <?php else: ?>
                                Nach der Bestätigung erhältst du sofortigen Zugang zu deinem Freebie.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="success-footer">
            <p class="footer-text">
                Bei Fragen oder Problemen kannst du uns jederzeit kontaktieren.<br>
                Wir helfen dir gerne weiter!
            </p>
            <div class="footer-links">
                <a href="<?php echo htmlspecialchars($imprint_url); ?>" class="footer-link">Impressum</a>
                <a href="<?php echo htmlspecialchars($privacy_url); ?>" class="footer-link">Datenschutz</a>
            </div>
        </div>
    </div>
    
    <!-- Optional: Confetti Animation -->
    <script>
        // Simple confetti effect on page load
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.opacity = '1';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '9999';
                
                document.body.appendChild(confetti);
                
                const duration = 3000 + Math.random() * 2000;
                const delay = Math.random() * 1000;
                
                setTimeout(() => {
                    confetti.animate([
                        {
                            transform: 'translateY(0) rotate(0deg)',
                            opacity: 1
                        },
                        {
                            transform: `translateY(100vh) rotate(${360 + Math.random() * 360}deg)`,
                            opacity: 0
                        }
                    ], {
                        duration: duration,
                        easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                    }).onfinish = () => confetti.remove();
                }, delay);
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
</body>
</html>

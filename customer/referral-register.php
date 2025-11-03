<?php
/**
 * Registrierungsseite für Empfehlungsprogramm
 * Überzeugende Landingpage mit klaren Vorteilen
 */

require_once __DIR__ . '/../config/database.php';

// Customer ID und Freebie ID aus URL
$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : 0;

if ($customer_id <= 0) {
    die('Ungültige Customer-ID');
}

// Prüfen ob User bereits registriert ist
try {
    $stmt = $pdo->prepare("
        SELECT 
            referral_enabled,
            ref_code,
            company_name
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die('User nicht gefunden');
    }
    
    $isAlreadyRegistered = $user['referral_enabled'] == 1;
    $companyName = $user['company_name'] ?? '';
    
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// Wenn bereits registriert, direkt zum Dashboard weiterleiten
if ($isAlreadyRegistered) {
    $redirect_url = '/customer/dashboard.php?customer=' . $customer_id . '&page=empfehlungsprogramm';
    if ($freebie_id > 0) {
        $redirect_url .= '&freebie=' . $freebie_id;
    }
    header('Location: ' . $redirect_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jetzt am Empfehlungsprogramm teilnehmen!</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1F2937;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            width: 100%;
        }
        
        /* Hero Section */
        .hero {
            background: white;
            border-radius: 24px;
            padding: 60px 50px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
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
        
        .hero-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .hero-title {
            font-size: 48px;
            font-family: 'Poppins', sans-serif;
            font-weight: 900;
            color: #111827;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 24px;
            color: #6b7280;
            margin-bottom: 40px;
            font-weight: 600;
        }
        
        .hero-description {
            font-size: 18px;
            color: #4b5563;
            margin-bottom: 40px;
            line-height: 1.8;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 24px 60px;
            border: none;
            border-radius: 16px;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .cta-button:hover::before {
            left: 100%;
        }
        
        .cta-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.5);
        }
        
        .cta-icon {
            font-size: 28px;
        }
        
        /* Benefits Section */
        .benefits {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        
        .benefits-title {
            font-size: 32px;
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .benefit-card {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .benefit-card:hover {
            transform: translateY(-8px);
            border-color: #667eea;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.2);
        }
        
        .benefit-icon {
            font-size: 56px;
            margin-bottom: 20px;
        }
        
        .benefit-title {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .benefit-text {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.7;
        }
        
        /* How It Works */
        .how-it-works {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease-out 0.4s both;
        }
        
        .how-title {
            font-size: 32px;
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
            margin-bottom: 48px;
        }
        
        .step {
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 900;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .step-text {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.7;
        }
        
        /* Testimonial Box */
        .testimonial {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 3px solid #3b82f6;
            border-radius: 20px;
            padding: 32px;
            margin-top: 32px;
            position: relative;
        }
        
        .testimonial-icon {
            font-size: 48px;
            color: #3b82f6;
            margin-bottom: 16px;
        }
        
        .testimonial-text {
            font-size: 18px;
            font-style: italic;
            color: #1e40af;
            margin-bottom: 16px;
            line-height: 1.8;
        }
        
        .testimonial-author {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }
        
        /* Final CTA */
        .final-cta {
            text-align: center;
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #e5e7eb;
        }
        
        .final-cta-text {
            font-size: 20px;
            color: #4b5563;
            margin-bottom: 24px;
            font-weight: 600;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero {
                padding: 40px 24px;
            }
            
            .hero-title {
                font-size: 32px;
            }
            
            .hero-subtitle {
                font-size: 18px;
            }
            
            .hero-description {
                font-size: 16px;
            }
            
            .cta-button {
                width: 100%;
                padding: 20px 40px;
                font-size: 18px;
            }
            
            .benefits {
                padding: 32px 24px;
            }
            
            .benefits-title {
                font-size: 24px;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
            }
            
            .how-it-works {
                padding: 32px 24px;
            }
            
            .how-title {
                font-size: 24px;
            }
            
            .steps {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <div class="hero-badge">
                🎁 Exklusives Angebot für Sie
            </div>
            
            <h1 class="hero-title">
                Verdienen Sie mit jedem geteilten Freebie!
            </h1>
            
            <p class="hero-subtitle">
                Werden Sie Teil unseres exklusiven Empfehlungsprogramms
            </p>
            
            <p class="hero-description">
                Teilen Sie Ihre Freebies mit Freunden, Familie und Ihrem Netzwerk und erhalten Sie <strong>attraktive Belohnungen für jeden generierten Lead</strong>. Komplett kostenlos, einfach zu nutzen und mit transparentem Tracking in Echtzeit!
            </p>
            
            <a href="javascript:void(0)" onclick="activateProgram()" class="cta-button">
                <span class="cta-icon">🚀</span>
                <span>Jetzt kostenlos registrieren</span>
            </a>
        </div>
        
        <!-- Benefits Section -->
        <div class="benefits">
            <h2 class="benefits-title">
                Ihre Vorteile auf einen Blick
            </h2>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">💰</div>
                    <h3 class="benefit-title">Attraktive Belohnungen</h3>
                    <p class="benefit-text">
                        Verdienen Sie wertvolle Prämien für jeden Lead, den Sie uns bringen. Je mehr Empfehlungen, desto höher Ihre Belohnungen!
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🎯</div>
                    <h3 class="benefit-title">Einfache Nutzung</h3>
                    <p class="benefit-text">
                        Erhalten Sie Ihren persönlichen Empfehlungslink und teilen Sie ihn einfach per E-Mail, Social Media oder WhatsApp.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h3 class="benefit-title">Live Tracking</h3>
                    <p class="benefit-text">
                        Behalten Sie alle Klicks, Conversions und Ihre Verdienste in Echtzeit im Blick – transparent und übersichtlich.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🏆</div>
                    <h3 class="benefit-title">Bonus-System</h3>
                    <p class="benefit-text">
                        Steigen Sie in höhere Belohnungsstufen auf und profitieren Sie von exklusiven Boni und Sonderaktionen.
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">✅</div>
                    <h3 class="benefit-title">100% Kostenlos</h3>
                    <p class="benefit-text">
                        Keine versteckten Kosten, keine Gebühren. Die Teilnahme am Empfehlungsprogramm ist für Sie vollkommen kostenlos!
                    </p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🔒</div>
                    <h3 class="benefit-title">Datenschutz</h3>
                    <p class="benefit-text">
                        Ihre Daten sind bei uns sicher. Wir behandeln alle Informationen vertraulich und DSGVO-konform.
                    </p>
                </div>
            </div>
            
            <!-- Testimonial -->
            <div class="testimonial">
                <div class="testimonial-icon">💬</div>
                <p class="testimonial-text">
                    "Das Empfehlungsprogramm ist fantastisch! Ich teile meine Freebies einfach in meinem Netzwerk und verdiene dabei. Die Belohnungen sind fair und das Tracking funktioniert einwandfrei. Absolute Empfehlung!"
                </p>
                <div class="testimonial-author">
                    — Sarah M., zufriedene Teilnehmerin
                </div>
            </div>
        </div>
        
        <!-- How It Works -->
        <div class="how-it-works">
            <h2 class="how-title">
                So einfach geht's
            </h2>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Registrieren</h3>
                    <p class="step-text">
                        Klicken Sie auf den Button und aktivieren Sie das Programm kostenlos
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Freebie wählen</h3>
                    <p class="step-text">
                        Wählen Sie eines Ihrer Freebies aus und erhalten Sie Ihren persönlichen Link
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Teilen & Verdienen</h3>
                    <p class="step-text">
                        Teilen Sie Ihren Link und verdienen Sie für jeden generierten Lead
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Belohnungen erhalten</h3>
                    <p class="step-text">
                        Sammeln Sie Ihre Belohnungen und profitieren Sie von Boni
                    </p>
                </div>
            </div>
            
            <!-- Final CTA -->
            <div class="final-cta">
                <p class="final-cta-text">
                    Bereit, mit Ihren Empfehlungen zu verdienen?
                </p>
                
                <a href="javascript:void(0)" onclick="activateProgram()" class="cta-button">
                    <span class="cta-icon">🎯</span>
                    <span>Jetzt durchstarten!</span>
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function activateProgram() {
            // Zeige Loading-Status
            const buttons = document.querySelectorAll('.cta-button');
            buttons.forEach(btn => {
                btn.innerHTML = '<span class="cta-icon"><i class="fas fa-spinner fa-spin"></i></span><span>Wird aktiviert...</span>';
                btn.style.pointerEvents = 'none';
            });
            
            // API-Call zum Aktivieren
            fetch('/api/referral/toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    enabled: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Erfolg - Weiterleitung zum Dashboard
                    const redirectUrl = '/customer/dashboard.php?customer=<?php echo $customer_id; ?>&page=empfehlungsprogramm<?php echo $freebie_id > 0 ? "&freebie=" . $freebie_id : ""; ?>';
                    window.location.href = redirectUrl;
                } else {
                    alert('Fehler: ' + (data.message || 'Aktivierung fehlgeschlagen'));
                    buttons.forEach(btn => {
                        btn.innerHTML = '<span class="cta-icon">🚀</span><span>Jetzt kostenlos registrieren</span>';
                        btn.style.pointerEvents = 'auto';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Verbindungsfehler. Bitte versuchen Sie es erneut.');
                buttons.forEach(btn => {
                    btn.innerHTML = '<span class="cta-icon">🚀</span><span>Jetzt kostenlos registrieren</span>';
                    btn.style.pointerEvents = 'auto';
                });
            });
        }
    </script>
</body>
</html>
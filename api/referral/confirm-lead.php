<?php
/**
 * API: Confirm Referral Lead Email
 * Double-Opt-In Bestätigung
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    die('Ungültiger Bestätigungslink');
}

try {
    $db = Database::getInstance()->getConnection();
    $referral = new ReferralHelper($db);
    
    $result = $referral->confirmLead($token);
    
    if ($result['success']) {
        // Erfolgs-Seite anzeigen
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>E-Mail bestätigt</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    max-width: 500px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }
                .success-icon {
                    width: 80px;
                    height: 80px;
                    background: #10b981;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    animation: scaleIn 0.5s ease-out;
                }
                .success-icon svg {
                    width: 40px;
                    height: 40px;
                    stroke: white;
                    stroke-width: 3;
                    fill: none;
                }
                h1 {
                    color: #1f2937;
                    margin-bottom: 15px;
                    font-size: 28px;
                }
                p {
                    color: #6b7280;
                    line-height: 1.6;
                    margin-bottom: 10px;
                }
                @keyframes scaleIn {
                    from { transform: scale(0); }
                    to { transform: scale(1); }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h1>✓ E-Mail bestätigt!</h1>
                <p>Ihre E-Mail-Adresse wurde erfolgreich bestätigt.</p>
                <p>Sie nehmen nun offiziell am Empfehlungsprogramm teil.</p>
            </div>
        </body>
        </html>
        <?php
    } else {
        // Fehler-Seite
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bestätigung fehlgeschlagen</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    max-width: 500px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }
                .error-icon {
                    width: 80px;
                    height: 80px;
                    background: #ef4444;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                }
                .error-icon svg {
                    width: 40px;
                    height: 40px;
                    stroke: white;
                    stroke-width: 3;
                    fill: none;
                }
                h1 {
                    color: #1f2937;
                    margin-bottom: 15px;
                    font-size: 28px;
                }
                p {
                    color: #6b7280;
                    line-height: 1.6;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-icon">
                    <svg viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
                <h1>Bestätigung fehlgeschlagen</h1>
                <p>Der Bestätigungslink ist ungültig oder wurde bereits verwendet.</p>
            </div>
        </body>
        </html>
        <?php
    }
    
} catch (Exception $e) {
    error_log("Lead Confirmation Error: " . $e->getMessage());
    die('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
}

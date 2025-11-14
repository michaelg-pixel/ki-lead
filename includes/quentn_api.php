<?php
/**
 * Quentn API Helper Functions
 * Für E-Mail-Versand über Quentn
 */

require_once __DIR__ . '/../config/quentn_config.php';

/**
 * Sendet eine Passwort-Reset E-Mail über Quentn
 * 
 * @param string $toEmail Empfänger E-Mail
 * @param string $toName Empfänger Name
 * @param string $resetLink Der Reset-Link
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    try {
        // E-Mail HTML erstellen
        $emailHtml = getPasswordResetEmailTemplate($toName, $resetLink);
        
        // Quentn API Request vorbereiten
        $data = [
            'email' => $toEmail,
            'first_name' => $toName,
            'tags' => ['password-reset'],
            'skip_double_opt_in' => true, // Wichtig: Kein DOI für Transaktions-E-Mails
            'custom_fields' => [
                'reset_link' => $resetLink,
                'email_subject' => 'Passwort zurücksetzen - Optinpilot',
                'email_body' => $emailHtml
            ]
        ];
        
        // cURL Request
        $ch = curl_init(QUENTN_API_BASE_URL . 'contacts');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log für Debugging
        error_log("Quentn API Response: HTTP $httpCode - " . substr($response, 0, 200));
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'E-Mail erfolgreich versendet'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'E-Mail-Versand fehlgeschlagen: HTTP ' . $httpCode
            ];
        }
        
    } catch (Exception $e) {
        error_log("Quentn API Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Fehler beim E-Mail-Versand: ' . $e->getMessage()
        ];
    }
}

/**
 * E-Mail-Template für Passwort-Reset
 */
function getPasswordResetEmailTemplate($name, $resetLink) {
    $firstName = htmlspecialchars($name);
    $safeResetLink = htmlspecialchars($resetLink);
    
    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700;">
                                🔐 Passwort zurücksetzen
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Hallo {$firstName},
                            </p>
                            
                            <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt. Kein Problem! 
                                Klicke einfach auf den Button unten, um ein neues Passwort zu setzen:
                            </p>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$safeResetLink}" 
                                           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                            Passwort jetzt zurücksetzen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Info Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 16px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.5;">
                                            ⏰ <strong>Wichtig:</strong> Dieser Link ist aus Sicherheitsgründen nur 1 Stunde gültig.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 20px 0 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail einfach ignorieren. 
                                Dein Passwort bleibt dann unverändert.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">
                                📧 Diese E-Mail wurde automatisch versendet von
                            </p>
                            <p style="margin: 0; color: #374151; font-size: 16px; font-weight: 600;">
                                Optinpilot - Dein Lead-System
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Rate Limiting für Passwort-Reset Anfragen
 * Max 3 Anfragen pro E-Mail pro Stunde
 * 
 * @param PDO $pdo Datenbankverbindung
 * @param string $email E-Mail Adresse
 * @return bool True wenn erlaubt, False wenn Limit erreicht
 */
function checkPasswordResetRateLimit($pdo, $email) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE email = ? 
            AND password_reset_token IS NOT NULL 
            AND password_reset_expires > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Max 3 Anfragen pro Stunde
        return ($result['count'] < 3);
        
    } catch (Exception $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return true; // Im Fehlerfall erlauben
    }
}

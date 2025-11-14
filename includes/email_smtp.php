<?php
/**
 * E-Mail Helper mit PHPMailer und SMTP
 * Nutzt SMTP statt PHP mail() für zuverlässigen Versand
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer laden
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

/**
 * SMTP Konfiguration
 * WICHTIG: Diese Werte müssen angepasst werden!
 */
define('SMTP_HOST', 'smtp.hostinger.com'); // Hostinger SMTP
define('SMTP_PORT', 587); // TLS Port
define('SMTP_USERNAME', 'noreply@mehr-infos-jetzt.de'); // Deine E-Mail
define('SMTP_PASSWORD', 'DEIN_EMAIL_PASSWORT_HIER'); // E-Mail Passwort
define('SMTP_FROM_EMAIL', 'noreply@mehr-infos-jetzt.de');
define('SMTP_FROM_NAME', 'Optinpilot');

/**
 * Sendet eine Passwort-Reset E-Mail via SMTP
 * 
 * @param string $toEmail Empfänger E-Mail
 * @param string $toName Empfänger Name
 * @param string $resetLink Der Reset-Link
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    
    try {
        // Server Einstellungen
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Absender
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Empfänger
        $mail->addAddress($toEmail, $toName);
        
        // Inhalt
        $mail->isHTML(true);
        $mail->Subject = 'Passwort zurücksetzen - Optinpilot';
        $mail->Body = getPasswordResetEmailTemplate($toName, $resetLink);
        
        // Alternative Text-Version
        $mail->AltBody = "Hallo $toName,\n\n" .
                        "du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.\n\n" .
                        "Klicke auf diesen Link um dein Passwort zurückzusetzen:\n" .
                        "$resetLink\n\n" .
                        "Dieser Link ist 1 Stunde gültig.\n\n" .
                        "Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail.";
        
        $mail->send();
        
        error_log("Password reset email sent to: $toEmail via SMTP");
        return [
            'success' => true,
            'message' => 'E-Mail erfolgreich versendet'
        ];
        
    } catch (Exception $e) {
        error_log("SMTP email error: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'E-Mail-Versand fehlgeschlagen: ' . $mail->ErrorInfo
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
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 16px;">
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
                            
                            <p style="margin: 20px 0 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:<br>
                                <a href="{$safeResetLink}" style="color: #667eea; word-break: break-all;">{$safeResetLink}</a>
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
                            <p style="margin: 10px 0 0 0; color: #9ca3af; font-size: 12px;">
                                mehr-infos-jetzt.de
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
        
        return ($result['count'] < 3);
        
    } catch (Exception $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return true;
    }
}

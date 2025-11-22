<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail bestätigen</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 30px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px 12px 0 0;">
                            <div style="font-size: 64px; line-height: 1; margin-bottom: 16px;">📧</div>
                            <h1 style="margin: 0; padding: 0; font-size: 32px; font-weight: 700; color: #ffffff; line-height: 1.2;">
                                E-Mail bestätigen
                            </h1>
                            <p style="margin: 12px 0 0; padding: 0; font-size: 18px; color: rgba(255,255,255,0.9);">
                                Nur noch ein Klick fehlt
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            
                            <p style="margin: 0 0 24px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hallo <?php echo htmlspecialchars($lead_name); ?>,
                            </p>
                            
                            <p style="margin: 0 0 24px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                danke für deine Anmeldung beim Empfehlungsprogramm von <?php echo htmlspecialchars($customer_name); ?>!
                            </p>
                            
                            <p style="margin: 0 0 32px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Bitte bestätige deine E-Mail-Adresse, um dein Konto zu aktivieren und loszulegen.
                            </p>
                            
                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo htmlspecialchars($verification_link); ?>" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; border-radius: 8px;">
                                            ✅ E-Mail jetzt bestätigen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Info Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; padding: 0; font-size: 14px; color: #92400e; line-height: 1.5;">
                                            <strong>⚠️ Wichtig:</strong> Dieser Link ist 24 Stunden gültig. Falls du diese E-Mail nicht angefordert hast, kannst du sie einfach ignorieren.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Alternative Link -->
                            <p style="margin: 0 0 8px; padding: 0; font-size: 12px; color: #6b7280;">
                                Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:
                            </p>
                            <p style="margin: 0 0 32px; padding: 12px; background-color: #f9fafb; border-radius: 6px; font-size: 12px; color: #374151; word-break: break-all; font-family: monospace;">
                                <?php echo htmlspecialchars($verification_link); ?>
                            </p>
                            
                            <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Viele Grüße<br>
                                Dein <?php echo htmlspecialchars($customer_name); ?> Team
                            </p>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 32px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
                            
                            <?php if (!empty($customer_impressum)): ?>
                            <div style="margin: 0 0 20px; padding: 0 0 20px; border-bottom: 1px solid #e5e7eb;">
                                <p style="margin: 0 0 8px; padding: 0; font-size: 12px; color: #374151; font-weight: 600; text-align: center;">
                                    <?php echo htmlspecialchars($customer_name); ?>
                                </p>
                                <div style="font-size: 11px; color: #6b7280; line-height: 1.6; text-align: center;">
                                    <?php echo $customer_impressum; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <p style="margin: 0 0 16px; padding: 0; font-size: 11px; color: #9ca3af; text-align: center;">
                                Technischer E-Mail-Versand durch: Opt-in Pilot
                            </p>
                            
                            <p style="margin: 0; padding: 0; font-size: 12px; color: #6b7280; text-align: center;">
                                <a href="https://app.mehr-infos-jetzt.de/datenschutz-programm.php" style="color: #6b7280; text-decoration: none; margin: 0 8px;">Datenschutz</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>

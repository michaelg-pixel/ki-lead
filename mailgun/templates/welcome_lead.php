<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen beim Empfehlungsprogramm!</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0;">
                            <div style="font-size: 64px; line-height: 1; margin-bottom: 16px;">👋</div>
                            <h1 style="margin: 0; padding: 0; font-size: 32px; font-weight: 700; color: #ffffff; line-height: 1.2;">
                                Willkommen!
                            </h1>
                            <p style="margin: 12px 0 0; padding: 0; font-size: 18px; color: rgba(255,255,255,0.9);">
                                Schön, dass du dabei bist
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
                                willkommen beim Empfehlungsprogramm von <?php echo htmlspecialchars($customer_name); ?>! 🎉
                            </p>
                            
                            <p style="margin: 0 0 32px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Du kannst jetzt Freunde und Bekannte einladen und dir dadurch tolle Belohnungen verdienen.
                            </p>
                            
                            <!-- Info Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eff6ff; border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <h2 style="margin: 0 0 16px; padding: 0; font-size: 20px; font-weight: 700; color: #1e40af;">
                                            So funktioniert's:
                                        </h2>
                                        <ol style="margin: 0; padding-left: 20px; font-size: 15px; color: #1e3a8a; line-height: 1.8;">
                                            <li style="margin-bottom: 12px;">Teile deinen persönlichen Empfehlungslink</li>
                                            <li style="margin-bottom: 12px;">Deine Freunde melden sich über deinen Link an</li>
                                            <li style="margin-bottom: 0;">Du erhältst automatisch Belohnungen ab einer bestimmten Anzahl erfolgreicher Empfehlungen</li>
                                        </ol>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Referral Link Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 12px; padding: 0; font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.9);">
                                            DEIN PERSÖNLICHER EMPFEHLUNGSLINK
                                        </p>
                                        <div style="background-color: rgba(255,255,255,0.2); padding: 12px; border-radius: 6px; word-break: break-all;">
                                            <a href="<?php echo htmlspecialchars($referral_link); ?>" style="color: #ffffff; text-decoration: none; font-size: 14px; font-family: monospace;">
                                                <?php echo htmlspecialchars($referral_link); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo htmlspecialchars($dashboard_link); ?>" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; border-radius: 8px;">
                                            🚀 Zum Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Viel Erfolg beim Empfehlen!<br><br>
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
                                <a href="<?php echo htmlspecialchars($dashboard_link); ?>" style="color: #6b7280; text-decoration: none; margin: 0 8px;">Dashboard</a>
                                <span style="color: #d1d5db;">|</span>
                                <a href="<?php echo htmlspecialchars($unsubscribe_link); ?>" style="color: #6b7280; text-decoration: none; margin: 0 8px;">Abmelden</a>
                                <span style="color: #d1d5db;">|</span>
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

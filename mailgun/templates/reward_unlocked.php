<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Belohnung freigeschaltet!</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <!-- Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px 12px 0 0;">
                            <div style="font-size: 64px; line-height: 1; margin-bottom: 16px;">🎉</div>
                            <h1 style="margin: 0; padding: 0; font-size: 32px; font-weight: 700; color: #ffffff; line-height: 1.2;">
                                Glückwunsch!
                            </h1>
                            <p style="margin: 12px 0 0; padding: 0; font-size: 18px; color: rgba(255,255,255,0.9);">
                                Du hast eine neue Belohnung erreicht
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            
                            <!-- Greeting -->
                            <p style="margin: 0 0 24px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hallo <?php echo htmlspecialchars($lead_name); ?>,
                            </p>
                            
                            <!-- Reward Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <h2 style="margin: 0 0 12px; padding: 0; font-size: 24px; font-weight: 700; color: #ffffff;">
                                            🎁 <?php echo htmlspecialchars($reward_title); ?>
                                        </h2>
                                        <p style="margin: 0; padding: 0; font-size: 16px; color: rgba(255,255,255,0.95); line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($reward_description)); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if (!empty($reward_warning)): ?>
                            <!-- Warning Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; padding: 0; font-size: 14px; color: #92400e; line-height: 1.5;">
                                            <strong>⚠️ Wichtig:</strong> <?php echo htmlspecialchars($reward_warning); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>
                            
                            <!-- Stats -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 20px; background-color: #f9fafb; border-radius: 8px;">
                                        <p style="margin: 0 0 12px; padding: 0; font-size: 14px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                            DEINE STATISTIK
                                        </p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; color: #6b7280;">Erfolgreiche Empfehlungen</p>
                                                    <p style="margin: 4px 0 0; padding: 0; font-size: 28px; font-weight: 700; color: #1f2937;"><?php echo (int)$successful_referrals; ?></p>
                                                </td>
                                                <td width="50%" style="padding: 8px 0; text-align: right;">
                                                    <p style="margin: 0; padding: 0; font-size: 12px; color: #6b7280;">Gesammelte Punkte</p>
                                                    <p style="margin: 4px 0 0; padding: 0; font-size: 28px; font-weight: 700; color: #1f2937;"><?php echo (int)$current_points; ?></p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo htmlspecialchars($dashboard_link); ?>" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; border-radius: 8px;">
                                            🚀 Belohnung im Dashboard ansehen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Share Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eff6ff; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px; padding: 0; font-size: 14px; font-weight: 600; color: #1e40af;">
                                            💪 Mach weiter so!
                                        </p>
                                        <p style="margin: 0 0 16px; padding: 0; font-size: 14px; color: #1e3a8a; line-height: 1.5;">
                                            Teile deinen Empfehlungslink und erreiche die nächste Belohnungsstufe:
                                        </p>
                                        <div style="background-color: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #bfdbfe; word-break: break-all;">
                                            <a href="<?php echo htmlspecialchars($referral_link); ?>" style="color: #2563eb; text-decoration: none; font-size: 13px; font-family: monospace;">
                                                <?php echo htmlspecialchars($referral_link); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Closing -->
                            <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Herzliche Grüße<br>
                                Dein <?php echo htmlspecialchars($customer_name); ?> Team
                            </p>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 32px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
                            
                            <?php if (!empty($customer_impressum)): ?>
                            <!-- Impressum vom KUNDEN -->
                            <div style="margin: 0 0 20px; padding: 0 0 20px; border-bottom: 1px solid #e5e7eb;">
                                <p style="margin: 0 0 8px; padding: 0; font-size: 12px; color: #374151; font-weight: 600; text-align: center;">
                                    <?php echo htmlspecialchars($customer_name); ?>
                                </p>
                                <div style="font-size: 11px; color: #6b7280; line-height: 1.6; text-align: center;">
                                    <?php echo $customer_impressum; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Technischer Hinweis -->
                            <p style="margin: 0 0 16px; padding: 0; font-size: 11px; color: #9ca3af; text-align: center; line-height: 1.4;">
                                Technischer E-Mail-Versand durch: Opt-in Pilot
                            </p>
                            
                            <!-- Links -->
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

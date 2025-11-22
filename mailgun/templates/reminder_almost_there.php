<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast geschafft!</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 30px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px 12px 0 0;">
                            <div style="font-size: 64px; line-height: 1; margin-bottom: 16px;">🔥</div>
                            <h1 style="margin: 0; padding: 0; font-size: 32px; font-weight: 700; color: #ffffff; line-height: 1.2;">
                                Fast geschafft!
                            </h1>
                            <p style="margin: 12px 0 0; padding: 0; font-size: 18px; color: rgba(255,255,255,0.9);">
                                Nur noch <?php echo (int)$remaining_referrals; ?> Empfehlung<?php echo $remaining_referrals > 1 ? 'en' : ''; ?>
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
                                du bist ganz nah dran! 💪
                            </p>
                            
                            <!-- Progress Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 12px; padding: 0; font-size: 14px; color: rgba(255,255,255,0.9); text-align: center;">
                                            DEIN FORTSCHRITT
                                        </p>
                                        <p style="margin: 0 0 16px; padding: 0; font-size: 48px; font-weight: 700; color: #ffffff; text-align: center; line-height: 1;">
                                            <?php echo (int)$current_referrals; ?> / <?php echo (int)$required_referrals; ?>
                                        </p>
                                        <div style="background-color: rgba(255,255,255,0.2); height: 12px; border-radius: 6px; overflow: hidden;">
                                            <div style="background-color: #ffffff; height: 100%; width: <?php echo min(100, ($current_referrals / $required_referrals) * 100); ?>%; border-radius: 6px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 32px; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Dir fehlt<?php echo $remaining_referrals > 1 ? 'en' : ''; ?> nur noch <strong><?php echo (int)$remaining_referrals; ?> erfolgreiche Empfehlung<?php echo $remaining_referrals > 1 ? 'en' : ''; ?></strong> bis zur nächsten Belohnung! 🎁
                            </p>
                            
                            <!-- Next Reward Preview -->
                            <?php if (!empty($next_reward_title)): ?>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eff6ff; border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 12px; padding: 0; font-size: 14px; font-weight: 600; color: #1e40af;">
                                            DEINE NÄCHSTE BELOHNUNG
                                        </p>
                                        <h2 style="margin: 0 0 8px; padding: 0; font-size: 20px; font-weight: 700; color: #1e3a8a;">
                                            🎁 <?php echo htmlspecialchars($next_reward_title); ?>
                                        </h2>
                                        <p style="margin: 0; padding: 0; font-size: 14px; color: #1e3a8a; line-height: 1.6;">
                                            <?php echo htmlspecialchars($next_reward_description); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>
                            
                            <!-- Share Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f9fafb; border-radius: 12px; margin-bottom: 32px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="margin: 0 0 16px; padding: 0; font-size: 14px; font-weight: 600; color: #374151;">
                                            💡 Teile einfach deinen Link:
                                        </p>
                                        <div style="background-color: #ffffff; padding: 12px; border-radius: 6px; border: 2px solid #e5e7eb; word-break: break-all;">
                                            <a href="<?php echo htmlspecialchars($referral_link); ?>" style="color: #2563eb; text-decoration: none; font-size: 13px; font-family: monospace;">
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
                                            📊 Dashboard ansehen
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0; padding: 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Viel Erfolg!<br>
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

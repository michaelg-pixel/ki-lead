<?php
/**
 * Auftragsverarbeitungsvertrag (AVV) - Mailgun E-Mail-Versand
 * PERSONALISIERT mit automatischen Kundendaten
 * Ansicht und Download als PDF
 */

// Kundendaten laden wenn customer_id übergeben wird
$customerData = null;
if (isset($_GET['customer_id'])) {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT 
                company_name,
                company_email,
                first_name,
                last_name,
                street,
                postal_code,
                city,
                country,
                phone,
                tax_id,
                vat_id
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([(int)$_GET['customer_id']]);
        $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("AVV Customer Load Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auftragsverarbeitungsvertrag - Mailgun E-Mail-Versand</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.8;
            color: #1f2937;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f9fafb;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 1rem 1rem 0 0;
            margin: -2rem -2rem 2rem -2rem;
        }
        
        .header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .document {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #667eea;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-top: 2rem;
        }
        
        h3 {
            color: #4b5563;
            margin-top: 1.5rem;
        }
        
        .party-box {
            background: #f3f4f6;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .party-box strong {
            color: #667eea;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .party-box-content {
            color: #1f2937;
            line-height: 1.6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        th, td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            text-align: left;
        }
        
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #667eea;
        }
        
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            margin: 2rem 0 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
        }
        
        .highlight {
            background: #fef3c7;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .download-btn {
                display: none;
            }
            .header {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Auftragsverarbeitungsvertrag (AVV)</h1>
        <p>Mailgun E-Mail-Versand für Empfehlungsprogramm</p>
    </div>
    
    <div class="document">
        <h2>§ 1 Vertragsgegenstand</h2>
        <p>Dieser Auftragsverarbeitungsvertrag (AVV) regelt die Verarbeitung personenbezogener Daten im Rahmen des Empfehlungsprogramms gemäß Art. 28 DSGVO.</p>
        
        <div class="party-box">
            <strong>Auftraggeber (Verantwortlicher):</strong>
            <div class="party-box-content">
                <?php if ($customerData): ?>
                    <?php if (!empty($customerData['company_name'])): ?>
                        <strong><?php echo htmlspecialchars($customerData['company_name']); ?></strong><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['first_name']) || !empty($customerData['last_name'])): ?>
                        Vertreten durch: <?php echo htmlspecialchars(trim($customerData['first_name'] . ' ' . $customerData['last_name'])); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['street'])): ?>
                        <?php echo htmlspecialchars($customerData['street']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['postal_code']) || !empty($customerData['city'])): ?>
                        <?php echo htmlspecialchars($customerData['postal_code'] . ' ' . $customerData['city']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['country'])): ?>
                        <?php echo htmlspecialchars($customerData['country']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['company_email'])): ?>
                        <br>E-Mail: <?php echo htmlspecialchars($customerData['company_email']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['phone'])): ?>
                        Telefon: <?php echo htmlspecialchars($customerData['phone']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['vat_id'])): ?>
                        <br>USt-IdNr.: <?php echo htmlspecialchars($customerData['vat_id']); ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($customerData['tax_id'])): ?>
                        Steuernummer: <?php echo htmlspecialchars($customerData['tax_id']); ?><br>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="highlight">Sie als Kunde vom Optin Pilot</span><br>
                    <em style="color: #6b7280; font-size: 0.875rem;">Ihre persönlichen Daten werden nach der Registrierung automatisch hier eingefügt.</em>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="party-box">
            <strong>Auftragsverarbeiter:</strong>
            <div class="party-box-content">
                Henry Landmann<br>
                c/o Block Services<br>
                Stuttgarter Str. 106<br>
                70736 Fellbach<br>
                Deutschland
            </div>
        </div>
        
        <h2>§ 2 Art und Zweck der Verarbeitung</h2>
        
        <h3>2.1 Gegenstand der Verarbeitung</h3>
        <p>Der Auftragsverarbeiter verarbeitet personenbezogene Daten ausschließlich zur automatischen Versendung von Belohnungs-E-Mails im Rahmen Ihres Empfehlungsprogramms über den E-Mail-Dienst Mailgun.</p>
        
        <h3>2.2 Verarbeitete Datenarten</h3>
        <table>
            <tr>
                <th>Datenkategorie</th>
                <th>Zweck</th>
            </tr>
            <tr>
                <td>Name des Leads</td>
                <td>Personalisierung der E-Mail</td>
            </tr>
            <tr>
                <td>E-Mail-Adresse des Leads</td>
                <td>E-Mail-Zustellung</td>
            </tr>
            <tr>
                <td>Empfehlungsinformationen</td>
                <td>Belohnungsstatus und Fortschritt</td>
            </tr>
            <tr>
                <td>Ihr Impressum</td>
                <td>Rechtliche Anforderungen (DSGVO)</td>
            </tr>
        </table>
        
        <h3>2.3 Betroffene Personengruppen</h3>
        <p>Ihre Leads, die sich freiwillig für Ihr Freebie registriert haben und am Empfehlungsprogramm teilnehmen.</p>
        
        <h2>§ 3 Technische und organisatorische Maßnahmen</h2>
        
        <h3>3.1 Mailgun (E-Mail-Versand)</h3>
        <ul>
            <li><strong>Server-Standort:</strong> Ausschließlich EU-Server (Frankfurt, Deutschland)</li>
            <li><strong>Verschlüsselung:</strong> TLS 1.3 für E-Mail-Transport</li>
            <li><strong>Datenspeicherung:</strong> Nur für technisch notwendige Logs (max. 30 Tage)</li>
            <li><strong>Zertifizierungen:</strong> ISO 27001, SOC 2 Type II</li>
            <li><strong>DSGVO-Konformität:</strong> Standard-Vertragsklauseln (SCC)</li>
        </ul>
        
        <h3>3.2 Ihre Datenhoheit</h3>
        <ul>
            <li>Sie bleiben Verantwortlicher für Ihre Lead-Daten</li>
            <li>Daten werden NUR nach Ihrer Weisung verarbeitet</li>
            <li>Keine Weitergabe an Dritte</li>
            <li>Keine Nutzung für Werbezwecke Dritter</li>
        </ul>
        
        <h2>§ 4 Rechte und Pflichten</h2>
        
        <h3>4.1 Weisungsrecht des Auftraggebers</h3>
        <p>Der Auftragsverarbeiter verarbeitet personenbezogene Daten nur auf dokumentierte Weisung des Auftraggebers. Die Weisung erfolgt durch:</p>
        <ul>
            <li>Konfiguration der Belohnungsstufen im Dashboard</li>
            <li>Aktivierung/Deaktivierung des Empfehlungsprogramms</li>
            <li>Anpassung der E-Mail-Templates und Ihres Impressums</li>
        </ul>
        
        <h3>4.2 Vertraulichkeit</h3>
        <p>Alle mit der Verarbeitung betrauten Personen sind zur Vertraulichkeit verpflichtet.</p>
        
        <h3>4.3 Unterstützung bei Betroffenenanfragen</h3>
        <p>Der Auftragsverarbeiter unterstützt Sie bei der Beantwortung von Anfragen betroffener Personen (Auskunft, Löschung, Korrektur).</p>
        
        <h2>§ 5 Unterauftragsverarbeiter</h2>
        
        <table>
            <tr>
                <th>Dienstleister</th>
                <th>Tätigkeit</th>
                <th>Standort</th>
            </tr>
            <tr>
                <td>Mailgun (Sinch MessageMedia Deutschland GmbH)</td>
                <td>E-Mail-Versand</td>
                <td>EU (Deutschland)</td>
            </tr>
            <tr>
                <td>Hostinger</td>
                <td>Hosting der Anwendung</td>
                <td>EU (Litauen)</td>
            </tr>
        </table>
        
        <p><strong>Ihr Recht:</strong> Sie werden über Änderungen der Unterauftragsverarbeiter informiert und haben ein Widerspruchsrecht.</p>
        
        <h2>§ 6 Löschung und Rückgabe der Daten</h2>
        
        <p>Nach Beendigung der Leistungen werden alle personenbezogenen Daten:</p>
        <ul>
            <li>Auf Ihre Weisung gelöscht oder zurückgegeben</li>
            <li>Datenträger werden sicher gelöscht/vernichtet</li>
            <li>Ausnahme: Gesetzliche Aufbewahrungsfristen</li>
        </ul>
        
        <h2>§ 7 Kontroll- und Prüfrechte</h2>
        
        <p>Sie haben das Recht:</p>
        <ul>
            <li>Informationen über die Verarbeitung anzufordern</li>
            <li>Die Einhaltung dieses AVV zu überprüfen</li>
            <li>Bei Verstößen Maßnahmen zu verlangen</li>
        </ul>
        
        <h2>§ 8 Haftung und Gewährleistung</h2>
        
        <p>Bei Verstößen gegen datenschutzrechtliche Bestimmungen haften die Parteien nach den gesetzlichen Bestimmungen. Der Auftragsverarbeiter haftet nur für vorsätzliche oder grob fahrlässige Pflichtverletzungen.</p>
        
        <h2>§ 9 Vertragslaufzeit</h2>
        
        <p>Dieser Vertrag gilt für die Dauer Ihrer Nutzung des Empfehlungsprogramms und endet automatisch bei:</p>
        <ul>
            <li>Deaktivierung des Empfehlungsprogramms</li>
            <li>Kündigung Ihres Accounts</li>
            <li>Widerruf Ihrer Zustimmung</li>
        </ul>
        
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
            <p><strong>Version:</strong> Mailgun_AVV_2025_v1</p>
            <p><strong>Stand:</strong> <?php echo date('d.m.Y'); ?></p>
            <p><strong>Rechtsgrundlage:</strong> Art. 28 DSGVO</p>
        </div>
        
        <a href="#" onclick="window.print(); return false;" class="download-btn">
            📥 AVV als PDF drucken/speichern
        </a>
        
        <p style="color: #6b7280; font-size: 0.875rem; margin-top: 2rem;">
            <strong>Hinweis:</strong> Verwenden Sie die Druckfunktion Ihres Browsers (Strg+P / Cmd+P), um diesen Vertrag als PDF zu speichern. 
            Wählen Sie dort "Als PDF speichern" als Drucker aus.
        </p>
    </div>
</body>
</html>

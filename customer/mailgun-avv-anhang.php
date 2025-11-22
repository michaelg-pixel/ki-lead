<?php
/**
 * Personalisierter Mailgun AVV-Anhang
 * Anhang zum bestehenden AV-Vertrag für Mailgun als Unterauftragsverarbeiter
 */

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../config/security.php';

// Starte sichere Session
startSecureSession();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Firmendaten abrufen
    $stmt = $pdo->prepare("
        SELECT company_name, company_address, company_zip, company_city, 
               company_country, contact_person, contact_email, contact_phone
        FROM user_company_data 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $company_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Wenn keine Firmendaten vorhanden sind, zur Einstellungsseite weiterleiten
    if (!$company_data) {
        header('Location: /customer/dashboard.php?page=einstellungen&error=no_company_data');
        exit;
    }
    
    // User-Daten abrufen
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prüfe ob Mailgun-Zustimmung bereits vorhanden
    $stmt = $pdo->prepare("
        SELECT accepted_at, ip_address 
        FROM av_contract_acceptances 
        WHERE user_id = ? AND acceptance_type = 'mailgun_consent'
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $consent = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Fehler beim Laden der Daten: ' . htmlspecialchars($e->getMessage()));
}

$current_date = date('d.m.Y');
$consent_date = $consent ? date('d.m.Y', strtotime($consent['accepted_at'])) : $current_date;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailgun AVV-Anhang - <?php echo htmlspecialchars($company_data['company_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .container { box-shadow: none !important; }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
            color: #374151;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            padding: 48px;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .header {
            text-align: center;
            margin-bottom: 48px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .parties {
            background: #f9fafb;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 32px;
            border-left: 4px solid #8b5cf6;
        }
        
        .parties h3 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .party-info {
            margin-bottom: 16px;
        }
        
        .party-info strong {
            display: block;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .party-info p {
            margin: 2px 0;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 32px;
        }
        
        .section h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .section h3 {
            font-size: 18px;
            color: #374151;
            margin-top: 20px;
            margin-bottom: 12px;
        }
        
        .section p {
            margin-bottom: 16px;
            text-align: justify;
        }
        
        .section ul, .section ol {
            margin-left: 24px;
            margin-bottom: 16px;
        }
        
        .section li {
            margin-bottom: 8px;
        }
        
        .highlight {
            background: #fef3c7;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #f59e0b;
        }
        
        .important {
            background: #fee2e2;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #ef4444;
        }
        
        .info-box {
            background: #eff6ff;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #3b82f6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        
        table th, table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }
        
        table th {
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }
        
        .consent-status {
            background: #d1fae5;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid #10b981;
            text-align: center;
        }
        
        .consent-status strong {
            color: #065f46;
            font-size: 18px;
        }
        
        .end-marker {
            text-align: center;
            margin-top: 48px;
            padding-top: 24px;
            border-top: 2px solid #e5e7eb;
            color: #6b7280;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 24px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .section h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Drucken / Als PDF speichern
            </button>
            <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn btn-secondary">
                ← Zurück zum Empfehlungsprogramm
            </a>
        </div>

        <?php if ($consent): ?>
        <div class="consent-status no-print">
            <p><strong>✅ Zustimmung erteilt am: <?php echo $consent_date; ?></strong></p>
            <p style="font-size: 14px; color: #065f46; margin-top: 8px;">IP-Adresse: <?php echo htmlspecialchars($consent['ip_address']); ?></p>
        </div>
        <?php endif; ?>

        <div class="header">
            <h1>Anhang zum Auftragsverarbeitungsvertrag<br>Mailgun als Unterauftragsverarbeiter</h1>
            <p style="color: #6b7280; font-size: 14px;">Empfehlungsprogramm - Belohnungs-E-Mail-Versand</p>
            <p style="color: #6b7280; font-size: 12px; margin-top: 8px;">Version: 1.0 | Erstellt am: <?php echo $current_date; ?></p>
        </div>

        <div class="parties">
            <h3>Vertragsparteien</h3>
            
            <div class="party-info">
                <strong>Hauptauftragsverarbeiter:</strong>
                <p>Henry Landmann</p>
                <p>c/o Block Services</p>
                <p>Stuttgarter Str. 106</p>
                <p>70736 Fellbach</p>
            </div>
            
            <div class="party-info">
                <strong>Verantwortlicher (Kunde):</strong>
                <p><strong><?php echo htmlspecialchars($company_data['company_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($company_data['company_address']); ?></p>
                <p><?php echo htmlspecialchars($company_data['company_zip'] . ' ' . $company_data['company_city']); ?></p>
                <p><?php echo htmlspecialchars($company_data['company_country']); ?></p>
            </div>
        </div>

        <div class="section">
            <h2>Präambel</h2>
            <p>Dieser Anhang erweitert den bestehenden Auftragsverarbeitungsvertrag zwischen dem Verantwortlichen und Henry Landmann (Hauptauftragsverarbeiter) um die Nutzung von <strong>Mailgun Europe Limited</strong> als Unterauftragsverarbeiter für den E-Mail-Versand im Rahmen des Empfehlungsprogramms.</p>
            
            <div class="info-box">
                <p><strong>Rechtsgrundlage:</strong> Art. 28 Abs. 2 und Abs. 4 DSGVO - Einsatz eines Unterauftragsverarbeiters</p>
            </div>
        </div>

        <div class="section">
            <h2>1. Unterauftragsverarbeiter</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Dienstleister</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Mailgun Europe Limited</strong></td>
                        <td>
                            <p><strong>Adresse:</strong> 151 Clapham High Street, London, UK, SW4 7SS</p>
                            <p><strong>Server-Standort:</strong> EU (Europa) - vollständig DSGVO-konform</p>
                            <p><strong>Datenschutz:</strong> <a href="https://www.mailgun.com/legal/privacy-policy/" target="_blank">mailgun.com/legal/privacy-policy</a></p>
                            <p><strong>AVV:</strong> Mailgun schließt standardmäßig DPA (Data Processing Agreement) gemäß DSGVO</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="highlight">
                <p><strong>Wichtig:</strong> Alle E-Mails werden ausschließlich über <strong>EU-Server</strong> versendet. Es findet keine Übermittlung in Drittländer statt.</p>
            </div>
        </div>

        <div class="section">
            <h2>2. Zweck der Datenverarbeitung durch Mailgun</h2>
            <p>Mailgun wird ausschließlich für folgende Zwecke eingesetzt:</p>
            <ul>
                <li><strong>Versand von Belohnungs-E-Mails:</strong> Automatische Benachrichtigungen an Leads bei Erreichen von Empfehlungsstufen</li>
                <li><strong>Tracking von Zustellungen:</strong> Technisch notwendige Verfolgung von Zustellstatus, Öffnungen und Klicks für Qualitätssicherung</li>
                <li><strong>SMTP-Relay-Service:</strong> Technische Bereitstellung der E-Mail-Infrastruktur</li>
            </ul>
            
            <p><strong>NICHT genutzt wird Mailgun für:</strong></p>
            <ul>
                <li>Marketing-E-Mails an Dritte</li>
                <li>Weitergabe von Daten an andere Mailgun-Kunden</li>
                <li>Profiling oder Datenanalyse über die technische Zustellung hinaus</li>
            </ul>
        </div>

        <div class="section">
            <h2>3. Verarbeitete Daten</h2>
            <p>Mailgun verarbeitet folgende personenbezogene Daten:</p>
            <ul>
                <li><strong>E-Mail-Adresse:</strong> Des Lead-Empfängers</li>
                <li><strong>Name:</strong> Falls vom Verantwortlichen bereitgestellt (optional)</li>
                <li><strong>E-Mail-Inhalt:</strong> Belohnungs-Benachrichtigung inkl. Impressum des Verantwortlichen</li>
                <li><strong>Metadaten:</strong> Zeitstempel, IP-Adresse des Empfängers (bei Öffnung), Klick-Tracking</li>
            </ul>
            
            <div class="important">
                <p><strong>Hinweis:</strong> Der Verantwortliche bleibt für die rechtmäßige Erhebung der E-Mail-Adressen verantwortlich (Double Opt-in, Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO).</p>
            </div>
        </div>

        <div class="section">
            <h2>4. Speicherdauer bei Mailgun</h2>
            <p>Mailgun speichert die Daten wie folgt:</p>
            <ul>
                <li><strong>E-Mail-Logs:</strong> 30 Tage (für technische Zustellberichte)</li>
                <li><strong>Event-Tracking:</strong> 30 Tage (Öffnungen, Klicks)</li>
                <li><strong>Dauerhaftigkeit:</strong> Keine dauerhafte Speicherung - nach 30 Tagen automatische Löschung</li>
            </ul>
            
            <p>Der Hauptauftragsverarbeiter (Henry Landmann) speichert die Event-Daten in der eigenen Datenbank für statistische Auswertungen (gemäß Haupt-AVV).</p>
        </div>

        <div class="section">
            <h2>5. Technische und organisatorische Maßnahmen (TOMs)</h2>
            <p>Mailgun setzt folgende Sicherheitsmaßnahmen um:</p>
            <ul>
                <li><strong>TLS 1.2/1.3 Verschlüsselung:</strong> Alle E-Mails werden verschlüsselt übertragen</li>
                <li><strong>DKIM/SPF/DMARC:</strong> E-Mail-Authentifizierung zur Spam-Vermeidung</li>
                <li><strong>ISO 27001 Zertifizierung:</strong> Mailgun ist ISO 27001 zertifiziert</li>
                <li><strong>Zugriffskontrolle:</strong> Nur autorisierte Mitarbeiter haben Zugriff auf Server</li>
                <li><strong>Monitoring:</strong> 24/7 Überwachung der Infrastruktur</li>
                <li><strong>EU-Datenzentren:</strong> Physische Server in Europa (keine Drittland-Übermittlung)</li>
            </ul>
            
            <div class="info-box">
                <p><strong>Zertifizierungen:</strong> ISO 27001, SOC 2 Type II, GDPR-konform</p>
            </div>
        </div>

        <div class="section">
            <h2>6. Impressumspflicht in E-Mails</h2>
            <p>Gemäß § 5 TMG und Art. 7 UWG muss jede geschäftliche E-Mail ein Impressum enthalten:</p>
            
            <div class="highlight">
                <p><strong>Automatische Integration:</strong> Das Impressum des Verantwortlichen wird automatisch in jede Belohnungs-E-Mail eingefügt.</p>
                <p style="margin-top: 8px;"><strong>Verantwortlich für den Inhalt:</strong> Der Verantwortliche (Kunde) ist für die Richtigkeit und Vollständigkeit des Impressums verantwortlich.</p>
            </div>
            
            <p>Das Impressum kann in den Einstellungen des KI-Lead-Systems hinterlegt werden.</p>
        </div>

        <div class="section">
            <h2>7. Rechte der betroffenen Personen</h2>
            <p>Leads haben folgende Rechte:</p>
            <ul>
                <li><strong>Auskunft (Art. 15 DSGVO):</strong> Anfragen über gespeicherte Daten</li>
                <li><strong>Löschung (Art. 17 DSGVO):</strong> "Recht auf Vergessenwerden"</li>
                <li><strong>Widerspruch (Art. 21 DSGVO):</strong> Widerspruch gegen die Verarbeitung</li>
                <li><strong>Datenportabilität (Art. 20 DSGVO):</strong> Datenübertragung an anderen Verantwortlichen</li>
            </ul>
            
            <p><strong>Abmeldung:</strong> Jede E-Mail enthält einen Abmelde-Link (Unsubscribe), der die weitere Verarbeitung beendet.</p>
            
            <div class="important">
                <p><strong>Haftung:</strong> Der Verantwortliche ist für die Bearbeitung von Betroffenenanfragen zuständig. Der Hauptauftragsverarbeiter unterstützt bei der technischen Umsetzung.</p>
            </div>
        </div>

        <div class="section">
            <h2>8. Weisungsbefugnis</h2>
            <p>Der Hauptauftragsverarbeiter (Henry Landmann) handelt im Auftrag des Verantwortlichen. Mailgun handelt wiederum im Auftrag des Hauptauftragsverarbeiters.</p>
            
            <p><strong>Weisungskette:</strong></p>
            <ol>
                <li><strong>Verantwortlicher (Kunde)</strong> → gibt Weisungen an</li>
                <li><strong>Hauptauftragsverarbeiter (Henry Landmann)</strong> → gibt technische Weisungen an</li>
                <li><strong>Unterauftragsverarbeiter (Mailgun)</strong> → führt E-Mail-Versand aus</li>
            </ol>
        </div>

        <div class="section">
            <h2>9. Haftung und Schadensersatz</h2>
            <p>Gemäß Art. 82 DSGVO:</p>
            <ul>
                <li>Bei Datenschutzverstößen durch Mailgun haftet der Hauptauftragsverarbeiter gegenüber dem Verantwortlichen</li>
                <li>Der Hauptauftragsverarbeiter kann sich gegenüber dem Verantwortlichen exkulpieren, wenn er nachweist, dass er für den Schaden nicht verantwortlich ist</li>
                <li>Mailgun haftet direkt gegenüber betroffenen Personen gemäß DSGVO</li>
            </ul>
        </div>

        <div class="section">
            <h2>10. Vertragslaufzeit</h2>
            <p>Dieser Anhang gilt ab dem Zeitpunkt der elektronischen Zustimmung und endet:</p>
            <ul>
                <li>Mit Kündigung des Haupt-AVV oder</li>
                <li>Bei Deaktivierung des Empfehlungsprogramms durch den Verantwortlichen</li>
            </ul>
            
            <p><strong>Datenlöschung nach Vertragsende:</strong> Alle bei Mailgun gespeicherten Daten werden automatisch nach 30 Tagen gelöscht.</p>
        </div>

        <div class="section">
            <h2>11. Zustimmung und Inkrafttreten</h2>
            
            <?php if ($consent): ?>
            <div class="consent-status">
                <p><strong>✅ Dieser Anhang wurde elektronisch akzeptiert</strong></p>
                <p style="font-size: 14px; margin-top: 8px;">Datum: <?php echo $consent_date; ?></p>
                <p style="font-size: 12px; margin-top: 4px;">IP-Adresse: <?php echo htmlspecialchars($consent['ip_address']); ?></p>
            </div>
            <?php else: ?>
            <div class="info-box">
                <p><strong>Elektronische Zustimmung erforderlich</strong></p>
                <p style="margin-top: 8px;">Bitte akzeptieren Sie diesen Anhang über das Empfehlungsprogramm-Dashboard.</p>
            </div>
            <?php endif; ?>
            
            <p>Durch die elektronische Zustimmung bestätigt der Verantwortliche:</p>
            <ul>
                <li>Die Kenntnisnahme dieses Anhangs</li>
                <li>Die Zustimmung zum Einsatz von Mailgun als Unterauftragsverarbeiter</li>
                <li>Die Einhaltung aller datenschutzrechtlichen Vorgaben gemäß DSGVO</li>
            </ul>
        </div>

        <div class="end-marker">
            <p>Ende des AVV-Anhangs</p>
            <p style="font-size: 12px; margin-top: 8px;">Dieser Anhang ist integraler Bestandteil des Hauptauftragsverarbeitungsvertrags</p>
        </div>
    </div>
</body>
</html>
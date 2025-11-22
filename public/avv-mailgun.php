<?php
/**
 * Auftragsverarbeitungsvertrag (AVV) - Mailgun E-Mail-Versand
 * PERSONALISIERT mit automatischen Kundendaten
 * Ansicht und Download als PDF
 * OPTIMIERT: Mobile-First, responsive Tabellen, erweiterte rechtliche Absicherung
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
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.8;
            color: #1f2937;
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
            background: #f9fafb;
        }
        
        @media (min-width: 768px) {
            body {
                padding: 2rem;
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem 1rem;
            border-radius: 1rem 1rem 0 0;
            margin: -1rem -1rem 1.5rem -1rem;
        }
        
        @media (min-width: 768px) {
            .header {
                padding: 2rem;
                margin: -2rem -2rem 2rem -2rem;
            }
        }
        
        .header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            word-wrap: break-word;
        }
        
        @media (min-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .header p {
                font-size: 1rem;
            }
        }
        
        .document {
            background: white;
            padding: 1.5rem 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @media (min-width: 768px) {
            .document {
                padding: 2rem;
            }
        }
        
        h2 {
            color: #667eea;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-top: 2rem;
            font-size: 1.3rem;
            word-wrap: break-word;
        }
        
        @media (min-width: 768px) {
            h2 {
                font-size: 1.5rem;
            }
        }
        
        h3 {
            color: #4b5563;
            margin-top: 1.5rem;
            font-size: 1.1rem;
        }
        
        @media (min-width: 768px) {
            h3 {
                font-size: 1.2rem;
            }
        }
        
        .party-box {
            background: #f3f4f6;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 1rem 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .party-box strong {
            color: #667eea;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        @media (min-width: 768px) {
            .party-box strong {
                font-size: 1.1rem;
            }
        }
        
        .party-box-content {
            color: #1f2937;
            line-height: 1.6;
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .party-box-content {
                font-size: 1rem;
            }
        }
        
        /* Responsive Tabellen */
        .table-wrapper {
            overflow-x: auto;
            margin: 1rem 0;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 300px;
            font-size: 0.85rem;
        }
        
        @media (min-width: 768px) {
            table {
                font-size: 1rem;
            }
        }
        
        th, td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem 0.5rem;
            text-align: left;
            word-wrap: break-word;
        }
        
        @media (min-width: 768px) {
            th, td {
                padding: 0.75rem;
            }
        }
        
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #667eea;
            position: sticky;
            top: 0;
        }
        
        /* Mobile: Stapel-Layout für Tabellen */
        @media (max-width: 640px) {
            .table-mobile-stack {
                display: block;
            }
            
            .table-mobile-stack thead {
                display: none;
            }
            
            .table-mobile-stack tbody,
            .table-mobile-stack tr,
            .table-mobile-stack td {
                display: block;
                width: 100%;
            }
            
            .table-mobile-stack tr {
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                overflow: hidden;
            }
            
            .table-mobile-stack td {
                padding: 0.75rem;
                border: none;
                border-bottom: 1px solid #e5e7eb;
                position: relative;
                padding-left: 40%;
            }
            
            .table-mobile-stack td:last-child {
                border-bottom: none;
            }
            
            .table-mobile-stack td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.75rem;
                font-weight: 600;
                color: #667eea;
                width: 35%;
            }
        }
        
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            margin: 2rem 0 1rem 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: all 0.3s;
            width: 100%;
            text-align: center;
            font-size: 0.95rem;
        }
        
        @media (min-width: 640px) {
            .download-btn {
                width: auto;
                padding: 1rem 2rem;
                font-size: 1rem;
            }
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
        
        ul, ol {
            padding-left: 1.5rem;
        }
        
        li {
            margin: 0.5rem 0;
        }
        
        p {
            margin: 1rem 0;
        }
        
        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0.25rem;
        }
        
        .alert-box strong {
            color: #92400e;
            display: block;
            margin-bottom: 0.5rem;
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
            .table-mobile-stack thead {
                display: table-header-group !important;
            }
            .table-mobile-stack tbody,
            .table-mobile-stack tr,
            .table-mobile-stack td {
                display: table-row-group !important;
                display: table-row !important;
                display: table-cell !important;
            }
            .table-mobile-stack td::before {
                display: none !important;
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
                Deutschland<br>
                <br>
                <strong>Datenschutzanfragen:</strong><br>
                E-Mail: datenschutz@mehr-infos-jetzt.de
            </div>
        </div>
        
        <h2>§ 2 Art und Zweck der Verarbeitung</h2>
        
        <h3>2.1 Gegenstand der Verarbeitung</h3>
        <p>Der Auftragsverarbeiter verarbeitet personenbezogene Daten ausschließlich zur automatischen Versendung von Belohnungs-E-Mails im Rahmen Ihres Empfehlungsprogramms über den E-Mail-Dienst Mailgun.</p>
        
        <h3>2.2 Verarbeitete Datenarten</h3>
        <div class="table-wrapper">
            <table class="table-mobile-stack">
                <thead>
                    <tr>
                        <th>Datenkategorie</th>
                        <th>Zweck</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Datenkategorie">Name des Leads</td>
                        <td data-label="Zweck">Personalisierung der E-Mail</td>
                    </tr>
                    <tr>
                        <td data-label="Datenkategorie">E-Mail-Adresse des Leads</td>
                        <td data-label="Zweck">E-Mail-Zustellung</td>
                    </tr>
                    <tr>
                        <td data-label="Datenkategorie">Empfehlungsinformationen</td>
                        <td data-label="Zweck">Belohnungsstatus und Fortschritt</td>
                    </tr>
                    <tr>
                        <td data-label="Datenkategorie">Ihr Impressum</td>
                        <td data-label="Zweck">Rechtliche Anforderungen (DSGVO)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h3>2.3 Betroffene Personengruppen</h3>
        <p>Ihre Leads, die sich freiwillig für Ihr Freebie registriert haben und am Empfehlungsprogramm teilnehmen.</p>
        
        <h3>2.4 Dauer der Verarbeitung</h3>
        <p>Die Verarbeitung erfolgt für die Dauer der aktiven Teilnahme am Empfehlungsprogramm. E-Mail-Logs werden bei Mailgun maximal 30 Tage gespeichert und dann automatisch gelöscht.</p>
        
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
        <p>Alle mit der Verarbeitung betrauten Personen sind zur Vertraulichkeit verpflichtet und wurden auf das Datengeheimnis nach Art. 28 Abs. 3 lit. b DSGVO verpflichtet.</p>
        
        <h3>4.3 Unterstützung bei Betroffenenanfragen</h3>
        <p>Der Auftragsverarbeiter unterstützt Sie bei der Beantwortung von Anfragen betroffener Personen (Auskunft, Löschung, Korrektur) und stellt Ihnen die notwendigen Informationen innerhalb von 48 Stunden zur Verfügung.</p>
        
        <h3>4.4 Meldepflicht bei Datenschutzverletzungen</h3>
        <div class="alert-box">
            <strong>⚠️ Wichtig:</strong>
            <p style="margin: 0;">Der Auftragsverarbeiter meldet Verletzungen des Schutzes personenbezogener Daten unverzüglich, spätestens jedoch innerhalb von 24 Stunden nach Kenntnisnahme, an den Auftraggeber. Die Meldung erfolgt per E-Mail und enthält mindestens:</p>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <li>Art der Verletzung</li>
                <li>Betroffene Datenkategorien und Personengruppen</li>
                <li>Wahrscheinliche Folgen</li>
                <li>Ergriffene oder vorgeschlagene Maßnahmen</li>
            </ul>
        </div>
        
        <h2>§ 5 Unterauftragsverarbeiter</h2>
        
        <div class="table-wrapper">
            <table class="table-mobile-stack">
                <thead>
                    <tr>
                        <th>Dienstleister</th>
                        <th>Tätigkeit</th>
                        <th>Standort</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Dienstleister">Mailgun (Sinch MessageMedia Deutschland GmbH)</td>
                        <td data-label="Tätigkeit">E-Mail-Versand</td>
                        <td data-label="Standort">EU (Deutschland)</td>
                    </tr>
                    <tr>
                        <td data-label="Dienstleister">Hostinger International Ltd.</td>
                        <td data-label="Tätigkeit">Hosting der Anwendung</td>
                        <td data-label="Standort">EU (Litauen)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p><strong>Ihr Recht:</strong> Sie werden über Änderungen der Unterauftragsverarbeiter mindestens 14 Tage im Voraus per E-Mail informiert und haben ein Widerspruchsrecht.</p>
        
        <h2>§ 6 Löschung und Rückgabe der Daten</h2>
        
        <p>Nach Beendigung der Leistungen werden alle personenbezogenen Daten:</p>
        <ul>
            <li>Auf Ihre schriftliche Weisung innerhalb von 30 Tagen gelöscht oder zurückgegeben</li>
            <li>Datenträger werden nach BSI-Standard sicher gelöscht/vernichtet</li>
            <li>Ausnahme: Gesetzliche Aufbewahrungsfristen (z.B. steuerrechtliche Pflichten)</li>
            <li>Sie erhalten eine schriftliche Bestätigung über die vollständige Löschung</li>
        </ul>
        
        <h2>§ 7 Kontroll- und Prüfrechte</h2>
        
        <p>Sie haben das Recht:</p>
        <ul>
            <li>Informationen über die Verarbeitung jederzeit anzufordern</li>
            <li>Die Einhaltung dieses AVV zu überprüfen (nach vorheriger Ankündigung)</li>
            <li>Bei Verstößen unverzügliche Maßnahmen zu verlangen</li>
            <li>Auskunft über eingesetzte technische und organisatorische Maßnahmen zu erhalten</li>
        </ul>
        
        <h2>§ 8 Haftung und Gewährleistung</h2>
        
        <p>Bei Verstößen gegen datenschutzrechtliche Bestimmungen haften die Parteien nach den gesetzlichen Bestimmungen gemäß Art. 82 DSGVO. Der Auftragsverarbeiter haftet nur für vorsätzliche oder grob fahrlässige Pflichtverletzungen im Rahmen der Auftragsverarbeitung.</p>
        
        <h2>§ 9 Vertragslaufzeit und Beendigung</h2>
        
        <p>Dieser Vertrag gilt für die Dauer Ihrer Nutzung des Empfehlungsprogramms und endet automatisch bei:</p>
        <ul>
            <li>Deaktivierung des Empfehlungsprogramms</li>
            <li>Kündigung Ihres Accounts</li>
            <li>Widerruf Ihrer Zustimmung zur Datenverarbeitung</li>
            <li>Außerordentlicher Kündigung aus wichtigem Grund (z.B. schwere Vertragsverletzung)</li>
        </ul>
        
        <p>Nach Vertragsende gelten die Regelungen aus § 6 (Löschung und Rückgabe der Daten).</p>
        
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
            <p><strong>Version:</strong> Mailgun_AVV_2025_v1.1</p>
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
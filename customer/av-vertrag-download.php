<?php
/**
 * Personalisierter AV-Vertrag
 * Generiert einen personalisierten AV-Vertrag basierend auf den Firmendaten des Kunden
 */

session_start();

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
    
} catch (Exception $e) {
    die('Fehler beim Laden der Daten: ' . htmlspecialchars($e->getMessage()));
}

$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auftragsverarbeitungsvertrag - <?php echo htmlspecialchars($company_data['company_name']); ?></title>
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
        
        .end-marker {
            text-align: center;
            margin-top: 48px;
            padding-top: 24px;
            border-top: 2px solid #e5e7eb;
            color: #6b7280;
            font-style: italic;
        }
        
        .signature-section {
            margin-top: 48px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }
        
        .signature-box {
            border: 1px solid #e5e7eb;
            padding: 24px;
            border-radius: 8px;
        }
        
        .signature-box h4 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 48px;
        }
        
        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 48px;
            padding-top: 8px;
            font-size: 12px;
            color: #6b7280;
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
            
            .signature-section {
                grid-template-columns: 1fr;
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
            <a href="/customer/dashboard.php?page=einstellungen" class="btn btn-secondary">
                ← Zurück zu Einstellungen
            </a>
        </div>

        <div class="header">
            <h1>Auftragsverarbeitungsvertrag (AV-Vertrag)<br>gemäß Art. 28 DSGVO</h1>
            <p style="color: #6b7280; font-size: 14px;">KI-Lead-System</p>
            <p style="color: #6b7280; font-size: 12px; margin-top: 8px;">Erstellt am: <?php echo $current_date; ?></p>
        </div>

        <div class="parties">
            <h3>Vertragsparteien</h3>
            
            <div class="party-info">
                <strong>Auftragsverarbeiter:</strong>
                <p>Henry Landmann</p>
                <p>c/o Block Services</p>
                <p>Stuttgarter Str. 106</p>
                <p>70736 Fellbach</p>
            </div>
            
            <div class="party-info">
                <strong>Verantwortlicher:</strong>
                <p><strong><?php echo htmlspecialchars($company_data['company_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($company_data['company_address']); ?></p>
                <p><?php echo htmlspecialchars($company_data['company_zip'] . ' ' . $company_data['company_city']); ?></p>
                <p><?php echo htmlspecialchars($company_data['company_country']); ?></p>
                <?php if (!empty($company_data['contact_person'])): ?>
                <p>Ansprechpartner: <?php echo htmlspecialchars($company_data['contact_person']); ?></p>
                <?php endif; ?>
                <?php if (!empty($company_data['contact_email'])): ?>
                <p>E-Mail: <?php echo htmlspecialchars($company_data['contact_email']); ?></p>
                <?php endif; ?>
                <?php if (!empty($company_data['contact_phone'])): ?>
                <p>Telefon: <?php echo htmlspecialchars($company_data['contact_phone']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>1. Vertragsgegenstand</h2>
            <p>Dieser Vertrag regelt die Verarbeitung personenbezogener Daten durch den Auftragsverarbeiter im Rahmen der Bereitstellung des KI-Lead-Systems. Das System ermöglicht die Erfassung, Speicherung, Verwaltung und Weiterleitung von Leads sowie den Betrieb eines Empfehlungsprogramms.</p>
            <div class="info-box">
                <p><strong>Wichtig:</strong> Die Verarbeitung erfolgt ausschließlich im Rahmen der Weisungen des Verantwortlichen.</p>
            </div>
        </div>

        <div class="section">
            <h2>2. Art und Zweck der Verarbeitung</h2>
            <ul>
                <li>Speicherung und Verwaltung von personenbezogenen Kontaktdaten</li>
                <li>Technische Bereitstellung einer Lead-Datenbank</li>
                <li>Verarbeitung von Empfehlungs-IDs zur Verfolgung von Lead-Ketten</li>
                <li>Automatisierte Versandprozesse für Benachrichtigungs-E-Mails</li>
            </ul>
            <p><strong>Zweck:</strong> Unterstützung des Verantwortlichen im digitalen Marketing und in der Lead-Generierung.</p>
        </div>

        <div class="section">
            <h2>3. Art der Daten</h2>
            <p>Verarbeitet werden insbesondere:</p>
            <ul>
                <li>E-Mail-Adressen</li>
                <li>Name (optional)</li>
                <li>Zeitstempel der Registrierung</li>
                <li>Zuordnung zu Empfehlungsquellen (Referral-IDs)</li>
                <li>Interaktions- und Nutzungsdaten</li>
            </ul>
            <div class="important">
                <p><strong>Hinweis:</strong> Es werden keine besonderen Kategorien personenbezogener Daten nach Art. 9 DSGVO verarbeitet.</p>
            </div>
        </div>

        <div class="section">
            <h2>4. Kategorien betroffener Personen</h2>
            <ul>
                <li>Website-Besucher</li>
                <li>Interessenten</li>
                <li>Leads</li>
                <li>Kunden des Verantwortlichen</li>
            </ul>
        </div>

        <div class="section">
            <h2>5. Pflichten des Verantwortlichen (Kunden)</h2>
            <p>Der Verantwortliche bestätigt, dass:</p>
            <ol>
                <li>Der Erwerb und die Nutzung personenbezogener Daten rechtmäßig erfolgt.</li>
                <li>Eine gültige Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO für E-Mail-Marketing vorliegt.</li>
                <li>Die Anforderungen des UWG (Anti-Spam-Gesetz) eingehalten werden.</li>
                <li>Ein gültiges Impressum und eine Datenschutzerklärung bereitgestellt werden.</li>
                <li>Die Daten ausschließlich für den vorgesehenen Zweck genutzt werden.</li>
            </ol>
            <div class="important">
                <p><strong>Haftungsausschluss:</strong> Der Auftragsverarbeiter haftet nicht für Rechtsverstöße des Verantwortlichen.</p>
            </div>
        </div>

        <div class="section">
            <h2>6. Pflichten des Auftragsverarbeiters</h2>
            <p>Der Auftragsverarbeiter verpflichtet sich:</p>
            <ul>
                <li>Daten ausschließlich im Rahmen dieses Vertrages zu verarbeiten,</li>
                <li>Daten nicht an unberechtigte Dritte weiterzugeben,</li>
                <li>angemessene technische und organisatorische Maßnahmen (TOMs) umzusetzen,</li>
                <li>Zugriffe auf Daten nur befugten Personen zu ermöglichen.</li>
            </ul>
        </div>

        <div class="section">
            <h2>7. Einsatz von Unterauftragsverarbeitern</h2>
            <p>Zur technischen Bereitstellung werden folgende Unterauftragsverarbeiter eingesetzt:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Dienstleister</th>
                        <th>Zweck</th>
                        <th>Standort</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Hostinger International</strong></td>
                        <td>Serverhosting / Datenbank</td>
                        <td>EU (Litauen)</td>
                    </tr>
                    <tr>
                        <td><strong>CloudPanel / Serververwaltung</strong></td>
                        <td>Systembetrieb / Kontrolle</td>
                        <td>EU</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="info-box">
                <p><strong>Datenschutz:</strong> Es findet keine Übermittlung in Drittländer ohne angemessenes Datenschutzniveau statt.</p>
            </div>
        </div>

        <div class="section">
            <h2>8. Technische und organisatorische Maßnahmen (TOMs)</h2>
            <p>Der Auftragsverarbeiter gewährleistet u. a.:</p>
            <ul>
                <li>SSL-/TLS-Verschlüsselung</li>
                <li>Passwort- und Zugriffsschutz</li>
                <li>System- und Zugriff-Logging</li>
                <li>Regelmäßige Sicherheitsupdates</li>
                <li>Automatisierte Backups innerhalb der EU</li>
            </ul>
        </div>

        <div class="section">
            <h2>9. Löschung und Rückgabe von Daten</h2>
            <p>Nach Vertragsende:</p>
            <ul>
                <li>erfolgt die Löschung aller verarbeiteten Daten nach spätestens 30 Tagen,</li>
                <li>sofern keine gesetzlichen Aufbewahrungspflichten dem entgegenstehen.</li>
            </ul>
        </div>

        <div class="section">
            <h2>10. Haftung</h2>
            <p>Der Auftragsverarbeiter haftet ausschließlich bei vorsätzlichem oder grob fahrlässigem Verhalten. Eine Haftung für entgangene Gewinne oder mittelbare Schäden wird ausgeschlossen.</p>
            <div class="highlight">
                <p><strong>Freistellung:</strong> Der Verantwortliche stellt den Auftragsverarbeiter von Ansprüchen Dritter frei, wenn diese aus einer rechtswidrigen Nutzung des Systems durch den Verantwortlichen entstehen.</p>
            </div>
        </div>

        <div class="section">
            <h2>11. Laufzeit</h2>
            <p>Dieser Vertrag tritt mit dem Zeitpunkt der Registrierung in Kraft und gilt solange die Nutzung des KI-Lead-Systems besteht. Eine gesonderte Unterschrift ist nicht erforderlich. Die Zustimmung erfolgt elektronisch über die Registrierungsseite.</p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <h4>Auftragsverarbeiter</h4>
                <div class="signature-line">
                    <p>Ort, Datum</p>
                    <p style="margin-top: 8px;">Henry Landmann</p>
                </div>
            </div>
            
            <div class="signature-box">
                <h4>Verantwortlicher</h4>
                <div class="signature-line">
                    <p>Ort, Datum</p>
                    <p style="margin-top: 8px;"><?php echo htmlspecialchars($company_data['company_name']); ?></p>
                </div>
            </div>
        </div>

        <div class="end-marker">
            <p>Ende des Vertrags</p>
        </div>
    </div>
</body>
</html>

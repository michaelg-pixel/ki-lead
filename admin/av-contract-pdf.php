<?php
/**
 * PDF-Generator für AV-Vertrags-Zustimmungen
 * 
 * Erstellt rechtssichere PDF-Dokumentation für DSGVO-Nachweispflicht
 * gem. Art. 28 DSGVO
 */

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../config/security.php';

// Starte sichere Session
startSecureSession();

// Login-Check
requireLogin('/public/login.php');

// Admin-Rollen-Prüfung
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

require_once '../config/database.php';

// ID validieren
$acceptance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($acceptance_id <= 0) {
    die('Ungültige ID');
}

try {
    $pdo = getDBConnection();
    
    // Daten laden
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            u.name as user_name,
            u.email as user_email,
            u.company_name,
            u.company_email,
            a.accepted_at,
            a.ip_address,
            a.user_agent,
            a.av_contract_version,
            a.acceptance_type,
            a.created_at
        FROM av_contract_acceptances a
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?
    ");
    
    $stmt->execute([$acceptance_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        die('Zustimmung nicht gefunden');
    }
    
    // Type Labels
    $type_labels = [
        'registration' => 'Erstregistrierung',
        'update' => 'Aktualisierung',
        'renewal' => 'Erneuerung',
        'mailgun_consent' => 'Mailgun E-Mail-Versand + AVV'
    ];
    
    $type_display = $type_labels[$data['acceptance_type']] ?? ucfirst($data['acceptance_type']);
    
    // Rechtsgrundlagen
    $legal_basis = [
        'registration' => 'Art. 28 DSGVO - Auftragsverarbeiter',
        'update' => 'Art. 28 DSGVO - Auftragsverarbeiter (Aktualisierung)',
        'renewal' => 'Art. 28 DSGVO - Auftragsverarbeiter (Erneuerung)',
        'mailgun_consent' => 'Art. 28 DSGVO - Auftragsverarbeiter (Mailgun + AVV)'
    ];
    
    $legal_text = $legal_basis[$data['acceptance_type']] ?? 'Art. 28 DSGVO';
    
} catch (PDOException $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    die('Fehler beim Laden der Daten');
}

// PDF generieren (HTML-basiert für Druckbarkeit)
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AV-Vertrag Zustimmung - <?= htmlspecialchars($data['user_name']) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .pdf-content {
            padding: 60px;
        }
        
        .header {
            border-bottom: 4px solid #8b5cf6;
            padding-bottom: 30px;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .doc-id {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .doc-id strong {
            color: #8b5cf6;
        }
        
        .section {
            margin-bottom: 35px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 12px 20px;
        }
        
        .info-label {
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 14px;
        }
        
        .info-value strong {
            color: #8b5cf6;
        }
        
        .highlight-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .highlight-box h3 {
            color: #065f46;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .highlight-box p {
            color: #047857;
            font-size: 14px;
            line-height: 1.8;
        }
        
        .legal-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
        }
        
        .legal-box h3 {
            color: #92400e;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .legal-box p {
            color: #78350f;
            font-size: 13px;
            line-height: 1.8;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .signature-line {
            margin-top: 60px;
            padding-top: 2px;
            border-top: 1px solid #1f2937;
            width: 300px;
        }
        
        .signature-label {
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .download-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(139, 92, 246, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(139, 92, 246, 0.4);
        }
        
        @page {
            margin: 2cm;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <button onclick="window.print()" class="download-btn no-print">
        <i class="fas fa-download"></i>
        PDF herunterladen
    </button>
    
    <div class="container">
        <div class="pdf-content">
            <div class="header">
                <h1>🔒 Auftragsverarbeitungsvertrag</h1>
                <div class="subtitle">Dokumentation der rechtsverbindlichen Zustimmung gem. Art. 28 DSGVO</div>
            </div>
            
            <div class="doc-id">
                <strong>Dokument-ID:</strong> AVV-<?= str_pad($data['id'], 8, '0', STR_PAD_LEFT) ?><br>
                <strong>Ausstellungsdatum:</strong> <?= date('d.m.Y H:i:s') ?> Uhr
            </div>
            
            <div class="section">
                <div class="section-title">Kundendaten</div>
                <div class="info-grid">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><strong><?= htmlspecialchars($data['user_name']) ?></strong></div>
                    
                    <div class="info-label">E-Mail:</div>
                    <div class="info-value"><?= htmlspecialchars($data['user_email']) ?></div>
                    
                    <?php if (!empty($data['company_name'])): ?>
                    <div class="info-label">Firma:</div>
                    <div class="info-value"><?= htmlspecialchars($data['company_name']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['company_email'])): ?>
                    <div class="info-label">Firmen-E-Mail:</div>
                    <div class="info-value"><?= htmlspecialchars($data['company_email']) ?></div>
                    <?php endif; ?>
                    
                    <div class="info-label">Kunden-ID:</div>
                    <div class="info-value">#<?= $data['user_id'] ?></div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Zustimmungsdetails</div>
                <div class="info-grid">
                    <div class="info-label">Art der Zustimmung:</div>
                    <div class="info-value"><strong><?= $type_display ?></strong></div>
                    
                    <div class="info-label">Zeitpunkt:</div>
                    <div class="info-value"><?= date('d.m.Y', strtotime($data['accepted_at'])) ?> um <?= date('H:i:s', strtotime($data['accepted_at'])) ?> Uhr</div>
                    
                    <div class="info-label">AV-Vertragsversion:</div>
                    <div class="info-value"><?= htmlspecialchars($data['av_contract_version']) ?></div>
                    
                    <div class="info-label">IP-Adresse:</div>
                    <div class="info-value" style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($data['ip_address']) ?></div>
                    
                    <div class="info-label">User-Agent:</div>
                    <div class="info-value" style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($data['user_agent']) ?></div>
                </div>
            </div>
            
            <div class="highlight-box">
                <h3>✅ Rechtsgültige Zustimmung erteilt</h3>
                <p>
                    Der oben genannte Kunde hat am <strong><?= date('d.m.Y', strtotime($data['accepted_at'])) ?> um <?= date('H:i:s', strtotime($data['accepted_at'])) ?> Uhr</strong> 
                    seine rechtsverbindliche Zustimmung zur Auftragsverarbeitung gemäß Art. 28 DSGVO erteilt.
                </p>
            </div>
            
            <div class="section">
                <div class="section-title">Rechtsgrundlage</div>
                <p style="font-size: 14px; line-height: 1.8;">
                    <strong>Datenschutz-Grundverordnung (DSGVO) - Art. 28:</strong><br>
                    <?= $legal_text ?>
                </p>
                
                <?php if ($data['acceptance_type'] === 'mailgun_consent'): ?>
                <div style="margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 6px;">
                    <p style="font-size: 13px; line-height: 1.8; color: #4b5563;">
                        <strong>Zusätzliche Zustimmung:</strong> E-Mail-Versand via Mailgun (EU-Server)<br>
                        Der Kunde hat zugestimmt, dass Belohnungs-E-Mails über den Auftragsverarbeiter Mailgun 
                        (EU-Server, DSGVO-konform) versendet werden dürfen. Die Daten werden ausschließlich für 
                        den E-Mail-Versand verwendet und nicht an Dritte weitergegeben.
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="legal-box">
                <h3>⚖️ Rechtliche Hinweise</h3>
                <p>
                    Dieses Dokument dient als rechtsgültiger Nachweis der erteilten Zustimmung zur Auftragsverarbeitung. 
                    Die Zustimmung wurde elektronisch erfasst und gemäß DSGVO-Anforderungen dokumentiert. 
                    Der Verantwortliche (Kunde) bleibt weiterhin für die Verarbeitung personenbezogener Daten zuständig.
                </p>
            </div>
            
            <div class="section">
                <div class="section-title">Vertragsparteien</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                    <div>
                        <p style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Verantwortlicher:</p>
                        <p style="font-size: 13px; line-height: 1.6;">
                            <?= htmlspecialchars($data['user_name']) ?><br>
                            <?= htmlspecialchars($data['user_email']) ?><br>
                            <?php if (!empty($data['company_name'])): ?>
                            <?= htmlspecialchars($data['company_name']) ?><br>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Auftragsverarbeiter:</p>
                        <p style="font-size: 13px; line-height: 1.6;">
                            KI Leadsystem<br>
                            app.mehr-infos-jetzt.de<br>
                            Deutschland
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>
                    Dieses Dokument wurde automatisch generiert am <?= date('d.m.Y') ?> um <?= date('H:i:s') ?> Uhr<br>
                    <strong>Dokument-ID:</strong> AVV-<?= str_pad($data['id'], 8, '0', STR_PAD_LEFT) ?><br>
                    <strong>Prüfcode:</strong> <?= strtoupper(md5($data['id'] . $data['accepted_at'] . $data['user_id'])) ?>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print dialog on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

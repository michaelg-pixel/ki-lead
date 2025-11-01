<?php
session_start();
require_once '../config/database.php';

// Login-Check - konsistent mit anderen Customer-Seiten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../public/login.php');
    exit;
}

$conn = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Rechtstexte laden oder erstellen
$stmt = $conn->prepare("SELECT * FROM legal_texts WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$legal_texts) {
    // Standard-Texte erstellen
    $stmt = $conn->prepare("
        INSERT INTO legal_texts (customer_id, impressum, datenschutz) 
        VALUES (?, '', '')
    ");
    $stmt->execute([$customer_id]);
    
    $stmt = $conn->prepare("SELECT * FROM legal_texts WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Speichern
if (isset($_POST['save_legal_texts'])) {
    $impressum = $_POST['impressum'];
    $datenschutz = $_POST['datenschutz'];
    
    $stmt = $conn->prepare("
        UPDATE legal_texts 
        SET impressum = ?, datenschutz = ? 
        WHERE customer_id = ?
    ");
    $stmt->execute([$impressum, $datenschutz, $customer_id]);
    
    $success = "Rechtstexte erfolgreich gespeichert!";
    
    // Neu laden
    $stmt = $conn->prepare("SELECT * FROM legal_texts WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechtstexte - KI Lead-System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="text-2xl font-bold text-purple-600">
                    🚀 KI Lead-System
                </div>
                <div class="flex gap-6">
                    <a href="dashboard.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="legal-texts.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-file-contract mr-2"></i> Rechtstexte
                    </a>
                    <a href="../public/logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-6 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-500 text-white px-6 py-4 rounded-lg mb-8 flex items-center gap-4">
                <i class="fas fa-check-circle text-2xl"></i>
                <div class="font-semibold"><?= $success ?></div>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Rechtstexte</h1>
            <p class="text-gray-600">Bearbeite dein Impressum und deine Datenschutzerklärung für deine Freebie-Seiten</p>
        </div>

        <!-- E-RECHT24 GENERATOR HINWEIS - PROMINENT -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-8 mb-8 text-white shadow-2xl">
            <div class="flex items-start gap-6">
                <div class="text-6xl">🎯</div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-3">Kostenlose Rechtstexte mit e-recht24 erstellen</h2>
                    <p class="text-blue-100 mb-4">
                        Erstelle professionelle, rechtssichere Texte in wenigen Minuten - kostenlos und DSGVO-konform!
                    </p>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="https://www.e-recht24.de/impressum-generator.html" target="_blank" 
                           class="bg-white text-blue-600 hover:bg-blue-50 px-6 py-4 rounded-lg font-bold text-center transition shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-external-link-alt"></i>
                            Impressum Generator
                        </a>
                        <a href="https://www.e-recht24.de/muster-datenschutzerklaerung.html" target="_blank" 
                           class="bg-white text-purple-600 hover:bg-purple-50 px-6 py-4 rounded-lg font-bold text-center transition shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-external-link-alt"></i>
                            Datenschutz Generator
                        </a>
                    </div>
                    <p class="text-sm text-blue-100 mt-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        Kopiere die generierten Texte einfach in die Felder unten und speichere sie ab.
                    </p>
                </div>
            </div>
        </div>

        <!-- Wichtiger Hinweis -->
        <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-6 mb-8">
            <div class="flex gap-4">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-600"></i>
                <div>
                    <h3 class="font-bold text-lg mb-2">Wichtiger rechtlicher Hinweis</h3>
                    <p class="text-gray-700 text-sm mb-3">
                        Die hier hinterlegten Texte werden automatisch auf allen deinen Freebie-Seiten im Footer verlinkt. 
                        Sie sind rechtlich bindend und müssen vollständig und korrekt sein.
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span><strong>Empfohlen:</strong> Nutze professionelle Generatoren wie e-recht24 (siehe oben)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>Bei geschäftlicher Nutzung: Rechtliche Beratung einholen</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>Aktualisiere deine Texte regelmäßig bei Änderungen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <!-- Impressum -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">
                        <i class="fas fa-address-card mr-2 text-purple-600"></i> Impressum
                    </h2>
                    <button type="button" onclick="loadTemplate('impressum')" 
                            class="text-sm bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200 flex items-center gap-2">
                        <i class="fas fa-file-import"></i> Mustertext laden
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        Dein Impressum (wird automatisch im Footer deiner Freebie-Seiten verlinkt)
                    </label>
                    <textarea name="impressum" rows="18" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none font-mono text-sm"
                              placeholder="Füge hier dein vollständiges Impressum ein..."><?= htmlspecialchars($legal_texts['impressum']) ?></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-bold text-sm mb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i> Was muss ins Impressum? (§ 5 TMG)
                    </h4>
                    <ul class="text-sm text-gray-700 space-y-1 ml-6 list-disc">
                        <li><strong>Name und Anschrift:</strong> Vollständiger Name, Straße, PLZ, Ort</li>
                        <li><strong>Kontakt:</strong> E-Mail-Adresse und Telefonnummer</li>
                        <li><strong>Bei Unternehmen:</strong> Rechtsform, Vertretungsberechtigte, Handelsregister-Nr.</li>
                        <li><strong>Umsatzsteuer-ID:</strong> Falls vorhanden (§ 27a UStG)</li>
                        <li><strong>Berufsbezeichnung:</strong> Falls zutreffend (z.B. bei reglementierten Berufen)</li>
                        <li><strong>Verantwortlich i.S.d. § 55 Abs. 2 RStV:</strong> Name und Anschrift</li>
                    </ul>
                </div>
            </div>

            <!-- Datenschutzerklärung -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">
                        <i class="fas fa-shield-alt mr-2 text-purple-600"></i> Datenschutzerklärung
                    </h2>
                    <button type="button" onclick="loadTemplate('datenschutz')" 
                            class="text-sm bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200 flex items-center gap-2">
                        <i class="fas fa-file-import"></i> Mustertext laden
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        Deine Datenschutzerklärung (DSGVO-konform)
                    </label>
                    <textarea name="datenschutz" rows="25" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none font-mono text-sm"
                              placeholder="Füge hier deine vollständige Datenschutzerklärung ein..."><?= htmlspecialchars($legal_texts['datenschutz']) ?></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-bold text-sm mb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i> Was muss in die Datenschutzerklärung? (DSGVO)
                    </h4>
                    <ul class="text-sm text-gray-700 space-y-1 ml-6 list-disc">
                        <li><strong>Verantwortlicher:</strong> Name und Kontaktdaten (Art. 13 Abs. 1a DSGVO)</li>
                        <li><strong>Datenarten:</strong> Welche Daten werden erhoben? (E-Mail, Name, IP-Adresse, etc.)</li>
                        <li><strong>Zweck:</strong> Wofür werden die Daten verwendet? (Newsletter, Lead-Magnet, etc.)</li>
                        <li><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 DSGVO (meist Einwilligung oder berechtigtes Interesse)</li>
                        <li><strong>Speicherdauer:</strong> Wie lange werden Daten gespeichert?</li>
                        <li><strong>Betroffenenrechte:</strong> Auskunft, Löschung, Berichtigung, Widerspruch (Art. 15-21 DSGVO)</li>
                        <li><strong>Cookies:</strong> Welche Cookies werden verwendet und wofür?</li>
                        <li><strong>Drittanbieter:</strong> z.B. E-Mail-Marketing-Tools, Analyse-Tools, Hosting</li>
                        <li><strong>Widerrufsrecht:</strong> Möglichkeit zum Widerruf der Einwilligung</li>
                    </ul>
                </div>
            </div>

            <!-- Speichern Button -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-8 text-center shadow-xl">
                <button type="submit" name="save_legal_texts" 
                        class="bg-white text-purple-600 hover:bg-gray-100 px-12 py-4 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i> Rechtstexte speichern
                </button>
                <p class="text-white text-sm mt-4">
                    Die Texte werden automatisch auf allen deinen Freebie-Seiten im Footer verlinkt
                </p>
            </div>
        </form>

        <!-- Preview Links -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-eye mr-2 text-purple-600"></i> Deine Rechtstexte-Links
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Diese Links werden automatisch im Footer deiner Freebie-Seiten verwendet:
            </p>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-2 font-semibold">Impressum-Link</div>
                    <code class="text-xs bg-white px-3 py-2 rounded block break-all">
                        /impressum.php?customer=<?= $customer_id ?>
                    </code>
                    <a href="/impressum.php?customer=<?= $customer_id ?>" target="_blank" 
                       class="text-xs text-purple-600 hover:text-purple-700 mt-2 inline-block">
                        <i class="fas fa-external-link-alt mr-1"></i> Vorschau öffnen
                    </a>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-2 font-semibold">Datenschutz-Link</div>
                    <code class="text-xs bg-white px-3 py-2 rounded block break-all">
                        /datenschutz.php?customer=<?= $customer_id ?>
                    </code>
                    <a href="/datenschutz.php?customer=<?= $customer_id ?>" target="_blank" 
                       class="text-xs text-purple-600 hover:text-purple-700 mt-2 inline-block">
                        <i class="fas fa-external-link-alt mr-1"></i> Vorschau öffnen
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadTemplate(type) {
            const templates = {
                impressum: `Angaben gemäß § 5 TMG

[Dein vollständiger Name oder Firmenname]
[Rechtsform, falls Unternehmen - z.B. GmbH, UG, Einzelunternehmen]
[Straße und Hausnummer]
[PLZ und Ort]
[Land]

Kontakt:
Telefon: [Deine Telefonnummer mit Ländervorwahl, z.B. +49 123 456789]
E-Mail: [Deine E-Mail-Adresse]
Website: [Deine Website]

Vertreten durch:
[Name des/der Geschäftsführer(s) bzw. Inhaber(s)]

Registereintrag:
[Falls vorhanden: Handelsregister, Vereinsregister, etc.]
Registergericht: [z.B. Amtsgericht München]
Registernummer: [z.B. HRB 123456]

Umsatzsteuer-ID:
Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:
[Deine USt-IdNr., falls vorhanden - z.B. DE123456789]

Berufsbezeichnung und berufsrechtliche Regelungen:
[Falls zutreffend, z.B. bei Ärzten, Anwälten, etc.]

Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:
[Name]
[Adresse]

Hinweis zur Streitbeilegung:
Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
https://ec.europa.eu/consumers/odr/

Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer
Verbraucherschlichtungsstelle teilzunehmen.`,

                datenschutz: `Datenschutzerklärung

1. Datenschutz auf einen Blick

Allgemeine Hinweise

Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können. Ausführliche Informationen zum Thema Datenschutz entnehmen Sie unserer unter diesem Text aufgeführten Datenschutzerklärung.

Datenerfassung auf dieser Website

Wer ist verantwortlich für die Datenerfassung auf dieser Website?

Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Impressum dieser Website entnehmen.

Wie erfassen wir Ihre Daten?

Ihre Daten werden zum einen dadurch erhoben, dass Sie uns diese mitteilen. Hierbei kann es sich z.B. um Daten handeln, die Sie in ein Kontaktformular eingeben.

Andere Daten werden automatisch oder nach Ihrer Einwilligung beim Besuch der Website durch unsere IT-Systeme erfasst. Das sind vor allem technische Daten (z.B. Internetbrowser, Betriebssystem oder Uhrzeit des Seitenaufrufs). Die Erfassung dieser Daten erfolgt automatisch, sobald Sie diese Website betreten.

Wofür nutzen wir Ihre Daten?

Ein Teil der Daten wird erhoben, um eine fehlerfreie Bereitstellung der Website zu gewährleisten. Andere Daten können zur Analyse Ihres Nutzerverhaltens verwendet werden oder um Ihnen den angeforderten kostenlosen Download (Lead-Magnet) bereitzustellen.

Welche Rechte haben Sie bezüglich Ihrer Daten?

Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung oder Löschung dieser Daten zu verlangen. Wenn Sie eine Einwilligung zur Datenverarbeitung erteilt haben, können Sie diese Einwilligung jederzeit für die Zukunft widerrufen. Außerdem haben Sie das Recht, unter bestimmten Umständen die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen.

Des Weiteren steht Ihnen ein Beschwerderecht bei der zuständigen Aufsichtsbehörde zu. Hierzu sowie zu weiteren Fragen zum Thema Datenschutz können Sie sich jederzeit an uns wenden.

2. Hosting

Diese Website wird bei [Hosting-Anbieter, z.B. "Hostinger"] gehostet. Der Anbieter erhebt in sogenannten Logfiles automatisch Daten, die Ihr Browser übermittelt. Dies sind:

- IP-Adresse
- Browsertyp und -version
- Verwendetes Betriebssystem
- Referrer URL
- Uhrzeit der Serveranfrage

Die Speicherung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Der Websitebetreiber hat ein berechtigtes Interesse an der technisch fehlerfreien Darstellung und der Optimierung seiner Website.

3. Allgemeine Hinweise und Pflichtinformationen

Datenschutz

Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre personenbezogenen Daten vertraulich und entsprechend den gesetzlichen Datenschutzvorschriften sowie dieser Datenschutzerklärung.

Wenn Sie diese Website benutzen, werden verschiedene personenbezogene Daten erhoben. Personenbezogene Daten sind Daten, mit denen Sie persönlich identifiziert werden können.

Hinweis zur verantwortlichen Stelle

Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:

[Dein Name/Firmenname]
[Deine Straße und Hausnummer]
[Deine PLZ und Ort]

Telefon: [Deine Telefonnummer]
E-Mail: [Deine E-Mail]

Verantwortliche Stelle ist die natürliche oder juristische Person, die allein oder gemeinsam mit anderen über die Zwecke und Mittel der Verarbeitung von personenbezogenen Daten (z.B. Namen, E-Mail-Adressen o.Ä.) entscheidet.

Speicherdauer

Soweit innerhalb dieser Datenschutzerklärung keine speziellere Speicherdauer genannt wurde, verbleiben Ihre personenbezogenen Daten bei uns, bis der Zweck für die Datenverarbeitung entfällt. Wenn Sie ein berechtigtes Löschersuchen geltend machen oder eine Einwilligung zur Datenverarbeitung widerrufen, werden Ihre Daten gelöscht, sofern wir keine anderen rechtlich zulässigen Gründe für die Speicherung Ihrer personenbezogenen Daten haben.

Widerruf Ihrer Einwilligung zur Datenverarbeitung

Viele Datenverarbeitungsvorgänge sind nur mit Ihrer ausdrücklichen Einwilligung möglich. Sie können eine bereits erteilte Einwilligung jederzeit widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten Datenverarbeitung bleibt vom Widerruf unberührt.

4. Datenerfassung auf dieser Website

Cookies

Unsere Internetseiten verwenden so genannte „Cookies". Cookies sind kleine Datenpakete und richten auf Ihrem Endgerät keinen Schaden an. Sie werden entweder vorübergehend für die Dauer einer Sitzung (Session-Cookies) oder dauerhaft (permanente Cookies) auf Ihrem Endgerät gespeichert.

Rechtsgrundlage: Die Verwendung von Cookies erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung).

Sie können Ihren Browser so einstellen, dass Sie über das Setzen von Cookies informiert werden und Cookies nur im Einzelfall erlauben, die Annahme von Cookies für bestimmte Fälle oder generell ausschließen sowie das automatische Löschen der Cookies beim Schließen des Browsers aktivieren.

5. E-Mail-Marketing und Lead-Magnet

Wenn Sie sich für unseren kostenlosen Download (Lead-Magnet) anmelden, verwenden wir die von Ihnen angegebenen Daten ausschließlich für diesen Zweck oder um Sie über neue relevante Angebote zu informieren (Newsletter).

Die Datenverarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung). Sie können diese Einwilligung jederzeit widerrufen. Hierzu genügt eine formlose Mitteilung per E-Mail an uns. Die Rechtmäßigkeit der bereits erfolgten Datenverarbeitungsvorgänge bleibt vom Widerruf unberührt.

Die von Ihnen zum Zweck des Bezugs des Downloads angegebenen Daten werden von uns bis zu Ihrer Austragung aus dem Verteiler bei uns bzw. dem E-Mail-Marketing-Dienstleister gespeichert.

[Falls zutreffend: Wir nutzen für den Versand [Name des E-Mail-Marketing-Tools, z.B. CleverReach, Mailchimp, ActiveCampaign]. Weitere Informationen finden Sie in deren Datenschutzerklärung.]

6. Ihre Rechte

Sie haben folgende Rechte:

- Recht auf Auskunft (Art. 15 DSGVO)
- Recht auf Berichtigung (Art. 16 DSGVO)
- Recht auf Löschung (Art. 17 DSGVO)
- Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO)
- Recht auf Datenübertragbarkeit (Art. 20 DSGVO)
- Widerspruchsrecht (Art. 21 DSGVO)

Bei Fragen zur Erhebung, Verarbeitung oder Nutzung Ihrer personenbezogenen Daten, bei Auskünften, Berichtigung, Einschränkung oder Löschung von Daten sowie Widerruf erteilter Einwilligungen wenden Sie sich bitte an:

[Deine E-Mail-Adresse]

Stand: [Aktuelles Datum einfügen]`
            };
            
            if (confirm(`Möchtest du den ${type === 'impressum' ? 'Impressums' : 'Datenschutz'}-Mustertext laden?\n\nWICHTIG: Dies ist nur eine Vorlage! Du musst alle Platzhalter (z.B. [Dein Name]) durch deine echten Daten ersetzen.\n\nDeine aktuellen Inhalte werden überschrieben.`)) {
                document.querySelector(`textarea[name="${type}"]`).value = templates[type];
                
                // Scroll to textarea
                document.querySelector(`textarea[name="${type}"]`).scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }
    </script>

</body>
</html>

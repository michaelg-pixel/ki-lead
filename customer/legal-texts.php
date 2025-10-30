<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
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
                    <a href="index.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-graduation-cap mr-2"></i> Kurse
                    </a>
                    <a href="freebie-editor.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-edit mr-2"></i> Freebie-Editor
                    </a>
                    <a href="legal-texts.php" class="text-purple-600 font-semibold">
                        <i class="fas fa-file-contract mr-2"></i> Rechtstexte
                    </a>
                    <a href="tutorials.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-question-circle mr-2"></i> Anleitungen
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
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
            <p class="text-gray-600">Bearbeite dein Impressum und deine Datenschutzerklärung</p>
        </div>

        <!-- Wichtiger Hinweis -->
        <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-6 mb-8">
            <div class="flex gap-4">
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-600"></i>
                <div>
                    <h3 class="font-bold text-lg mb-2">Wichtiger rechtlicher Hinweis</h3>
                    <p class="text-gray-700 text-sm mb-3">
                        Die hier hinterlegten Texte sind rechtlich bindend. Wir empfehlen dringend, 
                        professionelle Rechtstexte zu verwenden, die auf deine Situation zugeschnitten sind.
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>Nutze Generatoren wie <a href="https://www.e-recht24.de" target="_blank" class="text-purple-600 underline">eRecht24</a> oder <a href="https://www.activemind.de" target="_blank" class="text-purple-600 underline">Activemind</a></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>Hole dir rechtliche Beratung von einem Anwalt</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-green-600"></i>
                            <span>Aktualisiere deine Texte regelmäßig</span>
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
                            class="text-sm bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200">
                        <i class="fas fa-file-import mr-2"></i> Vorlage laden
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        Dein Impressum (wird auf deinen Freebie-Seiten angezeigt)
                    </label>
                    <textarea name="impressum" rows="15" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none font-mono text-sm"
                              placeholder="Angaben gemäß § 5 TMG&#10;&#10;Max Mustermann&#10;Musterstraße 123&#10;12345 Musterstadt&#10;&#10;Kontakt:&#10;Telefon: +49 123 456789&#10;E-Mail: kontakt@beispiel.de&#10;..."><?= htmlspecialchars($legal_texts['impressum']) ?></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-bold text-sm mb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i> Was muss ins Impressum?
                    </h4>
                    <ul class="text-sm text-gray-700 space-y-1 ml-6 list-disc">
                        <li>Name und Anschrift (bei Firmen: Rechtsform, Vertretungsberechtigte)</li>
                        <li>Kontaktmöglichkeiten (E-Mail, Telefon)</li>
                        <li>Handelsregister-Nummer (falls vorhanden)</li>
                        <li>Umsatzsteuer-ID (falls vorhanden)</li>
                        <li>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</li>
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
                            class="text-sm bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200">
                        <i class="fas fa-file-import mr-2"></i> Vorlage laden
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        Deine Datenschutzerklärung (DSGVO-konform)
                    </label>
                    <textarea name="datenschutz" rows="20" 
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none font-mono text-sm"
                              placeholder="1. Datenschutz auf einen Blick&#10;&#10;Allgemeine Hinweise&#10;Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert...&#10;&#10;2. Hosting&#10;&#10;3. Allgemeine Hinweise und Pflichtinformationen&#10;..."><?= htmlspecialchars($legal_texts['datenschutz']) ?></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-bold text-sm mb-2">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i> Was muss in die Datenschutzerklärung?
                    </h4>
                    <ul class="text-sm text-gray-700 space-y-1 ml-6 list-disc">
                        <li>Verantwortlicher für die Datenverarbeitung</li>
                        <li>Welche Daten werden erhoben? (E-Mail, Name, etc.)</li>
                        <li>Zu welchem Zweck? (Newsletter, Marketing)</li>
                        <li>Rechtsgrundlage (DSGVO Art. 6 Abs. 1)</li>
                        <li>Wie lange werden Daten gespeichert?</li>
                        <li>Rechte der Nutzer (Auskunft, Löschung, etc.)</li>
                        <li>Verwendung von Cookies</li>
                        <li>Einsatz von Analyse-Tools (Google Analytics, etc.)</li>
                        <li>E-Mail-Marketing-Tools (CleverReach, Mailchimp, etc.)</li>
                    </ul>
                </div>
            </div>

            <!-- Speichern Button -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-8 text-center">
                <button type="submit" name="save_legal_texts" 
                        class="bg-white text-purple-600 hover:bg-gray-100 px-12 py-4 rounded-lg font-bold text-lg shadow-lg">
                    <i class="fas fa-save mr-2"></i> Rechtstexte speichern
                </button>
                <p class="text-white text-sm mt-4">
                    Die Texte werden automatisch auf allen deinen Freebie-Seiten angezeigt
                </p>
            </div>
        </form>

        <!-- Preview Links -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="font-bold text-lg mb-4">
                <i class="fas fa-eye mr-2 text-purple-600"></i> Vorschau-Links
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Diese Links kannst du in deinen Freebie-Seiten im Footer verwenden:
            </p>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-1">Impressum</div>
                    <code class="text-xs bg-white px-3 py-2 rounded block">
                        /impressum.php?customer=<?= $customer_id ?>
                    </code>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600 mb-1">Datenschutz</div>
                    <code class="text-xs bg-white px-3 py-2 rounded block">
                        /datenschutz.php?customer=<?= $customer_id ?>
                    </code>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadTemplate(type) {
            const templates = {
                impressum: `Angaben gemäß § 5 TMG

[Dein vollständiger Name oder Firmenname]
[Straße und Hausnummer]
[PLZ und Ort]

Kontakt:
Telefon: [Deine Telefonnummer]
E-Mail: [Deine E-Mail]

Vertreten durch:
[Name des Vertretungsberechtigten]

Umsatzsteuer-ID:
Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:
[Deine USt-IdNr.]

Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:
[Name]
[Adresse]`,

                datenschutz: `1. Datenschutz auf einen Blick

Allgemeine Hinweise
Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.

2. Datenerfassung auf dieser Website

Verantwortlicher:
[Dein Name/Firma]
[Deine Adresse]
E-Mail: [Deine E-Mail]

3. Welche Daten erfassen wir?

Wir erfassen folgende Daten:
- E-Mail-Adresse (bei Newsletter-Anmeldung)
- Name (optional)
- Nutzungsdaten (Cookies, IP-Adressen)

4. Wofür nutzen wir Ihre Daten?

Die Daten werden verwendet für:
- Versand des kostenlosen Videokurses
- Newsletter (mit Ihrer Einwilligung)
- Verbesserung unseres Angebots

5. Rechtsgrundlage

Rechtsgrundlage für die Datenverarbeitung ist Art. 6 Abs. 1 lit. a DSGVO (Einwilligung).

6. Ihre Rechte

Sie haben jederzeit das Recht auf:
- Auskunft über Ihre gespeicherten Daten
- Berichtigung unrichtiger Daten
- Löschung Ihrer Daten
- Einschränkung der Verarbeitung
- Datenübertragbarkeit
- Widerruf Ihrer Einwilligung

Kontaktieren Sie uns dazu unter: [Deine E-Mail]`
            };
            
            if (confirm(`Möchtest du die ${type === 'impressum' ? 'Impressums' : 'Datenschutz'}-Vorlage laden? Deine aktuellen Inhalte werden überschrieben.`)) {
                document.querySelector(`textarea[name="${type}"]`).value = templates[type];
            }
        }
    </script>

</body>
</html>

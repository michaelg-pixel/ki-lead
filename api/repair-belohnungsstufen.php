<?php
/**
 * REPARATUR-SCRIPT
 * Stellt belohnungsstufen.php wieder her und fügt Programm-Sperre ein
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🔧 Starte Reparatur der belohnungsstufen.php...\n\n";

// Hole die funktionierende Version von GitHub
$url = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/42d8232fdcfaf85be954039e9d39eb9dd6c30817/customer/sections/belohnungsstufen.php';
$original_content = file_get_contents($url);

if (!$original_content) {
    die("❌ FEHLER: Konnte Original-Datei nicht laden\n");
}

echo "✅ Original-Datei geladen (" . strlen($original_content) . " bytes)\n";

// Füge Sperre am Anfang ein (nach dem ersten <?php Block)
$lock_code = '
// ===== PROGRAMM-SPERRE =====
// Prüfe ob Empfehlungsprogramm aktiviert ist
try {
    $stmt_check = $pdo->prepare("SELECT referral_enabled FROM users WHERE id = ?");
    $stmt_check->execute([$customer_id]);
    $referral_enabled = (bool)$stmt_check->fetchColumn();
} catch (PDOException $e) {
    error_log("Referral Check Error: " . $e->getMessage());
    $referral_enabled = false;
}

// Wenn NICHT aktiviert → Sperrbildschirm
if (!$referral_enabled) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(to bottom right, #1f2937, #111827, #1f2937);
                min-height: 100vh;
                margin: 0;
                padding: 2rem 1rem;
                font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .locked-screen {
                max-width: 700px;
                text-align: center;
            }
            .lock-icon {
                width: 100px;
                height: 100px;
                margin: 0 auto 2rem;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 3rem;
                color: white;
                box-shadow: 0 20px 40px -10px rgba(239, 68, 68, 0.5);
            }
            h1 { color: white; font-size: 2rem; margin-bottom: 1rem; }
            p { color: #9ca3af; font-size: 1.125rem; line-height: 1.6; margin-bottom: 2rem; }
            .action-button {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                padding: 1rem 2rem;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                text-decoration: none;
                border-radius: 0.75rem;
                font-size: 1.125rem;
                font-weight: 600;
                transition: all 0.3s;
                box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
            }
            .action-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.5);
            }
            .info-list {
                background: rgba(59, 130, 246, 0.1);
                border: 2px solid #3b82f6;
                border-radius: 1rem;
                padding: 1.5rem;
                margin: 2rem 0;
                text-align: left;
            }
            .info-list h3 {
                color: white;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .info-list ul {
                color: #9ca3af;
                margin: 0;
                padding-left: 1.5rem;
                line-height: 1.8;
            }
        </style>
    </head>
    <body>
        <div class="locked-screen">
            <div class="lock-icon">
                <i class="fas fa-lock"></i>
            </div>
            
            <h1>Empfehlungsprogramm nicht aktiviert</h1>
            
            <p>
                Um Belohnungsstufen zu erstellen, musst du zuerst dein Empfehlungsprogramm aktivieren. 
                Das dauert nur wenige Minuten!
            </p>
            
            <div class="info-list">
                <h3><i class="fas fa-list-check"></i> So geht\'s:</h3>
                <ul>
                    <li>Klicke auf den Button unten</li>
                    <li>Lies die Datenschutz-Informationen</li>
                    <li>Akzeptiere die Nutzungsbedingungen</li>
                    <li>Aktiviere das Programm mit dem Toggle</li>
                </ul>
            </div>
            
            <a href="?page=empfehlungsprogramm" class="action-button">
                <i class="fas fa-rocket"></i>
                Jetzt aktivieren
            </a>
        </div>
    </body>
    </html>
    <?php
    exit; // Beende Script hier
}
// ===== ENDE PROGRAMM-SPERRE =====
';

// Ersetze den Beginn (nach die('Nicht autorisiert');)
$pattern = '/(die\(\'Nicht autorisiert\'\);\s*\}\s*)/s';
$replacement = '$1' . $lock_code;
$new_content = preg_replace($pattern, $replacement, $original_content, 1);

if ($new_content === $original_content) {
    echo "⚠️ WARNUNG: Pattern nicht gefunden, verwende alternative Methode\n";
    // Alternative: Füge nach den ersten 500 Zeichen ein
    $insert_position = strpos($original_content, "// Freebie-ID aus URL Parameter");
    if ($insert_position !== false) {
        $new_content = substr($original_content, 0, $insert_position) . $lock_code . "\n" . substr($original_content, $insert_position);
        echo "✅ Sperre eingefügt an Position $insert_position\n";
    } else {
        die("❌ FEHLER: Konnte Einfügeposition nicht finden\n");
    }
} else {
    echo "✅ Sperre erfolgreich eingefügt\n";
}

// Schreibe die neue Datei
$target_file = __DIR__ . '/../customer/sections/belohnungsstufen.php';
$success = file_put_contents($target_file, $new_content);

if ($success) {
    echo "✅ Datei erfolgreich geschrieben (" . strlen($new_content) . " bytes)\n";
    echo "\n🎉 REPARATUR ERFOLGREICH!\n";
    echo "\nDie belohnungsstufen.php wurde wiederhergestellt und enthält jetzt:\n";
    echo "- ✅ Alle ursprünglichen Funktionen\n";
    echo "- ✅ Programm-Sperre (nur für nicht-aktivierte Programme)\n";
    echo "- ✅ Schöner Sperrbildschirm mit Anleitung\n";
} else {
    echo "❌ FEHLER: Konnte Datei nicht schreiben\n";
    echo "Ziel: $target_file\n";
}

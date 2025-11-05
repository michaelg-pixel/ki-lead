<?php
/**
 * Update-Script: Fügt Limits-Banner zum Empfehlungsprogramm hinzu
 */

$empfehlungFile = __DIR__ . '/../customer/sections/empfehlungsprogramm.php';

if (!file_exists($empfehlungFile)) {
    die("❌ Fehler: empfehlungsprogramm.php nicht gefunden");
}

$content = file_get_contents($empfehlungFile);

// Prüfe ob bereits vorhanden
if (strpos($content, 'empfehlungsprogramm-limits-banner.php') !== false) {
    echo "✅ Limits-Banner ist bereits integriert!<br>";
    echo "<a href='/customer/dashboard.php?page=empfehlungsprogramm'>→ Zum Empfehlungsprogramm</a>";
    exit;
}

// Füge das Include nach dem Header-Bereich ein (nach dem ersten animate-fade-in-up div)
$search = '        <!-- Statistiken -->';
$replace = '        <?php include \'empfehlungsprogramm-limits-banner.php\'; ?>
        
        <!-- Statistiken -->';

$content = str_replace($search, $replace, $content);

// Speichere die aktualisierte Datei
if (file_put_contents($empfehlungFile, $content)) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Update erfolgreich</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #10b981; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 6px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>✅ Update erfolgreich!</h1>
            
            <div class='success'>
                <strong>Der Limits-Banner wurde zum Empfehlungsprogramm hinzugefügt!</strong>
            </div>
            
            <div class='info'>
                <h3>🎯 Was wurde hinzugefügt:</h3>
                <ul>
                    <li>📊 Anzeige der Freebie-Limits</li>
                    <li>🚀 Anzeige der Empfehlungsprogramm-Slots</li>
                    <li>📈 Fortschrittsbalken für beide Limits</li>
                    <li>⚠️ Warnung bei erreichtem Limit</li>
                </ul>
                
                <h3>🔧 So sieht es aus:</h3>
                <p>Kunden sehen jetzt oben im Empfehlungsprogramm einen schönen Banner mit:</p>
                <ul>
                    <li><strong>Eigene Freebies:</strong> X von Y genutzt</li>
                    <li><strong>Empfehlungsprogramme:</strong> X von Y genutzt</li>
                </ul>
                <p>Die Limits werden automatisch basierend auf dem gekauften Produkt oder manuellen Admin-Einstellungen angezeigt.</p>
            </div>
            
            <a href='/customer/dashboard.php?page=empfehlungsprogramm' class='btn'>→ Zum Empfehlungsprogramm</a>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Update fehlgeschlagen</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #ef4444; }
            .error { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 6px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>❌ Update fehlgeschlagen</h1>
            <div class='error'>
                <p>Die Datei konnte nicht gespeichert werden. Bitte prüfe die Dateiberechtigungen für:</p>
                <p><code>$empfehlungFile</code></p>
            </div>
        </div>
    </body>
    </html>";
}

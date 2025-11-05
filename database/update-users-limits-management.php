<?php
/**
 * Update-Script: Fügt Limits-Verwaltung zur User-Section hinzu
 */

$usersFile = __DIR__ . '/../admin/sections/users.php';

if (!file_exists($usersFile)) {
    die("❌ Fehler: users.php nicht gefunden in: $usersFile");
}

$content = file_get_contents($usersFile);

// 1. Prüfe ob bereits vorhanden
if (strpos($content, 'manageLimits') !== false) {
    echo "✅ Limits-Verwaltung ist bereits in users.php integriert!<br>";
    echo "<a href='/admin/dashboard.php?page=users'>→ Zur Kundenverwaltung</a>";
    exit;
}

// 2. Füge Limits-Button zu den Aktionen hinzu
$search = '<button class="action-btn" 
                                    onclick="editCustomer(<?php echo $customer[\'id\']; ?>)" 
                                    title="Bearbeiten">
                                ✏️
                            </button>';

$replace = '<button class="action-btn" 
                                    onclick="editCustomer(<?php echo $customer[\'id\']; ?>)" 
                                    title="Bearbeiten">
                                ✏️
                            </button>
                            <button class="action-btn" 
                                    onclick="manageLimits(<?php echo $customer[\'id\']; ?>)" 
                                    title="Limits verwalten">
                                📊
                            </button>';

$content = str_replace($search, $replace, $content);

// 3. Füge Include für das Modal hinzu (vor dem schließenden body-Tag oder am Ende des PHP-Files)
$search = '// ESC-Taste zum Schließen von Modals';
$replace = '<?php include \'limits-management-modal.php\'; ?>

// ESC-Taste zum Schließen von Modals';

$content = str_replace($search, $replace, $content);

// 4. Speichere die aktualisierte Datei
if (file_put_contents($usersFile, $content)) {
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
                <strong>Die Limits-Verwaltung wurde erfolgreich zur Kundenverwaltung hinzugefügt!</strong>
            </div>
            
            <div class='info'>
                <h3>🎯 Was wurde hinzugefügt:</h3>
                <ul>
                    <li>📊 Neuer <code>Limits verwalten</code> Button bei jedem Kunden</li>
                    <li>✏️ Modal zum Anpassen von Freebie-Limits</li>
                    <li>🚀 Modal zum Anpassen von Empfehlungs-Slots</li>
                </ul>
                
                <h3>🔧 So nutzt du es:</h3>
                <ol>
                    <li>Gehe zur <strong>Kundenverwaltung</strong></li>
                    <li>Klicke bei einem Kunden auf das <strong>📊 Symbol</strong></li>
                    <li>Passe die Limits nach Bedarf an</li>
                    <li>Speichern - fertig!</li>
                </ol>
            </div>
            
            <a href='/admin/dashboard.php?page=users' class='btn'>→ Zur Kundenverwaltung</a>
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
                <p><code>$usersFile</code></p>
            </div>
        </div>
    </body>
    </html>";
}

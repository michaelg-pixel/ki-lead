<?php
/**
 * Datenbank-Migration: Passwort-Reset Spalten hinzufügen
 * 
 * Fügt folgende Spalten zur users Tabelle hinzu:
 * - password_reset_token: Token für Reset-Link
 * - password_reset_expires: Ablaufzeit des Tokens
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Migration</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .success { color: #00ff00; }
        .error { color: #ff4444; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
    </style>
</head>
<body>
<pre>
<?php

echo "<span class='info'>🔄 Starte Passwort-Reset Migration...</span>\n\n";

try {
    // Prüfe ob Spalten bereits existieren
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasResetToken = false;
    $hasResetExpires = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'password_reset_token') $hasResetToken = true;
        if ($col['Field'] === 'password_reset_expires') $hasResetExpires = true;
    }
    
    // Spalte password_reset_token hinzufügen
    if (!$hasResetToken) {
        echo "<span class='info'>→ Füge Spalte 'password_reset_token' hinzu...</span>\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL DEFAULT NULL");
        echo "<span class='success'>  ✓ password_reset_token hinzugefügt</span>\n";
    } else {
        echo "<span class='warning'>⚠️  Spalte 'password_reset_token' existiert bereits</span>\n";
    }
    
    // Spalte password_reset_expires hinzufügen
    if (!$hasResetExpires) {
        echo "<span class='info'>→ Füge Spalte 'password_reset_expires' hinzu...</span>\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL DEFAULT NULL");
        echo "<span class='success'>  ✓ password_reset_expires hinzugefügt</span>\n";
    } else {
        echo "<span class='warning'>⚠️  Spalte 'password_reset_expires' existiert bereits</span>\n";
    }
    
    // Index hinzufügen (nur wenn Spalte existiert)
    if ($hasResetToken || !$hasResetToken) {
        echo "<span class='info'>→ Erstelle Index auf 'password_reset_token'...</span>\n";
        try {
            $pdo->exec("ALTER TABLE users ADD INDEX idx_password_reset_token (password_reset_token)");
            echo "<span class='success'>  ✓ Index erstellt</span>\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<span class='warning'>⚠️  Index existiert bereits</span>\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n<span class='success'>✅ Migration erfolgreich abgeschlossen!</span>\n\n";
    
    // Finale Verifizierung
    echo "<span class='info'>Verifizierung der Spalten:</span>\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $found = ['password_reset_token' => false, 'password_reset_expires' => false];
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'password_reset_token') {
            $found['password_reset_token'] = true;
            echo "  <span class='success'>✓ password_reset_token: {$col['Type']}</span>\n";
        }
        if ($col['Field'] === 'password_reset_expires') {
            $found['password_reset_expires'] = true;
            echo "  <span class='success'>✓ password_reset_expires: {$col['Type']}</span>\n";
        }
    }
    
    if ($found['password_reset_token'] && $found['password_reset_expires']) {
        echo "\n<span class='success'>✅ Alle Spalten erfolgreich angelegt!</span>\n";
        echo "\n<span class='info'>Die Passwort-Reset-Funktion ist jetzt einsatzbereit.</span>\n";
    } else {
        echo "\n<span class='error'>❌ Fehler: Nicht alle Spalten gefunden!</span>\n";
    }
    
} catch (PDOException $e) {
    echo "\n<span class='error'>❌ Fehler bei Migration:</span>\n";
    echo "<span class='error'>" . htmlspecialchars($e->getMessage()) . "</span>\n";
    exit(1);
}

?>
</pre>

<p style="margin-top: 30px; padding: 20px; background: #2d2d2d; border-radius: 8px;">
    <strong style="color: #00aaff;">Nächste Schritte:</strong><br>
    <span style="color: #ccc;">
    1. Teste die Passwort-Reset-Anfrage: <a href="../public/password-reset-request.php" style="color: #00ff00;">Zur Reset-Anfrage</a><br>
    2. Zurück zum Login: <a href="../public/login.php" style="color: #00ff00;">Zum Login</a>
    </span>
</p>

</body>
</html>

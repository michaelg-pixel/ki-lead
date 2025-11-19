<?php
/**
 * DEBUG: Live-Test livetest@web.de
 */

require_once '../config/database.php';

echo "<h1>🔍 DEBUG: livetest@web.de Webhook-Diagnose</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    
    echo "=== 1. USER CHECK ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'livetest@web.de'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User existiert!\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "RAW-Code: " . $user['raw_code'] . "\n";
        echo "Erstellt: " . $user['created_at'] . "\n";
        
        $userId = $user['id'];
        
        echo "\n=== 2. FREEBIES CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($freebies) > 0) {
            echo "✅ Freebies gefunden: " . count($freebies) . "\n\n";
            foreach ($freebies as $freebie) {
                echo "Freebie ID: " . $freebie['id'] . "\n";
                echo "  - Headline: " . $freebie['headline'] . "\n";
                echo "  - Typ: " . $freebie['freebie_type'] . "\n";
                echo "  - Kopiert von: " . ($freebie['copied_from_freebie_id'] ?? 'Nein') . "\n";
                echo "  - URL-Slug: " . $freebie['url_slug'] . "\n\n";
            }
        } else {
            echo "❌ Keine Freebies gefunden für User $userId\n";
        }
        
        echo "\n=== 3. VIDEOKURS CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($courses) > 0) {
            echo "✅ Videokurse gefunden: " . count($courses) . "\n\n";
            foreach ($courses as $course) {
                echo "Kurs ID: " . $course['id'] . "\n";
                echo "  - Title: " . $course['title'] . "\n";
                echo "  - Freebie ID: " . $course['freebie_id'] . "\n";
                
                // Module zählen
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM freebie_course_modules WHERE course_id = ?");
                $stmt->execute([$course['id']]);
                $moduleCount = $stmt->fetch()['count'];
                echo "  - Module: $moduleCount\n";
                
                // Lektionen zählen
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM freebie_course_lessons 
                    WHERE module_id IN (SELECT id FROM freebie_course_modules WHERE course_id = ?)
                ");
                $stmt->execute([$course['id']]);
                $lessonCount = $stmt->fetch()['count'];
                echo "  - Lektionen: $lessonCount\n\n";
            }
        } else {
            echo "❌ Keine Videokurse gefunden für User $userId\n";
        }
        
    } else {
        echo "❌ User wurde NICHT angelegt!\n";
        echo "Das bedeutet: Webhook wurde entweder nicht aufgerufen oder ist fehlgeschlagen.\n";
    }
    
    echo "\n=== 4. MARKTPLATZ-FREEBIE CHECK (Quelle) ===\n";
    $stmt = $pdo->query("
        SELECT id, customer_id, headline, digistore_product_id, marketplace_enabled, marketplace_sales_count
        FROM customer_freebies 
        WHERE digistore_product_id = '639493'
    ");
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($source) {
        echo "✅ Marktplatz-Freebie existiert:\n";
        echo "Freebie ID: " . $source['id'] . "\n";
        echo "Verkäufer: customer_id " . $source['customer_id'] . "\n";
        echo "Headline: " . $source['headline'] . "\n";
        echo "Produkt-ID: " . $source['digistore_product_id'] . "\n";
        echo "Marktplatz aktiv: " . ($source['marketplace_enabled'] ? 'Ja' : 'Nein') . "\n";
        echo "Verkäufe: " . $source['marketplace_sales_count'] . "\n";
    } else {
        echo "❌ Kein Marktplatz-Freebie mit Produkt-ID 639493 gefunden!\n";
    }
    
    echo "\n=== 5. WEBHOOK LOG CHECK ===\n";
    $logFile = __DIR__ . '/webhook.log';
    if (file_exists($logFile)) {
        echo "✅ Log-Datei existiert: $logFile\n";
        echo "Größe: " . filesize($logFile) . " Bytes\n";
        echo "Letzte Änderung: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n\n";
        echo "=== LETZTE 50 LOG-ZEILEN ===\n";
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -50);
        echo implode("\n", $lastLines);
    } else {
        echo "❌ Log-Datei existiert NICHT!\n";
        echo "Das bedeutet: Webhook wurde wahrscheinlich NIE aufgerufen!\n";
        echo "Pfad erwartet: $logFile\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";

echo "<hr>";
echo "<h2>🔧 Nächste Schritte:</h2>";
echo "<ul>";
echo "<li><strong>Wenn User NICHT existiert:</strong> Webhook wurde nicht aufgerufen → IPN-URL bei Digistore24 prüfen</li>";
echo "<li><strong>Wenn User existiert, aber KEIN Freebie:</strong> Webhook läuft teilweise → Script-Fehler</li>";
echo "<li><strong>Wenn Log-Datei NICHT existiert:</strong> Webhook-Script wurde nie ausgeführt</li>";
echo "<li><strong>Wenn Log existiert:</strong> Logs analysieren um Fehler zu finden</li>";
echo "</ul>";
?>

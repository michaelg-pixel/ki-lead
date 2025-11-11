<?php
/**
 * Test ob freebies.php korrekt lädt
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die("❌ Nicht eingeloggt");
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

echo "<h2>🔍 Freebies Display Test</h2>";
echo "<hr><br>";

// Simuliere was freebies.php macht
try {
    // 1. Templates laden
    $stmt = $pdo->query("
        SELECT 
            f.id,
            f.name,
            f.headline,
            f.subheadline,
            f.preheadline,
            f.mockup_image_url,
            f.background_color,
            f.primary_color,
            f.unique_id,
            f.url_slug,
            f.layout,
            f.cta_text,
            f.bullet_points,
            f.niche,
            f.created_at,
            c.title as course_title,
            c.thumbnail as course_thumbnail
        FROM freebies f
        LEFT JOIN courses c ON f.course_id = c.id
        ORDER BY f.created_at DESC
    ");
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📚 Templates geladen: " . count($freebies) . "</h3>";
    
    if (!empty($freebies)) {
        echo "<pre>";
        print_r($freebies);
        echo "</pre>";
    } else {
        echo "⚠️ Keine Templates gefunden (Query funktioniert aber)<br><br>";
    }
    
    // 2. Customer Freebies laden (template-basiert)
    $stmt_customer = $pdo->prepare("
        SELECT template_id, id as customer_freebie_id, unique_id, mockup_image_url, niche
        FROM customer_freebies 
        WHERE customer_id = ? AND (freebie_type = 'template' OR freebie_type IS NULL)
    ");
    $stmt_customer->execute([$customer_id]);
    $customer_freebies_data = [];
    while ($row = $stmt_customer->fetch(PDO::FETCH_ASSOC)) {
        if ($row['template_id']) {
            $customer_freebies_data[$row['template_id']] = [
                'id' => $row['customer_freebie_id'],
                'unique_id' => $row['unique_id'],
                'mockup_image_url' => $row['mockup_image_url'],
                'niche' => $row['niche']
            ];
        }
    }
    
    echo "<hr><h3>🎨 Customer angepasste Templates: " . count($customer_freebies_data) . "</h3>";
    echo "<pre>";
    print_r($customer_freebies_data);
    echo "</pre>";
    
    // 3. Custom Freebies laden
    $stmt_custom = $pdo->prepare("
        SELECT 
            cf.id,
            cf.headline,
            cf.subheadline,
            cf.background_color,
            cf.primary_color,
            cf.unique_id,
            cf.layout,
            cf.mockup_image_url,
            cf.niche,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.customer_id = ? AND cf.freebie_type = 'custom'
        ORDER BY cf.created_at DESC
    ");
    $stmt_custom->execute([$customer_id]);
    $customFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<hr><h3>✨ Custom Freebies: " . count($customFreebies) . "</h3>";
    echo "<pre>";
    print_r($customFreebies);
    echo "</pre>";
    
    // 4. Test ob die Section eingebunden wird
    echo "<hr><h3>🔍 Include Test</h3>";
    
    $freebiesFile = __DIR__ . '/../customer/sections/freebies.php';
    if (file_exists($freebiesFile)) {
        echo "✅ Datei existiert: <code>$freebiesFile</code><br>";
        echo "Größe: " . filesize($freebiesFile) . " bytes<br>";
        
        // Prüfe auf Syntax-Fehler
        $output = shell_exec("php -l " . escapeshellarg($freebiesFile) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ Keine Syntax-Fehler<br>";
        } else {
            echo "❌ <strong>Syntax-Fehler gefunden:</strong><br><pre>$output</pre>";
        }
    } else {
        echo "❌ Datei nicht gefunden: <code>$freebiesFile</code><br>";
    }
    
    // 5. Prüfe ob dashboard.php die Section korrekt lädt
    echo "<hr><h3>📄 Dashboard Include Check</h3>";
    
    $page = $_GET['page'] ?? 'overview';
    echo "Aktuelle Page: <code>$page</code><br>";
    
    if ($page === 'freebies') {
        echo "✅ Freebies-Seite wird geladen<br>";
        
        $expectedFile = __DIR__ . '/../customer/sections/freebies.php';
        if (file_exists($expectedFile)) {
            echo "✅ Section-Datei existiert und sollte geladen werden<br>";
        } else {
            echo "❌ Section-Datei nicht gefunden!<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Fehler:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><br>";
echo "<h3>💡 Mögliche Probleme:</h3>";
echo "<ol>";
echo "<li>PHP-Fehler in freebies.php → Prüfe Server Error Log</li>";
echo "<li>JavaScript-Fehler → Öffne Browser DevTools (F12) → Console</li>";
echo "<li>CSS versteckt Elemente → Prüfe mit Browser Inspector</li>";
echo "<li>Include-Pfad falsch → Prüfe dashboard.php</li>";
echo "</ol>";

echo "<br><a href='/customer/dashboard.php?page=freebies'>→ Zurück zu Freebies (mit DevTools öffnen!)</a>";
?>
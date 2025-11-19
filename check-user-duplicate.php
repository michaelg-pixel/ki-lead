<?php
/**
 * CHECK USER DUPLICATE - Prüft ob User doppelt angelegt wurde
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>User Duplicate Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
        .highlight { background: #ff4444; padding: 2px 5px; border-radius: 3px; }
        .success { background: #10b981; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
<h1>🔍 User Duplicate Check</h1>";

// USER 17
echo "<div class='box'>";
echo "<h2>USER ID 17 (Eingeloggt: 12@abnehmen-fitness.com)</h2>";
$stmt = $pdo->prepare("SELECT id, email, name, created_at, digistore_order_id, digistore_product_id FROM users WHERE id = 17");
$stmt->execute();
$user17 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user17) {
    echo "<table>";
    foreach ($user17 as $key => $value) {
        echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='highlight'>❌ USER 17 NICHT GEFUNDEN!</p>";
}
echo "</div>";

// USER 52
echo "<div class='box'>";
echo "<h2>USER ID 52 (Freebie-Owner)</h2>";
$stmt = $pdo->prepare("SELECT id, email, name, created_at, digistore_order_id, digistore_product_id, source FROM users WHERE id = 52");
$stmt->execute();
$user52 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user52) {
    echo "<table>";
    foreach ($user52 as $key => $value) {
        echo "<tr><th>$key</th><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='highlight'>❌ USER 52 NICHT GEFUNDEN!</p>";
}
echo "</div>";

// ALLE USERS MIT GLEICHER E-MAIL
if ($user17) {
    echo "<div class='box'>";
    echo "<h2>ALLE USERS MIT E-MAIL: " . htmlspecialchars($user17['email']) . "</h2>";
    $stmt = $pdo->prepare("SELECT id, email, name, created_at, source FROM users WHERE email = ? ORDER BY id");
    $stmt->execute([$user17['email']]);
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allUsers) > 1) {
        echo "<p class='highlight'>⚠️ DUPLIKAT GEFUNDEN! " . count($allUsers) . " Users mit gleicher E-Mail!</p>";
    } else {
        echo "<p class='success'>✓ Keine Duplikate</p>";
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Created</th><th>Source</th></tr>";
    foreach ($allUsers as $u) {
        echo "<tr>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . htmlspecialchars($u['email']) . "</td>";
        echo "<td>" . htmlspecialchars($u['name']) . "</td>";
        echo "<td>" . $u['created_at'] . "</td>";
        echo "<td>" . ($u['source'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}

// FREEBIE 53
echo "<div class='box'>";
echo "<h2>FREEBIE ID 53 (Das gekaufte Marktplatz-Freebie)</h2>";
$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 53");
$stmt->execute();
$freebie53 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($freebie53) {
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    echo "<tr><th>id</th><td>" . $freebie53['id'] . "</td></tr>";
    echo "<tr><th>customer_id</th><td class='highlight'>" . $freebie53['customer_id'] . " (SOLLTE 17 SEIN!)</td></tr>";
    echo "<tr><th>headline</th><td>" . htmlspecialchars($freebie53['headline']) . "</td></tr>";
    echo "<tr><th>unique_id</th><td>" . htmlspecialchars($freebie53['unique_id']) . "</td></tr>";
    echo "<tr><th>copied_from_freebie_id</th><td>" . ($freebie53['copied_from_freebie_id'] ?? 'NULL') . "</td></tr>";
    echo "<tr><th>created_at</th><td>" . $freebie53['created_at'] . "</td></tr>";
    echo "<tr><th>digistore_order_id</th><td>" . ($freebie53['digistore_order_id'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='highlight'>❌ FREEBIE 53 NICHT GEFUNDEN!</p>";
}
echo "</div>";

// KURSE PRÜFEN
echo "<div class='box'>";
echo "<h2>🎓 KURSE ZU FREEBIE 53</h2>";
if ($freebie53) {
    $stmt = $pdo->prepare("SELECT id, course_name, customer_id, created_at FROM courses WHERE customer_id = ?");
    $stmt->execute([$freebie53['customer_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($courses) {
        echo "<p class='success'>✓ " . count($courses) . " Kurs(e) gefunden</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>customer_id</th><th>Created</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . $course['id'] . "</td>";
            echo "<td>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "<td>" . $course['customer_id'] . "</td>";
            echo "<td>" . $course['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='highlight'>⚠️ Kein Kurs gefunden für customer_id " . $freebie53['customer_id'] . "</p>";
    }
}
echo "</div>";

// ANALYSE
echo "<div class='box'>";
echo "<h2>📊 ANALYSE & LÖSUNG</h2>";

if ($user17 && $user52) {
    if ($user17['email'] === $user52['email']) {
        echo "<p class='highlight'>🔴 PROBLEM: Beide Users haben die GLEICHE E-Mail!</p>";
        echo "<p>User 17 und User 52 sind DUPLIKATE!</p>";
        echo "<p><strong>Lösung:</strong></p>";
        echo "<ol>";
        echo "<li>✅ Freebie ID 53: customer_id von 52 auf 17 ändern</li>";
        echo "<li>✅ Kurs: customer_id von 52 auf 17 ändern</li>";
        echo "<li>✅ Alle Freebies von User 52 auf User 17 verschieben</li>";
        echo "<li>✅ User 52 löschen (optional)</li>";
        echo "</ol>";
        
        echo "<p><a href='fix-user-duplicate.php' style='display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px;'>🔧 Jetzt automatisch reparieren</a></p>";
    } else {
        echo "<p class='success'>✓ Keine E-Mail-Duplikate</p>";
        echo "<p>User 17: " . htmlspecialchars($user17['email']) . "</p>";
        echo "<p>User 52: " . htmlspecialchars($user52['email']) . "</p>";
        echo "<p><strong>Das sind unterschiedliche Personen!</strong></p>";
        echo "<p>Aber Freebie 53 gehört zu User 52, nicht zu User 17!</p>";
    }
}

echo "</div>";

echo "</body></html>";
?>
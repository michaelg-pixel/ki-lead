<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Bitte einloggen');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quick Structure Check</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #fff; padding: 20px; }
        pre { background: #0f0f1e; padding: 15px; border-radius: 5px; }
        h2 { color: #667eea; }
    </style>
</head>
<body>
    <h1>🔍 Schnelle Struktur-Prüfung</h1>
    
    <h2>1️⃣ customer_freebies Struktur:</h2>
    <pre><?php
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    ?></pre>
    
    <h2>2️⃣ Alle Daten in customer_freebies:</h2>
    <pre><?php
    $stmt = $pdo->query("SELECT * FROM customer_freebies");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    ?></pre>
    
    <h2>3️⃣ Deine Customer ID:</h2>
    <pre>Customer ID: <?php echo $customer_id; ?></pre>
    
    <h2>4️⃣ course_access Struktur:</h2>
    <pre><?php
    try {
        $stmt = $pdo->query("DESCRIBE course_access");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Fehler: " . $e->getMessage();
    }
    ?></pre>
    
    <h2>5️⃣ customer_tracking Struktur:</h2>
    <pre><?php
    try {
        $stmt = $pdo->query("DESCRIBE customer_tracking");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Fehler: " . $e->getMessage();
    }
    ?></pre>
    
    <p><a href="/customer/dashboard.php" style="color: #667eea;">← Zurück</a></p>
</body>
</html>

<?php
/**
 * FREEBIE & KURS ZUWEISUNG
 * Weist dem eingeloggten User Freebies und Kurse zu
 * SICHER: Nur für den aktuell eingeloggten User, keine Doppeleinträge
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id'])) {
    die('❌ Nicht eingeloggt. Bitte zuerst einloggen: <a href="/public/login.php">Login</a>');
}

require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Unbekannt';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Freebie & Kurs Zuweisung</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #fff; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #667eea; }
        h2 { color: #764ba2; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .info-box { background: #16213e; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
        .alert { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; color: #92400e; }
        button { background: #667eea; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin: 10px 5px; }
        button:hover { background: #5568d3; }
        button.danger { background: #ef4444; }
        button.danger:hover { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #16213e; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #667eea; color: white; }
        .status-ok { color: #22c55e; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        code { background: #0f0f1e; padding: 2px 6px; border-radius: 4px; color: #fbbf24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎁 Freebie & Videokurs Zuweisung</h1>
        
        <div class="info-box">
            <p><strong>Eingeloggt als:</strong> <?php echo htmlspecialchars($user_name); ?></p>
            <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        </div>

        <?php
        try {
            $pdo = getDBConnection();
            
            // ===== AKTUELLER STATUS =====
            echo "<h2>📊 Aktueller Status</h2>";
            
            // Freebies zählen
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM customer_freebies cf
                INNER JOIN freebies f ON cf.freebie_id = f.id
                WHERE cf.customer_id = ?
            ");
            $stmt->execute([$user_id]);
            $current_freebies = $stmt->fetchColumn();
            
            // Kurse zählen
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM course_access 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $current_courses = $stmt->fetchColumn();
            
            echo "<div class='info-box'>";
            echo "<p><strong>Deine aktuellen Freebies:</strong> <span class='" . ($current_freebies > 0 ? 'status-ok' : 'status-error') . "'>$current_freebies</span></p>";
            echo "<p><strong>Deine aktuellen Kurse:</strong> <span class='" . ($current_courses > 0 ? 'status-ok' : 'status-error') . "'>$current_courses</span></p>";
            echo "</div>";
            
            // ===== VERFÜGBARE FREEBIES =====
            echo "<h2>🎁 Verfügbare Freebies im System</h2>";
            
            $stmt = $pdo->query("SELECT id, name FROM freebies ORDER BY id");
            $available_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($available_freebies)) {
                echo "<div class='alert error'>❌ Keine Freebies im System gefunden!</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
                
                foreach ($available_freebies as $freebie) {
                    // Prüfen ob bereits zugewiesen
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM customer_freebies 
                        WHERE customer_id = ? AND freebie_id = ?
                    ");
                    $stmt->execute([$user_id, $freebie['id']]);
                    $is_assigned = $stmt->fetchColumn() > 0;
                    
                    echo "<tr>";
                    echo "<td>{$freebie['id']}</td>";
                    echo "<td>{$freebie['name']}</td>";
                    echo "<td>" . ($is_assigned ? "<span class='status-ok'>✅ Bereits zugewiesen</span>" : "<span class='status-error'>❌ Nicht zugewiesen</span>") . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
            // ===== VERFÜGBARE KURSE =====
            echo "<h2>🎓 Verfügbare Kurse im System</h2>";
            
            $stmt = $pdo->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY id");
            $available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($available_courses)) {
                echo "<div class='alert error'>❌ Keine aktiven Kurse im System gefunden!</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Status</th></tr>";
                
                foreach ($available_courses as $course) {
                    // Prüfen ob bereits zugewiesen
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM course_access 
                        WHERE user_id = ? AND course_id = ?
                    ");
                    $stmt->execute([$user_id, $course['id']]);
                    $is_assigned = $stmt->fetchColumn() > 0;
                    
                    echo "<tr>";
                    echo "<td>{$course['id']}</td>";
                    echo "<td>{$course['title']}</td>";
                    echo "<td>" . ($is_assigned ? "<span class='status-ok'>✅ Bereits zugewiesen</span>" : "<span class='status-error'>❌ Nicht zugewiesen</span>") . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
            // ===== ZUWEISUNG BUTTON =====
            if (!empty($available_freebies) || !empty($available_courses)) {
                echo "<h2>⚡ Automatische Zuweisung</h2>";
                echo "<div class='info-box'>";
                echo "<p>Klicke auf den Button um dir <strong>ALLE</strong> verfügbaren Freebies und Kurse zuzuweisen.</p>";
                echo "<p><strong>⚠️ Hinweis:</strong> Bereits zugewiesene Items werden übersprungen (keine Duplikate).</p>";
                
                if (isset($_POST['assign_all'])) {
                    echo "<div class='alert success'>";
                    echo "<h3>✅ Zuweisung läuft...</h3>";
                    
                    $assigned_freebies = 0;
                    $assigned_courses = 0;
                    $errors = [];
                    
                    // FREEBIES ZUWEISEN
                    foreach ($available_freebies as $freebie) {
                        try {
                            // Prüfen ob schon vorhanden
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) 
                                FROM customer_freebies 
                                WHERE customer_id = ? AND freebie_id = ?
                            ");
                            $stmt->execute([$user_id, $freebie['id']]);
                            
                            if ($stmt->fetchColumn() == 0) {
                                // Noch nicht vorhanden -> zuweisen
                                $stmt = $pdo->prepare("
                                    INSERT INTO customer_freebies (customer_id, freebie_id, created_at)
                                    VALUES (?, ?, NOW())
                                ");
                                $stmt->execute([$user_id, $freebie['id']]);
                                $assigned_freebies++;
                                echo "<p>✅ Freebie zugewiesen: {$freebie['name']}</p>";
                            }
                        } catch (PDOException $e) {
                            $errors[] = "Freebie {$freebie['name']}: " . $e->getMessage();
                        }
                    }
                    
                    // KURSE ZUWEISEN
                    foreach ($available_courses as $course) {
                        try {
                            // Prüfen ob schon vorhanden
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) 
                                FROM course_access 
                                WHERE user_id = ? AND course_id = ?
                            ");
                            $stmt->execute([$user_id, $course['id']]);
                            
                            if ($stmt->fetchColumn() == 0) {
                                // Noch nicht vorhanden -> zuweisen
                                $stmt = $pdo->prepare("
                                    INSERT INTO course_access (user_id, course_id, granted_at)
                                    VALUES (?, ?, NOW())
                                ");
                                $stmt->execute([$user_id, $course['id']]);
                                $assigned_courses++;
                                echo "<p>✅ Kurs zugewiesen: {$course['title']}</p>";
                            }
                        } catch (PDOException $e) {
                            $errors[] = "Kurs {$course['title']}: " . $e->getMessage();
                        }
                    }
                    
                    echo "<hr>";
                    echo "<p><strong>📊 Zusammenfassung:</strong></p>";
                    echo "<p>✅ Freebies zugewiesen: <strong>$assigned_freebies</strong></p>";
                    echo "<p>✅ Kurse zugewiesen: <strong>$assigned_courses</strong></p>";
                    
                    if (!empty($errors)) {
                        echo "<p class='status-error'>⚠️ Fehler bei einigen Zuweisungen:</p><ul>";
                        foreach ($errors as $error) {
                            echo "<li>$error</li>";
                        }
                        echo "</ul>";
                    }
                    
                    if ($assigned_freebies > 0 || $assigned_courses > 0) {
                        echo "<p style='margin-top: 20px;'>🎉 <strong>Fertig! Gehe jetzt zurück zum Dashboard und aktualisiere die Seite.</strong></p>";
                        echo "<p><a href='/customer/dashboard.php?page=overview' style='color: #667eea; text-decoration: underline;'>→ Zum Dashboard</a></p>";
                    } else {
                        echo "<p>ℹ️ Alles war bereits zugewiesen.</p>";
                    }
                    
                    echo "</div>";
                } else {
                    echo "<form method='POST'>";
                    echo "<button type='submit' name='assign_all'>🚀 ALLE Freebies & Kurse zuweisen</button>";
                    echo "</form>";
                }
                
                echo "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='alert error'>";
            echo "<h3>❌ Datenbankfehler</h3>";
            echo "<p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #333;">
            <p><a href="/customer/dashboard.php?page=overview" style="color: #667eea;">← Zurück zum Dashboard</a></p>
        </div>
    </div>
</body>
</html>
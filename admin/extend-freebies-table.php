<?php
/**
 * FREEBIES TABELLE ERWEITERN - INTELLIGENT
 * Prüft welche Spalten fehlen und fügt nur diese hinzu
 * Sicher: Löscht KEINE Daten!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database einbinden
require_once __DIR__ . '/../config/database.php';

if (!isset($pdo)) {
    die("Datenbankverbindung fehlgeschlagen!");
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebies Tabelle Erweitern</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-blue-600 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <i class="fas fa-wrench"></i> Freebies Tabelle Erweitern
        </h1>

        <?php
        // Benötigte Spalten definieren
        $requiredColumns = [
            'name' => "varchar(255) NOT NULL DEFAULT ''",
            'description' => "text DEFAULT NULL",
            'preheadline' => "varchar(255) DEFAULT NULL",
            'headline' => "varchar(500) NOT NULL DEFAULT ''",
            'subheadline' => "text DEFAULT NULL",
            'bullet_points' => "text DEFAULT NULL",
            'urgency_text' => "varchar(500) DEFAULT NULL",
            'layout' => "enum('layout1','layout2','layout3') DEFAULT 'layout1'",
            'cta_text' => "varchar(255) DEFAULT 'Jetzt kostenlos downloaden'",
            'cta_button_color' => "varchar(20) DEFAULT '#5B8DEF'",
            'mockup_image_url' => "varchar(500) DEFAULT NULL",
            'allow_customer_image' => "tinyint(1) DEFAULT 1",
            'linked_course_id' => "int(11) DEFAULT NULL",
            'primary_color' => "varchar(20) DEFAULT '#7C3AED'",
            'secondary_color' => "varchar(20) DEFAULT '#EC4899'",
            'text_color' => "varchar(20) DEFAULT '#1F2937'",
            'background_color' => "varchar(20) DEFAULT '#FFFFFF'",
            'heading_font' => "varchar(100) DEFAULT 'Inter'",
            'headline_font' => "varchar(100) DEFAULT 'Inter'",
            'headline_size' => "int(11) DEFAULT 48",
            'headline_size_mobile' => "int(11) DEFAULT 32",
            'body_font' => "varchar(100) DEFAULT 'Inter'",
            'body_size' => "int(11) DEFAULT 16",
            'body_size_mobile' => "int(11) DEFAULT 14",
            'raw_code' => "text DEFAULT NULL",
            'pixel_code' => "text DEFAULT NULL",
            'custom_css' => "text DEFAULT NULL",
            'optin_placeholder_email' => "varchar(100) DEFAULT 'Deine E-Mail-Adresse'",
            'optin_button_text' => "varchar(100) DEFAULT 'KOSTENLOS DOWNLOADEN'",
            'optin_privacy_text' => "text DEFAULT NULL",
            'show_footer' => "tinyint(1) DEFAULT 1",
            'footer_links' => "text DEFAULT NULL",
            'usage_count' => "int(11) DEFAULT 0"
        ];

        // Bestehende Spalten abrufen
        $stmt = $pdo->query("SHOW COLUMNS FROM freebies");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fehlende Spalten finden
        $missingColumns = [];
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                $missingColumns[$column] = $definition;
            }
        }

        if (empty($missingColumns)) {
            echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">';
            echo '<p class="text-green-800 font-medium">';
            echo '<i class="fas fa-check-circle mr-2"></i>';
            echo 'Perfekt! Alle benötigten Spalten sind bereits vorhanden!';
            echo '</p>';
            echo '</div>';
            
            echo '<div class="mt-6">';
            echo '<a href="freebie-templates.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 inline-block">';
            echo 'Zu den Templates';
            echo '</a>';
            echo '</div>';
            
        } else {
            
            echo '<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded mb-6">';
            echo '<p class="text-yellow-800 font-medium">';
            echo '<i class="fas fa-exclamation-triangle mr-2"></i>';
            echo 'Es fehlen ' . count($missingColumns) . ' Spalten. Diese werden jetzt hinzugefügt...';
            echo '</p>';
            echo '</div>';

            if (isset($_POST['add_columns'])) {
                // Spalten hinzufügen
                $success = 0;
                $errors = 0;
                
                echo '<div class="space-y-2 mb-6">';
                
                foreach ($missingColumns as $column => $definition) {
                    try {
                        $sql = "ALTER TABLE freebies ADD COLUMN `$column` $definition";
                        $pdo->exec($sql);
                        
                        echo '<div class="bg-green-50 p-3 rounded">';
                        echo '<i class="fas fa-check text-green-600 mr-2"></i>';
                        echo '<code class="text-green-800">' . htmlspecialchars($column) . '</code> erfolgreich hinzugefügt';
                        echo '</div>';
                        
                        $success++;
                    } catch (PDOException $e) {
                        echo '<div class="bg-red-50 p-3 rounded">';
                        echo '<i class="fas fa-times text-red-600 mr-2"></i>';
                        echo '<code class="text-red-800">' . htmlspecialchars($column) . '</code>: ' . $e->getMessage();
                        echo '</div>';
                        
                        $errors++;
                    }
                }
                
                echo '</div>';
                
                if ($errors === 0) {
                    echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 rounded mb-6">';
                    echo '<p class="text-green-800 font-bold text-lg">';
                    echo '<i class="fas fa-check-circle mr-2"></i>';
                    echo 'Erfolgreich! Alle ' . $success . ' Spalten wurden hinzugefügt!';
                    echo '</p>';
                    echo '</div>';
                    
                    echo '<div class="space-x-4">';
                    echo '<a href="freebie-templates.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 inline-block">';
                    echo '<i class="fas fa-gift mr-2"></i>Zu den Templates';
                    echo '</a>';
                    echo '<a href="freebie-create.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 inline-block">';
                    echo '<i class="fas fa-plus mr-2"></i>Erstes Template erstellen';
                    echo '</a>';
                    echo '</div>';
                } else {
                    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">';
                    echo '<p class="text-red-800">';
                    echo '<strong>Achtung:</strong> ' . $errors . ' Fehler aufgetreten. Prüfe die Meldungen oben.';
                    echo '</p>';
                    echo '</div>';
                }
                
            } else {
                // Formular anzeigen
                echo '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-6">';
                echo '<h3 class="font-bold text-blue-800 mb-2">Folgende Spalten werden hinzugefügt:</h3>';
                echo '<ul class="list-disc list-inside space-y-1 text-blue-700">';
                foreach ($missingColumns as $column => $definition) {
                    echo '<li><code>' . htmlspecialchars($column) . '</code></li>';
                }
                echo '</ul>';
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="add_columns" class="bg-purple-600 text-white px-8 py-4 rounded-lg hover:bg-purple-700 font-bold text-lg">';
                echo '<i class="fas fa-magic mr-2"></i>';
                echo 'Spalten jetzt hinzufügen (' . count($missingColumns) . ')';
                echo '</button>';
                echo '</form>';
                
                echo '<p class="text-sm text-gray-600 mt-4">';
                echo '<i class="fas fa-info-circle mr-1"></i>';
                echo 'Deine bestehenden Daten bleiben erhalten! Es werden nur neue Spalten hinzugefügt.';
                echo '</p>';
            }
        }

        // Aktuelle Struktur anzeigen
        echo '<div class="mt-8 border-t pt-6">';
        echo '<h3 class="font-bold text-gray-800 mb-4">Aktuelle Tabellenstruktur:</h3>';
        
        $stmt = $pdo->query("SHOW COLUMNS FROM freebies");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<div class="overflow-x-auto">';
        echo '<table class="min-w-full text-sm">';
        echo '<thead class="bg-gray-100">';
        echo '<tr>';
        echo '<th class="px-4 py-2 text-left">Spalte</th>';
        echo '<th class="px-4 py-2 text-left">Typ</th>';
        echo '<th class="px-4 py-2 text-left">Null</th>';
        echo '<th class="px-4 py-2 text-left">Standard</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($columns as $col) {
            $isNew = isset($missingColumns[$col['Field']]);
            $rowClass = $isNew ? 'bg-green-50' : '';
            
            echo '<tr class="' . $rowClass . ' border-b">';
            echo '<td class="px-4 py-2"><code>' . htmlspecialchars($col['Field']) . '</code>';
            if ($isNew) echo ' <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">NEU</span>';
            echo '</td>';
            echo '<td class="px-4 py-2 text-gray-600">' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td class="px-4 py-2 text-gray-600">' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td class="px-4 py-2 text-gray-600">' . htmlspecialchars($col['Default'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
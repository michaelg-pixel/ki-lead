            <?php elseif ($page === 'marktplatz'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/marktplatz.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Marktplatz wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'marktplatz-browse'): ?>
                <?php 
                $section_file = __DIR__ . '/sections/marktplatz-browse.php';
                if (file_exists($section_file)) {
                    include $section_file;
                } else {
                    echo '<div style="padding: 32px; text-align: center;"><h3>Marktplatz wird geladen...</h3></div>';
                }
                ?>
            
            <?php elseif ($page === 'ki-prompt'): ?>
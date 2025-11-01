<?php
/**
 * Shows what happens when accessing clean URLs
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>URL Debug</title>
    <style>
        body { font-family: monospace; padding: 40px; background: #1a1a2e; color: #fff; }
        .info { background: #16213e; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .success { background: #0f3443; border-left: 4px solid #0ea5e9; }
        .key { color: #38bdf8; font-weight: bold; }
        .value { color: #a78bfa; }
    </style>
</head>
<body>
    <h1>🔍 URL Debugging Info</h1>
    
    <div class="info success">
        <h2>✅ This file is being executed!</h2>
        <p>If you see this, the .htaccess is working partially.</p>
    </div>
    
    <div class="info">
        <h3>$_SERVER Info:</h3>
        <p><span class="key">REQUEST_URI:</span> <span class="value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></span></p>
        <p><span class="key">SCRIPT_NAME:</span> <span class="value"><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?></span></p>
        <p><span class="key">PHP_SELF:</span> <span class="value"><?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'N/A'); ?></span></p>
        <p><span class="key">QUERY_STRING:</span> <span class="value"><?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? 'N/A'); ?></span></p>
        <p><span class="key">REQUEST_METHOD:</span> <span class="value"><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A'); ?></span></p>
    </div>
    
    <div class="info">
        <h3>$_GET Parameters:</h3>
        <?php if (empty($_GET)): ?>
            <p><em>No GET parameters</em></p>
        <?php else: ?>
            <?php foreach ($_GET as $key => $value): ?>
                <p><span class="key"><?php echo htmlspecialchars($key); ?>:</span> <span class="value"><?php echo htmlspecialchars($value); ?></span></p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <h3>Parsed URL Segments:</h3>
        <?php
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        echo "<p><span class='key'>Path:</span> <span class='value'>" . htmlspecialchars($path) . "</span></p>";
        echo "<p><span class='key'>Segments:</span></p>";
        echo "<ul>";
        foreach ($segments as $index => $segment) {
            echo "<li>[$index] <span class='value'>" . htmlspecialchars($segment) . "</span></li>";
        }
        echo "</ul>";
        ?>
    </div>
    
    <div class="info">
        <h3>.htaccess Test:</h3>
        <p>Access these URLs to test:</p>
        <ul>
            <li><a href="/freebie/index.php?id=08385ca983cb6dfdffca575e84e22e93" style="color: #38bdf8;">Direct with parameter</a></li>
            <li><a href="/freebie/08385ca983cb6dfdffca575e84e22e93" style="color: #a78bfa;">Clean URL</a></li>
        </ul>
    </div>
</body>
</html>
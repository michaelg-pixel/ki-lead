<?php
// SERVER DEBUG INFO
header('Content-Type: text/plain; charset=utf-8');

echo "=== SERVER DEBUG INFO ===\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Server Time: " . time() . "\n\n";

echo "=== CURRENT DIRECTORY ===\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Script: " . __FILE__ . "\n\n";

echo "=== DASHBOARD.PHP INFO ===\n";
$dashboard_file = __DIR__ . '/dashboard.php';
if (file_exists($dashboard_file)) {
    echo "Dashboard exists: YES\n";
    echo "Dashboard size: " . filesize($dashboard_file) . " bytes\n";
    echo "Dashboard modified: " . date('Y-m-d H:i:s', filemtime($dashboard_file)) . "\n";
    
    // Erste 500 Zeichen lesen
    $content = file_get_contents($dashboard_file);
    $first_500 = substr($content, 0, 500);
    echo "\n=== ERSTE 500 ZEICHEN VON DASHBOARD.PHP ===\n";
    echo $first_500 . "\n\n";
    
    // Prüfe auf VERSION
    if (preg_match('/DASHBOARD_VERSION.*?[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        echo "GEFUNDENE VERSION: " . $matches[1] . "\n";
    } else {
        echo "KEINE VERSION GEFUNDEN!\n";
    }
} else {
    echo "Dashboard exists: NO - FILE NOT FOUND!\n";
}

echo "\n=== SECTIONS DIRECTORY ===\n";
$sections_dir = __DIR__ . '/sections';
if (is_dir($sections_dir)) {
    echo "Sections dir exists: YES\n";
    $files = scandir($sections_dir);
    echo "Files in sections/:\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $full_path = $sections_dir . '/' . $file;
            $size = filesize($full_path);
            $modified = date('Y-m-d H:i:s', filemtime($full_path));
            echo "  - $file ($size bytes, modified: $modified)\n";
        }
    }
} else {
    echo "Sections dir exists: NO\n";
}

echo "\n=== GIT INFO ===\n";
$git_dir = dirname(__DIR__);
if (is_dir($git_dir . '/.git')) {
    echo "Git repository: YES\n";
    
    // Git Status
    $git_status = shell_exec("cd $git_dir && git status 2>&1");
    echo "Git Status:\n$git_status\n";
    
    // Current commit
    $git_commit = shell_exec("cd $git_dir && git log -1 --oneline 2>&1");
    echo "Current Commit:\n$git_commit\n";
    
    // Remote status
    $git_remote = shell_exec("cd $git_dir && git remote -v 2>&1");
    echo "Git Remote:\n$git_remote\n";
} else {
    echo "Git repository: NO\n";
}

echo "\n=== PHP INFO ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OPcache Enabled: " . (function_exists('opcache_get_status') && opcache_get_status() ? 'YES' : 'NO') . "\n";

if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache) {
        echo "OPcache Memory Used: " . round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
        echo "OPcache Cached Scripts: " . $opcache['opcache_statistics']['num_cached_scripts'] . "\n";
    }
}

echo "\n=== DONE ===\n";

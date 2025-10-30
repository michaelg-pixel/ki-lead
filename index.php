cat > /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/index.php << 'INDEXEOF'
<?php
session_start();

// Wenn eingeloggt, zum Dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /customer/dashboard.php');
    exit;
}

// Sonst zum Login
header('Location: /public/login.php');
exit;
?>
INDEXEOF
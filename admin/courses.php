<?php
session_start();

// Debug
if (!isset($_SESSION['user_id'])) {
    die("Keine Session! <a href='/public/login.php'>Login</a>");
}
if ($_SESSION['user_role'] !== 'admin') {
    die("Kein Admin! Deine Rolle: " . ($_SESSION['user_role'] ?? 'keine'));
}

require_once '../config/database.php';
$pdo = getDBConnection();
$courses = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kurse - KI Leadsystem Admin</title>
    <style>
        body { font-family: Arial; background: #f5f7fa; margin: 0; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .empty { text-align: center; padding: 40px; background: white; }
        .back { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <a href="/admin/dashboard.php" class="back">← Zurück</a>
    <h1>📚 Kurse verwalten</h1>
    
    <?php if (count($courses) > 0): ?>
    <table>
        <tr><th>ID</th><th>Titel</th><th>Aktiv</th><th>Erstellt</th></tr>
        <?php foreach($courses as $c): ?>
        <tr>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo htmlspecialchars($c['title'] ?? 'Unbekannt'); ?></td>
            <td><?php echo $c['is_active'] ? '✓' : '✗'; ?></td>
            <td><?php echo date('d.m.Y', strtotime($c['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <div class="empty">
        <p>📚 Noch keine Kurse vorhanden.</p>
    </div>
    <?php endif; ?>
</body>
</html>

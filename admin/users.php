<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: /public/login.php'); exit; }
require_once '../config/database.php';
$pdo = getDBConnection();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>User - KI Leadsystem Admin</title>
<style>body{font-family:Arial;background:#f5f7fa;margin:0}.nav{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:16px 24px;display:flex;justify-content:space-between}.container{max-width:1200px;margin:40px auto;padding:0 24px}table{width:100%;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}th,td{padding:12px;text-align:left;border-bottom:1px solid #eee}th{background:#667eea;color:#fff}.badge{padding:4px 8px;border-radius:4px;font-size:12px}.badge-admin{background:#667eea;color:#fff}.badge-customer{background:#e0e0e0}</style>
</head><body>
<div class="nav"><span>🌟 KI Leadsystem Admin</span><a href="/admin/dashboard.php" style="color:#fff">Dashboard</a></div>
<div class="container"><h1>👥 User verwalten</h1>
<table><tr><th>ID</th><th>Name</th><th>E-Mail</th><th>Rolle</th><th>Aktiv</th><th>Registriert</th></tr>
<?php foreach($users as $u): ?>
<tr><td><?=$u['id']?></td><td><?=htmlspecialchars($u['name'])?></td><td><?=htmlspecialchars($u['email'])?></td>
<td><span class="badge badge-<?=$u['role']?>"><?=strtoupper($u['role'])?></span></td>
<td><?=$u['is_active']?'✓':'✗'?></td><td><?=date('d.m.Y',strtotime($u['created_at']))?></td></tr>
<?php endforeach; ?>
</table></div></body></html>

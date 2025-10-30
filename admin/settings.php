<?php session_start(); if(!isset($_SESSION['user_id'])||$_SESSION['user_role']!=='admin'){header('Location:/public/login.php');exit;} ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Einstellungen</title>
<style>body{font-family:Arial;background:#f5f7fa;margin:0}.nav{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:16px 24px}.container{max-width:800px;margin:40px auto;padding:24px}.card{background:#fff;padding:24px;border-radius:8px;margin:20px 0;box-shadow:0 2px 8px rgba(0,0,0,0.1)}</style>
</head><body>
<div class="nav">🌟 KI Leadsystem Admin</div>
<div class="container"><h1>⚙️ Einstellungen</h1>
<div class="card"><h3>System</h3><p>PHP: <?=phpversion()?></p></div>
<div class="card"><h3>Weitere Einstellungen</h3><p>Hier können später Einstellungen konfiguriert werden.</p></div>
<a href="/admin/dashboard.php">← Zurück</a>
</div></body></html>

<?php
/**
 * SIMPLE TEST - No includes!
 * URL: /f/simple-test.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Simple Public Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-align: center;
        }
        .box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 16px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        h1 { font-size: 48px; margin: 0 0 20px 0; }
        p { font-size: 18px; line-height: 1.6; }
        .code { 
            background: rgba(0,0,0,0.2); 
            padding: 10px; 
            border-radius: 8px; 
            font-family: monospace;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>✅ SUCCESS!</h1>
        <p><strong>The /f/ folder is publicly accessible!</strong></p>
        <p>This file has NO includes, NO auth checks, NOTHING.</p>
        <p>If you can see this, the basic access works.</p>
        
        <div class="code">
            <strong>File:</strong> /f/simple-test.php<br>
            <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>PHP:</strong> <?php echo phpversion(); ?>
        </div>
    </div>
</body>
</html>

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['lead_id'])) {
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Session abgelaufen</title></head><body style="font-family:sans-serif;text-align:center;padding:100px"><h1>🔒 Session abgelaufen</h1><p>Bitte registriere dich erneut.</p></body></html>';
    exit();
}

$lead_id = $_SESSION['lead_id'];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Verbindung fehlgeschlagen");
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT * FROM lead_users WHERE id = ?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    session_destroy();
    die("Lead nicht gefunden.");
}

// Freebies mit KORREKTEN Spaltennamen
$freebies = [];
$stmt = $conn->prepare("
    SELECT 
        cf.id,
        cf.headline as name,
        cf.subheadline as description,
        cf.mockup_image_url as mockup_image,
        cf.customer_id,
        cf.unique_id,
        lfa.granted_at,
        (
            SELECT COUNT(*) 
            FROM freebie_course_lessons fcl
            INNER JOIN freebie_course_modules fcm ON fcl.module_id = fcm.id
            INNER JOIN freebie_courses fc ON fcm.course_id = fc.id
            WHERE fc.freebie_id = cf.id
        ) as total_lessons
    FROM customer_freebies cf
    INNER JOIN lead_freebie_access lfa ON cf.id = lfa.freebie_id
    WHERE lfa.lead_id = ?
    ORDER BY lfa.granted_at DESC
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $freebies[] = $row;
}
$stmt->close();

$referral_enabled = false;
$referral_link = '';
$referral_stats = ['total' => 0, 'pending' => 0];

if (!empty($lead['user_id'])) {
    $stmt = $conn->prepare("SELECT referral_enabled FROM users WHERE id = ?");
    $stmt->bind_param("i", $lead['user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user_data && $user_data['referral_enabled']) {
        $referral_enabled = true;
        
        // KORREKTUR: Link zur Freebie-Landingpage statt direkt zur Registrierung
        // Hole die unique_id des ersten Freebies für den Link
        if (!empty($freebies) && !empty($freebies[0]['unique_id'])) {
            $referral_link = SITE_URL . '/freebie/?id=' . $freebies[0]['unique_id'] . '&ref=' . $lead['referral_code'];
        } else {
            // Fallback: Wenn keine unique_id vorhanden, verwende den alten Link
            $referral_link = SITE_URL . '/lead_register.php?freebie=' . $lead['freebie_id'] . '&customer=' . $lead['user_id'] . '&ref=' . $lead['referral_code'];
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending FROM lead_referrals WHERE referrer_id = ?");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $referral_stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$current_page = $_GET['page'] ?? 'dashboard';
$menu_items = ['dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard'], 'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse']];
if ($referral_enabled) $menu_items['empfehlen'] = ['icon' => 'fa-share-alt', 'label' => 'Empfehlen'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px}.container{max-width:1200px;margin:0 auto}.header{background:#fff;border-radius:20px;padding:30px;margin-bottom:30px;box-shadow:0 10px 40px rgba(0,0,0,0.1)}.header h1{color:#333;font-size:28px;margin-bottom:10px}.header p{color:#666;font-size:16px}.logout-btn{float:right;background:#ff4757;color:#fff;border:none;padding:12px 24px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;text-decoration:none;display:inline-block;transition:all .3s}.logout-btn:hover{background:#ee5a6f;transform:translateY(-2px)}.nav-tabs{background:#fff;border-radius:20px;padding:20px;margin-bottom:30px;box-shadow:0 10px 40px rgba(0,0,0,0.1);display:flex;gap:10px;flex-wrap:wrap}.nav-tab{padding:12px 24px;background:#f8f9fa;color:#666;border-radius:12px;font-size:15px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .3s;border:none;cursor:pointer}.nav-tab:hover{background:#e9ecef;color:#333}.nav-tab.active{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}.content{background:#fff;border-radius:20px;padding:40px;box-shadow:0 10px 40px rgba(0,0,0,0.1)}.page-content{display:none}.page-content.active{display:block}.freebie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:25px;margin-top:30px}.freebie-card{background:#fff;border:2px solid #e9ecef;border-radius:16px;overflow:hidden;transition:all .3s;cursor:pointer}.freebie-card:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(0,0,0,0.15);border-color:#667eea}.freebie-mockup{width:100%;height:200px;object-fit:cover;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;color:#fff;font-size:48px}.freebie-content{padding:25px}.freebie-title{font-size:20px;font-weight:700;color:#333;margin-bottom:10px}.freebie-description{font-size:14px;color:#666;line-height:1.6;margin-bottom:20px;min-height:60px}.freebie-meta{display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid #e9ecef}.lesson-count{display:flex;align-items:center;gap:8px;color:#666;font-size:14px}.lesson-count i{color:#667eea}.start-btn{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;width:100%;margin-top:15px;transition:all .3s}.start-btn:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,0.4)}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin:30px 0}.stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:25px;border-radius:16px;text-align:center}.stat-value{font-size:36px;font-weight:700;margin-bottom:8px}.stat-label{font-size:14px;opacity:0.9}.referral-link-box{background:#f8f9fa;border:2px dashed #dee2e6;border-radius:12px;padding:20px;margin:30px 0}.referral-link-box h3{margin-bottom:15px;color:#333}.link-input-group{display:flex;gap:10px}.link-input{flex:1;padding:12px 15px;border:1px solid #dee2e6;border-radius:8px;font-size:13px;font-family:monospace}.copy-btn{background:#667eea;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:600;transition:all .3s}.copy-btn:hover{background:#5568d3}.copy-btn.copied{background:#2ecc71}.empty-state{text-align:center;padding:60px 20px;color:#999}.empty-state i{font-size:64px;margin-bottom:20px;opacity:0.5}.empty-state h3{font-size:24px;margin-bottom:10px;color:#666}.empty-state p{font-size:16px}@media (max-width:768px){body{padding:10px}.header{padding:20px}.header h1{font-size:22px}.logout-btn{float:none;display:block;margin-top:15px;text-align:center}.nav-tabs{padding:15px}.nav-tab{flex:1;min-width:calc(50% - 5px);justify-content:center}.content{padding:20px}.freebie-grid{grid-template-columns:1fr}.stats-grid{grid-template-columns:1fr}.link-input-group{flex-direction:column}}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="lead_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            <h1>Willkommen<?php echo !empty($lead['name']) ? ', ' . htmlspecialchars($lead['name']) : ''; ?>!</h1>
            <p>E-Mail: <?php echo htmlspecialchars($lead['email']); ?></p>
        </div>
        
        <div class="nav-tabs">
            <?php foreach ($menu_items as $page => $item): ?>
                <a href="?page=<?php echo $page; ?>" class="nav-tab <?php echo $current_page === $page ? 'active' : ''; ?>">
                    <i class="fas <?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="content">
            <div class="page-content <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <h2>Dein Dashboard</h2>
                <p style="color:#666;margin-top:10px;margin-bottom:30px">Hier findest du eine Übersicht über deine verfügbaren Kurse.</p>
                
                <?php if ($referral_enabled): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total'] - $referral_stats['pending']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <h3 style="margin-top:40px;margin-bottom:20px">Deine Kurse</h3>
                <?php if (empty($freebies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Noch keine Kurse verfügbar</h3>
                        <p>Neue Kurse werden hier erscheinen, sobald sie verfügbar sind.</p>
                    </div>
                <?php else: ?>
                    <div class="freebie-grid">
                        <?php foreach ($freebies as $freebie): ?>
                            <div class="freebie-card" onclick="window.location.href='customer/view_freebie.php?id=<?php echo $freebie['id']; ?>'">
                                <?php if (!empty($freebie['mockup_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" alt="<?php echo htmlspecialchars($freebie['name'] ?? 'Kurs'); ?>" class="freebie-mockup">
                                <?php else: ?>
                                    <div class="freebie-mockup"><i class="fas fa-graduation-cap"></i></div>
                                <?php endif; ?>
                                
                                <div class="freebie-content">
                                    <div class="freebie-title"><?php echo htmlspecialchars($freebie['name'] ?? 'Unbenannter Kurs'); ?></div>
                                    <div class="freebie-description"><?php echo htmlspecialchars($freebie['description'] ?? 'Keine Beschreibung'); ?></div>
                                    <div class="freebie-meta">
                                        <span class="lesson-count">
                                            <i class="fas fa-play-circle"></i>
                                            <?php echo $freebie['total_lessons']; ?> Lektionen
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="page-content <?php echo $current_page === 'kurse' ? 'active' : ''; ?>">
                <h2>Meine Kurse</h2>
                <p style="color:#666;margin-top:10px;margin-bottom:30px">Alle deine verfügbaren Kurse im Überblick.</p>
                
                <?php if (empty($freebies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Noch keine Kurse verfügbar</h3>
                        <p>Neue Kurse werden hier erscheinen.</p>
                    </div>
                <?php else: ?>
                    <div class="freebie-grid">
                        <?php foreach ($freebies as $freebie): ?>
                            <div class="freebie-card">
                                <?php if (!empty($freebie['mockup_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($freebie['mockup_image']); ?>" alt="Kurs" class="freebie-mockup">
                                <?php else: ?>
                                    <div class="freebie-mockup"><i class="fas fa-graduation-cap"></i></div>
                                <?php endif; ?>
                                
                                <div class="freebie-content">
                                    <div class="freebie-title"><?php echo htmlspecialchars($freebie['name'] ?? 'Kurs'); ?></div>
                                    <div class="freebie-description"><?php echo htmlspecialchars($freebie['description'] ?? ''); ?></div>
                                    <div class="freebie-meta">
                                        <span class="lesson-count">
                                            <i class="fas fa-play-circle"></i>
                                            <?php echo $freebie['total_lessons']; ?> Lektionen
                                        </span>
                                    </div>
                                    <button class="start-btn" onclick="window.location.href='customer/view_freebie.php?id=<?php echo $freebie['id']; ?>'">
                                        Starten
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($referral_enabled): ?>
            <div class="page-content <?php echo $current_page === 'empfehlen' ? 'active' : ''; ?>">
                <h2><i class="fas fa-share-alt"></i> Freunde empfehlen</h2>
                <p style="color:#666;margin-top:10px;margin-bottom:30px">Teile deinen persönlichen Link und zeige deinen Freunden dieses großartige Angebot!</p>
                
                <div class="referral-link-box">
                    <h3>Dein Empfehlungslink:</h3>
                    <div class="link-input-group">
                        <input type="text" class="link-input" value="<?php echo htmlspecialchars($referral_link); ?>" id="referralLink" readonly>
                        <button class="copy-btn" onclick="copyReferralLink()"><i class="fas fa-copy"></i> Kopieren</button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total']; ?></div>
                        <div class="stat-label">Empfehlungen gesamt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['total'] - $referral_stats['pending']; ?></div>
                        <div class="stat-label">Aktive Empfehlungen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $referral_stats['pending']; ?></div>
                        <div class="stat-label">Ausstehend</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyReferralLink(){const input=document.getElementById('referralLink');const button=event.target.closest('.copy-btn');input.select();document.execCommand('copy');const originalHTML=button.innerHTML;button.innerHTML='<i class="fas fa-check"></i> Kopiert!';button.classList.add('copied');setTimeout(()=>{button.innerHTML=originalHTML;button.classList.remove('copied')},2000)}
    </script>
</body>
</html>
<?php $conn->close(); ?>

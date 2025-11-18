<?php
/**
 * DEBUG: Warum wird mein gekauftes Freebie nicht angezeigt?
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('❌ Nicht eingeloggt!');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['email'] ?? 'unbekannt';

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Freebie Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 40px;
            font-size: 14px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 32px;
            margin-bottom: 30px;
        }
        .section {
            background: #16213e;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #0f3460;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #0f3460;
            padding: 10px;
            text-align: left;
            color: #667eea;
            font-weight: bold;
            font-size: 12px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #0f3460;
            font-size: 12px;
            word-break: break-word;
        }
        tr:hover { background: #0f3460; }
        .highlight { background: #fbbf24; color: #1a1a2e; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .null { color: #888; font-style: italic; }
        .green { color: #22c55e; }
        .red { color: #ef4444; }
        .info-box {
            background: #0f3460;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        .query-box {
            background: #0f3460;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Warum wird mein Freebie nicht angezeigt?</h1>
        
        <!-- SESSION INFO -->
        <div class="section">
            <h2>👤 Deine Session</h2>
            <div class="info-box">
                <strong>User ID:</strong> <span class="highlight"><?php echo $userId; ?></span><br>
                <strong>E-Mail:</strong> <?php echo htmlspecialchars($userEmail); ?><br>
                <strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'unbekannt'; ?>
            </div>
        </div>
        
        <!-- ALLE FREEBIES FÜR DIESEN USER -->
        <div class="section">
            <h2>📦 ALLE deine Freebies in customer_freebies</h2>
            <?php
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    customer_id,
                    headline,
                    freebie_type,
                    template_id,
                    copied_from_freebie_id,
                    original_creator_id,
                    unique_id,
                    created_at
                FROM customer_freebies
                WHERE customer_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $allFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (empty($allFreebies)): ?>
                <div class="info-box">
                    <span class="red">❌ KEINE FREEBIES GEFUNDEN!</span><br>
                    Es gibt <strong>KEINE</strong> Einträge in customer_freebies mit customer_id = <?php echo $userId; ?>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <span class="green">✅ <?php echo count($allFreebies); ?> Freebie(s) gefunden!</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>customer_id</th>
                            <th>headline</th>
                            <th>freebie_type</th>
                            <th>template_id</th>
                            <th>copied_from</th>
                            <th>original_creator</th>
                            <th>unique_id</th>
                            <th>Erstellt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allFreebies as $f): ?>
                            <tr>
                                <td><?php echo $f['id']; ?></td>
                                <td><?php echo $f['customer_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($f['headline']); ?></strong></td>
                                <td>
                                    <?php if ($f['freebie_type'] === null): ?>
                                        <span class="null">NULL</span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($f['freebie_type']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($f['template_id'] === null): ?>
                                        <span class="null">NULL</span>
                                    <?php else: ?>
                                        <?php echo $f['template_id']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($f['copied_from_freebie_id'] === null): ?>
                                        <span class="null">NULL</span>
                                    <?php else: ?>
                                        <span class="highlight"><?php echo $f['copied_from_freebie_id']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($f['original_creator_id'] === null): ?>
                                        <span class="null">NULL</span>
                                    <?php else: ?>
                                        <span class="highlight"><?php echo $f['original_creator_id']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 10px;"><?php echo htmlspecialchars($f['unique_id']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($f['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- QUERY DIE IM DASHBOARD BENUTZT WIRD -->
        <div class="section">
            <h2>🔍 Die Query aus freebies.php</h2>
            <div class="query-box">
                <pre>SELECT * FROM customer_freebies 
WHERE customer_id = <span class="highlight"><?php echo $userId; ?></span>
AND (
    freebie_type = 'custom' 
    OR copied_from_freebie_id IS NOT NULL
    OR original_creator_id IS NOT NULL
)
ORDER BY updated_at DESC, created_at DESC</pre>
            </div>
            
            <?php
            $stmt = $pdo->prepare("
                SELECT * FROM customer_freebies 
                WHERE customer_id = ? 
                AND (
                    freebie_type = 'custom' 
                    OR copied_from_freebie_id IS NOT NULL
                    OR original_creator_id IS NOT NULL
                )
                ORDER BY updated_at DESC, created_at DESC
            ");
            $stmt->execute([$userId]);
            $dashboardFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <div class="info-box">
                <?php if (empty($dashboardFreebies)): ?>
                    <span class="red">❌ Diese Query findet KEINE Freebies!</span>
                <?php else: ?>
                    <span class="green">✅ Diese Query findet <?php echo count($dashboardFreebies); ?> Freebie(s)!</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($dashboardFreebies)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>headline</th>
                            <th>freebie_type</th>
                            <th>copied_from</th>
                            <th>original_creator</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboardFreebies as $f): ?>
                            <tr>
                                <td><?php echo $f['id']; ?></td>
                                <td><?php echo htmlspecialchars($f['headline']); ?></td>
                                <td><?php echo $f['freebie_type'] ?? '<span class="null">NULL</span>'; ?></td>
                                <td><?php echo $f['copied_from_freebie_id'] ?? '<span class="null">NULL</span>'; ?></td>
                                <td><?php echo $f['original_creator_id'] ?? '<span class="null">NULL</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- PRÜFUNG: GEKAUFTE FREEBIES -->
        <div class="section">
            <h2>🛒 Gekaufte Marktplatz-Freebies prüfen</h2>
            <?php
            $stmt = $pdo->prepare("
                SELECT 
                    cf.id,
                    cf.customer_id,
                    cf.headline,
                    cf.copied_from_freebie_id,
                    cf.original_creator_id,
                    original.headline as original_headline,
                    seller.email as seller_email
                FROM customer_freebies cf
                LEFT JOIN customer_freebies original ON cf.copied_from_freebie_id = original.id
                LEFT JOIN users seller ON cf.original_creator_id = seller.id
                WHERE cf.customer_id = ? 
                AND cf.copied_from_freebie_id IS NOT NULL
            ");
            $stmt->execute([$userId]);
            $purchased = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (empty($purchased)): ?>
                <div class="info-box">
                    <span class="red">❌ Keine gekauften Marktplatz-Freebies gefunden!</span><br>
                    (Freebies mit copied_from_freebie_id)
                </div>
            <?php else: ?>
                <div class="info-box">
                    <span class="green">✅ <?php echo count($purchased); ?> gekaufte(s) Freebie(s) gefunden!</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Dein Freebie ID</th>
                            <th>Headline</th>
                            <th>Kopiert von ID</th>
                            <th>Original Headline</th>
                            <th>Verkäufer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchased as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($p['headline']); ?></strong></td>
                                <td><?php echo $p['copied_from_freebie_id']; ?></td>
                                <td><?php echo htmlspecialchars($p['original_headline']); ?></td>
                                <td><?php echo htmlspecialchars($p['seller_email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- DIAGNOSE -->
        <div class="section">
            <h2>🩺 Diagnose</h2>
            <div class="info-box">
                <?php if (empty($allFreebies)): ?>
                    <p class="red"><strong>PROBLEM:</strong> Es gibt KEINE Freebies mit deiner customer_id (<?php echo $userId; ?>)!</p>
                    <p style="margin-top: 10px;">Mögliche Ursachen:</p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Der Test hat das Freebie mit einer anderen customer_id erstellt</li>
                        <li>Du bist mit einem anderen Account eingeloggt als beim Test</li>
                        <li>Das Freebie wurde noch gar nicht kopiert</li>
                    </ul>
                <?php elseif (empty($dashboardFreebies)): ?>
                    <p class="red"><strong>PROBLEM:</strong> Freebies existieren, aber die Dashboard-Query findet sie nicht!</p>
                    <p style="margin-top: 10px;">Schaue dir die Werte oben an:</p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Ist <code>freebie_type</code> = 'custom'? NEIN → <span class="null">NULL</span></li>
                        <li>Ist <code>copied_from_freebie_id</code> gesetzt? Prüfe oben!</li>
                        <li>Ist <code>original_creator_id</code> gesetzt? Prüfe oben!</li>
                    </ul>
                <?php else: ?>
                    <p class="green"><strong>✅ ALLES OK!</strong> Die Query findet deine Freebies.</p>
                    <p style="margin-top: 10px;">Sie sollten im Dashboard unter "Meine Freebies" erscheinen.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
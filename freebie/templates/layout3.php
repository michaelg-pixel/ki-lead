<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freebie['headline']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="container-custom">
        <!-- Preheadline -->
        <?php if (!empty($freebie['preheadline'])): ?>
            <div class="text-center mb-4">
                <p class="text-sm font-bold uppercase tracking-wider" style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                    <?= htmlspecialchars($freebie['preheadline']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Headline -->
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-center text-gray-900 mb-6 leading-tight">
            <?= htmlspecialchars($freebie['headline']) ?>
        </h1>
        
        <!-- Subheadline -->
        <?php if (!empty($freebie['subheadline'])): ?>
            <p class="text-xl md:text-2xl text-center text-gray-600 mb-12 max-w-3xl mx-auto">
                <?= htmlspecialchars($freebie['subheadline']) ?>
            </p>
        <?php endif; ?>
        
        <!-- Content Grid: Bulletpoints left, Mockup right -->
        <div class="grid md:grid-cols-2 gap-12 items-start mb-8">
            <!-- Bulletpoints -->
            <div>
                <?php 
                $bullets = [];
                if (!empty($freebie['bullet_points'])) {
                    if (is_string($freebie['bullet_points'])) {
                        $bullets = array_filter(explode("\n", $freebie['bullet_points']), 'trim');
                    } elseif (is_array($freebie['bullet_points'])) {
                        $bullets = $freebie['bullet_points'];
                    }
                }
                
                if (!empty($bullets)): 
                ?>
                    <ul class="space-y-4">
                        <?php foreach ($bullets as $bullet): ?>
                            <?php 
                            $clean_bullet = trim($bullet);
                            $clean_bullet = preg_replace('/^[✓✔︎•\-\*]\s*/', '', $clean_bullet);
                            if ($clean_bullet): 
                            ?>
                                <li class="flex items-start gap-4">
                                    <i class="fas fa-check-circle text-2xl mt-1" 
                                       style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>"></i>
                                    <span class="text-lg text-gray-700"><?= htmlspecialchars($clean_bullet) ?></span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Mockup -->
            <div class="flex justify-center">
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                         alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                         class="w-full max-w-md rounded-2xl shadow-2xl">
                <?php elseif (!empty($course['thumbnail'])): ?>
                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                         alt="Course" 
                         class="w-full max-w-md rounded-2xl shadow-2xl">
                <?php endif; ?>
            </div>
        </div>
        
        <!-- E-Mail Optin direkt unter Bulletpoints -->
        <div class="max-w-2xl mb-12">
            <?php if (!empty($freebie['raw_code'])): ?>
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <?= $freebie['raw_code'] ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <form class="space-y-4">
                        <input type="email" 
                               placeholder="Deine E-Mail-Adresse" 
                               required
                               class="w-full px-6 py-4 border-2 border-gray-200 rounded-xl text-lg focus:border-purple-500 focus:outline-none">
                        <button type="submit" 
                                class="w-full text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition"
                                style="background-color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                            <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt für 0€ statt 27€ kostenlos downloaden') ?>
                        </button>
                    </form>
                    <p class="text-sm text-gray-500 mt-4 text-center">
                        <i class="fas fa-lock mr-1"></i> 
                        100% Datenschutz • Kein Spam • Jederzeit abmelden
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Hinweis -->
        <?php if (!empty($freebie['urgency_text'])): ?>
            <div class="text-center mb-8">
                <p class="text-red-600 font-bold text-lg">
                    <?= htmlspecialchars($freebie['urgency_text']) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 py-8 mt-16">
        <div class="container-custom">
            <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-600">
                <a href="/impressum.php" class="hover:text-gray-900 transition">Impressum</a>
                <span class="text-gray-400">•</span>
                <a href="/datenschutz.php" class="hover:text-gray-900 transition">Datenschutzerklärung</a>
            </div>
        </div>
    </footer>

    <!-- Cookie Banner -->
    <div id="cookie-banner" class="hidden fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-6 shadow-2xl z-50">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex-1">
                <p class="text-sm">
                    <i class="fas fa-cookie-bite mr-2"></i>
                    Wir verwenden Cookies, um Ihnen die bestmögliche Erfahrung zu bieten. 
                    Weitere Informationen finden Sie in unserer 
                    <a href="/datenschutz.php" class="underline">Datenschutzerklärung</a>.
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="acceptCookies()" 
                        class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg font-semibold transition">
                    Alle akzeptieren
                </button>
                <button onclick="rejectCookies()" 
                        class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg transition">
                    Ablehnen
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/cookie-banner.js"></script>
    <script>
        // Show cookie banner if not decided
        if (!localStorage.getItem('cookieConsent')) {
            document.getElementById('cookie-banner').classList.remove('hidden');
        }
    </script>

</body>
</html>
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
            background: linear-gradient(135deg, <?= htmlspecialchars($freebie['primary_color']) ?> 0%, <?= htmlspecialchars($freebie['secondary_color']) ?> 100%);
        }
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Hero Image -->
        <?php if ($course['thumbnail']): ?>
            <div class="relative">
                <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                     alt="Course" class="w-full h-64 object-cover">
                <div class="absolute top-4 right-4 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full font-bold text-sm pulse-animation">
                    <i class="fas fa-gift mr-2"></i> KOSTENLOS
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="p-8 lg:p-12">
            <h1 class="text-4xl lg:text-5xl font-bold mb-4" style="color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                <?= htmlspecialchars($freebie['headline']) ?>
            </h1>
            
            <?php if ($freebie['subheadline']): ?>
                <p class="text-xl text-gray-600 mb-8">
                    <?= htmlspecialchars($freebie['subheadline']) ?>
                </p>
            <?php endif; ?>
            
            <!-- Bullet Points -->
            <?php 
            $bullets = json_decode($freebie['bullet_points'], true);
            if (!empty($bullets)): 
            ?>
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h3 class="font-bold text-lg mb-4">Das bekommst du:</h3>
                    <ul class="space-y-3">
                        <?php foreach ($bullets as $bullet): ?>
                            <?php if (trim($bullet)): ?>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                                    <span class="text-gray-700"><?= htmlspecialchars($bullet) ?></span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Form -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-8 mb-6">
                <h3 class="text-2xl font-bold mb-6 text-center">Jetzt eintragen & sofort starten:</h3>
                <?= $freebie['raw_code'] ?>
            </div>
            
            <!-- CTA Button Alternative -->
            <div class="text-center">
                <a href="#form" 
                   class="inline-block text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition"
                   style="background-color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                    <?= htmlspecialchars($freebie['cta_text']) ?>
                </a>
            </div>
            
            <!-- Trust Badges -->
            <div class="mt-8 pt-8 border-t border-gray-200 flex justify-center gap-8 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <i class="fas fa-lock"></i> 100% Datenschutz
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-envelope"></i> Kein Spam
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-times-circle"></i> Jederzeit abmelden
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Banner -->
    <div id="cookie-banner" class="hidden fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-6 shadow-2xl z-50">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex-1">
                <p class="text-sm">
                    <i class="fas fa-cookie-bite mr-2"></i>
                    Wir verwenden Cookies, um Ihnen die bestmögliche Erfahrung zu bieten. 
                    Weitere Informationen finden Sie in unserer 
                    <a href="/datenschutz" class="underline">Datenschutzerklärung</a>.
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="acceptCookies()" 
                        class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg font-semibold">
                    Alle akzeptieren
                </button>
                <button onclick="rejectCookies()" 
                        class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Ablehnen
                </button>
                <button onclick="showCookieSettings()" 
                        class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Einstellungen
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

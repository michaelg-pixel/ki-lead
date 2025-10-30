<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freebie['headline']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-text {
            background: linear-gradient(135deg, <?= htmlspecialchars($freebie['primary_color']) ?>, <?= htmlspecialchars($freebie['secondary_color']) ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="container mx-auto px-4 py-12 max-w-5xl">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <!-- Left Side: Image -->
            <div class="order-2 md:order-1">
                <?php if ($course['thumbnail']): ?>
                    <div class="relative">
                        <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                             alt="Course" class="w-full rounded-2xl shadow-2xl">
                        <div class="absolute -top-4 -right-4 bg-red-500 text-white px-6 py-3 rounded-full font-bold transform rotate-12 shadow-lg">
                            100% GRATIS!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Side: Content -->
            <div class="order-1 md:order-2">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h1 class="text-4xl font-bold mb-4 gradient-text">
                        <?= htmlspecialchars($freebie['headline']) ?>
                    </h1>
                    
                    <?php if ($freebie['subheadline']): ?>
                        <p class="text-lg text-gray-600 mb-6">
                            <?= htmlspecialchars($freebie['subheadline']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Bullet Points -->
                    <?php 
                    $bullets = json_decode($freebie['bullet_points'], true);
                    if (!empty($bullets)): 
                    ?>
                        <ul class="space-y-3 mb-8">
                            <?php foreach ($bullets as $bullet): ?>
                                <?php if (trim($bullet)): ?>
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0" 
                                             style="background-color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                        <?= htmlspecialchars($bullet) ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- Form -->
                    <div class="mb-6">
                        <?= $freebie['raw_code'] ?>
                    </div>
                    
                    <!-- CTA Button -->
                    <button 
                        class="w-full text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition transform hover:scale-105"
                        style="background-color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                        <?= htmlspecialchars($freebie['cta_text']) ?>
                    </button>
                    
                    <!-- Security Note -->
                    <p class="text-xs text-gray-500 text-center mt-4">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Deine Daten sind bei uns sicher. Kein Spam, versprochen!
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Bottom Trust Section -->
        <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
            <div class="grid md:grid-cols-3 gap-6 text-center">
                <div>
                    <i class="fas fa-users text-4xl mb-3" style="color: <?= htmlspecialchars($freebie['primary_color']) ?>"></i>
                    <h3 class="font-bold mb-2">10.000+ Teilnehmer</h3>
                    <p class="text-sm text-gray-600">Vertrauen bereits auf uns</p>
                </div>
                <div>
                    <i class="fas fa-star text-4xl mb-3" style="color: <?= htmlspecialchars($freebie['primary_color']) ?>"></i>
                    <h3 class="font-bold mb-2">4.9/5 Sterne</h3>
                    <p class="text-sm text-gray-600">Durchschnittliche Bewertung</p>
                </div>
                <div>
                    <i class="fas fa-clock text-4xl mb-3" style="color: <?= htmlspecialchars($freebie['primary_color']) ?>"></i>
                    <h3 class="font-bold mb-2">Sofortiger Zugang</h3>
                    <p class="text-sm text-gray-600">Keine Wartezeit</p>
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
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="acceptCookies()" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg font-semibold">
                    Akzeptieren
                </button>
                <button onclick="rejectCookies()" class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Ablehnen
                </button>
                <button onclick="showCookieSettings()" class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Einstellungen
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/cookie-banner.js"></script>
    <script>
        if (!localStorage.getItem('cookieConsent')) {
            document.getElementById('cookie-banner').classList.remove('hidden');
        }
    </script>

</body>
</html>

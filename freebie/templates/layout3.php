<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freebie['headline']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-white min-h-screen flex items-center justify-center p-6">

    <div class="max-w-xl w-full">
        <!-- Minimalist Header -->
        <div class="text-center mb-12">
            <?php if ($course['thumbnail']): ?>
                <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                     alt="Course" class="w-48 h-48 mx-auto rounded-full shadow-lg mb-8 object-cover">
            <?php endif; ?>
            
            <h1 class="text-5xl font-bold mb-4" style="color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                <?= htmlspecialchars($freebie['headline']) ?>
            </h1>
            
            <?php if ($freebie['subheadline']): ?>
                <p class="text-xl text-gray-600">
                    <?= htmlspecialchars($freebie['subheadline']) ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Clean Bullet Points -->
        <?php 
        $bullets = json_decode($freebie['bullet_points'], true);
        if (!empty($bullets)): 
        ?>
            <div class="mb-12">
                <ul class="space-y-4">
                    <?php foreach ($bullets as $bullet): ?>
                        <?php if (trim($bullet)): ?>
                            <li class="text-lg text-gray-700 flex items-start gap-3">
                                <span style="color: <?= htmlspecialchars($freebie['primary_color']) ?>">→</span>
                                <?= htmlspecialchars($bullet) ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Minimal Form Container -->
        <div class="border-2 rounded-lg p-8 mb-8" style="border-color: <?= htmlspecialchars($freebie['primary_color']) ?>">
            <?= $freebie['raw_code'] ?>
        </div>
        
        <!-- Simple CTA -->
        <div class="text-center">
            <button 
                class="px-12 py-4 rounded-lg font-bold text-lg text-white shadow-lg hover:shadow-xl transition"
                style="background-color: <?= htmlspecialchars($freebie['primary_color']) ?>">
                <?= htmlspecialchars($freebie['cta_text']) ?>
            </button>
            
            <p class="text-sm text-gray-400 mt-6">
                Kostenlos. Kein Spam. Jederzeit abmelden.
            </p>
        </div>
        
        <!-- Minimal Footer -->
        <div class="mt-16 pt-8 border-t border-gray-200 text-center text-sm text-gray-500">
            <div class="flex justify-center gap-6">
                <a href="/impressum" class="hover:text-gray-700">Impressum</a>
                <a href="/datenschutz" class="hover:text-gray-700">Datenschutz</a>
            </div>
        </div>
    </div>

    <!-- Cookie Banner -->
    <div id="cookie-banner" class="hidden fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-6 shadow-2xl z-50">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex-1">
                <p class="text-sm">
                    <i class="fas fa-cookie-bite mr-2"></i>
                    Diese Website verwendet Cookies für eine bessere Nutzererfahrung.
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="acceptCookies()" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg font-semibold">
                    OK
                </button>
                <button onclick="rejectCookies()" class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Ablehnen
                </button>
                <button onclick="showCookieSettings()" class="bg-gray-700 hover:bg-gray-600 px-6 py-2 rounded-lg">
                    Details
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

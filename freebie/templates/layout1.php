<?php
/**
 * Layout 1 Template - ALL 3 LAYOUTS (Hybrid, Centered, Sidebar)
 * 🆕 MIT BULLET ICON STYLE SUPPORT & FONT-SYSTEM
 * ✨ Optimiert: Kleinere Mockups & Optins ohne Schatten - ALLE LAYOUTS
 */

// 🆕 BULLET ICON STYLE LADEN
$bulletIconStyle = $freebie['bullet_icon_style'] ?? 'standard';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freebie['headline']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- 🆕 GOOGLE FONTS DYNAMISCH LADEN -->
    <?php if (isset($is_google_font_heading) && $is_google_font_heading || isset($is_google_font_body) && $is_google_font_body): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php 
        $fonts_to_load = [];
        if (isset($is_google_font_heading) && $is_google_font_heading && isset($font_heading)) {
            $fonts_to_load[] = str_replace(' ', '+', $font_heading) . ':wght@400;600;700;800';
        }
        if (isset($is_google_font_body) && $is_google_font_body && isset($font_body) && $font_body !== $font_heading) {
            $fonts_to_load[] = str_replace(' ', '+', $font_body) . ':wght@400;600;700;800';
        }
        echo implode('&family=', $fonts_to_load);
    ?>&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        body {
            font-family: <?= isset($font_body_stack) ? $font_body_stack : '\'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif' ?>;
            font-size: <?= isset($sizes) && isset($sizes['body']) ? $sizes['body'] : 16 ?>px;
        }
        
        h1, h2, h3, h4, h5, h6, .headline, .preheadline {
            font-family: <?= isset($font_heading_stack) ? $font_heading_stack : '\'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif' ?>;
        }
        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Button Animationen */
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            50% { transform: scale(1.05); box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 10px currentColor, 0 0 20px currentColor; }
            50% { box-shadow: 0 0 20px currentColor, 0 0 40px currentColor; }
        }
        
        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        .animate-shake { animation: shake 0.6s ease-in-out infinite; }
        .animate-bounce { animation: bounce 1.2s ease-in-out infinite; }
        .animate-glow { animation: glow 2s ease-in-out infinite; }
        
        /* Popup Styles */
        .popup-overlay {
            backdrop-filter: blur(8px);
        }
        
        body.popup-open {
            overflow: hidden;
        }
        
        /* Video Container */
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .video-container.shorts {
            padding-bottom: 177.78%; /* 9:16 ratio for Shorts */
            max-width: 400px;
            margin: 0 auto;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        /* ✨ Direct Optin Form Styling - KOMPAKTER & OHNE SCHATTEN */
        .direct-optin-form {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 2px solid #e5e7eb;
        }
        
        .direct-optin-form input[type="text"],
        .direct-optin-form input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .direct-optin-form input:focus {
            outline: none;
            border-color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .direct-optin-form button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .direct-optin-form button[type="submit"]:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        /* 🆕 CUSTOM ICON STYLING */
        .bullet-icon-custom {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="container-custom">
        <!-- Preheadline - IMMER ZENTRIERT -->
        <?php if (!empty($freebie['preheadline'])): ?>
            <div class="text-center mb-4">
                <p class="preheadline text-sm font-bold uppercase tracking-wider" 
                   style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>; font-size: <?= isset($sizes) && isset($sizes['preheadline']) ? $sizes['preheadline'] : 13 ?>px;">
                    <?= htmlspecialchars($freebie['preheadline']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Headline - IMMER ZENTRIERT -->
        <h1 class="headline font-bold text-center text-gray-900 mb-6 leading-tight"
            style="font-size: <?= isset($sizes) && isset($sizes['headline']) ? $sizes['headline'] : 40 ?>px;">
            <?= htmlspecialchars($freebie['headline']) ?>
        </h1>
        
        <!-- Subheadline - IMMER ZENTRIERT -->
        <?php if (!empty($freebie['subheadline'])): ?>
            <p class="text-center text-gray-600 mb-12 max-w-3xl mx-auto"
               style="font-size: <?= isset($sizes) && isset($sizes['subheadline']) ? $sizes['subheadline'] : 20 ?>px;">
                <?= htmlspecialchars($freebie['subheadline']) ?>
            </p>
        <?php endif; ?>
        
        <?php
        // 🆕 FUNKTION ZUM VERARBEITEN DER BULLETPOINTS
        function processBulletPoint($bullet, $bulletIconStyle, $primaryColor) {
            $bullet = trim($bullet);
            if (empty($bullet)) {
                return null;
            }
            
            $icon = '✓';
            $text = $bullet;
            $iconColor = $primaryColor;
            $iconType = 'fontawesome'; // oder 'emoji'
            
            if ($bulletIconStyle === 'custom') {
                // Versuche Emoji/Icon am Anfang zu extrahieren
                if (preg_match('/^([\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}])/u', $bullet, $matches)) {
                    $icon = $matches[1];
                    $text = trim(substr($bullet, strlen($icon)));
                    $iconColor = 'inherit';
                    $iconType = 'emoji';
                } else {
                    // Fallback: erstes Zeichen prüfen
                    $firstChar = mb_substr($bullet, 0, 1);
                    if ($firstChar && !preg_match('/[a-zA-Z0-9\s]/', $firstChar)) {
                        $icon = $firstChar;
                        $text = trim(mb_substr($bullet, 1));
                        $iconColor = 'inherit';
                        $iconType = 'emoji';
                    } else {
                        // Kein Icon gefunden, nutze den vollständigen Text
                        $text = $bullet;
                        $iconType = 'none';
                    }
                }
            } else {
                // Standard: Text bereinigen und grünen Haken nutzen
                $text = preg_replace('/^[✓✔︎•\-\*]\s*/', '', $bullet);
                $iconType = 'fontawesome';
            }
            
            return [
                'icon' => $icon,
                'text' => $text,
                'iconColor' => $iconColor,
                'iconType' => $iconType
            ];
        }
        
        // Bulletpoints vorbereiten
        $bullets = [];
        if (!empty($freebie['bullet_points'])) {
            if (is_string($freebie['bullet_points'])) {
                $rawBullets = array_filter(explode("\n", $freebie['bullet_points']), 'trim');
                foreach ($rawBullets as $bullet) {
                    $processed = processBulletPoint($bullet, $bulletIconStyle, $freebie['primary_color'] ?? '#7C3AED');
                    if ($processed) {
                        $bullets[] = $processed;
                    }
                }
            } elseif (is_array($freebie['bullet_points'])) {
                foreach ($freebie['bullet_points'] as $bullet) {
                    $processed = processBulletPoint($bullet, $bulletIconStyle, $freebie['primary_color'] ?? '#7C3AED');
                    if ($processed) {
                        $bullets[] = $processed;
                    }
                }
            }
        }
        ?>
        
        <?php if ($layout === 'centered'): ?>
            <!-- ✨ CENTERED LAYOUT: Alles vertikal zentriert - OPTIMIERT -->
            <div class="max-w-4xl mx-auto">
                <!-- Video (wenn vorhanden) -->
                <?php if (!empty($videoEmbedUrl)): ?>
                    <div class="mb-12">
                        <div class="video-container <?= $videoFormat === 'shorts' ? 'shorts' : '' ?>">
                            <iframe 
                                src="<?= htmlspecialchars($videoEmbedUrl) ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ✨ Mockup - KLEINER & OHNE SCHATTEN -->
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <div class="flex justify-center mb-12">
                        <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                             alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                             class="w-full max-w-xs rounded-xl">
                    </div>
                <?php endif; ?>
                
                <!-- Bulletpoints - zentriert -->
                <?php if (!empty($bullets)): ?>
                    <ul class="space-y-4 mb-12 max-w-2xl mx-auto">
                        <?php foreach ($bullets as $bullet): ?>
                            <li class="flex items-start gap-4 justify-center">
                                <?php if ($bullet['iconType'] === 'fontawesome'): ?>
                                    <i class="fas fa-check-circle text-2xl mt-1" 
                                       style="color: <?= htmlspecialchars($bullet['iconColor']) ?>"></i>
                                <?php elseif ($bullet['iconType'] === 'emoji'): ?>
                                    <span class="bullet-icon-custom mt-1" 
                                          style="color: <?= htmlspecialchars($bullet['iconColor']) ?>">
                                        <?= htmlspecialchars($bullet['icon']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-lg text-gray-700" style="font-size: <?= isset($sizes) && isset($sizes['body']) ? $sizes['body'] : 16 ?>px;">
                                    <?= htmlspecialchars($bullet['text']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- ✨ Optin - zentriert & KLEINER -->
                <div class="max-w-sm mx-auto">
                    <?php if ($optinDisplayMode === 'popup'): ?>
                        <div class="text-center">
                            <button 
                                onclick="openOptinPopup()" 
                                class="px-6 py-3 text-white rounded-xl font-bold text-base transition-all <?= $ctaAnimation !== 'none' ? 'animate-' . $ctaAnimation : '' ?>"
                                style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                                <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern') ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="direct-optin-form">
                            <?php if (!empty($freebie['raw_code'])): ?>
                                <div class="optin-form-wrapper">
                                    <?= $freebie['raw_code'] ?>
                                </div>
                            <?php else: ?>
                                <form class="space-y-3">
                                    <input type="text" name="first_name" placeholder="Vorname">
                                    <input type="email" name="email" placeholder="E-Mail-Adresse" required>
                                    <button type="submit">
                                        <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                                    </button>
                                </form>
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    <i class="fas fa-lock mr-1"></i> 
                                    100% Datenschutz • Kein Spam
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($layout === 'sidebar'): ?>
            <!-- SIDEBAR LAYOUT: Bulletpoints LINKS, Video/Mockup RECHTS -->
            <div class="grid md:grid-cols-2 gap-8 items-start mb-12">
                
                <!-- LINKE SPALTE: Bulletpoints + Optin -->
                <div>
                    <!-- Bulletpoints -->
                    <?php if (!empty($bullets)): ?>
                        <ul class="space-y-4 mb-8">
                            <?php foreach ($bullets as $bullet): ?>
                                <li class="flex items-start gap-4">
                                    <?php if ($bullet['iconType'] === 'fontawesome'): ?>
                                        <i class="fas fa-check-circle text-2xl mt-1" 
                                           style="color: <?= htmlspecialchars($bullet['iconColor']) ?>"></i>
                                    <?php elseif ($bullet['iconType'] === 'emoji'): ?>
                                        <span class="bullet-icon-custom mt-1" 
                                              style="color: <?= htmlspecialchars($bullet['iconColor']) ?>">
                                            <?= htmlspecialchars($bullet['icon']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-lg text-gray-700" style="font-size: <?= isset($sizes) && isset($sizes['body']) ? $sizes['body'] : 16 ?>px;">
                                        <?= htmlspecialchars($bullet['text']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- ✨ Optin unter Bulletpoints - KOMPAKT -->
                    <?php if ($optinDisplayMode === 'popup'): ?>
                        <div class="mt-6">
                            <button 
                                onclick="openOptinPopup()" 
                                class="w-full px-6 py-3 text-white rounded-xl font-bold text-base transition-all <?= $ctaAnimation !== 'none' ? 'animate-' . $ctaAnimation : '' ?>"
                                style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                                <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern') ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="direct-optin-form mt-6 max-w-sm">
                            <?php if (!empty($freebie['raw_code'])): ?>
                                <div class="optin-form-wrapper">
                                    <?= $freebie['raw_code'] ?>
                                </div>
                            <?php else: ?>
                                <form class="space-y-3">
                                    <input type="text" name="first_name" placeholder="Vorname">
                                    <input type="email" name="email" placeholder="E-Mail-Adresse" required>
                                    <button type="submit">
                                        <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                                    </button>
                                </form>
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    <i class="fas fa-lock mr-1"></i> 
                                    100% Datenschutz • Kein Spam
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- ✨ RECHTE SPALTE: Video/Mockup - KLEINER -->
                <div class="flex flex-col justify-center items-center">
                    <!-- Video (wenn vorhanden) -->
                    <?php if (!empty($videoEmbedUrl)): ?>
                        <div class="w-full mb-6">
                            <div class="video-container <?= $videoFormat === 'shorts' ? 'shorts' : '' ?>">
                                <iframe 
                                    src="<?= htmlspecialchars($videoEmbedUrl) ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- ✨ Mockup - KLEINER & OHNE SCHATTEN -->
                    <?php if (!empty($freebie['mockup_image_url'])): ?>
                        <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                             alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                             class="w-full max-w-xs rounded-xl">
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- ✨ HYBRID LAYOUT (Default): Video/Mockup LINKS, Bulletpoints RECHTS - OPTIMIERT -->
            <div class="grid md:grid-cols-2 gap-8 items-start mb-12">
                
                <!-- ✨ LINKE SPALTE: Video/Mockup - KLEINER -->
                <div class="flex flex-col justify-center items-center">
                    <!-- Video (wenn vorhanden) -->
                    <?php if (!empty($videoEmbedUrl)): ?>
                        <div class="w-full mb-6">
                            <div class="video-container <?= $videoFormat === 'shorts' ? 'shorts' : '' ?>">
                                <iframe 
                                    src="<?= htmlspecialchars($videoEmbedUrl) ?>" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- ✨ Mockup - KLEINER & OHNE SCHATTEN -->
                    <?php if (!empty($freebie['mockup_image_url'])): ?>
                        <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                             alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                             class="w-full max-w-xs rounded-xl">
                    <?php endif; ?>
                </div>
                
                <!-- RECHTE SPALTE: Bulletpoints + Optin -->
                <div>
                    <!-- Bulletpoints -->
                    <?php if (!empty($bullets)): ?>
                        <ul class="space-y-4 mb-8">
                            <?php foreach ($bullets as $bullet): ?>
                                <li class="flex items-start gap-4">
                                    <?php if ($bullet['iconType'] === 'fontawesome'): ?>
                                        <i class="fas fa-check-circle text-2xl mt-1" 
                                           style="color: <?= htmlspecialchars($bullet['iconColor']) ?>"></i>
                                    <?php elseif ($bullet['iconType'] === 'emoji'): ?>
                                        <span class="bullet-icon-custom mt-1" 
                                              style="color: <?= htmlspecialchars($bullet['iconColor']) ?>">
                                            <?= htmlspecialchars($bullet['icon']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-lg text-gray-700" style="font-size: <?= isset($sizes) && isset($sizes['body']) ? $sizes['body'] : 16 ?>px;">
                                        <?= htmlspecialchars($bullet['text']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- ✨ Optin unter Bulletpoints - KOMPAKT -->
                    <?php if ($optinDisplayMode === 'popup'): ?>
                        <div class="mt-6">
                            <button 
                                onclick="openOptinPopup()" 
                                class="w-full px-6 py-3 text-white rounded-xl font-bold text-base transition-all <?= $ctaAnimation !== 'none' ? 'animate-' . $ctaAnimation : '' ?>"
                                style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                                <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern') ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="direct-optin-form mt-6 max-w-sm">
                            <?php if (!empty($freebie['raw_code'])): ?>
                                <div class="optin-form-wrapper">
                                    <?= $freebie['raw_code'] ?>
                                </div>
                            <?php else: ?>
                                <form class="space-y-3">
                                    <input type="text" name="first_name" placeholder="Vorname">
                                    <input type="email" name="email" placeholder="E-Mail-Adresse" required>
                                    <button type="submit">
                                        <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                                    </button>
                                </form>
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    <i class="fas fa-lock mr-1"></i> 
                                    100% Datenschutz • Kein Spam
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 py-8 mt-16">
        <div class="container-custom">
            <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-600">
                <a href="<?= $impressum_link ?>" class="hover:text-gray-900 transition">Impressum</a>
                <span class="text-gray-400">•</span>
                <a href="<?= $datenschutz_link ?>" class="hover:text-gray-900 transition">Datenschutzerklärung</a>
            </div>
        </div>
    </footer>

    <?php if ($optinDisplayMode === 'popup'): ?>
    <!-- Popup Overlay -->
    <div id="optinPopupOverlay" 
         class="popup-overlay hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4" 
         onclick="if(event.target === this) closeOptinPopup()">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl" onclick="event.stopPropagation()">
            <!-- Popup Header -->
            <div class="relative p-8 text-center border-b-2 border-gray-100">
                <button onclick="closeOptinPopup()" 
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-600 text-3xl rounded-full hover:bg-gray-100 transition">
                    ×
                </button>
                <div class="text-6xl mb-4">🎁</div>
                <h2 class="headline text-3xl font-bold mb-3" 
                    style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                    <?= htmlspecialchars($freebie['headline'] ?? '') ?>
                </h2>
                <p class="text-lg text-gray-600">
                    <?= htmlspecialchars($popupMessage ?? 'Trage dich jetzt unverbindlich ein!') ?>
                </p>
            </div>
            
            <!-- Popup Content -->
            <div class="p-8">
                <?php if (!empty($freebie['raw_code'])): ?>
                    <div class="optin-form-wrapper">
                        <?= $freebie['raw_code'] ?>
                    </div>
                <?php else: ?>
                    <form class="space-y-4">
                        <input type="text" name="first_name" placeholder="Vorname" 
                               class="w-full p-4 border-2 border-gray-300 rounded-xl text-lg focus:border-purple-500 focus:outline-none transition">
                        <input type="email" name="email" placeholder="E-Mail-Adresse" required
                               class="w-full p-4 border-2 border-gray-300 rounded-xl text-lg focus:border-purple-500 focus:outline-none transition">
                        <button type="submit" 
                                class="w-full p-4 text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl"
                                style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                            <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                        </button>
                    </form>
                    <p class="text-sm text-gray-500 mt-4 text-center">
                        <i class="fas fa-lock mr-1"></i> 
                        100% Datenschutz • Kein Spam • Jederzeit abmelden
                    </p>
                <?php endif; ?>
                
                <!-- Datenschutz-Hinweis -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        Mit der Anmeldung akzeptierst du unsere 
                        <a href="<?= $datenschutz_link ?>" target="_blank" 
                           class="underline hover:no-underline"
                           style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                            Datenschutzbestimmungen
                        </a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openOptinPopup() {
            document.getElementById('optinPopupOverlay').classList.remove('hidden');
            document.body.classList.add('popup-open');
        }
        
        function closeOptinPopup() {
            document.getElementById('optinPopupOverlay').classList.add('hidden');
            document.body.classList.remove('popup-open');
        }
        
        // ESC-Taste zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOptinPopup();
            }
        });
        
        // Automatisches Formular-Styling für raw_code
        document.addEventListener('DOMContentLoaded', function() {
            // Popup Formular-Styling
            const popupFormWrapper = document.querySelector('#optinPopupOverlay .optin-form-wrapper');
            if (popupFormWrapper) {
                const popupForm = popupFormWrapper.querySelector('form');
                if (popupForm) {
                    styleForm(popupForm, '<?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>');
                }
            }
            
            // Direct Optin Formular-Styling
            const directFormWrapper = document.querySelector('.direct-optin-form .optin-form-wrapper');
            if (directFormWrapper) {
                const directForm = directFormWrapper.querySelector('form');
                if (directForm) {
                    styleForm(directForm, '<?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>');
                }
            }
        });
        
        function styleForm(form, primaryColor) {
            // Style inputs
            const inputs = form.querySelectorAll('input[type="email"], input[type="text"]');
            inputs.forEach(input => {
                input.style.cssText = `
                    width: 100%;
                    padding: 12px 16px;
                    margin-bottom: 10px;
                    border: 2px solid #e5e7eb;
                    border-radius: 10px;
                    font-size: 15px;
                    transition: all 0.2s;
                `;
                
                input.addEventListener('focus', function() {
                    this.style.borderColor = primaryColor;
                    this.style.outline = 'none';
                    this.style.boxShadow = '0 0 0 3px rgba(124, 58, 237, 0.1)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#e5e7eb';
                    this.style.boxShadow = 'none';
                });
            });
            
            // Style submit button
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.style.cssText = `
                    width: 100%;
                    padding: 14px;
                    background: ${primaryColor};
                    color: white;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.3s;
                `;
                
                submitBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.opacity = '0.9';
                });
                
                submitBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.opacity = '1';
                });
            }
        }
    </script>
    <?php endif; ?>

</body>
</html>
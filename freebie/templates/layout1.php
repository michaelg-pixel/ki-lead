<?php
/**
 * Layout 1 Template mit POPUP & VIDEO Support
 */
?>
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
    </style>
</head>
<body class="bg-gray-50">

    <div class="container-custom">
        <!-- Preheadline -->
        <?php if (!empty($freebie['preheadline'])): ?>
            <div class="text-center mb-4">
                <p class="text-sm font-bold uppercase tracking-wider" 
                   style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
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
        
        <!-- Video (wenn vorhanden) -->
        <?php if (!empty($videoEmbedUrl)): ?>
            <div class="mb-12 max-w-4xl mx-auto">
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
        
        <div class="grid md:grid-cols-2 gap-12 items-start mb-12">
            <!-- Mockup -->
            <div class="flex justify-center">
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                         alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                         class="w-full max-w-sm rounded-2xl shadow-2xl">
                <?php endif; ?>
            </div>
            
            <!-- Bulletpoints & Optin -->
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
                    <ul class="space-y-4 mb-8">
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
                
                <!-- E-Mail Optin -->
                <?php if ($optinDisplayMode === 'popup'): ?>
                    <!-- Popup-Button -->
                    <div class="text-center">
                        <button 
                            onclick="openOptinPopup()" 
                            class="px-8 py-4 text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl <?= $ctaAnimation !== 'none' ? 'animate-' . $ctaAnimation : '' ?>"
                            style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                            <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern') ?>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Direkte Anzeige -->
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <?php if (!empty($freebie['raw_code'])): ?>
                            <?= $freebie['raw_code'] ?>
                        <?php else: ?>
                            <form class="space-y-4">
                                <input type="text" name="first_name" placeholder="Vorname" 
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg">
                                <input type="email" name="email" placeholder="E-Mail-Adresse" required
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg">
                                <button type="submit" 
                                        class="w-full p-4 text-white rounded-lg font-bold"
                                        style="background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                                    <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                                </button>
                            </form>
                            <p class="text-sm text-gray-500 mt-4 text-center">
                                <i class="fas fa-lock mr-1"></i> 
                                100% Datenschutz • Kein Spam
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                <h2 class="text-3xl font-bold mb-3" 
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
        
        // Formular-Styling im Popup
        document.addEventListener('DOMContentLoaded', function() {
            const popupForm = document.querySelector('#optinPopupOverlay .optin-form-wrapper form');
            if (popupForm) {
                const inputs = popupForm.querySelectorAll('input[type="email"], input[type="text"]');
                inputs.forEach(input => {
                    input.style.cssText = `
                        width: 100%;
                        padding: 16px;
                        border: 2px solid #e5e7eb;
                        border-radius: 12px;
                        font-size: 16px;
                        margin-bottom: 12px;
                        transition: all 0.2s;
                    `;
                    
                    input.addEventListener('focus', function() {
                        this.style.borderColor = '<?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>';
                        this.style.outline = 'none';
                    });
                    
                    input.addEventListener('blur', function() {
                        this.style.borderColor = '#e5e7eb';
                    });
                });
                
                const submitBtn = popupForm.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.style.cssText = `
                        width: 100%;
                        padding: 16px;
                        background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>;
                        color: white;
                        border: none;
                        border-radius: 12px;
                        font-size: 16px;
                        font-weight: 700;
                        cursor: pointer;
                        transition: all 0.3s;
                    `;
                }
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>

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
        
        /* E-Mail Optin Styling */
        .email-optin-wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .email-optin-wrapper input[type="text"],
        .email-optin-wrapper input[type="email"] {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .email-optin-wrapper input[type="text"]:focus,
        .email-optin-wrapper input[type="email"]:focus {
            outline: none;
            border-color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .email-optin-wrapper button[type="submit"] {
            width: 100%;
            padding: 16px 24px;
            background: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
        }
        
        .email-optin-wrapper button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5);
        }
        
        /* Cookie Modal */
        #cookie-settings-modal {
            backdrop-filter: blur(5px);
            background: rgba(0, 0, 0, 0.5);
        }
        
        .cookie-toggle {
            position: relative;
            width: 44px;
            height: 24px;
            background: #cbd5e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cookie-toggle.active {
            background: #48bb78;
        }
        
        .cookie-toggle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .cookie-toggle.active::after {
            transform: translateX(20px);
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="container-custom">
        <!-- Preheadline - ZENTRIERT -->
        <?php if (!empty($freebie['preheadline'])): ?>
            <div class="text-center mb-4">
                <p class="text-sm font-bold uppercase tracking-wider" style="color: <?= htmlspecialchars($freebie['primary_color'] ?? '#7C3AED') ?>">
                    <?= htmlspecialchars($freebie['preheadline']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Headline - ZENTRIERT -->
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-center text-gray-900 mb-6 leading-tight">
            <?= htmlspecialchars($freebie['headline']) ?>
        </h1>
        
        <!-- Subheadline - ZENTRIERT -->
        <?php if (!empty($freebie['subheadline'])): ?>
            <p class="text-xl md:text-2xl text-center text-gray-600 mb-12 max-w-3xl mx-auto">
                <?= htmlspecialchars($freebie['subheadline']) ?>
            </p>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-2 gap-12 items-start mb-12">
            <!-- Mockup -->
            <div class="flex justify-center">
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" 
                         alt="<?= htmlspecialchars($freebie['headline']) ?>" 
                         class="w-full max-w-sm rounded-2xl shadow-2xl">
                <?php elseif (!empty($course['thumbnail'])): ?>
                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" 
                         alt="Course" 
                         class="w-full max-w-sm rounded-2xl shadow-2xl">
                <?php endif; ?>
            </div>
            
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
                
                <!-- E-Mail Optin unter den Bulletpoints -->
                <div class="email-optin-wrapper">
                    <?php if (!empty($freebie['raw_code'])): ?>
                        <div class="bg-white rounded-2xl shadow-xl p-8">
                            <?= $freebie['raw_code'] ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl shadow-xl p-8">
                            <form class="space-y-4">
                                <input type="text" 
                                       name="first_name"
                                       placeholder="Vorname" 
                                       class="w-full">
                                <input type="email" 
                                       name="email"
                                       placeholder="E-Mail-Adresse" 
                                       required
                                       class="w-full">
                                <button type="submit">
                                    <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos bestellen') ?>
                                </button>
                            </form>
                            <p class="text-sm text-gray-500 mt-4 text-center">
                                <i class="fas fa-lock mr-1"></i> 
                                100% Datenschutz • Kein Spam • Jederzeit abmelden
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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

    <!-- MODERNISIERTER Cookie Banner -->
    <div id="cookie-banner" class="hidden fixed bottom-0 left-0 right-0 bg-white border-t-2 border-gray-200 shadow-2xl z-50">
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-start gap-4 flex-1">
                    <div class="text-3xl">🍪</div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">Wir respektieren Ihre Privatsphäre</h3>
                        <p class="text-sm text-gray-600">
                            Wir verwenden Cookies, um Ihnen die bestmögliche Erfahrung zu bieten. 
                            <a href="/datenschutz.php" class="text-blue-600 hover:underline">Mehr erfahren</a>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button onclick="openCookieSettings()" 
                            class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                        <i class="fas fa-cog mr-2"></i>Einstellungen
                    </button>
                    <button onclick="rejectCookies()" 
                            class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                        Nur notwendige
                    </button>
                    <button onclick="acceptAllCookies()" 
                            class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition shadow-md">
                        <i class="fas fa-check mr-2"></i>Alle akzeptieren
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Settings Modal -->
    <div id="cookie-settings-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Cookie-Einstellungen</h2>
                    <button onclick="closeCookieSettings()" class="text-white hover:text-gray-200 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <p class="text-gray-600">
                    Wir verwenden Cookies und ähnliche Technologien, um Ihnen das beste Erlebnis auf unserer Website zu bieten.
                </p>
                
                <div class="border-2 border-gray-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-bold text-gray-900">Notwendige Cookies</h3>
                            <p class="text-sm text-gray-600 mt-1">Diese Cookies sind für die Funktion der Website erforderlich.</p>
                        </div>
                        <div class="cookie-toggle active" data-locked="true"></div>
                    </div>
                </div>
                
                <div class="border-2 border-gray-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-bold text-gray-900">Analyse & Statistik</h3>
                            <p class="text-sm text-gray-600 mt-1">Helfen uns zu verstehen, wie Besucher mit der Website interagieren.</p>
                        </div>
                        <div class="cookie-toggle" id="analytics-toggle" onclick="toggleCookie('analytics')"></div>
                    </div>
                </div>
                
                <div class="border-2 border-gray-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h3 class="font-bold text-gray-900">Marketing & Werbung</h3>
                            <p class="text-sm text-gray-600 mt-1">Werden verwendet, um Besuchern relevante Anzeigen zu zeigen.</p>
                        </div>
                        <div class="cookie-toggle" id="marketing-toggle" onclick="toggleCookie('marketing')"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-b-2xl flex flex-wrap gap-3 justify-end">
                <button onclick="saveSettings()" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    Einstellungen speichern
                </button>
                <button onclick="acceptAllFromModal()" 
                        class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    Alle akzeptieren
                </button>
            </div>
        </div>
    </div>

    <script>
        let cookiePreferences = {
            necessary: true,
            analytics: false,
            marketing: false
        };

        window.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('cookieConsent')) {
                document.getElementById('cookie-banner').classList.remove('hidden');
            }
        });

        function toggleCookie(type) {
            cookiePreferences[type] = !cookiePreferences[type];
            const toggle = document.getElementById(type + '-toggle');
            if (cookiePreferences[type]) {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
        }

        function openCookieSettings() {
            document.getElementById('cookie-settings-modal').classList.remove('hidden');
            const saved = localStorage.getItem('cookiePreferences');
            if (saved) {
                cookiePreferences = JSON.parse(saved);
                if (cookiePreferences.analytics) {
                    document.getElementById('analytics-toggle').classList.add('active');
                }
                if (cookiePreferences.marketing) {
                    document.getElementById('marketing-toggle').classList.add('active');
                }
            }
        }

        function closeCookieSettings() {
            document.getElementById('cookie-settings-modal').classList.add('hidden');
        }

        function saveSettings() {
            localStorage.setItem('cookieConsent', 'custom');
            localStorage.setItem('cookiePreferences', JSON.stringify(cookiePreferences));
            document.getElementById('cookie-banner').classList.add('hidden');
            closeCookieSettings();
        }

        function acceptAllCookies() {
            cookiePreferences = {
                necessary: true,
                analytics: true,
                marketing: true
            };
            localStorage.setItem('cookieConsent', 'all');
            localStorage.setItem('cookiePreferences', JSON.stringify(cookiePreferences));
            document.getElementById('cookie-banner').classList.add('hidden');
        }

        function acceptAllFromModal() {
            cookiePreferences = {
                necessary: true,
                analytics: true,
                marketing: true
            };
            saveSettings();
        }

        function rejectCookies() {
            cookiePreferences = {
                necessary: true,
                analytics: false,
                marketing: false
            };
            localStorage.setItem('cookieConsent', 'necessary');
            localStorage.setItem('cookiePreferences', JSON.stringify(cookiePreferences));
            document.getElementById('cookie-banner').classList.add('hidden');
        }
    </script>

</body>
</html>
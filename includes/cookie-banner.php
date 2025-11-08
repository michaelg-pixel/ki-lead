<?php
/**
 * DSGVO-konformer Cookie-Banner
 * Wird überall im System eingebunden
 */

// Customer/User ID für Links ermitteln
$cookie_banner_user_id = null;

// Versuche user_id aus verschiedenen Quellen zu ermitteln
if (isset($customer_id)) {
    $cookie_banner_user_id = $customer_id;
} elseif (isset($user_id)) {
    $cookie_banner_user_id = $user_id;
} elseif (isset($_SESSION['user_id'])) {
    $cookie_banner_user_id = $_SESSION['user_id'];
} elseif (isset($_GET['customer'])) {
    $cookie_banner_user_id = (int)$_GET['customer'];
} elseif (isset($_GET['user'])) {
    $cookie_banner_user_id = (int)$_GET['user'];
}

// Links für Impressum und Datenschutz
$impressum_url = $cookie_banner_user_id ? "/impressum.php?user=" . $cookie_banner_user_id : "/impressum.php";
$datenschutz_url = $cookie_banner_user_id ? "/datenschutz.php?user=" . $cookie_banner_user_id : "/datenschutz.php";

// Prüfen ob wir in einem Unterordner sind (z.B. /freebie/)
$base_path = '';
if (strpos($_SERVER['SCRIPT_NAME'], '/freebie/') !== false) {
    $base_path = '..';
} elseif (strpos($_SERVER['SCRIPT_NAME'], '/customer/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $base_path = '..';
}
?>

<!-- Cookie Banner HTML -->
<div id="cookie-banner" class="hidden fixed bottom-0 left-0 right-0 bg-white shadow-2xl border-t-4 border-purple-600 z-50 animate-slide-up">
    <div class="max-w-6xl mx-auto px-6 py-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Text -->
            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="text-3xl">🍪</div>
                    <h3 class="text-lg font-bold text-gray-900">Cookie-Einstellungen</h3>
                </div>
                <p class="text-sm text-gray-600">
                    Wir verwenden Cookies, um Ihre Erfahrung zu verbessern. 
                    Erfahren Sie mehr in unserer 
                    <a href="<?= htmlspecialchars($datenschutz_url) ?>" 
                       target="_blank" 
                       class="text-purple-600 hover:text-purple-700 underline font-semibold">
                        Datenschutzerklärung
                    </a>.
                </p>
            </div>
            
            <!-- Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <button onclick="showCookieSettings()" 
                        class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all">
                    <i class="fas fa-cog mr-2"></i>Einstellungen
                </button>
                <button onclick="rejectCookies()" 
                        class="px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all">
                    <i class="fas fa-times mr-2"></i>Ablehnen
                </button>
                <button onclick="acceptCookies()" 
                        class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold shadow-lg transition-all">
                    <i class="fas fa-check mr-2"></i>Alle akzeptieren
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cookie Banner Styles -->
<style>
    @keyframes slide-up {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .animate-slide-up {
        animation: slide-up 0.4s ease-out;
    }
    
    #cookie-banner {
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
    }
</style>

<!-- Cookie Banner JavaScript -->
<script src="<?= $base_path ?>/assets/js/cookie-banner.js"></script>

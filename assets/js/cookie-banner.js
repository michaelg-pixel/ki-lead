// Cookie Banner Management
// DSGVO-konform mit 3 Optionen: Ablehnen, Akzeptieren, Einstellungen

function acceptCookies() {
    localStorage.setItem('cookieConsent', 'accepted');
    document.getElementById('cookie-banner').classList.add('hidden');
    
    // Google Analytics oder andere Tracking-Tools hier aktivieren
    enableTracking();
}

function rejectCookies() {
    localStorage.setItem('cookieConsent', 'rejected');
    document.getElementById('cookie-banner').classList.add('hidden');
    
    // Nur essentielle Cookies erlauben
    disableTracking();
}

function showCookieSettings() {
    // Modal für detaillierte Cookie-Einstellungen öffnen
    const modal = document.createElement('div');
    modal.id = 'cookie-settings-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-6';
    modal.innerHTML = `
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Cookie-Einstellungen</h2>
                <button onclick="closeCookieSettings()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- Essenzielle Cookies -->
                <div class="border-b pb-6">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-lg">Essenzielle Cookies</h3>
                        <span class="text-sm text-green-600 font-semibold">Immer aktiv</span>
                    </div>
                    <p class="text-sm text-gray-600">
                        Diese Cookies sind für die Grundfunktionen der Website erforderlich und können nicht deaktiviert werden.
                    </p>
                </div>
                
                <!-- Analytics Cookies -->
                <div class="border-b pb-6">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-lg">Analyse-Cookies</h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="analytics-cookies" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <p class="text-sm text-gray-600">
                        Diese Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren (z.B. Google Analytics).
                    </p>
                </div>
                
                <!-- Marketing Cookies -->
                <div class="pb-6">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-lg">Marketing-Cookies</h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="marketing-cookies" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <p class="text-sm text-gray-600">
                        Diese Cookies werden verwendet, um Werbung relevanter für Sie zu machen (z.B. Facebook Pixel).
                    </p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-8">
                <button onclick="saveCustomCookieSettings()" 
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold">
                    Einstellungen speichern
                </button>
                <button onclick="acceptAllCookies()" 
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                    Alle akzeptieren
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeCookieSettings() {
    const modal = document.getElementById('cookie-settings-modal');
    if (modal) {
        modal.remove();
    }
}

function saveCustomCookieSettings() {
    const analytics = document.getElementById('analytics-cookies').checked;
    const marketing = document.getElementById('marketing-cookies').checked;
    
    const settings = {
        essential: true,
        analytics: analytics,
        marketing: marketing
    };
    
    localStorage.setItem('cookieConsent', 'custom');
    localStorage.setItem('cookieSettings', JSON.stringify(settings));
    
    document.getElementById('cookie-banner').classList.add('hidden');
    closeCookieSettings();
    
    // Cookies basierend auf Einstellungen aktivieren/deaktivieren
    if (analytics) {
        enableAnalytics();
    } else {
        disableAnalytics();
    }
    
    if (marketing) {
        enableMarketing();
    } else {
        disableMarketing();
    }
}

function acceptAllCookies() {
    const settings = {
        essential: true,
        analytics: true,
        marketing: true
    };
    
    localStorage.setItem('cookieConsent', 'accepted');
    localStorage.setItem('cookieSettings', JSON.stringify(settings));
    
    document.getElementById('cookie-banner').classList.add('hidden');
    closeCookieSettings();
    
    enableTracking();
}

function enableTracking() {
    enableAnalytics();
    enableMarketing();
}

function disableTracking() {
    disableAnalytics();
    disableMarketing();
}

function enableAnalytics() {
    // Google Analytics aktivieren (Beispiel)
    console.log('Analytics enabled');
    
    // Hier Google Analytics Code einfügen:
    // window.dataLayer = window.dataLayer || [];
    // function gtag(){dataLayer.push(arguments);}
    // gtag('js', new Date());
    // gtag('config', 'GA_MEASUREMENT_ID');
}

function disableAnalytics() {
    console.log('Analytics disabled');
    
    // Google Analytics deaktivieren
    // window['ga-disable-GA_MEASUREMENT_ID'] = true;
}

function enableMarketing() {
    // Facebook Pixel, etc. aktivieren
    console.log('Marketing cookies enabled');
}

function disableMarketing() {
    console.log('Marketing cookies disabled');
}

// Cookie-Banner bei Seite-Load prüfen
document.addEventListener('DOMContentLoaded', function() {
    const consent = localStorage.getItem('cookieConsent');
    
    if (!consent) {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.classList.remove('hidden');
        }
    } else if (consent === 'accepted' || consent === 'custom') {
        // Cookies gemäß gespeicherter Einstellungen aktivieren
        const settings = JSON.parse(localStorage.getItem('cookieSettings') || '{}');
        
        if (settings.analytics) {
            enableAnalytics();
        }
        
        if (settings.marketing) {
            enableMarketing();
        }
    }
});

// ESC-Taste schließt Cookie-Settings-Modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCookieSettings();
    }
});

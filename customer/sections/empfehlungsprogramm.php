<?php
/**
 * Customer Dashboard Section: Empfehlungsprogramm
 * Vollständige Verwaltung des Referral-Systems
 */

if (!isset($_SESSION['customer_id'])) {
    header('Location: /public/login.php');
    exit;
}
?>

<div class="referral-section">
    <!-- Header mit Toggle -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">🎁 Empfehlungsprogramm</h1>
                <p class="text-gray-600 mt-1">Verwalten Sie Ihr Empfehlungsprogramm und tracken Sie Ihre Erfolge</p>
            </div>
            <div class="flex items-center space-x-3">
                <span id="statusLabel" class="text-sm font-medium text-gray-700">Deaktiviert</span>
                <button id="toggleReferral" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 bg-gray-200">
                    <span id="toggleButton" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                </button>
            </div>
        </div>
        
        <!-- Warnung wenn deaktiviert -->
        <div id="disabledWarning" class="hidden mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800">Empfehlungsprogramm ist deaktiviert</h3>
                    <p class="mt-1 text-sm text-yellow-700">Aktivieren Sie das Programm, um Referral-Links zu nutzen und Statistiken zu sehen.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik-Dashboard -->
    <div id="statsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Klicks -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Gesamt-Klicks</h3>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900" id="totalClicks">0</div>
            <div class="text-sm text-gray-500 mt-1">
                <span id="uniqueClicks">0</span> unique
            </div>
        </div>

        <!-- Conversions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Conversions</h3>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900" id="totalConversions">0</div>
            <div class="text-sm text-gray-500 mt-1">
                <span id="suspiciousConversions" class="text-red-500">0</span> verdächtig
            </div>
        </div>

        <!-- Leads -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Registrierte Leads</h3>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900" id="totalLeads">0</div>
            <div class="text-sm text-gray-500 mt-1">
                <span id="confirmedLeads">0</span> bestätigt
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Conversion Rate</h3>
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900">
                <span id="conversionRate">0</span>%
            </div>
            <div class="text-sm text-gray-500 mt-1">von unique Klicks</div>
        </div>
    </div>

    <!-- Referral-Links und Pixel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Referral-Link -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Ihr Referral-Link</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Freebie-Seite mit Referral</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="freebieLink" readonly class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono" value="">
                        <button onclick="copyToClipboard('freebieLink')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition whitespace-nowrap">
                            Kopieren
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Teilen Sie diesen Link, um Klicks zu tracken</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ihr Referral-Code</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="refCode" readonly class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono" value="">
                        <button onclick="copyToClipboard('refCode')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition whitespace-nowrap">
                            Kopieren
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Fügen Sie <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">&ref=IHR_CODE</code> an beliebige URLs an</p>
                </div>
            </div>
        </div>

        <!-- Tracking Pixel -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 Tracking Pixel</h3>
            <p class="text-sm text-gray-600 mb-4">Fügen Sie diesen Code auf Ihrer externen Danke-Seite ein, um Conversions zu tracken:</p>
            <div class="relative">
                <textarea id="pixelCode" readonly rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-xs font-mono resize-none" style="font-size: 11px;"></textarea>
                <button onclick="copyToClipboard('pixelCode')" class="absolute top-2 right-2 px-3 py-1 bg-white border border-gray-300 rounded text-xs hover:bg-gray-50 transition">
                    Kopieren
                </button>
            </div>
            <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-800">
                    <strong>💡 Tipp:</strong> Der Pixel muss den Parameter <code class="bg-blue-100 px-1 rounded">?ref=CODE</code> enthalten, den der Besucher mitbringt.
                </p>
            </div>
        </div>
    </div>

    <!-- Firmendaten / Impressum -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">🏢 Firmendaten & Impressum</h3>
        <p class="text-sm text-gray-600 mb-4">Diese Daten werden in E-Mails an Ihre Leads verwendet (DSGVO-konform):</p>
        
        <form id="companyForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Firmenname</label>
                    <input type="text" id="companyName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ihre Firma GmbH">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail-Adresse (Absender)</label>
                    <input type="email" id="companyEmail" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="info@ihre-firma.de">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Impressum (HTML erlaubt)</label>
                <textarea id="companyImprint" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ihre Firma GmbH&#10;Musterstraße 123&#10;12345 Musterstadt&#10;&#10;E-Mail: info@ihre-firma.de&#10;Tel: +49 123 456789"></textarea>
                <p class="mt-1 text-xs text-gray-500">Wird am Ende jeder E-Mail angezeigt. HTML-Tags erlaubt: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;a&gt;</p>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Speichern
            </button>
        </form>
    </div>

    <!-- Tabs für Listen -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-4 px-6" aria-label="Tabs">
                <button onclick="switchTab('clicks')" id="tabClicks" class="tab-button py-4 px-1 border-b-2 border-indigo-600 font-medium text-sm text-indigo-600">
                    Letzte Klicks
                </button>
                <button onclick="switchTab('conversions')" id="tabConversions" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Conversions
                </button>
                <button onclick="switchTab('leads')" id="tabLeads" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Leads
                </button>
            </nav>
        </div>

        <!-- Klicks-Tabelle -->
        <div id="contentClicks" class="tab-content p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref-Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fingerprint</th>
                        </tr>
                    </thead>
                    <tbody id="clicksTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Klicks vorhanden</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Conversions-Tabelle -->
        <div id="contentConversions" class="tab-content p-6 hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref-Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quelle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zeit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="conversionsTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Conversions vorhanden</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leads-Tabelle -->
        <div id="contentLeads" class="tab-content p-6 hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref-Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Belohnung</th>
                        </tr>
                    </thead>
                    <tbody id="leadsTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Leads vorhanden</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Anleitung -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📖 So funktioniert's</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="flex items-center mb-2">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-sm mr-3">1</span>
                    <h4 class="font-semibold text-gray-900">Teilen</h4>
                </div>
                <p class="text-sm text-gray-600 ml-11">Teilen Sie Ihren Referral-Link oder fügen Sie <code class="bg-white px-1 py-0.5 rounded text-xs">&ref=CODE</code> an Ihre URLs an.</p>
            </div>
            <div>
                <div class="flex items-center mb-2">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-sm mr-3">2</span>
                    <h4 class="font-semibold text-gray-900">Tracken</h4>
                </div>
                <p class="text-sm text-gray-600 ml-11">Klicks und Conversions werden automatisch erfasst. Nutzen Sie optional den Tracking-Pixel für externe Seiten.</p>
            </div>
            <div>
                <div class="flex items-center mb-2">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-sm mr-3">3</span>
                    <h4 class="font-semibold text-gray-900">Erfolg messen</h4>
                </div>
                <p class="text-sm text-gray-600 ml-11">Sehen Sie Ihre Statistiken in Echtzeit und verwalten Sie Ihre Leads direkt im Dashboard.</p>
            </div>
        </div>
    </div>
</div>

<script>
let referralEnabled = false;
let referralCode = '';
let customerId = <?php echo $_SESSION['customer_id']; ?>;

// Lade Daten beim Seitenstart
document.addEventListener('DOMContentLoaded', function() {
    loadReferralData();
    loadCompanyData();
});

// Lade Referral-Daten
async function loadReferralData() {
    try {
        const response = await fetch('/api/referral/get-stats.php');
        const data = await response.json();
        
        if (data.success) {
            referralEnabled = data.data.enabled;
            referralCode = data.data.ref_code;
            
            updateUI(data.data);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Daten:', error);
    }
}

// Lade Firmendaten
async function loadCompanyData() {
    try {
        const response = await fetch('/api/customer-get-profile.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('companyName').value = data.data.company_name || '';
            document.getElementById('companyEmail').value = data.data.company_email || '';
            document.getElementById('companyImprint').value = data.data.company_imprint_html || '';
        }
    } catch (error) {
        console.error('Fehler beim Laden der Firmendaten:', error);
    }
}

// Update UI
function updateUI(data) {
    // Toggle-Status
    const toggle = document.getElementById('toggleReferral');
    const toggleBtn = document.getElementById('toggleButton');
    const statusLabel = document.getElementById('statusLabel');
    const warning = document.getElementById('disabledWarning');
    
    if (referralEnabled) {
        toggle.classList.remove('bg-gray-200');
        toggle.classList.add('bg-indigo-600');
        toggleBtn.classList.remove('translate-x-1');
        toggleBtn.classList.add('translate-x-6');
        statusLabel.textContent = 'Aktiviert';
        statusLabel.classList.remove('text-gray-700');
        statusLabel.classList.add('text-green-600');
        warning.classList.add('hidden');
    } else {
        toggle.classList.add('bg-gray-200');
        toggle.classList.remove('bg-indigo-600');
        toggleBtn.classList.add('translate-x-1');
        toggleBtn.classList.remove('translate-x-6');
        statusLabel.textContent = 'Deaktiviert';
        statusLabel.classList.add('text-gray-700');
        statusLabel.classList.remove('text-green-600');
        warning.classList.remove('hidden');
    }
    
    // Statistiken
    document.getElementById('totalClicks').textContent = data.stats.total_clicks;
    document.getElementById('uniqueClicks').textContent = data.stats.unique_clicks;
    document.getElementById('totalConversions').textContent = data.stats.total_conversions;
    document.getElementById('suspiciousConversions').textContent = data.stats.suspicious_conversions;
    document.getElementById('totalLeads').textContent = data.stats.total_leads;
    document.getElementById('confirmedLeads').textContent = data.stats.confirmed_leads;
    document.getElementById('conversionRate').textContent = data.stats.conversion_rate;
    
    // Links
    const baseUrl = window.location.origin;
    document.getElementById('freebieLink').value = `${baseUrl}/freebie.php?customer=${customerId}&ref=${referralCode}`;
    document.getElementById('refCode').value = referralCode;
    document.getElementById('pixelCode').value = `<img src="${baseUrl}/api/referral/track.php?customer=${customerId}&ref=${referralCode}" width="1" height="1" style="display:none;">`;
    
    // Tabellen
    updateClicksTable(data.recent_clicks);
    updateConversionsTable(data.recent_conversions);
    updateLeadsTable(data.leads);
}

// Toggle Referral-Programm
document.getElementById('toggleReferral').addEventListener('click', async function() {
    try {
        const response = await fetch('/api/referral/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: !referralEnabled })
        });
        
        const data = await response.json();
        
        if (data.success) {
            referralEnabled = data.enabled;
            loadReferralData();
            showNotification(data.message, 'success');
        }
    } catch (error) {
        showNotification('Fehler beim Aktualisieren', 'error');
    }
});

// Firmendaten speichern
document.getElementById('companyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        company_name: document.getElementById('companyName').value,
        company_email: document.getElementById('companyEmail').value,
        company_imprint_html: document.getElementById('companyImprint').value
    };
    
    try {
        const response = await fetch('/api/referral/update-company.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Firmendaten erfolgreich gespeichert', 'success');
        } else {
            showNotification(data.message || 'Fehler beim Speichern', 'error');
        }
    } catch (error) {
        showNotification('Fehler beim Speichern', 'error');
    }
});

// Tabs wechseln
function switchTab(tab) {
    // Tabs
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('border-indigo-600', 'text-indigo-600');
    
    // Content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    document.getElementById('content' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.remove('hidden');
}

// Tabellen aktualisieren
function updateClicksTable(clicks) {
    const tbody = document.getElementById('clicksTable');
    tbody.innerHTML = '';
    
    if (!clicks || clicks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Klicks vorhanden</td></tr>';
        return;
    }
    
    clicks.forEach(click => {
        tbody.innerHTML += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${click.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">${click.ref_code}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-400">${click.fingerprint.substring(0, 16)}...</td>
            </tr>
        `;
    });
}

function updateConversionsTable(conversions) {
    const tbody = document.getElementById('conversionsTable');
    tbody.innerHTML = '';
    
    if (!conversions || conversions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Conversions vorhanden</td></tr>';
        return;
    }
    
    conversions.forEach(conv => {
        const statusBadge = conv.suspicious === '1' 
            ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Verdächtig</span>'
            : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Valid</span>';
        
        tbody.innerHTML += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${conv.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">${conv.ref_code}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${conv.source}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${conv.time_to_convert ? conv.time_to_convert + 's' : '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
            </tr>
        `;
    });
}

function updateLeadsTable(leads) {
    const tbody = document.getElementById('leadsTable');
    tbody.innerHTML = '';
    
    if (!leads || leads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">Keine Leads vorhanden</td></tr>';
        return;
    }
    
    leads.forEach(lead => {
        const confirmedBadge = lead.confirmed === '1'
            ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Bestätigt</span>'
            : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Ausstehend</span>';
        
        const rewardBadge = lead.reward_notified === '1'
            ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Gesendet</span>'
            : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">-</span>';
        
        tbody.innerHTML += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${lead.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${lead.email}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">${lead.ref_code}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${confirmedBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${rewardBadge}</td>
            </tr>
        `;
    });
}

// Copy to Clipboard
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    showNotification('In Zwischenablage kopiert!', 'success');
}

// Notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} z-50 animate-slide-in`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<style>
@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
</style>

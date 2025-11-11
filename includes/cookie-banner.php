<?php
/**
 * DSGVO-konformer Cookie-Banner (ohne Tailwind CSS)
 * Wird überall im System eingebunden
 */

// Customer/User ID für Links ermitteln
$cookie_banner_user_id = null;

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

// Prüfen ob wir in einem Unterordner sind
$base_path = '';
if (strpos($_SERVER['SCRIPT_NAME'], '/freebie/') !== false) {
    $base_path = '..';
} elseif (strpos($_SERVER['SCRIPT_NAME'], '/customer/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $base_path = '..';
}
?>

<!-- Cookie Banner Styles -->
<style>
    .cookie-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        border-top: 4px solid #667eea;
        animation: slideUp 0.4s ease-out;
    }
    
    .cookie-banner.hidden {
        display: none;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .cookie-banner-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .cookie-banner-text {
        flex: 1;
        min-width: 300px;
    }
    
    .cookie-banner-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    
    .cookie-banner-icon {
        font-size: 32px;
    }
    
    .cookie-banner-title {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0;
    }
    
    .cookie-banner-description {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
        margin: 0;
    }
    
    .cookie-banner-description a {
        color: #667eea;
        font-weight: 600;
        text-decoration: underline;
    }
    
    .cookie-banner-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .cookie-btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        white-space: nowrap;
    }
    
    .cookie-btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .cookie-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }
    
    .cookie-btn-secondary {
        background: transparent;
        color: #667eea;
        border: 2px solid #667eea;
    }
    
    .cookie-btn-secondary:hover {
        background: #667eea;
        color: white;
    }
    
    .cookie-btn-tertiary {
        background: transparent;
        color: #666;
        border: 2px solid #e5e7eb;
    }
    
    .cookie-btn-tertiary:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    
    @media (max-width: 768px) {
        .cookie-banner-content {
            flex-direction: column;
            align-items: stretch;
        }
        
        .cookie-banner-buttons {
            justify-content: stretch;
        }
        
        .cookie-btn {
            flex: 1;
        }
    }
</style>

<!-- Cookie Banner HTML -->
<div id="cookie-banner" class="cookie-banner hidden">
    <div class="cookie-banner-content">
        <div class="cookie-banner-text">
            <div class="cookie-banner-header">
                <div class="cookie-banner-icon">🍪</div>
                <h3 class="cookie-banner-title">Cookie-Einstellungen</h3>
            </div>
            <p class="cookie-banner-description">
                Wir verwenden Cookies, um Ihre Erfahrung zu verbessern. 
                Erfahren Sie mehr in unserer 
                <a href="<?= htmlspecialchars($datenschutz_url) ?>" target="_blank">Datenschutzerklärung</a>.
            </p>
        </div>
        
        <div class="cookie-banner-buttons">
            <button onclick="showCookieSettings()" class="cookie-btn cookie-btn-tertiary">
                Einstellungen
            </button>
            <button onclick="rejectCookies()" class="cookie-btn cookie-btn-secondary">
                Ablehnen
            </button>
            <button onclick="acceptCookies()" class="cookie-btn cookie-btn-primary">
                Alle akzeptieren
            </button>
        </div>
    </div>
</div>

<!-- Cookie Banner JavaScript -->
<script src="<?= $base_path ?>/assets/js/cookie-banner.js"></script>

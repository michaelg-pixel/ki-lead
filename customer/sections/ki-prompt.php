<?php
/**
 * KI Agent Seite
 * Zugriff auf Henry Landmanns KI Tools
 */

if (!defined('INCLUDED_FROM_DASHBOARD')) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        header('Location: /public/login.php');
        exit;
    }
}
?>

<style>
    .ki-prompt-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px;
    }
    
    .ki-header {
        text-align: center;
        margin-bottom: 48px;
    }
    
    .ki-header h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
    }
    
    .ki-header p {
        font-size: 18px;
        color: #999;
    }
    
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 32px;
    }
    
    .tool-card {
        background: linear-gradient(145deg, #1a1a2e 0%, #16213e 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        padding: 32px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .tool-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .tool-card:hover {
        transform: translateY(-4px);
        border-color: rgba(102, 126, 234, 0.5);
        box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
    }
    
    .tool-card:hover::before {
        opacity: 1;
    }
    
    .tool-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 20px;
    }
    
    .tool-title {
        font-size: 22px;
        font-weight: 700;
        color: white;
        margin-bottom: 12px;
    }
    
    .tool-description {
        font-size: 14px;
        color: #999;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .tool-features {
        margin-bottom: 24px;
    }
    
    .tool-features-title {
        font-size: 13px;
        font-weight: 600;
        color: #667eea;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .feature-item {
        font-size: 13px;
        color: #bbb;
        padding: 6px 0;
        padding-left: 20px;
        position: relative;
    }
    
    .feature-item::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #10b981;
        font-weight: bold;
    }
    
    .tool-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .tool-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
    }
    
    .tool-button i {
        font-size: 18px;
    }
    
    @media (max-width: 768px) {
        .ki-prompt-container {
            padding: 20px;
        }
        
        .ki-header h1 {
            font-size: 28px;
        }
        
        .ki-header p {
            font-size: 16px;
        }
        
        .tools-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .tool-card {
            padding: 24px;
        }
    }
</style>

<div class="ki-prompt-container">
    <div class="ki-header">
        <h1>🤖 KI Agent Tools</h1>
        <p>Professionelle KI-Tools im Stil von Henry Landmann</p>
    </div>
    
    <div class="tools-grid">
        <!-- KI Super Poster -->
        <div class="tool-card">
            <div class="tool-icon">📱</div>
            <h3 class="tool-title">KI Super Poster</h3>
            <p class="tool-description">
                Erstellt professionelle Social Media Posts im Stil von Henry Landmann für maximale Reichweite und Engagement.
            </p>
            <div class="tool-features">
                <div class="tool-features-title">Beispiel Funktionen:</div>
                <div class="feature-item">Freebie-Post / Webinar Post</div>
                <div class="feature-item">Angebots-Pitch (Abo oder Lifetime Deal)</div>
                <div class="feature-item">Engagement-Post (Community anheizen)</div>
                <div class="feature-item">Proof-Post (Social Proof & Storytelling)</div>
                <div class="feature-item">Countdown / Letzte Chance</div>
            </div>
            <a href="https://chatgpt.com/g/g-68b1992a0fb88191bda8ab9c8ca955da-ki-super-poster" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tool-button">
                <span>Tool öffnen</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        
        <!-- KI Super Mailer -->
        <div class="tool-card">
            <div class="tool-icon">✉️</div>
            <h3 class="tool-title">KI Super Mailer</h3>
            <p class="tool-description">
                Schreibt verkaufsstarke E-Mails im Stil von Henry Landmann, die konvertieren und überzeugen.
            </p>
            <div class="tool-features">
                <div class="tool-features-title">Beispiel Funktionen:</div>
                <div class="feature-item">E-Mail neu erstellen (konvertierende Version)</div>
                <div class="feature-item">3 starke Betreffs und Call-to-Actions</div>
                <div class="feature-item">3 aufeinander aufbauende E-Mails</div>
                <div class="feature-item">URL analysieren und E-Mail-Serie erstellen</div>
            </div>
            <a href="https://chatgpt.com/g/g-6894b36839dc81918fbd77eedd74415c-ki-super-mailer" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tool-button">
                <span>Tool öffnen</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        
        <!-- Produkt Review Experte -->
        <div class="tool-card">
            <div class="tool-icon">⭐</div>
            <h3 class="tool-title">Produkt Review Experte</h3>
            <p class="tool-description">
                Erstellt professionelle Produktreviews als SEO-optimierte HTML/WordPress-Vorlage direkt aus einer URL.
            </p>
            <div class="tool-features">
                <div class="tool-features-title">Beispiel Funktionen:</div>
                <div class="feature-item">URL-basierte Produktanalyse</div>
                <div class="feature-item">SEO-optimierte HTML-Ausgabe</div>
                <div class="feature-item">WordPress-ready Format</div>
                <div class="feature-item">Strukturierte Review-Templates</div>
            </div>
            <a href="https://chatgpt.com/g/g-68f89d898fec81918b883977a2551d74-produkt-review-experte" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tool-button">
                <span>Tool öffnen</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        
        <!-- Produkt Cover Designer -->
        <div class="tool-card">
            <div class="tool-icon">🎨</div>
            <h3 class="tool-title">Produkt Cover Designer</h3>
            <p class="tool-description">
                Erstellt professionelle Produkt-Boxen und Cover für Reports, Video-Kurse und digitale Produkte.
            </p>
            <div class="tool-features">
                <div class="tool-features-title">Beispiel Funktionen:</div>
                <div class="feature-item">3D Produkt-Boxen Design</div>
                <div class="feature-item">Report-Cover Erstellung</div>
                <div class="feature-item">Video-Kurs Mockups</div>
                <div class="feature-item">Professionelle Templates</div>
            </div>
            <a href="https://chatgpt.com/g/g-6908a069a1b0819188bf762e194f29db-produkt-cover-designer" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tool-button">
                <span>Tool öffnen</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        
        <!-- Report E-Book Creator -->
        <div class="tool-card">
            <div class="tool-icon">📚</div>
            <h3 class="tool-title">Report E-Book Creator</h3>
            <p class="tool-description">
                Erstellt hochwertige Reports / E-Books im PDF-Format inklusive Produkt Cover + Texte für Leadpage.
            </p>
            <div class="tool-features">
                <div class="tool-features-title">Beispiel Funktionen:</div>
                <div class="feature-item">Professionelle PDF-Reports erstellen</div>
                <div class="feature-item">E-Book Design & Layout</div>
                <div class="feature-item">Produkt Cover Generation</div>
                <div class="feature-item">Leadpage-optimierte Texte</div>
            </div>
            <a href="https://chatgpt.com/g/g-690b11cb92008191866aa4ec84d24d95-report-und-leadmagnet-generator" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="tool-button">
                <span>Tool öffnen</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    </div>
</div>

<!-- Font Awesome für Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
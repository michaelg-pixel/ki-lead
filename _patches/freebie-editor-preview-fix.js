// WICHTIG: Diese updatePreview() Funktion in custom-freebie-editor.php einfügen
// Ersetze die bestehende updatePreview() Funktion mit dieser Version

function updatePreview() {
    const preheadline = document.querySelector('input[name="preheadline"]').value;
    const headline = document.querySelector('input[name="headline"]').value;
    const subheadline = document.querySelector('input[name="subheadline"]').value;
    const bulletPoints = document.querySelector('textarea[name="bullet_points"]').value;
    const bulletIconStyle = document.querySelector('input[name="bullet_icon_style"]:checked').value;
    const ctaText = document.querySelector('input[name="cta_text"]').value;
    const layout = document.querySelector('input[name="layout"]:checked').value;
    const primaryColor = document.getElementById('primary_color').value;
    const backgroundColor = document.getElementById('background_color').value;
    const mockupImageUrl = document.getElementById('mockupImageUrl').value;
    const videoUrl = document.getElementById('videoUrl').value;
    const videoFormat = document.querySelector('input[name="video_format"]:checked').value;
    
    // 🆕 FONT-WERTE (jetzt Pixel-Inputs)
    const fontHeading = document.querySelector('select[name="font_heading"]').value;
    const fontBody = document.querySelector('select[name="font_body"]').value;
    const fontSizeHeadline = document.querySelector('input[name="font_size_headline"]').value + 'px';
    const fontSizeSubheadline = document.querySelector('input[name="font_size_subheadline"]').value + 'px';
    const fontSizeBullet = document.querySelector('input[name="font_size_bullet"]').value + 'px';
    const fontSizePreheadline = document.querySelector('input[name="font_size_preheadline"]').value + 'px';
    
    // Get font stacks
    const headingFontFamily = fontStacks[fontHeading] || fontStacks['Inter'];
    const bodyFontFamily = fontStacks[fontBody] || fontStacks['Inter'];
    
    // POPUP-WERTE
    const ctaAnimation = document.querySelector('select[name="cta_animation"]').value;
    
    const previewContent = document.getElementById('previewContent');
    previewContent.style.background = backgroundColor;
    previewContent.style.fontFamily = bodyFontFamily;
    
    let bulletHTML = '';
    if (bulletPoints.trim()) {
        const bullets = bulletPoints.split('\n').filter(b => b.trim());
        
        bulletHTML = bullets.map(bullet => {
            let icon = '✓';
            let text = bullet;
            
            // LOGIK FÜR BULLET ICON STYLE
            if (bulletIconStyle === 'custom') {
                // Versuche Icon aus dem Text zu extrahieren
                const extracted = extractIconFromBullet(bullet);
                if (extracted) {
                    icon = extracted.icon;
                    text = extracted.text;
                } else {
                    // Kein Icon gefunden, nutze den vollständigen Text
                    text = bullet;
                }
            } else {
                // Standard: Text bereinigen und grünen Haken nutzen
                text = bullet.replace(/^[✓✔︎•-]\s*/, '').trim();
            }
            
            const iconColor = bulletIconStyle === 'standard' ? primaryColor : 'inherit';
            
            return `
                <div class="preview-bullet">
                    <span class="preview-bullet-icon" style="color: ${iconColor}; font-size: ${fontSizeBullet};">${icon}</span>
                    <span class="preview-bullet-text" style="font-family: ${bodyFontFamily}; font-size: ${fontSizeBullet};">${escapeHtml(text)}</span>
                </div>
            `;
        }).join('');
        
        bulletHTML = `<div class="preview-bullets">${bulletHTML}</div>`;
    }
    
    let mockupHTML = '';
    if (mockupImageUrl) {
        mockupHTML = `
            <div class="preview-mockup">
                <img src="${escapeHtml(mockupImageUrl)}" alt="Mockup" style="max-width: 180px;">
            </div>
        `;
    }
    
    let videoHTML = '';
    const embedUrl = getVideoEmbedUrl(videoUrl);
    if (embedUrl) {
        const isPortrait = videoFormat === 'portrait';
        const videoWidth = isPortrait ? '315px' : '100%';
        const videoHeight = isPortrait ? '560px' : '315px';
        const videoMaxWidth = isPortrait ? '315px' : '560px';
        
        videoHTML = `
            <div class="preview-video" style="max-width: ${videoMaxWidth}; margin: 0 auto;">
                <iframe 
                    width="${videoWidth}" 
                    height="${videoHeight}" 
                    src="${embedUrl}" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen
                    style="display: block; margin: 0 auto;">
                </iframe>
            </div>
        `;
    }
    
    // ✅ ALLE ÜBERSCHRIFTEN MIT text-align: center
    const preheadlineHTML = preheadline ? `
        <div class="preview-preheadline" style="color: ${primaryColor}; font-family: ${bodyFontFamily}; font-size: ${fontSizePreheadline}; text-align: center;">
            ${escapeHtml(preheadline)}
        </div>
    ` : '';
    
    const headlineHTML = `
        <div class="preview-headline" style="color: ${primaryColor}; font-family: ${headingFontFamily}; font-size: ${fontSizeHeadline}; text-align: center;">
            ${escapeHtml(headline || 'Deine Hauptüberschrift')}
        </div>
    `;
    
    const subheadlineHTML = subheadline ? `
        <div class="preview-subheadline" style="font-family: ${bodyFontFamily}; font-size: ${fontSizeSubheadline}; text-align: center;">${escapeHtml(subheadline)}</div>
    ` : '';
    
    // Priorität: Video > Mockup > Icon
    const mediaElement = videoHTML || mockupHTML || `<div style="text-align: center; color: ${primaryColor}; font-size: 50px;">🎁</div>`;
    
    // BUTTON MIT ANIMATION
    const animationClass = ctaAnimation !== 'none' ? `animate-${ctaAnimation}` : '';
    const ctaButton = `
        <button class="preview-button ${animationClass}" style="background: ${primaryColor}; color: white; font-family: ${bodyFontFamily}; font-size: 12px;">
            ${escapeHtml(ctaText || 'BUTTON TEXT')}
        </button>
    `;
    
    // ✅ LAYOUTS MIT KORREKTER REIHENFOLGE
    let layoutHTML = '';
    
    if (layout === 'centered') {
        // ZENTRIERT: Überschriften ZUERST, dann Media, dann Bullets, dann CTA
        layoutHTML = `
            <div style="max-width: 800px; margin: 0 auto;">
                ${preheadlineHTML}
                ${headlineHTML}
                ${subheadlineHTML}
                ${mediaElement}
                ${bulletHTML}
                <div class="preview-cta">
                    ${ctaButton}
                </div>
            </div>
        `;
    } else if (layout === 'hybrid') {
        // HYBRID: Media links, Text rechts (mit zentrierten Überschriften)
        layoutHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                <div>${mediaElement}</div>
                <div>
                    ${preheadlineHTML}
                    ${headlineHTML}
                    ${subheadlineHTML}
                    ${bulletHTML}
                    <div class="preview-cta" style="text-align: center;">
                        ${ctaButton}
                    </div>
                </div>
            </div>
        `;
    } else { // sidebar
        // SIDEBAR: Text links, Media rechts (mit zentrierten Überschriften)
        layoutHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                <div>
                    ${preheadlineHTML}
                    ${headlineHTML}
                    ${subheadlineHTML}
                    ${bulletHTML}
                    <div class="preview-cta" style="text-align: center;">
                        ${ctaButton}
                    </div>
                </div>
                <div>${mediaElement}</div>
            </div>
        `;
    }
    
    previewContent.innerHTML = layoutHTML;
}

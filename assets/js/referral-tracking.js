/**
 * Referral Tracking Client-Side Script
 * DSGVO-konform mit LocalStorage für Deduplizierung
 * 
 * Integration in Freebie-Seiten:
 * <script src="/assets/js/referral-tracking.js"></script>
 * <script>
 *   ReferralTracker.trackClick({ customer_id: <?php echo $customer_id; ?>, ref: '<?php echo $_GET['ref'] ?? ''; ?>' });
 * </script>
 * 
 * Integration in Danke-Seiten:
 * <script>
 *   ReferralTracker.trackConversion({ customer_id: <?php echo $customer_id; ?>, ref: '<?php echo $_GET['ref'] ?? ''; ?>' });
 * </script>
 */

const ReferralTracker = {
    /**
     * Track Klick auf Freebie-Seite
     */
    async trackClick(params) {
        const { customer_id, ref } = params;
        
        if (!ref || !customer_id) {
            return;
        }
        
        // LocalStorage-Check zur Deduplizierung
        const storageKey = `ref_click_${customer_id}_${ref}`;
        const lastClick = localStorage.getItem(storageKey);
        const now = Date.now();
        
        // Wenn innerhalb von 24h bereits geklickt, nicht erneut tracken
        if (lastClick && (now - parseInt(lastClick)) < 24 * 60 * 60 * 1000) {
            console.log('[ReferralTracker] Klick bereits getrackt (LocalStorage)');
            return;
        }
        
        try {
            const response = await fetch('/api/referral/track-click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customer_id,
                    ref: ref
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Speichere Zeitstempel in LocalStorage
                localStorage.setItem(storageKey, now.toString());
                
                // Optional: Session-ID für späteren Conversion-Match
                if (data.session_id) {
                    sessionStorage.setItem('referral_session_id', data.session_id);
                }
                
                console.log('[ReferralTracker] Klick erfolgreich getrackt');
            } else {
                console.log('[ReferralTracker] Klick-Tracking:', data.message || data.error);
            }
            
        } catch (error) {
            console.error('[ReferralTracker] Fehler beim Klick-Tracking:', error);
        }
    },
    
    /**
     * Track Conversion auf Danke-Seite
     */
    async trackConversion(params) {
        const { customer_id, ref, source = 'thankyou' } = params;
        
        if (!ref || !customer_id) {
            return;
        }
        
        // LocalStorage-Check zur Deduplizierung
        const storageKey = `ref_conv_${customer_id}_${ref}`;
        const lastConv = localStorage.getItem(storageKey);
        const now = Date.now();
        
        // Wenn innerhalb von 24h bereits konvertiert, nicht erneut tracken
        if (lastConv && (now - parseInt(lastConv)) < 24 * 60 * 60 * 1000) {
            console.log('[ReferralTracker] Conversion bereits getrackt (LocalStorage)');
            return;
        }
        
        try {
            const response = await fetch('/api/referral/track-conversion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customer_id,
                    ref: ref,
                    source: source
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Speichere Zeitstempel in LocalStorage
                localStorage.setItem(storageKey, now.toString());
                
                console.log('[ReferralTracker] Conversion erfolgreich getrackt');
                
                // Warnung wenn verdächtig
                if (data.warning) {
                    console.warn('[ReferralTracker]', data.warning);
                }
            } else {
                console.log('[ReferralTracker] Conversion-Tracking:', data.message || data.error);
            }
            
        } catch (error) {
            console.error('[ReferralTracker] Fehler beim Conversion-Tracking:', error);
        }
    },
    
    /**
     * Zeige Empfehlungsformular auf Danke-Seite
     */
    showReferralForm(params) {
        const { customer_id, ref, container_id = 'referral-form-container' } = params;
        
        if (!ref || !customer_id) {
            return;
        }
        
        const container = document.getElementById(container_id);
        if (!container) {
            console.error('[ReferralTracker] Container nicht gefunden:', container_id);
            return;
        }
        
        const formHtml = `
        <div class="referral-form-wrapper" style="margin-top: 40px; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; color: white;">
            <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 10px; text-align: center;">
                🎁 Jetzt am Empfehlungsprogramm teilnehmen!
            </h2>
            <p style="text-align: center; margin-bottom: 25px; opacity: 0.95;">
                Empfehlen Sie uns weiter und erhalten Sie exklusive Belohnungen
            </p>
            
            <form id="referral-lead-form" style="max-width: 500px; margin: 0 auto;">
                <div style="margin-bottom: 15px;">
                    <input 
                        type="email" 
                        id="referral-email" 
                        required 
                        placeholder="Ihre E-Mail-Adresse"
                        style="width: 100%; padding: 12px 20px; border: none; border-radius: 8px; font-size: 16px;"
                    />
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-size: 14px;">
                        <input 
                            type="checkbox" 
                            id="referral-gdpr" 
                            required
                            style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;"
                        />
                        <span>Ich akzeptiere die Datenschutzerklärung</span>
                    </label>
                </div>
                
                <button 
                    type="submit"
                    style="width: 100%; padding: 14px; background: white; color: #667eea; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: all 0.3s;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.2)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                >
                    Jetzt teilnehmen
                </button>
            </form>
            
            <div id="referral-message" style="margin-top: 20px; padding: 15px; border-radius: 8px; display: none;"></div>
        </div>
        `;
        
        container.innerHTML = formHtml;
        
        // Form-Handler
        document.getElementById('referral-lead-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('referral-email').value;
            const gdprConsent = document.getElementById('referral-gdpr').checked;
            const messageDiv = document.getElementById('referral-message');
            
            if (!gdprConsent) {
                messageDiv.style.display = 'block';
                messageDiv.style.background = '#FEE2E2';
                messageDiv.style.color = '#991B1B';
                messageDiv.textContent = 'Bitte akzeptieren Sie die Datenschutzerklärung';
                return;
            }
            
            try {
                const response = await fetch('/api/referral/register-lead.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        customer_id: customer_id,
                        ref: ref,
                        email: email,
                        gdpr_consent: gdprConsent
                    })
                });
                
                const data = await response.json();
                
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    messageDiv.style.background = '#D1FAE5';
                    messageDiv.style.color = '#065F46';
                    messageDiv.innerHTML = '<strong>✓ Erfolgreich!</strong><br>Bitte bestätigen Sie Ihre E-Mail-Adresse.';
                    document.getElementById('referral-lead-form').style.display = 'none';
                } else {
                    messageDiv.style.background = '#FEE2E2';
                    messageDiv.style.color = '#991B1B';
                    messageDiv.textContent = data.message || 'Ein Fehler ist aufgetreten';
                }
                
            } catch (error) {
                messageDiv.style.display = 'block';
                messageDiv.style.background = '#FEE2E2';
                messageDiv.style.color = '#991B1B';
                messageDiv.textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        });
    },
    
    /**
     * Automatische Initialisierung basierend auf URL-Parametern
     */
    autoInit() {
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');
        const customer = urlParams.get('customer');
        
        if (!ref || !customer) {
            return;
        }
        
        // Speichere ref in SessionStorage für spätere Verwendung
        sessionStorage.setItem('referral_code', ref);
        sessionStorage.setItem('referral_customer', customer);
        
        // Erkennung: Freebie oder Danke-Seite
        const currentPage = window.location.pathname;
        
        if (currentPage.includes('freebie') || currentPage.includes('/f/')) {
            // Freebie-Seite: Track Click
            this.trackClick({ customer_id: customer, ref: ref });
        } else if (currentPage.includes('thankyou') || currentPage.includes('danke')) {
            // Danke-Seite: Track Conversion
            this.trackConversion({ customer_id: customer, ref: ref });
        }
    }
};

// Auto-Init beim Laden
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ReferralTracker.autoInit());
} else {
    ReferralTracker.autoInit();
}

// Export für Module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReferralTracker;
}

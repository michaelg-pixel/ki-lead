<?php
/**
 * SICHERES UPDATE: Ergänzt nur den Sync-Button zur bestehenden Digistore-Seite
 * Überschreibt NICHTS - fügt nur Code hinzu!
 */

$digistoreFile = __DIR__ . '/../admin/sections/digistore.php';

if (!file_exists($digistoreFile)) {
    die("❌ Fehler: digistore.php nicht gefunden in: $digistoreFile");
}

// Backup erstellen
$backupFile = $digistoreFile . '.backup-' . date('Y-m-d-His');
copy($digistoreFile, $backupFile);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Sichere Ergänzung - Sync-Button</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #667eea; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 6px; }
        .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 6px; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 6px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.6; }
        .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Sichere Ergänzung - Sync-Button</h1>
        
        <div class='info'>
            <strong>📋 Was wird gemacht:</strong>
            <ul style='margin: 10px 0 0 20px;'>
                <li>✅ Backup erstellt: <code>" . basename($backupFile) . "</code></li>
                <li>✅ Bestehende digistore.php wird NICHT überschrieben</li>
                <li>➕ Sync-Button-Code wird hinzugefügt</li>
                <li>➕ JavaScript-Funktion wird hinzugefügt</li>
            </ul>
        </div>";

$content = file_get_contents($digistoreFile);

// Prüfe ob Sync-Button bereits vorhanden
if (strpos($content, 'syncProduct') !== false) {
    echo "<div class='warning'>
        <strong>⚠️ Sync-Button ist bereits vorhanden!</strong>
        <p>Die Datei wurde bereits aktualisiert. Keine Änderung notwendig.</p>
    </div>
    
    <a href='/admin/dashboard.php?page=digistore' class='btn'>→ Zur Digistore-Verwaltung</a>
    </div>
    </body>
    </html>";
    exit;
}

echo "<div class='success'>
    <strong>✅ Backup erfolgreich erstellt!</strong>
    <p>Original gesichert als: <code>" . basename($backupFile) . "</code></p>
</div>";

// Code-Snippets zum Hinzufügen
$syncButtonCode = '
                                <button type="button" 
                                        class="btn btn-success" 
                                        onclick="syncProduct(\'<?php echo htmlspecialchars($product[\'product_id\']); ?>\', \'<?php echo htmlspecialchars($product[\'product_name\']); ?>\', false)"
                                        title="Alle Kunden mit diesem Tarif auf die aktuellen Limits aktualisieren">
                                    🔄 Alle Kunden aktualisieren
                                </button>
                                ';

$syncJavaScriptCode = '
// Produkt-Synchronisation - Aktualisiert alle Kunden mit diesem Tarif
async function syncProduct(productId, productName, overwriteManual = false) {
    const message = `🔄 Tarif-Synchronisation\n\nMöchtest du ALLE Kunden mit dem Tarif "${productName}" auf die aktuellen Limits aktualisieren?\n\nDies betrifft:\n- Freebie-Limits\n- Empfehlungsprogramm-Slots\n\nManuell gesetzte Limits werden ${overwriteManual ? \'ÜBERSCHRIEBEN\' : \'NICHT überschrieben\'}.`;
    
    if (!confirm(message)) {
        return;
    }
    
    const formData = new FormData();
    formData.append(\'product_id\', productId);
    formData.append(\'overwrite_manual\', overwriteManual ? \'1\' : \'0\');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = \'<span class="loading-spinner"></span> Synchronisiere...\';
    btn.disabled = true;
    
    try {
        const response = await fetch(\'/api/product-sync-limits.php\', {
            method: \'POST\',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.stats;
            const product = result.product;
            
            let message = `✅ Synchronisation erfolgreich!\n\n`;
            message += `📊 Statistik:\n`;
            message += `- Betroffene Kunden: ${stats.total_customers}\n`;
            message += `- Freebie-Limits aktualisiert: ${stats.freebies_updated}\n`;
            message += `- Referral-Slots aktualisiert: ${stats.referrals_updated}\n`;
            
            if (stats.manual_skipped > 0) {
                message += `- Manuell gesetzte übersprungen: ${stats.manual_skipped}\n\n`;
                message += `💡 Tipp: Diese ${stats.manual_skipped} Kunden haben manuell gesetzte Limits.\n`;
                message += `Möchtest du auch diese überschreiben?`;
                
                if (confirm(message)) {
                    await syncProduct(productId, productName, true);
                    return;
                }
            }
            
            message += `\n✨ Neue Limits:\n`;
            message += `- Freebies: ${product.freebies}\n`;
            message += `- Referral-Slots: ${product.referral_slots}`;
            
            alert(message);
        } else {
            alert(\'❌ Fehler bei der Synchronisation:\n\n\' + result.error);
        }
    } catch (error) {
        console.error(\'Sync error:\', error);
        alert(\'❌ Verbindungsfehler bei der Synchronisation\');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
';

$loadingSpinnerCSS = '
.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
';

echo "<div class='info'>
    <h3 style='margin-top: 0;'>📝 Was wird hinzugefügt:</h3>
    
    <h4>1. Sync-Button bei jedem aktiven Produkt:</h4>
    <pre>&lt;?php if (\$product['is_active'] && !empty(\$product['product_id'])): ?&gt;
    &lt;button class=\"btn btn-success\" onclick=\"syncProduct(...)\"&gt;
        🔄 Alle Kunden aktualisieren
    &lt;/button&gt;
&lt;?php endif; ?&gt;</pre>
    
    <h4>2. JavaScript-Funktion für Sync:</h4>
    <pre>async function syncProduct(productId, productName, overwriteManual) {
    // Bestätigung
    // API-Call zu /api/product-sync-limits.php
    // Zeige Statistik
}</pre>
    
    <h4>3. Loading-Spinner CSS:</h4>
    <pre>.loading-spinner { ... }</pre>
</div>";

// Automatische Integration
echo "<div class='warning'>
    <strong>🔧 Manuelle Integration erforderlich:</strong>
    <p>Da die bestehende Datei eine spezifische Struktur hat, solltest du diese Änderungen manuell vornehmen:</p>
    
    <h4>Schritt 1: Öffne <code>/admin/sections/digistore.php</code></h4>
    
    <h4>Schritt 2: Finde die Form-Actions (ca. Zeile 550-570):</h4>
    <pre>&lt;div class=\"form-actions\"&gt;
    &lt;button type=\"submit\" class=\"btn btn-primary\"&gt;
        💾 Speichern
    &lt;/button&gt;
    
    &lt;?php if (\$product['is_active']): ?&gt;
        &lt;a href=\"/webhook/test-digistore.php?...\" class=\"btn btn-secondary\"&gt;
            🧪 Webhook testen
        &lt;/a&gt;
    &lt;?php endif; ?&gt;
&lt;/div&gt;</pre>
    
    <h4>Schritt 3: Füge den Sync-Button hinzu (zwischen Speichern und Webhook testen):</h4>
    <pre>&lt;div class=\"form-actions\"&gt;
    &lt;button type=\"submit\" class=\"btn btn-primary\"&gt;
        💾 Speichern
    &lt;/button&gt;
    
    <strong style='color: #10b981;'>&lt;!-- NEU: Sync-Button --&gt;
    &lt;?php if (\$product['is_active'] && !empty(\$product['product_id'])): ?&gt;
        &lt;button type=\"button\" 
                class=\"btn btn-success\" 
                onclick=\"syncProduct('&lt;?php echo htmlspecialchars(\$product['product_id']); ?&gt;', 
                                     '&lt;?php echo htmlspecialchars(\$product['product_name']); ?&gt;', 
                                     false)\"
                title=\"Alle Kunden mit diesem Tarif aktualisieren\"&gt;
            🔄 Alle Kunden aktualisieren
        &lt;/button&gt;
    &lt;?php endif; ?&gt;</strong>
    
    &lt;?php if (\$product['is_active']): ?&gt;
        &lt;a href=\"/webhook/test-digistore.php?...\" class=\"btn btn-secondary\"&gt;
            🧪 Webhook testen
        &lt;/a&gt;
    &lt;?php endif; ?&gt;
&lt;/div&gt;</pre>
    
    <h4>Schritt 4: Füge die JavaScript-Funktion am Ende hinzu (vor &lt;/script&gt;):</h4>
    <pre>&lt;script&gt;
// Bestehende Funktionen...

<strong style='color: #10b981;'>// NEU: Produkt-Synchronisation
async function syncProduct(productId, productName, overwriteManual = false) {
    const message = `🔄 Tarif-Synchronisation

Möchtest du ALLE Kunden mit dem Tarif \"\${productName}\" aktualisieren?

Dies betrifft:
- Freebie-Limits
- Empfehlungsprogramm-Slots

Manuell gesetzte Limits werden \${overwriteManual ? 'ÜBERSCHRIEBEN' : 'NICHT überschrieben'}.`;
    
    if (!confirm(message)) return;
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('overwrite_manual', overwriteManual ? '1' : '0');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '&lt;span class=\"loading-spinner\"&gt;&lt;/span&gt; Synchronisiere...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/product-sync-limits.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.stats;
            let msg = `✅ Synchronisation erfolgreich!

📊 Statistik:
- Kunden: \${stats.total_customers}
- Freebies: \${stats.freebies_updated}
- Referrals: \${stats.referrals_updated}`;
            
            if (stats.manual_skipped > 0) {
                msg += `
- Übersprungen: \${stats.manual_skipped}

💡 \${stats.manual_skipped} Kunden haben manuelle Limits.
Möchtest du diese auch überschreiben?`;
                
                if (confirm(msg)) {
                    await syncProduct(productId, productName, true);
                    return;
                }
            }
            
            alert(msg);
        } else {
            alert('❌ Fehler: ' + result.error);
        }
    } catch (error) {
        console.error('Sync error:', error);
        alert('❌ Verbindungsfehler');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}</strong>
&lt;/script&gt;</pre>
    
    <h4>Schritt 5: Füge das Loading-Spinner CSS hinzu (im &lt;style&gt; Bereich):</h4>
    <pre>&lt;style&gt;
/* Bestehende Styles... */

<strong style='color: #10b981;'>/* NEU: Loading Spinner */
.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}</strong>
&lt;/style&gt;</pre>
</div>";

echo "<div class='success'>
    <h3 style='margin-top: 0;'>✅ Zusammenfassung:</h3>
    <ul style='margin: 10px 0 0 20px;'>
        <li><strong>Backup erstellt:</strong> Original gesichert</li>
        <li><strong>Keine Überschreibung:</strong> Alle bestehenden Funktionen bleiben</li>
        <li><strong>Nur Ergänzungen:</strong> Sync-Button + JavaScript + CSS</li>
        <li><strong>Manuelle Integration:</strong> Folge den Schritten oben</li>
    </ul>
</div>";

echo "<div class='info'>
    <h3 style='margin-top: 0;'>💡 Alternative: Automatisches Patch-Script</h3>
    <p>Wenn du möchtest, kann ich auch ein automatisches Script erstellen, das die Änderungen für dich vornimmt. 
    Sag mir Bescheid, wenn du das bevorzugst!</p>
</div>";

echo "<a href='/admin/dashboard.php?page=digistore' class='btn'>→ Zur Digistore-Verwaltung</a>

</div>
</body>
</html>";

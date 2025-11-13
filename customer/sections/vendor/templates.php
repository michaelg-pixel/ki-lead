                <!-- Zugriff & Lieferung -->
                <div class="form-section">
                    <h4 class="form-section-title">🚀 Zugriff & Lieferung</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Lieferungs-Typ</label>
                        <select class="form-select" id="deliveryType" name="reward_delivery_type">
                            <option value="manual">Manuell</option>
                            <option value="automatic">Automatisch</option>
                            <option value="code">Zugriffscode</option>
                            <option value="url">URL/Link</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Download URL</label>
                        <input type="url" class="form-input" id="downloadUrl" name="reward_download_url" placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Anweisungen</label>
                        <textarea class="form-textarea" id="instructions" name="reward_instructions" placeholder="Anweisungen für den Lead..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Videokurs-Dauer</label>
                        <input type="text" class="form-input" id="courseDuration" name="course_duration" placeholder="z.B. 3 Stunden, 5 Wochen">
                        <div class="form-hint">Optional: Wie lange dauert der Kurs (z.B. "3 Stunden", "5 Wochen")</div>
                    </div>
                </div>
                
                <!-- Produkt-Mockup & Links -->
                <div class="form-section">
                    <h4 class="form-section-title">🎨 Produkt-Mockup & Links</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Produkt-Mockup URL</label>
                        <input type="url" class="form-input" id="mockupUrl" name="product_mockup_url" placeholder="https://...">
                        <div class="form-hint">URL zu einem Mockup-Bild des Belohnungsprodukts (z.B. E-Book-Cover, Kurs-Thumbnail)</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link zum Original-Produkt</label>
                        <input type="url" class="form-input" id="originalProductLink" name="original_product_link" placeholder="https://...">
                        <div class="form-hint">Optional: Link zu deinem Original-Produkt (z.B. Verkaufsseite)</div>
                    </div>
                </div>
async function loadTemplate(templateId) {
    try {
        const response = await fetch(`/api/vendor/templates/get.php?id=${templateId}`);
        const data = await response.json();
        
        if (data.success && data.template) {
            const t = data.template;
            
            // Fill form
            document.getElementById('templateId').value = t.id;
            document.getElementById('templateName').value = t.template_name;
            document.getElementById('templateDescription').value = t.template_description || '';
            document.getElementById('category').value = t.category || '';
            document.getElementById('niche').value = t.niche || '';
            document.getElementById('rewardType').value = t.reward_type;
            document.getElementById('rewardTitle').value = t.reward_title;
            document.getElementById('rewardDescription').value = t.reward_description || '';
            document.getElementById('rewardValue').value = t.reward_value || '';
            document.getElementById('rewardIcon').value = t.reward_icon || '';
            document.getElementById('rewardColor').value = t.reward_color || '#667eea';
            document.getElementById('rewardColorText').value = t.reward_color || '#667eea';
            document.getElementById('deliveryType').value = t.reward_delivery_type || 'manual';
            document.getElementById('downloadUrl').value = t.reward_download_url || '';
            document.getElementById('instructions').value = t.reward_instructions || '';
            document.getElementById('courseDuration').value = t.course_duration || '';
            document.getElementById('mockupUrl').value = t.product_mockup_url || '';
            document.getElementById('originalProductLink').value = t.original_product_link || '';
            document.getElementById('tierLevel').value = t.suggested_tier_level || 1;
            document.getElementById('referralsRequired').value = t.suggested_referrals_required || 3;
            document.getElementById('price').value = t.marketplace_price || 0;
            document.getElementById('digistoreId').value = t.digistore_product_id || '';
        } else {
            alert('Fehler beim Laden des Templates');
            closeTemplateModal();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
        closeTemplateModal();
    }
}
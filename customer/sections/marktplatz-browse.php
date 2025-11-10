.marketplace-browse-container {
    padding: 32px;
    max-width: 1800px;
    margin: 0 auto;
    background: #0f0f1e;
    min-height: 100vh;
}

.marketplace-browse-header {
    margin-bottom: 32px;
}

.marketplace-browse-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
}

.marketplace-browse-header p {
    font-size: 16px;
    color: #a0aec0;
}

.filters-bar {
    background: #1a1a2e;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: grid;
    grid-template-columns: 1fr 300px 200px;
    gap: 16px;
    border: 1px solid rgba(255,255,255,0.1);
}

.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 14px;
    background: rgba(255,255,255,0.05);
    color: #ffffff;
    transition: all 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255,255,255,0.08);
}

.search-box::before {
    content: '🔍';
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
}

.filter-select {
    padding: 12px 16px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    background: rgba(255,255,255,0.05);
    color: #ffffff;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255,255,255,0.08);
}

.filter-select option {
    background: #1a1a2e;
    color: #ffffff;
}

.marketplace-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.marketplace-item {
    background: #1a1a2e;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.1);
}

.marketplace-item:hover {
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    transform: translateY(-2px);
    border-color: rgba(102, 126, 234, 0.5);
}

.item-preview {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.item-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-price-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #10b981;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

.item-content {
    padding: 16px;
}

.item-niche {
    display: inline-block;
    font-size: 11px;
    padding: 3px 8px;
    background: rgba(102, 126, 234, 0.2);
    color: #a0aec0;
    border-radius: 10px;
    margin-bottom: 8px;
}

.item-content h3 {
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.item-description {
    font-size: 13px;
    color: #a0aec0;
    line-height: 1.4;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 12px;
    color: #a0aec0;
}

.item-seller {
    display: flex;
    align-items: center;
    gap: 6px;
}

.item-sales {
    color: #10b981;
    font-weight: 600;
}

.btn-view-details {
    width: 100%;
    margin-top: 12px;
    padding: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-view-details:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.empty-marketplace {
    text-align: center;
    padding: 80px 20px;
    background: #1a1a2e;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
}

.empty-marketplace-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-marketplace h3 {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
}

.empty-marketplace p {
    font-size: 14px;
    color: #a0aec0;
}

@media (max-width: 1400px) {
    .marketplace-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .marketplace-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters-bar {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .marketplace-browse-container {
        padding: 20px;
    }
    
    .marketplace-grid {
        grid-template-columns: 1fr;
    }
}
    // FIX: mockup_image_url bevorzugt aus customer_freebies, fallback zu freebies Template
    $stmt = $pdo->prepare("
        SELECT cf.*, u.id as customer_id, COALESCE(cf.mockup_image_url, f.mockup_image_url) as mockup_image_url 
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        LEFT JOIN freebies f ON cf.template_id = f.id 
        WHERE cf.unique_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
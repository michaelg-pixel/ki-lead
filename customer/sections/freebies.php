<?php
// Freebies Section für Customer Dashboard
// Diese Datei wird über dashboard.php?page=freebies eingebunden

// Globale PDO-Variable verwenden
global $pdo;

// Sicherstellen, dass $pdo verfügbar ist
if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

// Customer ID holen
if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Domain für vollständige URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

// Nischen-Kategorien Labels
$nicheLabels = [
    'online-business' => '💼 Online Business & Marketing',
    'gesundheit-fitness' => '💪 Gesundheit & Fitness',
    'persoenliche-entwicklung' => '🧠 Persönliche Entwicklung',
    'finanzen-investment' => '💰 Finanzen & Investment',
    'immobilien' => '🏠 Immobilien',
    'ecommerce-dropshipping' => '🛒 E-Commerce & Dropshipping',
    'affiliate-marketing' => '📈 Affiliate Marketing',
    'social-media-marketing' => '📱 Social Media Marketing',
    'ki-automation' => '🤖 KI & Automation',
    'coaching-consulting' => '👔 Coaching & Consulting',
    'spiritualitaet-mindfulness' => '✨ Spiritualität & Mindfulness',
    'beziehungen-dating' => '❤️ Beziehungen & Dating',
    'eltern-familie' => '👨‍👩‍👧 Eltern & Familie',
    'karriere-beruf' => '🎯 Karriere & Beruf',
    'hobbys-freizeit' => '🎨 Hobbys & Freizeit',
    'sonstiges' => '📂 Sonstiges'
];

// FREEBIE-LIMIT FÜR KUNDE HOLEN
try {
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $limitData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $freebieLimit = $limitData['freebie_limit'] ?? 0;
    $packageName = $limitData['product_name'] ?? 'Basis';
    
    // Anzahl eigener Freebies zählen
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customer_freebies 
        WHERE customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$customer_id]);
    $customFreebiesCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $freebieLimit = 0;
    $customFreebiesCount = 0;
    $packageName = 'Unbekannt';
}

// Freebies aus der Datenbank laden (vom Admin erstellt - Templates)
try {
    $stmt = $pdo->query("
        SELECT 
            f.id,
            f.name,
            f.headline,
            f.subheadline,
            f.preheadline,
            f.mockup_image_url,
            f.background_color,
            f.primary_color,
            f.unique_id,
            f.url_slug,
            f.layout,
            f.cta_text,
            f.bullet_points,
            f.niche,
            f.created_at,
            c.title as course_title,
            c.thumbnail as course_thumbnail
        FROM freebies f
        LEFT JOIN courses c ON f.course_id = c.id
        ORDER BY f.created_at DESC
    ");
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prüfen, welche Freebies der Kunde bereits bearbeitet hat (template-basiert)
    // WICHTIG: mockup_image_url auch aus customer_freebies laden!
    $stmt_customer = $pdo->prepare("
        SELECT template_id, id as customer_freebie_id, unique_id, mockup_image_url, niche
        FROM customer_freebies 
        WHERE customer_id = ? AND (freebie_type = 'template' OR freebie_type IS NULL)
    ");
    $stmt_customer->execute([$customer_id]);
    $customer_freebies_data = [];
    while ($row = $stmt_customer->fetch(PDO::FETCH_ASSOC)) {
        if ($row['template_id']) {
            $customer_freebies_data[$row['template_id']] = [
                'id' => $row['customer_freebie_id'],
                'unique_id' => $row['unique_id'],
                'mockup_image_url' => $row['mockup_image_url'],
                'niche' => $row['niche']
            ];
        }
    }
    
    // EIGENE FREEBIES LADEN (custom type)
    $stmt_custom = $pdo->prepare("
        SELECT 
            cf.id,
            cf.headline,
            cf.subheadline,
            cf.background_color,
            cf.primary_color,
            cf.unique_id,
            cf.layout,
            cf.mockup_image_url,
            cf.niche,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.customer_id = ? AND cf.freebie_type = 'custom'
        ORDER BY cf.created_at DESC
    ");
    $stmt_custom->execute([$customer_id]);
    $customFreebies = $stmt_custom->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $freebies = [];
    $customer_freebies_data = [];
    $customFreebies = [];
    $error = $e->getMessage();
}
?>
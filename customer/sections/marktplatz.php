<?php
// Marktplatz Section für Customer Dashboard
global $pdo;

if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

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

// EIGENE Freebies laden (NUR selbst erstellte Custom Freebies)
try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            headline,
            subheadline,
            mockup_image_url,
            background_color,
            primary_color,
            niche,
            marketplace_enabled,
            marketplace_price,
            digistore_product_id,
            marketplace_description,
            course_lessons_count,
            course_duration,
            marketplace_sales_count,
            freebie_type,
            created_at
        FROM customer_freebies
        WHERE customer_id = ? 
        AND freebie_type = 'custom'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_freebies = [];
    $error = $e->getMessage();
}
?>
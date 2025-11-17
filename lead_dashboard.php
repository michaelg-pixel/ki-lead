// ===== MENÜ-NAVIGATION =====
$current_page = $_GET['page'] ?? 'dashboard';

$menu_items = [
    'dashboard' => ['icon' => 'fa-home', 'label' => 'Dashboard'],
    'kurse' => ['icon' => 'fa-graduation-cap', 'label' => 'Meine Kurse'],
];

if ($referral_enabled) {
    $menu_items['anleitung'] = ['icon' => 'fa-book-open', 'label' => 'So funktioniert\'s'];
    $menu_items['empfehlen'] = ['icon' => 'fa-share-alt', 'label' => 'Empfehlen'];
    if (!empty($delivered_rewards)) {
        $menu_items['belohnungen'] = ['icon' => 'fa-gift', 'label' => 'Meine Belohnungen'];
    }
    $menu_items['social'] = ['icon' => 'fa-robot', 'label' => 'KI Social Assistant'];
}

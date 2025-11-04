<?php
/**
 * API: Firmendaten für AV-Vertrag speichern
 */

session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // POST-Daten validieren
    $required_fields = ['company_name', 'company_address', 'company_zip', 'company_city'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Feld '$field' ist erforderlich");
        }
    }
    
    // Daten aus POST holen
    $company_name = trim($_POST['company_name']);
    $company_address = trim($_POST['company_address']);
    $company_zip = trim($_POST['company_zip']);
    $company_city = trim($_POST['company_city']);
    $company_country = trim($_POST['company_country'] ?? 'Deutschland');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    
    // E-Mail-Validierung wenn angegeben
    if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    // Prüfen ob bereits Daten existieren
    $stmt = $pdo->prepare("SELECT id FROM user_company_data WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE user_company_data 
            SET company_name = ?, 
                company_address = ?, 
                company_zip = ?, 
                company_city = ?, 
                company_country = ?,
                contact_person = ?,
                contact_email = ?,
                contact_phone = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $company_name,
            $company_address,
            $company_zip,
            $company_city,
            $company_country,
            $contact_person,
            $contact_email,
            $contact_phone,
            $user_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Firmendaten erfolgreich aktualisiert',
            'action' => 'updated'
        ]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO user_company_data 
            (user_id, company_name, company_address, company_zip, company_city, 
             company_country, contact_person, contact_email, contact_phone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $company_name,
            $company_address,
            $company_zip,
            $company_city,
            $company_country,
            $contact_person,
            $contact_email,
            $contact_phone
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Firmendaten erfolgreich gespeichert',
            'action' => 'created'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

<?php
header('Content-Type: application/json; charset=utf-8');

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // JSON-Daten empfangen
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (!isset($data['template_id'])) {
        throw new Exception('Template ID fehlt');
    }
    
    $template_id = (int)$data['template_id'];
    
    if ($template_id < 1) {
        throw new Exception('Ungültige Template ID');
    }
    
    $pdo = getDBConnection();
    
    // 1. Template-Daten laden
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception("Template mit ID {$template_id} nicht gefunden");
    }
    
    // 2. Alle Customer Freebies für dieses Template finden
    $stmt = $pdo->prepare("SELECT id, customer_id, unique_id, url_slug FROM customer_freebies WHERE template_id = ?");
    $stmt->execute([$template_id]);
    $customer_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customer_freebies)) {
        throw new Exception("Keine Customer Freebies für Template {$template_id} gefunden. Das Template wurde noch von keinem Kunden verwendet.");
    }
    
    $updated_count = 0;
    $errors = [];
    
    // 3. Jedes Customer Freebie aktualisieren
    foreach ($customer_freebies as $customer_freebie) {
        try {
            $sql = "UPDATE customer_freebies SET
                headline = ?,
                subheadline = ?,
                preheadline = ?,
                bullet_points = ?,
                cta_text = ?,
                layout = ?,
                background_color = ?,
                primary_color = ?,
                secondary_color = ?,
                text_color = ?,
                mockup_image_url = ?,
                show_mockup = ?,
                preheadline_font = ?,
                preheadline_size = ?,
                headline_font = ?,
                headline_size = ?,
                subheadline_font = ?,
                subheadline_size = ?,
                bulletpoints_font = ?,
                bulletpoints_size = ?,
                raw_code = ?,
                custom_css = ?,
                updated_at = NOW()
            WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $template['headline'],
                $template['subheadline'],
                $template['preheadline'],
                $template['bullet_points'],
                $template['cta_text'],
                $template['layout'],
                $template['background_color'],
                $template['primary_color'],
                $template['secondary_color'] ?? '#EC4899',
                $template['text_color'] ?? '#1F2937',
                $template['mockup_image_url'],
                $template['show_mockup'] ?? 1,
                $template['preheadline_font'] ?? 'Poppins',
                $template['preheadline_size'] ?? 14,
                $template['headline_font'] ?? 'Poppins',
                $template['headline_size'] ?? 48,
                $template['subheadline_font'] ?? 'Poppins',
                $template['subheadline_size'] ?? 20,
                $template['bulletpoints_font'] ?? 'Poppins',
                $template['bulletpoints_size'] ?? 16,
                $template['raw_code'] ?? '',
                $template['custom_css'] ?? '',
                $customer_freebie['id']
            ]);
            
            $updated_count++;
            
        } catch (PDOException $e) {
            $errors[] = "Customer Freebie ID {$customer_freebie['id']}: " . $e->getMessage();
        }
    }
    
    // 4. Ergebnis zusammenstellen
    $details = "Synchronisierte Customer Freebies: {$updated_count}\n";
    $details .= "Template ID: {$template_id}\n";
    $details .= "Template Name: {$template['name']}\n\n";
    
    $details .= "Synchronisierte Felder:\n";
    $details .= "- Headline: " . substr($template['headline'], 0, 50) . "...\n";
    $details .= "- Subheadline: " . substr($template['subheadline'], 0, 50) . "...\n";
    $details .= "- Preheadline: " . ($template['preheadline'] ?: '(leer)') . "\n";
    $details .= "- Bullet Points: " . (strlen($template['bullet_points']) > 0 ? 'Ja' : 'Nein') . "\n";
    $details .= "- Mockup URL: " . ($template['mockup_image_url'] ?: '(leer)') . "\n";
    $details .= "- Layout: " . $template['layout'] . "\n";
    $details .= "- Primärfarbe: " . $template['primary_color'] . "\n";
    $details .= "- Hintergrundfarbe: " . $template['background_color'] . "\n";
    
    if (!empty($errors)) {
        $details .= "\n⚠️ Fehler bei einigen Customer Freebies:\n";
        foreach ($errors as $error) {
            $details .= "- {$error}\n";
        }
    }
    
    if ($updated_count > 0) {
        echo json_encode([
            'success' => true,
            'message' => "{$updated_count} Customer Freebie(s) erfolgreich synchronisiert!",
            'details' => $details,
            'updated_count' => $updated_count,
            'error_count' => count($errors)
        ]);
    } else {
        throw new Exception('Keine Customer Freebies wurden aktualisiert. ' . implode('; ', $errors));
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

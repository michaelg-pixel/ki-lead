<?php
/**
 * API für Video-Tutorial-Verwaltung
 * CRUD Operationen für Vimeo-Videos in Videoanleitung
 * NUR für Admin (michael.gluska@gmail.com)
 * Globale Videos (freebie_id = 0) sind für ALLE Leads ALLER Customers sichtbar
 */

require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: application/json');

// Prüfe ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Prüfe ob der Benutzer der Admin ist (NUR michael.gluska@gmail.com)
try {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_email = $stmt->fetchColumn();
    
    if ($user_email !== 'michael.gluska@gmail.com') {
        http_response_code(403);
        echo json_encode(['error' => 'Zugriff verweigert - Nur für Admin']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Prüfen der Berechtigung']);
    exit;
}

$action = $_GET['action'] ?? '';

// Tabelle erstellen falls nicht vorhanden
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS video_tutorials (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id INT NOT NULL,
            freebie_id INT NOT NULL DEFAULT 0,
            category_name VARCHAR(255) NOT NULL,
            category_icon VARCHAR(50) DEFAULT 'fa-video',
            category_color VARCHAR(50) DEFAULT 'purple',
            vimeo_url TEXT NOT NULL,
            description TEXT,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_freebie (freebie_id)
        )
    ");
} catch (PDOException $e) {
    error_log("Fehler beim Erstellen der video_tutorials Tabelle: " . $e->getMessage());
}

// GET: Alle globalen Videos laden (nur für Admin)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    try {
        // Zeige alle globalen Videos (freebie_id = 0)
        $stmt = $pdo->prepare("
            SELECT * FROM video_tutorials 
            WHERE freebie_id = 0 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute();
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'videos' => $videos]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Laden der Videos: ' . $e->getMessage()]);
    }
    exit;
}

// POST: Neues Video hinzufügen (nur für Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // freebie_id muss 0 sein für globale Videos
    $freebie_id = isset($data['freebie_id']) ? (int)$data['freebie_id'] : 0;
    $category_name = $data['category_name'] ?? '';
    $category_icon = $data['category_icon'] ?? 'fa-video';
    $category_color = $data['category_color'] ?? 'purple';
    $vimeo_url = $data['vimeo_url'] ?? '';
    $description = $data['description'] ?? '';
    $sort_order = (int)($data['sort_order'] ?? 0);
    
    if (!$category_name || !$vimeo_url) {
        echo json_encode(['error' => 'Pflichtfelder fehlen (category_name und vimeo_url erforderlich)']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO video_tutorials 
            (customer_id, freebie_id, category_name, category_icon, category_color, vimeo_url, description, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $freebie_id, $category_name, $category_icon, $category_color, $vimeo_url, $description, $sort_order]);
        
        $video_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'video_id' => $video_id,
            'message' => $freebie_id === 0 ? 'Globales Video für alle Leads erstellt' : 'Video erstellt'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Erstellen des Videos: ' . $e->getMessage()]);
    }
    exit;
}

// PUT: Video aktualisieren (nur für Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $video_id = (int)($data['id'] ?? 0);
    $category_name = $data['category_name'] ?? '';
    $category_icon = $data['category_icon'] ?? 'fa-video';
    $category_color = $data['category_color'] ?? 'purple';
    $vimeo_url = $data['vimeo_url'] ?? '';
    $description = $data['description'] ?? '';
    $sort_order = (int)($data['sort_order'] ?? 0);
    
    if (!$video_id || !$category_name || !$vimeo_url) {
        echo json_encode(['error' => 'Pflichtfelder fehlen']);
        exit;
    }
    
    try {
        // Update ohne customer_id Check - Admin kann alle Videos bearbeiten
        $stmt = $pdo->prepare("
            UPDATE video_tutorials 
            SET category_name = ?, category_icon = ?, category_color = ?, 
                vimeo_url = ?, description = ?, sort_order = ? 
            WHERE id = ?
        ");
        $stmt->execute([$category_name, $category_icon, $category_color, $vimeo_url, $description, $sort_order, $video_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Aktualisieren des Videos: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE: Video löschen (nur für Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $video_id = (int)($data['id'] ?? 0);
    
    if (!$video_id) {
        echo json_encode(['error' => 'Video ID fehlt']);
        exit;
    }
    
    try {
        // Delete ohne customer_id Check - Admin kann alle Videos löschen
        $stmt = $pdo->prepare("DELETE FROM video_tutorials WHERE id = ?");
        $stmt->execute([$video_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Löschen des Videos: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Ungültige Anfrage']);

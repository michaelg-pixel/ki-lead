<?php
/**
 * Globale Helper-Funktionen
 */

/**
 * Sicheres Escapen von HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect-Helper
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * JSON-Response senden
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Slug generieren
 */
function generateSlug($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Datei-Upload validieren
 */
function validateUpload($file, $allowed_types = [], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!empty($allowed_types) && !in_array($mime, $allowed_types)) {
        return false;
    }
    
    return true;
}

/**
 * Sicheren Dateinamen generieren
 */
function generateSafeFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Dateigröße formatieren
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Datum formatieren
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

/**
 * E-Mail validieren
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * URL validieren
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Pagination-Helper
 */
function paginate($total, $per_page = 20, $current_page = 1) {
    $total_pages = ceil($total / $per_page);
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Erfolgs-Nachricht setzen
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Fehler-Nachricht setzen
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Flash-Messages abrufen und löschen
 */
function getFlashMessage($type = 'success') {
    $key = $type . '_message';
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Array nach Key sortieren
 */
function sortByKey($array, $key, $direction = 'asc') {
    usort($array, function($a, $b) use ($key, $direction) {
        if ($direction === 'asc') {
            return $a[$key] <=> $b[$key];
        }
        return $b[$key] <=> $a[$key];
    });
    return $array;
}

/**
 * Zufälliges Token generieren
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * IP-Adresse abrufen
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Debug-Helper (nur im DEBUG-Modus!)
 */
function dd(...$vars) {
    if (DEBUG_MODE) {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}

/**
 * Log-Helper
 */
function logMessage($message, $level = 'info') {
    $log_file = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

<?php
// sucky.life configuration
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('EGGS_PATH', DATA_PATH . '/eggs');
define('UPLOADS_PATH', DATA_PATH . '/uploads');
define('CONFIG_FILE', DATA_PATH . '/config.json');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Helper functions
function getConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return null;
    }
    return json_decode(file_get_contents(CONFIG_FILE), true);
}

function saveConfig($config) {
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    
    $fp = fopen(CONFIG_FILE, 'c');
    if (!$fp) {
        return false;
    }
    
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        $result = fwrite($fp, json_encode($config, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result !== false;
    }
    
    fclose($fp);
    return false;
}

function isSetupComplete() {
    return file_exists(CONFIG_FILE);
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
    
    // Check session timeout (30 minutes)
    $timeout = 1800; // 30 minutes in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        session_destroy();
        header('Location: /admin/login.php?timeout=1');
        exit;
    }
    
    // Update last activity time
    $_SESSION['login_time'] = time();
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getAllEggs($includePublished = true, $includeDrafts = false) {
    $eggs = [];
    if (!is_dir(EGGS_PATH)) {
        return $eggs;
    }
    
    $files = glob(EGGS_PATH . '/*.json');
    foreach ($files as $file) {
        $egg = json_decode(file_get_contents($file), true);
        if ($egg) {
            $egg['slug'] = basename($file, '.json');
            $isDraft = isset($egg['draft']) && $egg['draft'];
            
            if (($includePublished && !$isDraft) || ($includeDrafts && $isDraft)) {
                $eggs[] = $egg;
            }
        }
    }
    
    return $eggs;
}

function getEgg($slug) {
    $file = EGGS_PATH . '/' . $slug . '.json';
    if (!file_exists($file)) {
        return null;
    }
    
    $egg = json_decode(file_get_contents($file), true);
    if ($egg) {
        $egg['slug'] = $slug;
    }
    return $egg;
}

function saveEgg($slug, $data) {
    if (!is_dir(EGGS_PATH)) {
        mkdir(EGGS_PATH, 0755, true);
    }
    
    $file = EGGS_PATH . '/' . $slug . '.json';
    $fp = fopen($file, 'c');
    if (!$fp) {
        return false;
    }
    
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        $result = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result !== false;
    }
    
    fclose($fp);
    return false;
}

function deleteEgg($slug) {
    $file = EGGS_PATH . '/' . $slug . '.json';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

function renameEgg($oldSlug, $newSlug) {
    $oldFile = EGGS_PATH . '/' . $oldSlug . '.json';
    $newFile = EGGS_PATH . '/' . $newSlug . '.json';
    
    if (file_exists($oldFile) && !file_exists($newFile)) {
        return rename($oldFile, $newFile);
    }
    return false;
}

function getUploadedMedia($type = null) {
    $media = [];
    if (!is_dir(UPLOADS_PATH)) {
        return $media;
    }
    
    $files = glob(UPLOADS_PATH . '/*.*');
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $filename = basename($file);
        
        $mediaType = null;
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $mediaType = 'image';
        } elseif (in_array($ext, ['mp4', 'webm', 'mov'])) {
            $mediaType = 'video';
        } elseif (in_array($ext, ['mp3', 'wav', 'ogg'])) {
            $mediaType = 'audio';
        }
        
        if ($mediaType && ($type === null || $type === $mediaType)) {
            $media[] = [
                'filename' => $filename,
                'path' => '/data/uploads/' . $filename,
                'type' => $mediaType,
                'size' => filesize($file)
            ];
        }
    }
    
    return $media;
}

function sanitizeSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    $slug = $text ?: 'egg-' . time();
    
    // Ensure slug is unique by checking existing eggs
    $counter = 1;
    $originalSlug = $slug;
    while (getEgg($slug) !== null) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

function logError($message) {
    $logFile = DATA_PATH . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}
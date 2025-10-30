<?php
// Enable error logging but disable display
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/error.log');
ini_set('display_errors', 0);

require_once '../config.php';

// Check auth without redirect
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Update session activity time
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}
$_SESSION['login_time'] = time();

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if this is a file upload (multipart/form-data)
    if (isset($_POST['csrf_token'])) {
        // File upload request
        $action = 'upload_media';
        $csrfToken = $_POST['csrf_token'];
    } else {
        // JSON request
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $csrfToken = $input['csrf_token'] ?? '';
    }

    // Debug CSRF token (TEMPORARY - REMOVE AFTER TESTING)
    $debug = [
        'received_token' => $csrfToken ?? 'NULL',
        'session_token' => $_SESSION['csrf_token'] ?? 'NULL',
        'tokens_match' => isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken ?? ''),
        'session_id' => session_id(),
        'action' => $action,
        'post_keys' => array_keys($_POST)
    ];

    // Verify CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid CSRF token',
            'debug' => $debug
        ]);
        exit;
    }

    switch ($action) {
        case 'delete_egg':
            $slug = $input['slug'] ?? '';
            if (empty($slug)) {
                echo json_encode(['success' => false, 'error' => 'Slug is required']);
                exit;
            }
            
            if (deleteEgg($slug)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete egg']);
            }
            break;

        case 'update_position':
            $slug = $input['slug'] ?? '';
            $posLeft = $input['pos_left'] ?? null;
            $posTop = $input['pos_top'] ?? null;
            
            if (empty($slug) || $posLeft === null || $posTop === null) {
                echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
                exit;
            }
            
            // Validate position values (clamp to 0-100)
            $posLeft = max(0, min(100, (float)$posLeft));
            $posTop = max(0, min(100, (float)$posTop));
            
            $egg = getEgg($slug);
            if (!$egg) {
                echo json_encode(['success' => false, 'error' => 'Egg not found']);
                exit;
            }
            
            $egg['pos_left'] = $posLeft;
            $egg['pos_top'] = $posTop;
            
            if (saveEgg($slug, $egg)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save position']);
            }
            break;

        case 'upload_media':
            // Handle file upload
            if (!isset($_FILES['file'])) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                exit;
            }
            
            $file = $_FILES['file'];
            $error = $file['error'];
            
            if ($error !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'Upload failed with error code ' . $error]);
                exit;
            }
            
            // Check file size (max 2MB - matches typical PHP limits)
            $maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 2MB.']);
                exit;
            }
            
            $tmpName = $file['tmp_name'];
            $originalName = basename($file['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'mp3', 'wav', 'ogg'];
            if (!in_array($ext, $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Invalid file type']);
                exit;
            }
            
            // Ensure uploads directory exists
            if (!is_dir(UPLOADS_PATH)) {
                mkdir(UPLOADS_PATH, 0755, true);
            }
            
            // Generate unique filename
            $filename = pathinfo($originalName, PATHINFO_FILENAME);
            $filename = preg_replace('/[^a-z0-9-_]/i', '-', $filename);
            $filename = $filename . '-' . time() . '.' . $ext;
            
            $destination = UPLOADS_PATH . '/' . $filename;
            
            if (!move_uploaded_file($tmpName, $destination)) {
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
                exit;
            }
            
            // Convert images to WebP
            $webpPath = null;
            if (in_array($ext, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
                $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
                $webpPath = UPLOADS_PATH . '/' . $webpFilename;
                
                try {
                    $image = null;
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $image = @imagecreatefromjpeg($destination);
                    } elseif ($ext === 'png') {
                        $image = @imagecreatefrompng($destination);
                    }
                    
                    if ($image) {
                        @imagewebp($image, $webpPath, 85);
                        @imagedestroy($image);
                    }
                } catch (Exception $e) {
                    // WebP conversion failed, but original file is still uploaded
                    $webpPath = null;
                }
            }
            
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'path' => '/data/uploads/' . $filename,
                'webp_path' => $webpPath ? '/data/uploads/' . basename($webpPath) : null
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
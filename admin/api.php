<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

// Parse JSON request
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Verify CSRF token
if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
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
        
        $egg = getEgg($slug);
        if (!$egg) {
            echo json_encode(['success' => false, 'error' => 'Egg not found']);
            exit;
        }
        
        $egg['pos_left'] = (float)$posLeft;
        $egg['pos_top'] = (float)$posTop;
        
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
        
        $tmpName = $file['tmp_name'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'mp3', 'wav', 'ogg'];
        if (!in_array($ext, $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
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
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            $webpPath = UPLOADS_PATH . '/' . $webpFilename;
            
            try {
                $image = null;
                if ($ext === 'jpg' || $ext === 'jpeg') {
                    $image = imagecreatefromjpeg($destination);
                } elseif ($ext === 'png') {
                    $image = imagecreatefrompng($destination);
                }
                
                if ($image) {
                    imagewebp($image, $webpPath, 85);
                    imagedestroy($image);
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
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}

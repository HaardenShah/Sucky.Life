<?php
require_once '../config.php';
requireAuth();

$config = getConfig();
$slug = $_GET['slug'] ?? '';
$egg = getEgg($slug);

if (!$egg) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $egg['title'] = trim($_POST['title'] ?? '');
        $egg['caption'] = trim($_POST['caption'] ?? '');
        $egg['body'] = $_POST['body'] ?? '';
        $egg['alt'] = trim($_POST['alt'] ?? '');
        $egg['draft'] = isset($_POST['draft']);
        
        // Handle media selections
        $egg['image'] = !empty($_POST['image']) ? $_POST['image'] : null;
        $egg['image_webp'] = !empty($_POST['image_webp']) ? $_POST['image_webp'] : null;
        $egg['video'] = !empty($_POST['video']) ? $_POST['video'] : null;
        $egg['video_poster'] = !empty($_POST['video_poster']) ? $_POST['video_poster'] : null;
        $egg['audio'] = !empty($_POST['audio']) ? $_POST['audio'] : null;
        
        if (saveEgg($slug, $egg)) {
            $success = 'Egg updated successfully!';
        } else {
            $error = 'Failed to save egg';
        }
    }
}

$allImages = getUploadedMedia('image');
$allVideos = getUploadedMedia('video');
$allAudio = getUploadedMedia('audio');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?php echo htmlspecialchars($egg['title']); ?> - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/theme-toggle.js"></script>
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <div class="admin-nav-content">
            <h1 class="admin-brand"><?php echo htmlspecialchars($config['site_name']); ?></h1>
            <div class="admin-nav-links">
                <a href="/admin/index.php" class="nav-link">Dashboard</a>
                <a href="/admin/settings.php" class="nav-link">Settings</a>
                <a href="/admin/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <div class="admin-header">
            <h2>Edit Egg: <?php echo htmlspecialchars($egg['title']); ?></h2>
            <div class="header-actions">
                <a href="/admin/egg-place.php?slug=<?php echo urlencode($slug); ?>" class="btn">📍 Place on Site</a>
                <a href="/admin/index.php" class="btn">← Back</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="egg-editor-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="editor-grid">
                <div class="editor-main">
                    <div class="content-card">
                        <h3>Basic Info</h3>
                        
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($egg['title']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="caption">Caption</label>
                            <input type="text" id="caption" name="caption"
                                   value="<?php echo htmlspecialchars($egg['caption']); ?>"
                                   placeholder="Short italic text shown below title">
                        </div>

                        <div class="form-group">
                            <label for="body">Body (HTML allowed)</label>
                            <textarea id="body" name="body" rows="10"><?php echo htmlspecialchars($egg['body']); ?></textarea>
                            <small>You can use HTML tags like &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, etc.</small>
                        </div>

                        <div class="form-group">
                            <label for="alt">Alt Text</label>
                            <input type="text" id="alt" name="alt"
                                   value="<?php echo htmlspecialchars($egg['alt']); ?>"
                                   placeholder="Describe the image for accessibility">
                        </div>
                    </div>

                    <div class="content-card">
                        <h3>Media</h3>
                        
                        <div class="form-group">
                            <label>Image</label>
                            <div class="media-selector" data-type="image">
                                <input type="hidden" name="image" id="selected-image" value="<?php echo htmlspecialchars($egg['image'] ?? ''); ?>">
                                <input type="hidden" name="image_webp" id="selected-image-webp" value="<?php echo htmlspecialchars($egg['image_webp'] ?? ''); ?>">
                                
                                <div class="selected-media" id="selected-image-preview">
                                    <?php if (!empty($egg['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($egg['image_webp'] ?? $egg['image']); ?>" alt="Selected">
                                        <button type="button" class="remove-media" onclick="clearImage()">×</button>
                                    <?php else: ?>
                                        <div class="no-media">No image selected</div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="btn btn-sm" onclick="openMediaPicker('image')">Choose Image</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Video (alternative to image)</label>
                            <div class="media-selector" data-type="video">
                                <input type="hidden" name="video" id="selected-video" value="<?php echo htmlspecialchars($egg['video'] ?? ''); ?>">
                                
                                <div class="selected-media" id="selected-video-preview">
                                    <?php if (!empty($egg['video'])): ?>
                                        <video src="<?php echo htmlspecialchars($egg['video']); ?>" controls style="width: 100%;"></video>
                                        <button type="button" class="remove-media" onclick="clearVideo()">×</button>
                                    <?php else: ?>
                                        <div class="no-media">No video selected</div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="btn btn-sm" onclick="openMediaPicker('video')">Choose Video</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Audio (optional)</label>
                            <div class="media-selector" data-type="audio">
                                <input type="hidden" name="audio" id="selected-audio" value="<?php echo htmlspecialchars($egg['audio'] ?? ''); ?>">
                                
                                <div class="selected-media" id="selected-audio-preview">
                                    <?php if (!empty($egg['audio'])): ?>
                                        <audio src="<?php echo htmlspecialchars($egg['audio']); ?>" controls style="width: 100%;"></audio>
                                        <button type="button" class="remove-media" onclick="clearAudio()">×</button>
                                    <?php else: ?>
                                        <div class="no-media">No audio selected</div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="btn btn-sm" onclick="openMediaPicker('audio')">Choose Audio</button>
                            </div>
                        </div>

                        <div class="upload-zone" id="upload-zone">
                            <p>📁 Drag & drop files here to upload</p>
                            <p class="upload-hint">or click to browse</p>
                            <input type="file" id="file-input" multiple accept="image/*,video/*,audio/*" style="display: none;">
                        </div>
                    </div>
                </div>

                <div class="editor-sidebar">
                    <div class="content-card">
                        <h3>Status</h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="draft" <?php echo (isset($egg['draft']) && $egg['draft']) ? 'checked' : ''; ?>>
                                <span>Save as draft</span>
                            </label>
                            <small>Drafts are not visible on the public site</small>
                        </div>

                        <div class="form-group">
                            <label>Position</label>
                            <div class="position-display">
                                <span><?php echo round($egg['pos_left'] ?? 50, 1); ?>vw</span>
                                <span><?php echo round($egg['pos_top'] ?? 50, 1); ?>vh</span>
                            </div>
                            <a href="/admin/egg-place.php?slug=<?php echo urlencode($slug); ?>" class="btn btn-sm btn-block">Update Position</a>
                        </div>
                    </div>

                    <div class="form-actions-sticky">
                        <button type="submit" class="btn btn-primary btn-large">Save Changes</button>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <!-- Media Picker Modal -->
    <div id="media-picker-modal" class="modal hidden">
        <div class="modal-backdrop" onclick="closeMediaPicker()"></div>
        <div class="modal-card modal-large">
            <button class="modal-close" onclick="closeMediaPicker()">×</button>
            <div class="modal-content">
                <h2 id="media-picker-title">Select Media</h2>
                <div id="media-picker-grid" class="media-picker-grid">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // CRITICAL: Define csrfToken for admin-editor.js
        const csrfToken = '<?php echo generateCSRFToken(); ?>';
        const allImages = <?php echo json_encode($allImages); ?>;
        const allVideos = <?php echo json_encode($allVideos); ?>;
        const allAudio = <?php echo json_encode($allAudio); ?>;
        let currentMediaType = null;
    </script>
    <script src="/assets/js/admin-editor.js"></script>
</body>
</html>
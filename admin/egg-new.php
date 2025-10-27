<?php
require_once '../config.php';
requireAuth();

$config = getConfig();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        $slug = sanitizeSlug($title);
        
        // Check if slug already exists
        if (getEgg($slug)) {
            $slug = $slug . '-' . time();
        }
        
        // Create new egg with default values
        $eggData = [
            'title' => $title,
            'caption' => '',
            'body' => '',
            'alt' => '',
            'image' => null,
            'image_webp' => null,
            'video' => null,
            'video_poster' => null,
            'audio' => null,
            'pos_left' => 50,
            'pos_top' => 50,
            'draft' => true
        ];
        
        if (saveEgg($slug, $eggData)) {
            header('Location: /admin/egg-edit.php?slug=' . urlencode($slug));
            exit;
        } else {
            $error = 'Failed to create egg';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Egg - <?php echo htmlspecialchars($config['site_name']); ?></title>
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
            <h2>Create New Egg</h2>
        </div>

        <div class="content-card">
            <form method="POST" class="form">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Egg Title *</label>
                    <input type="text" id="title" name="title" required autofocus
                           placeholder="e.g., The Great Coffee Incident">
                    <small>This will be shown in the tooltip and modal</small>
                </div>

                <div class="form-actions">
                    <a href="/admin/index.php" class="btn">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Egg</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

<?php
require_once '../config.php';
requireAuth();

$config = getConfig();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_settings') {
            $config['site_name'] = trim($_POST['site_name'] ?? '');
            $config['domain'] = trim($_POST['domain'] ?? '');
            $config['hero_text'] = trim($_POST['hero_text'] ?? 'When the universe has it out for you');
            $config['hero_image'] = trim($_POST['hero_image'] ?? '');
            $config['site_password_enabled'] = isset($_POST['site_password_enabled']);
            
            if ($config['site_password_enabled'] && !empty($_POST['site_password'])) {
                $config['site_password_hash'] = password_hash($_POST['site_password'], PASSWORD_DEFAULT);
            }
            
            if (saveConfig($config)) {
                $success = 'Settings updated successfully!';
            } else {
                $error = 'Failed to save settings';
            }
        } elseif ($action === 'change_admin_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (!password_verify($currentPassword, $config['admin_password_hash'])) {
                $error = 'Current password is incorrect';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters';
            } else {
                $config['admin_password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                if (saveConfig($config)) {
                    $success = 'Admin password changed successfully!';
                } else {
                    $error = 'Failed to change password';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <div class="admin-nav-content">
            <h1 class="admin-brand"><?php echo htmlspecialchars($config['site_name']); ?></h1>
            <div class="admin-nav-links">
                <a href="/admin/index.php" class="nav-link">Dashboard</a>
                <a href="/admin/settings.php" class="nav-link active">Settings</a>
                <a href="/admin/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <div class="admin-header">
            <h2>Settings</h2>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3>Site Settings</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_settings">

                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" required
                           value="<?php echo htmlspecialchars($config['site_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="domain">Domain</label>
                    <input type="text" id="domain" name="domain" required
                           value="<?php echo htmlspecialchars($config['domain']); ?>">
                </div>

                <div class="form-group">
                    <label for="hero_text">Hero Text</label>
                    <input type="text" id="hero_text" name="hero_text"
                           value="<?php echo htmlspecialchars($config['hero_text'] ?? 'When the universe has it out for you'); ?>"
                           placeholder="When the universe has it out for you">
                    <small>The main dramatic text displayed on the homepage</small>
                </div>

                <div class="form-group">
                    <label>Hero Background Image</label>
                    <?php if (!empty($config['hero_image'])): ?>
                        <div class="selected-media">
                            <img src="<?php echo htmlspecialchars($config['hero_image']); ?>" alt="Hero background" style="max-height: 200px;">
                            <button type="button" class="remove-media" onclick="clearHeroImage()">×</button>
                        </div>
                    <?php else: ?>
                        <div class="selected-media" id="hero-image-preview">
                            <div class="no-media">Using default gradient background</div>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="hero_image" id="hero-image-input" value="<?php echo htmlspecialchars($config['hero_image'] ?? ''); ?>">
                    <button type="button" class="btn btn-sm" onclick="openHeroImagePicker()">Choose Background Image</button>
                    <small>Optional: Upload a background image for the hero section</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="site_password_enabled" 
                               <?php echo ($config['site_password_enabled'] ?? false) ? 'checked' : ''; ?>>
                        <span>Enable site password (gate for visitors)</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="site_password">Site Password (leave blank to keep current)</label>
                    <input type="password" id="site_password" name="site_password">
                    <small>This password is required for visitors to access the site</small>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>

        <div class="content-card">
            <h3>Change Admin Password</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="change_admin_password">

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                </div>

                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </main>

    <!-- Media Picker Modal for Hero Image -->
    <div id="hero-image-modal" class="modal hidden">
        <div class="modal-backdrop" onclick="closeHeroImagePicker()"></div>
        <div class="modal-card modal-large">
            <button class="modal-close" onclick="closeHeroImagePicker()">×</button>
            <div class="modal-content">
                <h2>Select Hero Background Image</h2>
                <div id="hero-image-grid" class="media-picker-grid">
                    <?php 
                    $heroImages = getUploadedMedia('image');
                    if (empty($heroImages)): ?>
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem; grid-column: 1/-1;">No images uploaded yet. Use the upload zone below to add images.</p>
                    <?php else: ?>
                        <?php foreach ($heroImages as $img): ?>
                            <div class="media-item" onclick="selectHeroImage('<?php echo htmlspecialchars($img['path']); ?>')">
                                <img src="<?php echo htmlspecialchars($img['path']); ?>" alt="<?php echo htmlspecialchars($img['filename']); ?>" loading="lazy">
                                <div class="media-item-name"><?php echo htmlspecialchars($img['filename']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="upload-zone" id="hero-upload-zone">
                    <p>📁 Drag & drop images here to upload</p>
                    <p class="upload-hint">or click to browse</p>
                    <input type="file" id="hero-file-input" multiple accept="image/*" style="display: none;">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hero image picker
        function openHeroImagePicker() {
            document.getElementById('hero-image-modal').classList.remove('hidden');
        }

        function closeHeroImagePicker() {
            document.getElementById('hero-image-modal').classList.add('hidden');
        }

        function selectHeroImage(path) {
            document.getElementById('hero-image-input').value = path;
            document.getElementById('hero-image-preview').innerHTML = `
                <img src="${path}" alt="Hero background" style="max-height: 200px;">
                <button type="button" class="remove-media" onclick="clearHeroImage()">×</button>
            `;
            closeHeroImagePicker();
        }

        function clearHeroImage() {
            document.getElementById('hero-image-input').value = '';
            document.getElementById('hero-image-preview').innerHTML = '<div class="no-media">Using default gradient background</div>';
        }

        // Upload functionality
        const heroUploadZone = document.getElementById('hero-upload-zone');
        const heroFileInput = document.getElementById('hero-file-input');

        heroUploadZone.addEventListener('click', () => {
            heroFileInput.click();
        });

        heroUploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            heroUploadZone.style.borderColor = '#667eea';
            heroUploadZone.style.background = 'rgba(102, 126, 234, 0.05)';
        });

        heroUploadZone.addEventListener('dragleave', () => {
            heroUploadZone.style.borderColor = 'var(--border-color)';
            heroUploadZone.style.background = '';
        });

        heroUploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            heroUploadZone.style.borderColor = 'var(--border-color)';
            heroUploadZone.style.background = '';
            
            const files = e.dataTransfer.files;
            handleHeroFiles(files);
        });

        heroFileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            handleHeroFiles(files);
        });

        async function handleHeroFiles(files) {
            for (let i = 0; i < files.length; i++) {
                await uploadHeroFile(files[i]);
            }
            
            // Reload page to show new uploads
            location.reload();
        }

        async function uploadHeroFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/admin/api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Upload error: ' + error.message);
            }
        }

        // Close modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.getElementById('hero-image-modal').classList.contains('hidden')) {
                closeHeroImagePicker();
            }
        });
    </script>
</body>
</html>
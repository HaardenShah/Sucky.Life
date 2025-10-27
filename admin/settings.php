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
    <script src="/assets/js/theme-toggle.js"></script>
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
</body>
</html>

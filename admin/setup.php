<?php
require_once '../config.php';

// Redirect if already setup
if (isSetupComplete()) {
    header('Location: /admin/login.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim($_POST['site_name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($siteName) || empty($domain) || empty($adminPassword)) {
        $error = 'All fields are required';
    } elseif ($adminPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($adminPassword) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Create directories
        $dirs = [DATA_PATH, EGGS_PATH, UPLOADS_PATH];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Save configuration
        $config = [
            'site_name' => $siteName,
            'domain' => $domain,
            'admin_password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            'site_password_enabled' => false,
            'setup_complete' => true
        ];

        if (saveConfig($config)) {
            $success = true;
        } else {
            $error = 'Failed to save configuration';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - sucky.life</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="setup-page">
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <h1 class="setup-title">✨ Welcome to sucky.life</h1>
                <p class="setup-subtitle">Let's get your inside-joke website set up</p>
            </div>

            <?php if ($success): ?>
                <div class="success-animation">
                    <div class="checkmark">✓</div>
                    <h2>All set!</h2>
                    <p>Your site is ready to go.</p>
                    <a href="/admin/login.php" class="btn btn-primary">Go to Admin Login</a>
                </div>
            <?php else: ?>
                <form method="POST" class="setup-form">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($_POST['site_name'] ?? 'sucky.life'); ?>" 
                               required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="domain">Domain</label>
                        <input type="text" id="domain" name="domain" 
                               value="<?php echo htmlspecialchars($_POST['domain'] ?? 'sucky.life'); ?>" 
                               required>
                        <small>e.g., sucky.life or example.com</small>
                    </div>

                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" 
                               minlength="8" required>
                        <small>Minimum 8 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">Complete Setup</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .setup-page {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .setup-container {
            width: 100%;
            max-width: 500px;
        }

        .setup-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .setup-title {
            font-size: 2rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .setup-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }

        .success-animation {
            text-align: center;
            animation: fadeIn 0.6s;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
            animation: scaleUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</body>
</html>

<?php
require_once '../config.php';

// Redirect to setup if not complete
if (!isSetupComplete()) {
    header('Location: /admin/setup.php');
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$config = getConfig();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (password_verify($password, $config['admin_password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Incorrect password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle"><?php echo htmlspecialchars($config['site_name']); ?></p>

            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-error">Your session has expired. Please login again.</div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-large">Login</button>
            </form>

            <div class="login-footer">
                <a href="/" class="link-secondary">← Back to site</a>
            </div>
        </div>
    </div>
</body>
</html>
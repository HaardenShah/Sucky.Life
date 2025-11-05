<?php
require_once 'config.php';

$config = getConfig();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['gate_access']) && isset($config['site_password_hash'])) {
        if (password_verify($_POST['gate_access'], $config['site_password_hash'])) {
            $_SESSION['site_access'] = true;
            header('Location: /index.php');
            exit;
        } else {
            $error = 'Incorrect password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Required - <?php echo htmlspecialchars($config['site_name'] ?? 'sucky.life'); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .gate-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }
        .gate-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .gate-title {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 1rem;
            text-align: center;
        }
        .gate-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .gate-input {
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
        }
        .gate-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.4);
        }
        .gate-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .gate-button:hover {
            transform: translateY(-2px);
        }
        .gate-error {
            color: #ff6b6b;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="gate-container">
        <div class="gate-card">
            <h1 class="gate-title">🔒 Password Required</h1>
            <form method="POST" class="gate-form" autocomplete="off">
                <?php if ($error): ?>
                    <div class="gate-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <input type="password" name="gate_access" class="gate-input" placeholder="Enter password" required autofocus autocomplete="off">
                <button type="submit" class="gate-button">Enter</button>
            </form>
        </div>
    </div>
</body>
</html>

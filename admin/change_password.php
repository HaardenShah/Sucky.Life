<?php
/**
 * change_password.php — Admin password change screen
 * - Requires admin session
 * - Verifies current password (if set)
 * - Writes admin/password.json with new hash
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';
if (empty($_SESSION['authed'])) { header('Location: /admin/login.php'); exit; }

$PWD_JSON = __DIR__.'/password.json';
$rec = file_exists($PWD_JSON) ? (json_decode(@file_get_contents($PWD_JSON), true) ?: []) : [];
$hash = $rec['hash'] ?? '';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cur = $_POST['current'] ?? '';
  $a = $_POST['a'] ?? '';
  $b = $_POST['b'] ?? '';
  if ($a === '' || $a !== $b) {
    $err = 'New passwords do not match.';
  } elseif ($hash && !password_verify($cur, $hash)) {
    $err = 'Current password is incorrect.';
  } else {
    $newHash = password_hash($a, PASSWORD_DEFAULT);
    $rec = ['hash'=>$newHash, 'updated_at'=>date('c')];
    if (!@file_put_contents($PWD_JSON, json_encode($rec, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))) {
      $err = 'Failed to write password file.';
    } else {
      $msg = 'Password updated.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change Admin Password</title>
<style>
  :root{--bg:#0e0f12;--panel:#0f1423;--line:#23283a;--fg:#eef2ff;--mut:#a6aec2;--bezier:cubic-bezier(.22,.61,.36,1)}
  *{box-sizing:border-box} html,body{height:100%} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Inter,Arial}
  .wrap{max-width:720px;margin:40px auto;padding:0 16px}
  .panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:18px 16px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
  h1{margin:0 0 10px}
  label{display:block;margin:12px 0 6px;color:#c8cbe0}
  input{width:100%;padding:12px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff}
  button{margin-top:14px;padding:12px 16px;border-radius:12px;border:1px solid #3a405b;background:#141a2b;color:#eef2ff;font-weight:700;cursor:pointer}
  .muted{color:#9aa2b8}
  .ok{color:#b7f39a;margin-top:10px}
  .err{color:#ff9a9a;margin-top:10px}
  a{color:#cbd5ff}
</style>
</head>
<body>
<div class="wrap">
  <h1>Change Admin Password</h1>
  <div class="panel">
    <form method="post" action="">
      <?php if($hash): ?>
        <label for="current">Current password</label>
        <input type="password" id="current" name="current" required>
      <?php else: ?>
        <div class="muted">No existing password found; you can set one now.</div>
      <?php endif; ?>

      <label for="a">New password</label>
      <input type="password" id="a" name="a" required>

      <label for="b">Confirm new password</label>
      <input type="password" id="b" name="b" required>

      <button type="submit">Update password</button>
    </form>

    <?php if($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <p style="margin-top:14px"><a href="/admin/index.php">← Back to Admin</a></p>
  </div>
</div>
</body>
</html>
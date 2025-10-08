<?php
/**
 * admin/login.php — Simple admin login
 * - Uses admin/password.json (hash via password_hash)
 * - Sets $_SESSION['authed']=true on success
 * - Redirects to /admin/index.php
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';

$PWD_JSON = __DIR__ . '/password.json';
if ($NEEDS_SETUP) { header('Location: /admin/setup.php'); exit; }
if (!file_exists($PWD_JSON)) { header('Location: /admin/setup.php'); exit; }

// Already authed? go to manager
if (!empty($_SESSION['authed'])) {
  header('Location: /admin/index.php');
  exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pw = $_POST['pw'] ?? '';
  $rec = json_decode(@file_get_contents($PWD_JSON), true) ?: [];
  $hash = $rec['hash'] ?? '';
  if ($hash && password_verify($pw, $hash)) {
    $_SESSION['authed'] = true;
    // optional: regenerate session id for safety
    if (function_exists('session_regenerate_id')) @session_regenerate_id(true);
    header('Location: /admin/index.php');
    exit;
  } else {
    $err = 'Incorrect password.';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{--bg:#0e0f12;--panel:#101425;--line:#23283a;--fg:#eef2ff;--mut:#9aa2b8;--brand:#ffcc00;--bezier:cubic-bezier(.22,.61,.36,1)}
  *{box-sizing:border-box} html,body{height:100%} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Inter,Arial;display:grid;place-items:center}
  .card{width:min(480px,92vw);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));border:1px solid var(--line);border-radius:18px;padding:24px 20px;box-shadow:0 30px 80px rgba(0,0,0,.45)}
  h1{margin:0 0 6px;font-size:clamp(20px,2.6vw,26px)}
  p{margin:8px 0 18px;color:var(--mut)}
  .field{display:flex;gap:10px}
  input[type=password]{flex:1;padding:14px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff;font-size:16px;outline:none}
  input[type=password]:focus{border-color:#3e4560;box-shadow:0 0 0 4px rgba(255,255,255,.05)}
  button{padding:12px 16px;border-radius:12px;border:1px solid #3a3f56;background:rgba(255,255,255,.08);color:var(--fg);font-weight:700;cursor:pointer;transition:transform .18s var(--bezier),background .25s}
  button:hover{transform:translateY(-1px);background:rgba(255,255,255,.12)}
  .brand{color:var(--brand);font-weight:800}
  .err{color:#ff6b6b;margin-top:10px}
  .links{margin-top:14px;display:flex;justify-content:space-between;font-size:12px}
  a{color:#cbd5ff}
</style>
</head>
<body>
  <form class="card" method="post" action="">
    <h1>Admin — <span class="brand"><?= htmlspecialchars($SITE_NAME) ?></span></h1>
    <p>Enter your admin password.</p>

    <div class="field">
      <input type="password" name="pw" placeholder="Password" autofocus required>
      <button type="submit">Sign in</button>
    </div>

    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="links">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></span>
      <span><a href="/admin/setup.php">Run setup</a></span>
    </div>
  </form>
</body>
</html>
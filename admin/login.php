<?php
header('Content-Type: text/html; charset=utf-8'); ini_set('default_charset','UTF-8');
require __DIR__ . '/config.php';

$PWD_JSON = __DIR__ . '/password.json';
if ($NEEDS_SETUP || !file_exists($PWD_JSON)) { header('Location: /admin/setup.php'); exit; }
if (!empty($_SESSION['authed'])) { header('Location: /admin/index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pw = $_POST['pw'] ?? '';
  if (!rate_limit('admin_login', 8, 300)) { sleep(2); $err = 'Too many attempts. Try again later.'; }
  else {
    $rec = json_decode(@file_get_contents($PWD_JSON), true) ?: [];
    $hash = $rec['hash'] ?? '';
    if ($hash && password_verify($pw, $hash)) { $_SESSION['authed']=true; if(function_exists('session_regenerate_id')) @session_regenerate_id(true); header('Location: /admin/index.php'); exit; }
    else { $err='Incorrect password.'; }
  }
}
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{--bg:#0e0f12;--panel:#101425;--line:#23283a;--fg:#eef2ff;--mut:#9aa2b8;--brand:#ffcc00;--bezier:cubic-bezier(.22,.61,.36,1)}
  *{box-sizing:border-box} html,body{height:100%} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Inter,Arial;display:grid;place-items:center}
  .card{width:min(480px,92vw);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));border:1px solid var(--line);border-radius:18px;padding:24px 20px;box-shadow:0 30px 80px rgba(0,0,0,.45)}
  h1{margin:0 0 6px;font-size:clamp(20px,2.6vw,26px)}
  p{margin:8px 0 18px;color:var(--mut)}
  .field{display:flex;gap:10px}
  input[type=password]{flex:1;padding:14px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff;font-size:16px}
  button{padding:12px 16px;border-radius:12px;border:1px solid #3a3f56;background:rgba(255,255,255,.08);color:var(--fg);font-weight:700;cursor:pointer}
  .err{color:#ff6b6b;margin-top:10px}
  a{color:#cbd5ff}
</style></head><body>
<form class="card" method="post" action="">
  <h1>Admin — <span style="color:#ffcc00;font-weight:800"><?= htmlspecialchars($SITE_NAME) ?></span></h1>
  <p>Enter your admin password.</p>
  <div class="field">
    <input type="password" name="pw" placeholder="Password" autofocus required>
    <button type="submit">Sign in</button>
  </div>
  <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <p style="margin-top:12px"><a href="/admin/setup.php">Run setup</a></p>
</form>
</body></html>
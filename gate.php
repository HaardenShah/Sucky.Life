<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');
require __DIR__ . '/admin/config.php';

if ($NEEDS_SETUP) { header('Location: /admin/setup.php'); exit; }
if (empty($GATE_ON)) { header('Location: /'); exit; }

if (isset($_GET['logout'])) { unset($_SESSION['visitor_ok']); header('Location: /gate.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pw = $_POST['pw'] ?? '';
  if (!rate_limit('visitor_gate', 12, 600)) { // 12 tries / 10 min
    sleep(2);
    $err = 'Too many attempts. Try again later.';
  } else {
    if ($GATE_HASH && password_verify($pw, $GATE_HASH)) {
      $_SESSION['visitor_ok'] = true;
      if (function_exists('session_regenerate_id')) @session_regenerate_id(true);
      $ret = $_GET['return'] ?? '/';
      header('Location: ' . $ret);
      exit;
    } else {
      $err = 'Incorrect password. Try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($SITE_NAME) ?> — Enter</title>
<style>
  :root{ --bg:#0e0f12; --panel:#12151e; --line:#262a36; --fg:#f2f5ff; --mut:#a6aec2; --brand:#ffcc00; --bezier:cubic-bezier(.22,.61,.36,1) }
  *{box-sizing:border-box} html,body{height:100%} body{margin:0; background:var(--bg); color:var(--fg); font-family:system-ui,Segoe UI,Inter,Arial; display:grid; place-items:center}
  .card{width:min(520px,92vw); background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03)); border:1px solid var(--line); border-radius:18px; padding:26px 22px; box-shadow:0 30px 80px rgba(0,0,0,.45)}
  h1{margin:0 0 4px; font-size:clamp(20px,2.8vw,26px)}
  p{margin:8px 0 18px; color:var(--mut)}
  .field{display:flex; gap:10px; margin:10px 0 6px}
  input[type=password]{flex:1; padding:14px; border-radius:12px; border:1px solid var(--line); background:#0b0f1a; color:#f0f4ff; font-size:16px; outline:none}
  input[type=password]:focus{border-color:#3e4560; box-shadow:0 0 0 4px rgba(255,255,255,.05)}
  button{padding:12px 16px; border-radius:12px; border:1px solid #3a3f56; background:rgba(255,255,255,.08); color:var(--fg); font-weight:700; cursor:pointer; transition:transform .18s var(--bezier), background .25s}
  button:hover{transform:translateY(-1px); background:rgba(255,255,255,.12)}
  .brand{color:var(--brand); font-weight:800}
  .err{color:#ff6b6b; margin-top:10px}
  .small{margin-top:14px; display:flex; justify-content:space-between; font-size:12px}
  a{color:#cbd5ff}
</style>
</head>
<body>
  <form class="card" method="post" action="">
    <h1>Welcome to <span class="brand"><?= htmlspecialchars($SITE_NAME) ?></span></h1>
    <p>Friends only. Enter the password to continue.</p>
    <div class="field">
      <input type="password" name="pw" placeholder="Password" autofocus required>
      <button type="submit">Enter</button>
    </div>
    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <div class="small">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></span>
      <a href="/gate.php?logout=1">Log out</a>
    </div>
  </form>
</body>
</html>
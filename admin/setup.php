<?php
/**
 * admin/setup.php — First-run setup
 * - Creates admin/site.json and admin/password.json
 * - Shows clear diagnostics if writes fail
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';

/* Already configured? */
if (is_file($SITE_JSON) && is_file($PWD_JSON)) {
  header('Location: /admin/' . (empty($_SESSION['authed']) ? 'login.php' : 'index.php'));
  exit;
}

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();

  $site_name = trim($_POST['site_name'] ?? '');
  $domain    = trim($_POST['domain'] ?? '');
  $pw        = $_POST['pw']  ?? '';
  $pw2       = $_POST['pw2'] ?? '';

  if ($site_name === '') {
    $err = 'Please provide a site name.';
  } elseif ($pw === '' || $pw2 === '') {
    $err = 'Please enter and confirm the admin password.';
  } elseif ($pw !== $pw2) {
    $err = 'Passwords do not match.';
  } else {
    $site = [
      'site_name' => $site_name,
      'domain'    => $domain,
      'visitor_gate_on' => false,
      'visitor_password_hash' => '',
    ];
    $pwd  = [
      'hash'       => password_hash($pw, PASSWORD_DEFAULT),
      'updated_at' => date('c'),
    ];

    $w1 = write_json_atomic($SITE_JSON, $site);
    $w2 = write_json_atomic($PWD_JSON,  $pwd);

    if (!$w1 || !$w2) {
      $err = "Failed to write configuration files.\n".
             "SITE_JSON: {$SITE_JSON} (dir writable? ".(is_writable(dirname($SITE_JSON))?'yes':'no').")\n".
             "PWD_JSON:  {$PWD_JSON} (dir writable? ".(is_writable(dirname($PWD_JSON))?'yes':'no').")";
    } else {
      $_SESSION['authed'] = false; // fresh start
      header('Location: /admin/login.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>First-run Setup</title>
<style>
  :root{--bg:#0e0f12;--panel:#0f1423;--line:#23283a;--fg:#eef2ff;--mut:#a6aec2;--brand:#ffcc00;--bezier:cubic-bezier(.22,.61,.36,1)}
  *{box-sizing:border-box} html,body{height:100%}
  body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Inter,Arial;display:grid;place-items:center}
  .card{width:min(760px,94vw);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));border:1px solid var(--line);border-radius:18px;padding:22px 18px;box-shadow:0 30px 80px rgba(0,0,0,.45)}
  h1{margin:0 0 6px;font-size:clamp(20px,2.6vw,26px)} p{margin:4px 0 16px;color:var(--mut)}
  label{display:block;margin:12px 0 6px;color:#c8cbe0}
  input{width:100%;padding:12px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  button{margin-top:14px;padding:12px 16px;border-radius:12px;border:1px solid #3a3f56;background:rgba(255,255,255,.08);color:var(--fg);font-weight:700;cursor:pointer}
  .err{white-space:pre-wrap;color:#ff9a9a;margin-top:10px}
  .diag{margin-top:12px;padding:10px;border-radius:12px;background:#0d1224;border:1px solid #2a2f42;color:#a6aec2;font-size:12px}
  code{color:#cbd5ff}
</style>
</head>
<body>
  <form class="card" method="post" action="">
    <h1>Welcome to <span style="color:#ffcc00;font-weight:800">sucky.life</span></h1>
    <p>Quick setup. You can tweak these later in Admin.</p>

    <?= csrf_input() ?>

    <label for="site_name">Site name</label>
    <input id="site_name" name="site_name" type="text" required value="<?= htmlspecialchars($SITE_NAME) ?>">

    <label for="domain">Domain (optional)</label>
    <input id="domain" name="domain" type="text" placeholder="sucky.life" value="<?= htmlspecialchars($SITE_DOMAIN) ?>">

    <div class="row">
      <div>
        <label for="pw">Admin password</label>
        <input id="pw" name="pw" type="password" required>
      </div>
      <div>
        <label for="pw2">Confirm password</label>
        <input id="pw2" name="pw2" type="password" required>
      </div>
    </div>

    <button type="submit">Finish setup</button>

    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="diag">
      <div><strong>Paths</strong></div>
      <div>ADMIN_DIR: <code><?= htmlspecialchars($ADMIN_DIR) ?></code></div>
      <div>SITE_JSON: <code><?= htmlspecialchars($SITE_JSON) ?></code> (dir writable: <?= is_writable(dirname($SITE_JSON))?'yes':'no' ?>)</div>
      <div>PWD_JSON:  <code><?= htmlspecialchars($PWD_JSON) ?></code> (dir writable: <?= is_writable(dirname($PWD_JSON))?'yes':'no' ?>)</div>
      <div>PHP UID may need write perms on <code>admin/</code>. If you see “no”, set folder perms to 755/775 via your host’s file manager.</div>
    </div>
  </form>
</body>
</html>
<?php
/**
 * admin/setup.php — First-run setup
 * - Creates admin/site.json and admin/password.json
 * - Lets you set site name, domain, and admin password
 * - Uses CSRF protection from config.php
 * - Redirects to /admin/login.php after success
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/config.php';

// If we already have both files, bounce to admin (or login if not authed)
if (file_exists($SITE_JSON) && file_exists($PWD_JSON)) {
  header('Location: /admin/' . (empty($_SESSION['authed']) ? 'login.php' : 'index.php'));
  exit;
}

// Ensure admin dir exists and is writable
@mkdir(__DIR__, 0775, true);

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF check
  require_csrf();

  $site_name = trim($_POST['site_name'] ?? '');
  $domain    = trim($_POST['domain'] ?? '');
  $pw        = $_POST['pw'] ?? '';
  $pw2       = $_POST['pw2'] ?? '';

  if ($site_name === '') {
    $err = 'Please provide a site name.';
  } elseif ($pw === '' || $pw2 === '') {
    $err = 'Please enter and confirm the admin password.';
  } elseif ($pw !== $pw2) {
    $err = 'Passwords do not match.';
  } else {
    // Write site.json
    $site = [
      'site_name' => $site_name,
      'domain'    => $domain,
      // Visitor gate defaults (off)
      'visitor_gate_on'   => false,
      'visitor_password_hash' => '',
    ];

    // Create parent dir if needed
    $ok1 = @file_put_contents($SITE_JSON, json_encode($site, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

    // Write password.json
    $pwd = [
      'hash'       => password_hash($pw, PASSWORD_DEFAULT),
      'updated_at' => date('c'),
    ];
    $ok2 = @file_put_contents($PWD_JSON, json_encode($pwd, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

    // Create required content dirs
    $dataDir = __DIR__ . '/../eggs/data';
    $upDir   = __DIR__ . '/../assets/uploads';
    @mkdir($dataDir, 0775, true);
    @mkdir($upDir,   0775, true);

    if (!$ok1 || !$ok2) {
      $err = 'Failed to write configuration files. Check file permissions on the /admin folder.';
    } else {
      // Reset cached flags for this request
      $_SESSION['authed'] = false;
      // All good → go sign in
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
  .card{width:min(720px,94vw);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.03));border:1px solid var(--line);border-radius:18px;padding:20px 18px;box-shadow:0 30px 80px rgba(0,0,0,.45)}
  h1{margin:0 0 6px;font-size:clamp(20px,2.6vw,26px)}
  p{margin:4px 0 16px;color:var(--mut)}
  label{display:block;margin:12px 0 6px;color:#c8cbe0}
  input{width:100%;padding:12px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff;outline:none}
  input:focus{border-color:#3e4560;box-shadow:0 0 0 4px rgba(255,255,255,.05)}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  button{margin-top:14px;padding:12px 16px;border-radius:12px;border:1px solid #3a3f56;background:rgba(255,255,255,.08);color:var(--fg);font-weight:700;cursor:pointer;transition:transform .18s var(--bezier),background .25s}
  button:hover{transform:translateY(-1px);background:rgba(255,255,255,.12)}
  .brand{color:var(--brand);font-weight:800}
  .err{color:#ff9a9a;margin-top:10px}
  .ok{color:#b7f39a;margin-top:10px}
  .mut{color:#a6aec2}
</style>
</head>
<body>
  <form class="card" method="post" action="">
    <h1>Welcome to <span class="brand">sucky.life</span></h1>
    <p>Let’s do a quick setup. You can change these later in Admin.</p>

    <?= csrf_input() ?>

    <label for="site_name">Site name</label>
    <input id="site_name" name="site_name" type="text" required placeholder="sucky.life" value="<?= htmlspecialchars($SITE_NAME) ?>">

    <label for="domain">Domain (optional)</label>
    <input id="domain" name="domain" type="text" placeholder="sucky.life" value="<?= htmlspecialchars($SITE_DOMAIN) ?>">

    <div class="row">
      <div>
        <label for="pw">Admin password</label>
        <input id="pw" name="pw" type="password" required placeholder="Choose a strong password">
      </div>
      <div>
        <label for="pw2">Confirm password</label>
        <input id="pw2" name="pw2" type="password" required placeholder="Repeat password">
      </div>
    </div>

    <button type="submit">Finish setup</button>

    <?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <p class="mut" style="margin-top:10px">This will create <code>admin/site.json</code> and <code>admin/password.json</code>, and ensure <code>eggs/data/</code> &amp; <code>assets/uploads/</code> exist.</p>
  </form>
</body>
</html>
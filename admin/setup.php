<?php
require __DIR__.'/config.php';

// If already configured, bounce to admin
if(!$NEEDS_SETUP){
  header('Location: /admin/index.php'); exit;
}

$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $site_name = trim($_POST['site_name'] ?? '');
  $domain    = trim($_POST['domain'] ?? '');
  $pass      = $_POST['password'] ?? '';
  $confirm   = $_POST['confirm']  ?? '';

  if($site_name === '' || $domain === ''){
    $err = 'Please provide a site name and domain.';
  } elseif(strlen($pass) < 8){
    $err = 'Password must be at least 8 characters.';
  } elseif($pass !== $confirm){
    $err = 'Passwords do not match.';
  } else {
    // Save site.json
    $site = [
      'site_name' => $site_name,
      'domain'    => $domain,
      'first_run' => false,
      'created_at'=> time()
    ];
    file_put_contents($SITE_FILE, json_encode($site, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

    // Save password.json
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $pwdPayload = ['hash'=>$hash, 'updated_at'=>time()];
    file_put_contents($PWD_FILE, json_encode($pwdPayload, JSON_PRETTY_PRINT));

    // Log in and go to Admin
    $_SESSION['authed'] = true;
    header('Location: /admin/index.php'); exit;
  }
}
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Welcome — Setup</title>
  <style>
    :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; --bezier:cubic-bezier(.22,.61,.36,1) }
    *{box-sizing:border-box}
    body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:radial-gradient(1200px 600px at 50% 10%, #0b0e17 10%, #0b0e17 30%, #070a12 70%); color:var(--fg); min-height:100vh; display:grid; place-items:center; overflow:hidden}
    .wrap{width:min(980px, 92vw); display:grid; grid-template-columns:1.1fr 1fr; gap:22px; align-items:stretch}
    .hero{position:relative; border:1px solid var(--line); border-radius:16px; padding:26px; background:linear-gradient(160deg, rgba(255,255,255,.04), rgba(255,255,255,.02)); overflow:hidden}
    .card{border:1px solid var(--line); border-radius:16px; padding:16px; background:rgba(10,14,26,.8); backdrop-filter: blur(4px)}
    h1{margin:0 0 8px; font-size:clamp(28px,4vw,40px)}
    p.muted{color:var(--muted)}
    .row{display:grid; grid-template-columns:1fr 1fr; gap:10px}
    label{font-weight:600; font-size:13px}
    input{width:100%; padding:12px; background:#0b0f1a; border:1px solid var(--line); border-radius:12px; color:var(--fg)}
    button{width:100%; padding:12px; border-radius:12px; border:1px solid #343c56; background:#141b2c; color:#f5f5f5; cursor:pointer; transition:transform .18s var(--bezier), background .25s}
    button:hover{transform:translateY(-1px); background:#1a243a}
    .logo{font-weight:900; letter-spacing:.4px}
    .accent{color:var(--brand)}
    .blob{position:absolute; width:520px; height:520px; background:radial-gradient(closest-side, rgba(255,204,0,.22), transparent 68%); right:-120px; top:-80px; filter: blur(20px); transform:scale(.9); animation:float 8s var(--bezier) infinite alternate}
    @keyframes float{to{transform:translateY(-8px) scale(1.02)}}
    .rowgap{height:10px}
    .err{color:#ff7b7b; margin:0 0 8px}
    .check{display:flex; gap:8px; align-items:center; margin:8px 0 0}
    .check input{width:auto}
    small.hint{display:block; color:var(--muted); margin-top:6px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="hero">
      <div class="blob"></div>
      <div class="logo">s<span class="accent">ucky</span>.life</div>
      <h1>Welcome 👋</h1>
      <p class="muted">Let’s do a 30-second setup: name your site, confirm your domain, set an admin password. You can change any of this later in Admin.</p>
      <ul class="muted" style="line-height:1.8">
        <li>⚡ Visual editor for egg placement</li>
        <li>🖼️ Drag-and-drop images (auto-WebP)</li>
        <li>🔊 Per-egg audio players</li>
        <li>🔐 Password-protected Admin</li>
      </ul>
    </div>
    <form class="card" method="post" autocomplete="off">
      <?php if($err): ?><p class="err"><?=htmlspecialchars($err)?></p><?php endif; ?>
      <label>Site name</label>
      <input name="site_name" placeholder="e.g., sucky.life" required>
      <small class="hint">Shown in the header and page title.</small>
      <div class="rowgap"></div>

      <label>Domain</label>
      <input name="domain" placeholder="e.g., sucky.life" required>
      <small class="hint">Used for informational display; doesn’t affect hosting.</small>
      <div class="rowgap"></div>

      <div class="row">
        <div>
          <label>Admin password</label>
          <input type="password" name="password" minlength="8" required>
        </div>
        <div>
          <label>Confirm password</label>
          <input type="password" name="confirm" minlength="8" required>
        </div>
      </div>
      <div class="rowgap"></div>
      <button type="submit">Finish setup →</button>
      <small class="hint">This writes <code>/admin/site.json</code> and <code>/admin/password.json</code>.</small>
    </form>
  </div>
</body>
</html>
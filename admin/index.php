<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';

// If needs setup, force to setup
if($NEEDS_SETUP){ header('Location: /admin/setup.php'); exit; }

if(isset($_POST['password'])){
  if(password_verify($_POST['password'], $ADMIN_PASSWORD_HASH)){
    $_SESSION['authed'] = true; header('Location: index.php'); exit;
  } else { $error='Invalid password'; }
}
if(isset($_GET['logout'])){ session_destroy(); header('Location: index.php'); exit; }
$authed = !empty($_SESSION['authed']);

$eggs = list_eggs();
$slug = $_GET['slug'] ?? '';
if(!$slug && count($eggs)) $slug = $eggs[0];
$current = $slug ? load_egg($slug) : null;
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Egg Manager — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:var(--bg); color:var(--fg)}
  header,footer{padding:12px 16px; background:#101421; border-bottom:1px solid var(--line)}
  main{display:grid; grid-template-columns:320px 1fr; gap:18px; padding:18px}
  .card{background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px}
  a{color:var(--brand)}
  input,textarea{width:100%; padding:10px; border-radius:10px; border:1px solid var(--line); background:#0c0f19; color:var(--fg)}
  label{font-weight:600; font-size:13px}
  .row{display:grid; gap:10px; grid-template-columns:1fr 1fr}
  button{padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:#1a2030; color:var(--fg); cursor:pointer}
  ul{list-style:none; padding:0; margin:0}
  li{margin:0 0 8px; display:flex; align-items:center; justify-content:space-between; gap:8px}
  .muted{color:var(--muted); font-size:12px}
  .actions button{font-size:12px; padding:6px 8px}
  .drop{border:2px dashed #3a4363; border-radius:12px; padding:12px; text-align:center; background:#0b0f1a}
  .drop.drag{background:#0e1424; border-color:#6573a3}
  .preview{margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap}
  .preview img{max-height:90px; border-radius:8px; border:1px solid var(--line)}
  .pill{display:inline-block; padding:2px 8px; border:1px solid var(--line); border-radius:999px; font-size:12px; color:var(--muted)}
</style></head>
<body>
<header>
  <strong>Egg Manager — <?= htmlspecialchars($SITE_NAME) ?></strong>
  <span style="float:right;"><?php if($authed): ?><a href="setpwd.php">Change password</a> &nbsp;|&nbsp; <a href="?logout=1">Logout</a><?php endif; ?></span>
</header>

<?php if(!$authed): ?>
  <main style="display:block; max-width:420px; margin:80px auto;">
    <div class="card">
      <?php if(!empty($error)) echo '<p style="color:#ff6b6b">'.$error.'</p>'; ?>
      <form method="post">
        <label>Password</label>
        <input type="password" name="password" required>
        <div style="margin-top:10px"><button type="submit">Enter</button></div>
      </form>
      <p class="muted" style="margin-top:8px"><a href="/admin/setup.php">Run setup again</a> (will overwrite settings)</p>
    </div>
  </main>
<?php else: ?>
  <!-- (rest of your existing Admin UI here unchanged) -->
  <?php /* Keep your current admin UI block here without changes.
          If you want me to paste the entire file again with audio, visual editor, etc., say the word. */ ?>
<?php endif; ?>

<footer><small>© <?=date('Y')?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></small></footer>
</body></html>
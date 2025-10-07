<?php
require __DIR__.'/config.php';

if(empty($_SESSION['authed'])){
  header('Location: index.php'); exit;
}

// Show form & handle POST to update password.json
$err = '';
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  if(strlen($new) < 8){
    $err = 'Password must be at least 8 characters.';
  } elseif($new !== $confirm){
    $err = 'Passwords do not match.';
  } else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $payload = ['hash' => $hash, 'first_run' => false, 'updated_at' => time()];
    file_put_contents($PWD_FILE, json_encode($payload));
    $msg = 'Password updated.';
  }
}
$first = isset($_GET['first']);
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set Admin Password</title>
<style>
  :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:var(--bg); color:var(--fg)}
  main{display:block; max-width:480px; margin:80px auto; padding:18px}
  .card{background:var(--card); border:1px solid var(--line); border-radius:12px; padding:16px}
  input{width:100%; padding:10px; border-radius:10px; border:1px solid var(--line); background:#0c0f19; color:var(--fg)}
  label{font-weight:600; font-size:13px}
  button{padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:#1a2030; color:var(--fg); cursor:pointer}
  .muted{color:var(--muted); font-size:12px}
</style></head>
<body>
  <main>
    <div class="card">
      <h2><?= $first ? 'Welcome! Set your admin password' : 'Change Password' ?></h2>
      <?php if($err): ?><p style="color:#ff6b6b;"><?=$err?></p><?php endif; ?>
      <?php if($msg): ?><p style="color:#85ff85;"><?=$msg?></p><?php endif; ?>
      <form method="post" autocomplete="new-password">
        <label>New password</label>
        <input type="password" name="new_password" required minlength="8" placeholder="At least 8 characters">
        <div style="height:10px"></div>
        <label>Confirm password</label>
        <input type="password" name="confirm_password" required minlength="8" placeholder="Repeat password">
        <div style="margin-top:12px">
          <button type="submit">Save</button>
          <a href="index.php" style="margin-left:8px; color:var(--brand)">Back</a>
        </div>
        <p class="muted" style="margin-top:12px">Tip: Use a strong unique password. You can also add HTTP auth to the /admin directory.</p>
      </form>
    </div>
  </main>
</body></html>

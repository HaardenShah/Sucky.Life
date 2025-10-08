<?php
require __DIR__.'/config.php';
if (empty($_SESSION['authed'])) { http_response_code(403); exit('Forbidden'); }
$SITE_JSON = __DIR__.'/site.json';
$site = file_exists($SITE_JSON) ? (json_decode(file_get_contents($SITE_JSON), true) ?: []) : [];
$gate_on  = !empty($site['visitor_gate_on']);
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Privacy — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  body{margin:0; font-family:system-ui,Segoe UI,Inter,Arial; background:#0e0f12; color:#eef2ff}
  .wrap{max-width:860px; margin:40px auto; padding:0 16px}
  .panel{background:#0f1423; border:1px solid #23283a; border-radius:16px; padding:18px 16px; box-shadow:0 20px 60px rgba(0,0,0,.35)}
  h1{margin:0 0 12px} label{display:block;margin:12px 0 6px;color:#c8cbe0}
  input[type=password]{width:100%;padding:12px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff}
  .row{display:flex;align-items:center;gap:10px}
  .row input[type=checkbox]{transform:scale(1.25)}
  button{margin-top:14px;padding:12px 16px;border-radius:12px;border:1px solid #3a405b;background:#141a2b;color:#eef2ff;font-weight:700;cursor:pointer}
  .muted{color:#9aa2b8;font-size:13px} a{color:#cbd5ff}
</style></head><body>
<div class="wrap">
  <h1>Privacy</h1>
  <div class="panel">
    <form method="post" action="update_privacy.php">
      <?= csrf_input() ?>
      <div class="row">
        <input type="checkbox" id="gate" name="gate" value="1" <?= $gate_on?'checked':'' ?>>
        <label for="gate"><strong>Require visitor password to view the site</strong></label>
      </div>
      <label for="pw">Set / Change visitor password</label>
      <input type="password" id="pw" name="pw" placeholder="Leave blank to keep current password">
      <div class="muted">Tip: Shared password is hashed in site.json.</div>
      <button type="submit">Save settings</button>
    </form>
    <p class="muted" style="margin-top:16px">Public gate page: <a href="/gate.php" target="_blank">/gate.php</a></p>
    <p><a href="/admin/index.php">← Back to Admin</a></p>
  </div>
</div>
</body></html>
<?php
declare(strict_types=1);

/**
 * admin/setup.php
 * One-time environment/setup checks for the file-storage variant.
 * - Ensures eggs/data and assets/uploads exist and are writable.
 * - Can be called via GET (HTML) or POST (AJAX/JSON).
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Must be logged-in to run setup (keeps this behind auth).
if (empty($_SESSION['authed'])) {
  header('Location: /admin/login.php');
  exit;
}

$ROOT        = project_root();
$DATA_DIR    = $ROOT . '/eggs/data';
$UPLOADS_DIR = $ROOT . '/assets/uploads';

function check_and_make(string $dir): array {
  $ok = is_dir($dir) || @mkdir($dir, 0775, true);
  $write = $ok && is_writable($dir);
  return ['path'=>$dir, 'exists'=>$ok, 'writable'=>$write];
}

function respond_json(array $payload): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  // CSRF required for POST
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    respond_json(['ok'=>false, 'error'=>'CSRF validation failed.']);
  }

  $eggs   = check_and_make($DATA_DIR);
  $uploads= check_and_make($UPLOADS_DIR);

  $problems = [];
  foreach ([ $eggs, $uploads ] as $chk) {
    if (!$chk['exists'])   $problems[] = "Directory missing: {$chk['path']}";
    if (!$chk['writable']) $problems[] = "Directory not writable: {$chk['path']}";
  }

  if ($problems) {
    respond_json(['ok'=>false, 'error'=>implode('; ', $problems), 'checks'=>['eggs'=>$eggs,'uploads'=>$uploads]]);
  }

  // Touch a tiny file to confirm write
  $probe = $DATA_DIR . '/.__probe';
  @file_put_contents($probe, 'ok');
  $probe_ok = is_file($probe);
  if ($probe_ok) @unlink($probe);

  if (!$probe_ok) {
    respond_json(['ok'=>false, 'error'=>'Unable to write test file in eggs/data', 'checks'=>['eggs'=>$eggs,'uploads'=>$uploads]]);
  }

  respond_json(['ok'=>true, 'checks'=>['eggs'=>$eggs,'uploads'=>$uploads]]);
  // no exit; respond_json already exited
}

// GET: show a tiny “Run Setup” page (useful if you visit directly)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Setup — <?= htmlspecialchars($SITE_NAME ?? 'sucky.life', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Inter,Arial;margin:2rem;line-height:1.5;color:#111}
    button{padding:.6rem 1rem;border:1px solid #ccc;border-radius:8px;background:#f8f8f8;cursor:pointer}
    pre{background:#f6f8fa;padding:1rem;border-radius:8px;overflow:auto}
    .ok{color:#0a7}
    .bad{color:#c00}
  </style>
</head>
<body>
  <h1>Environment setup</h1>
  <p>This will create (if needed) and verify writability of:</p>
  <ul>
    <li><code><?= htmlspecialchars($DATA_DIR) ?></code></li>
    <li><code><?= htmlspecialchars($UPLOADS_DIR) ?></code></li>
  </ul>

  <form method="post" id="setupForm">
    <?= csrf_input() ?>
    <button type="submit">Run setup</button>
  </form>

  <div id="out" style="margin-top:1rem"></div>

  <script>
  document.getElementById('setupForm')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const r = await fetch(location.href, { method:'POST', body:fd });
    const j = await r.json().catch(()=>({ok:false,error:'Invalid JSON'}));
    const out = document.getElementById('out');
    out.innerHTML = '<pre>'+JSON.stringify(j,null,2)+'</pre>';
    if (j && j.ok) out.insertAdjacentHTML('afterbegin','<p class="ok">✓ Setup OK</p>');
    else out.insertAdjacentHTML('afterbegin','<p class="bad">✗ Setup failed</p>');
  });
  </script>
</body>
</html>

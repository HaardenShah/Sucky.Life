<?php
/**
 * admin/visual.php — Visual Hotspot Editor (CSP-safe)
 * - No inline JS; loads /admin/visual.js (defer)
 * - Overlay captures clicks/taps; dot + Save button handled in JS
 * - High-contrast UI; toolbar always clickable
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

if (empty($_SESSION['authed'])) {
  http_response_code(403);
  exit('Forbidden');
}

$slug = $_GET['slug'] ?? '';
$egg  = $slug ? (load_egg($slug) ?? []) : [];

$posLeft = isset($egg['pos_left']) ? floatval($egg['pos_left']) : '';
$posTop  = isset($egg['pos_top'])  ? floatval($egg['pos_top'])  : '';
$csrf    = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Place Egg — <?= htmlspecialchars($slug ?: 'untitled') ?></title>
<style>
  :root{
    --bg:#0e0f12; --panel:#141a2b; --line:#2e3550; --fg:#ffffff; --mut:#c7d2fe; --brand:#ffcc00;
    --bez:cubic-bezier(.22,.61,.36,1);
    --z-toolbar: 2147483647;
    --z-overlay: 2147483638;
    --z-marker:  2147483639;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,-apple-system,Segoe UI,Inter,Arial;overflow:hidden}

  .stage{position:fixed; inset:0}
  .stage iframe{position:absolute; inset:0; width:100%; height:100%; border:0; background:#000}

  .toolbar{
    position:fixed; top:12px; left:12px; right:12px; z-index:var(--z-toolbar);
    display:flex; gap:10px; align-items:center; flex-wrap:wrap;
  }
  .btn{
    padding:12px 16px; border-radius:12px; border:1px solid var(--line);
    background:var(--panel); color:var(--fg); font-weight:800; cursor:pointer;
    transition:transform .15s var(--bez), background .2s ease;
  }
  .btn:hover{ transform:translateY(-1px) }
  .btn.save{ border-color:var(--brand); box-shadow:0 0 0 2px rgba(255,204,0,.25) inset }
  .btn:disabled{ opacity:.55; cursor:not-allowed }
  .right{margin-left:auto; display:flex; gap:10px; align-items:center}
  .status{font-size:13px; color:var(--mut)}

  .badge{
    position:fixed; top:60px; left:12px; z-index:var(--z-toolbar);
    padding:8px 12px; border-radius:12px; background:var(--panel); border:1px solid var(--line);
    color:var(--fg); font-weight:700;
  }

  /* Overlay under toolbar; tiny alpha bg ensures hit-testing */
  #hit{
    position:fixed; inset:0; z-index:var(--z-overlay);
    cursor:crosshair; background:rgba(0,0,0,0.001);
    touch-action:none;
  }

  #dot{
    position:fixed; z-index:var(--z-marker);
    width:20px; height:20px; border-radius:50%;
    background:var(--brand); box-shadow:0 6px 24px rgba(0,0,0,.5);
    transform:translate(-50%,-50%); display:none;
  }
  #dot.pop{ animation:pop .32s var(--bez) }
  @keyframes pop{
    0%{transform:translate(-50%,-50%) scale(.75)}
    60%{transform:translate(-50%,-50%) scale(1.25)}
    100%{transform:translate(-50%,-50%) scale(1)}
  }

  .ping{
    position:fixed; z-index:var(--z-marker); pointer-events:none;
    width:32px; height:32px; border-radius:999px;
    border:2px solid var(--brand);
    transform:translate(-50%,-50%) scale(.6); opacity:.9;
    animation:ping .55s ease-out forwards;
  }
  @keyframes ping{
    60%{transform:translate(-50%,-50%) scale(1.25);opacity:.6}
    100%{transform:translate(-50%,-50%) scale(1.7);opacity:0}
  }

  .tip{
    position:fixed; left:12px; right:12px; bottom:12px; z-index:var(--z-toolbar);
    color:var(--mut); font-size:14px; text-align:center; font-weight:700;
    background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:10px 12px;
  }

  .toast{
    position:fixed; right:12px; bottom:56px; z-index:var(--z-toolbar);
    background:#0f172a; border:1px solid #334155; color:#e2e8f0;
    padding:8px 10px; border-radius:10px; font-size:12px; box-shadow:0 8px 24px rgba(0,0,0,.4);
    display:none;
  }
  .toast.show{ display:block }
</style>
</head>
<body
  id="editorRoot"
  data-slug="<?= htmlspecialchars($slug, ENT_QUOTES) ?>"
  data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>"
  data-left="<?= htmlspecialchars($posLeft, ENT_QUOTES) ?>"
  data-top="<?= htmlspecialchars($posTop, ENT_QUOTES) ?>"
>
  <div class="toolbar">
    <button id="btnSave" class="btn save" disabled>Save position</button>
    <div class="right">
      <div class="status" id="coord">vw: — , vh: —</div>
      <a class="btn" href="/admin/index.php?slug=<?= urlencode($slug) ?>">Back</a>
    </div>
  </div>

  <div class="badge">Placing: <?= htmlspecialchars($slug ?: 'untitled') ?></div>

  <div class="stage">
    <iframe src="/index.php" title="Homepage preview"></iframe>
  </div>

  <!-- Overlays -->
  <div id="dot" aria-hidden="true"></div>
  <div id="hit" title="Click/tap anywhere to place"></div>

  <div class="tip">Click or tap anywhere to place the egg, then press <strong>Save position</strong>.</div>
  <div class="toast" id="toast">ready</div>

  <!-- External JS (CSP-safe) -->
  <script src="/admin/visual.js" defer></script>
</body>
</html>

<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
require __DIR__ . '/../admin/config.php';

if ($NEEDS_SETUP) { header('Location: /admin/setup.php'); exit; }
if (!empty($GATE_ON) && $GATE_ON === true && empty($_SESSION['visitor_ok'])) { http_response_code(403); exit('Forbidden'); }

$vt = $_GET['vt'] ?? '';
if (!$vt || !hash_equals($_SESSION['egg_view_token'] ?? '', $vt)) { http_response_code(404); exit('Not found'); }

$slug = $_GET['slug'] ?? '';
if (!preg_match('~^[a-z0-9\-_]{1,120}$~', $slug)) { http_response_code(400); exit('Bad request'); }

$FILE = __DIR__ . '/data/' . $slug . '.json';
if (!is_file($FILE)) { http_response_code(404); exit('Not found'); }
$egg = json_decode(@file_get_contents($FILE), true) ?: [];

if (!empty($egg['draft']) && empty($_SESSION['authed'])) { http_response_code(404); exit('Not found'); }

$title   = $egg['title']   ?? $slug;
$caption = $egg['caption'] ?? '';
$alt     = $egg['alt']     ?? '';
$image   = $egg['image']   ?? '';
$video   = $egg['video']   ?? '';
$poster  = $egg['poster']  ?? '';
$audio   = $egg['audio']   ?? '';
$body    = $egg['body']    ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{ --bg:#0b0b0b; --fg:#f5f7ff; --mut:#a9b0c6; --line:#23283a; --bezier:cubic-bezier(.22,.61,.36,1) }
  *{box-sizing:border-box} html,body{height:100%}
  body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,-apple-system,Segoe UI,Inter,Arial;display:flex;flex-direction:column;animation:fade .28s var(--bezier)}
  @keyframes fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
  header{padding:10px 12px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:10px;background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02))}
  h1{font-size:16px;margin:0}.mut{color:var(--mut);font-size:12px}
  .wrap{padding:12px;display:grid;gap:12px;overflow:auto}
  .media{border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#0e0f12}
  .media img,.media video{display:block;width:100%;height:auto;max-height:56vh;object-fit:contain;background:#000}
  .body{border:1px solid var(--line);border-radius:12px;padding:12px;background:#0d0f16;line-height:1.5}
  .audio{border:1px dashed #2b3354;border-radius:12px;padding:8px;background:#0a0f1e}
  .audio audio{width:100%}
</style>
</head>
<body>
  <header>
    <div>
      <h1><?= htmlspecialchars($title) ?></h1>
      <?php if($caption): ?><div class="mut"><?= htmlspecialchars($caption) ?></div><?php endif; ?>
    </div>
  </header>

  <div class="wrap">
    <?php if($image): ?><div class="media"><img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($alt ?: $title) ?>"></div><?php endif; ?>
    <?php if($video): ?><div class="media"><video controls preload="metadata" poster="<?= htmlspecialchars($poster) ?>" src="<?= htmlspecialchars($video) ?>"></video></div><?php endif; ?>
    <?php if($audio): ?><div class="audio"><audio preload="auto" src="<?= htmlspecialchars($audio) ?>" controls></audio></div><?php endif; ?>
    <?php if($body): ?><div class="body"><?= $body ?></div><?php endif; ?>
    <?php if(!$image && !$video && !$audio && !$body): ?><div class="body"><em class="mut">Empty egg</em></div><?php endif; ?>
  </div>
</body>
</html>

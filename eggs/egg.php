<?php
$slug = preg_replace('~[^a-z0-9_-]+~i','', $_GET['slug'] ?? '');
$file = __DIR__.'/data/'.$slug.'.json';
if(!$slug || !file_exists($file)){
  http_response_code(404);
  echo '<!doctype html><meta charset="utf-8"><body style="background:#0b0b0b;color:#eee;font:14px system-ui">Not found.</body>'; exit;
}
$egg = json_decode(file_get_contents($file), true) ?: [];
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($egg['title'] ?? $slug) ?></title>
<style>
  body { margin:0; font-family: system-ui, Segoe UI, Roboto, Inter, Arial; color:#f5f5f5; background:#0b0b0b; display:grid; place-items:center; padding:18px; }
  .card { max-width: 820px; border:1px solid rgba(255,255,255,.15); border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.5) }
  img, video { display:block; width:100%; height:auto; background:#0b0b0b }
  figcaption { padding:14px 16px; font-size:15px; color:#ddd; background:rgba(255,255,255,.03) }
  .body { padding:14px 16px; font-size:15px; color:#dcdcdc; line-height:1.5; background:#0f0f0f}
  a { color:#ffcc00 }
</style></head>
<body>
  <figure class="card">
    <?php if(!empty($egg['image'])): ?>
      <img src="<?= htmlspecialchars($egg['image']) ?>" alt="<?= htmlspecialchars($egg['alt'] ?? '') ?>"/>
    <?php endif; ?>
    <?php if(!empty($egg['caption'])): ?>
      <figcaption><?= $egg['caption'] ?></figcaption>
    <?php endif; ?>
    <?php if(!empty($egg['body'])): ?>
      <div class="body"><?= $egg['body'] ?></div>
    <?php endif; ?>
  </figure>
</body></html>

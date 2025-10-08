<?php
/**
 * eggs/egg.php — Polished modal content (sleek card layout, better spacing)
 *
 * Security:
 * - Draft eggs are hidden from non-admins (remove the block if you want drafts public).
 *
 * Media:
 * - Video (native controls, optional poster)
 * - Image (supports <picture> with WebP variants)
 * - Optional audio track (native controls)
 * - Body HTML (trusted content saved via admin)
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/../admin/config.php';
require __DIR__ . '/../admin/util.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(404); exit('Not found'); }

$egg = load_egg($slug);
if (!$egg) { http_response_code(404); exit('Not found'); }

$authed = !empty($_SESSION['authed']);
if (!empty($egg['draft']) && !$authed) { http_response_code(404); exit('Not found'); }

// Extract fields
$title    = $egg['title']   ?? $slug;
$caption  = $egg['caption'] ?? '';
$body     = $egg['body']    ?? '';
$image    = $egg['image']   ?? '';
$variants = $egg['image_variants'] ?? [];
$video    = $egg['video']   ?? '';
$poster   = $egg['poster']  ?? '';
$audio    = $egg['audio']   ?? '';
$alt      = $egg['alt']     ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($title) ?></title>
<style>
  :root{
    --bg:#0b0c10; --panel:#0f1117; --ink:#eef2ff; --mut:#b8c1d9; --line:rgba(255,255,255,.12);
    --ring:rgba(255,255,255,.25); --accent:#ffcc00; --bezier:cubic-bezier(.22,.61,.36,1);
  }
  html,body{height:100%} *{box-sizing:border-box}
  body{
    margin:0; background:var(--bg); color:var(--ink);
    font-family:system-ui,-apple-system,Segoe UI,Inter,Arial;
    -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
  }

  /* Subtle pattern backdrop so the card doesn't feel like a void */
  .back{
    position:fixed; inset:0; pointer-events:none;
    background:
      radial-gradient(1200px 600px at 70% 0%, rgba(255,255,255,.06), transparent 60%),
      linear-gradient(180deg, rgba(255,255,255,.02), transparent 40%),
      repeating-linear-gradient(90deg, rgba(255,255,255,.03) 0 1px, transparent 1px 24px);
  }

  /* Centered card with soft shine border */
  .shell{
    min-height:100%;
    display:grid; place-items:center;
    padding:18px;
  }
  .card{
    width:min(900px, 92vw);
    background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:0 30px 80px rgba(0,0,0,.6);
    overflow:hidden;
  }

  /* Top ribbon */
  .ribbon{
    height:8px;
    background:linear-gradient(90deg, #ffd84d, #ff7aa2 40%, #7aa2ff 80%, #ffd84d);
    filter:saturate(1.1) brightness(1.05);
    opacity:.8;
  }

  .content{
    padding:18px 18px 16px 18px;
  }

  /* Header area */
  .head{
    margin:4px 0 14px 0;
  }
  h2{
    margin:0 0 6px 0; line-height:1.15;
    font-size:clamp(18px, 2.4vw, 22px);
    letter-spacing:.2px;
  }
  .sub{
    margin:0; color:var(--mut);
    font-size:clamp(13px, 1.6vw, 14px);
  }

  /* Media block with proper radius & shadow */
  .media{
    border:1px solid var(--line);
    border-radius:14px;
    background:#0a0a0a;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.45);
  }
  /* Maintain height when image is loading */
  .media .ph{
    aspect-ratio:16/9;
    background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
  }
  .media img, .media video{
    display:block; width:100%; height:auto; background:#000;
  }
  .media video{ outline:none }

  .block-gap{ height:12px }      /* compact rhythm between sections */
  .thin-sep{ height:1px; background:var(--line); border-radius:1px; margin:8px 0 6px }

  /* Audio + small controls row */
  .audio{
    border:1px solid var(--line);
    border-radius:12px;
    padding:8px;
    background:rgba(255,255,255,.03);
  }
  audio{ width:100% }

  /* Body copy */
  .body{
    color:#e7eaf5;
    font-size:clamp(14px, 1.7vw, 15px);
    line-height:1.6;
  }

  /* Compact footer note line */
  .foot{
    margin-top:10px; padding-top:10px;
    border-top:1px dashed var(--line);
    color:#aab4cf; font-size:12px;
    display:flex; align-items:center; gap:8px;
  }
  .dot{width:6px; height:6px; border-radius:50%; background:var(--accent); box-shadow:0 0 0 2px rgba(255,204,0,.2)}
</style>
</head>
<body>
  <div class="back"></div>

  <div class="shell">
    <article class="card" role="article">
      <div class="ribbon" aria-hidden="true"></div>
      <div class="content">
        <!-- Title & subtitle -->
        <header class="head">
          <h2><?= htmlspecialchars($title) ?></h2>
          <?php if ($caption): ?>
            <p class="sub"><?= htmlspecialchars($caption) ?></p>
          <?php endif; ?>
        </header>

        <!-- Media -->
        <?php if ($video || $image): ?>
          <section class="media" aria-label="media">
            <?php if ($video): ?>
              <video controls playsinline <?= $poster ? 'poster="'.htmlspecialchars($poster).'"' : '' ?>>
                <source src="<?= htmlspecialchars($video) ?>" />
              </video>
            <?php elseif ($image): ?>
              <?php if (!empty($variants)): ?>
                <picture>
                  <?php foreach ($variants as $v): ?>
                    <source srcset="<?= htmlspecialchars($v) ?>" type="image/webp" />
                  <?php endforeach; ?>
                  <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($alt) ?>" loading="lazy" />
                </picture>
              <?php else: ?>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($alt) ?>" loading="lazy" />
              <?php endif; ?>
            <?php endif; ?>
          </section>
          <div class="block-gap"></div>
        <?php endif; ?>

        <!-- Audio (optional) -->
        <?php if ($audio): ?>
          <section class="audio" aria-label="audio">
            <audio controls preload="auto">
              <source src="<?= htmlspecialchars($audio) ?>" />
            </audio>
          </section>
          <div class="block-gap"></div>
        <?php endif; ?>

        <!-- Body copy -->
        <?php if ($body): ?>
          <section class="body"><?= $body ?></section>
        <?php endif; ?>

        <!-- Tiny footer line for polish -->
        <div class="foot"><span class="dot" aria-hidden="true"></span><span>Tap outside this card to close.</span></div>
      </div>
    </article>
  </div>
</body>
</html>

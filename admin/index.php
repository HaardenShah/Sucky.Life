<?php
/**
 * admin/index.php
 * Admin dashboard (CSP-safe, no inline JS).
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

if (empty($_SESSION['authed'])) {
  header('Location: /admin/login.php');
  exit;
}

$eggs = list_eggs();
$slug = $_GET['slug'] ?? ($eggs[0] ?? '');
$data = $slug ? (load_egg($slug) ?? []) : [];

$site_name   = $SITE_NAME;
$site_domain = $SITE_DOMAIN;
$CSRF        = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Egg Manager — <?= htmlspecialchars($site_name) ?></title>
<style>
  :root{ --bg:#0e0f12; --panel:#0f1423; --line:#23283a; --fg:#eef2ff; --mut:#a6aec2; --brand:#ffcc00; --accent:#86b5ff; --danger:#ff6b6b; --bezier:cubic-bezier(.22,.61,.36,1) }
  *{box-sizing:border-box} html,body{height:100%} body{margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Inter,Arial}
  .topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#0b0f1d;border-bottom:1px solid var(--line)}
  .brand{font-weight:800;letter-spacing:.3px}
  .nav a{color:#ffd84d;text-decoration:none;margin-left:16px}
  .nav a.muted{color:#cbd5ff}.nav a.danger{color:#ff6b6b}
  .wrap{display:grid;grid-template-columns:300px 1fr;gap:16px;padding:16px}
  .panel{background:var(--panel);border:1px solid var(--line);border-radius:14px;box-shadow:0 16px 50px rgba(0,0,0,.35)}
  .side{padding:12px}.main{padding:16px}
  .sec-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:4px 0 10px}
  .sec-title h2{margin:0;font-size:16px}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;border:1px solid #3a405b;background:#141a2b;color:#eef2ff;cursor:pointer;text-decoration:none;transition:transform .18s var(--bezier), background .2s}
  .btn:hover{transform:translateY(-1px);background:#1a2035}.btn.brand{border-color:#6d5b00;background:rgba(255,204,0,.1);color:#ffd84d}.btn.small{padding:6px 10px;font-size:13px}.btn.danger{border-color:#6b2b2b;color:#ffb5b5;background:rgba(255,0,0,.05)}
  .list{margin:0;padding:0;list-style:none}
  .list li{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px;border-radius:10px;cursor:pointer}
  .list li.active{background:#171d31}.list li:hover{background:#141a2b}
  .list .title{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .list .slug{color:#9aa2b8;font-size:12px}
  .empty{padding:16px;color:#b9c0d4;font-style:italic}
  label{display:block;margin:14px 0 6px;color:#c8cbe0}
  input[type=text], textarea{width:100%;padding:12px;border-radius:12px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff;font-size:14px}
  textarea{min-height:140px;resize:vertical} input[type=file]{display:none}
  .row{display:flex;gap:12px;flex-wrap:wrap}.col{flex:1 1 260px}
  .media-block{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center}
  .media-actions{display:flex;gap:8px}
  .preview{margin-top:8px;border:1px dashed #364061;border-radius:12px;padding:8px;background:#0a0f1e}
  .preview img{max-width:100%;display:block;border-radius:10px}
  .preview audio,.preview video{width:100%;display:block}
  .drop{margin-top:8px;border:1px dashed #2c3556;border-radius:12px;padding:10px;text-align:center;color:#90a0c2;background:#0a0f1e}
  .drop.drag{border-color:#86b5ff;color:#cfe2ff;background:#0d1530}
  .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
  .modal{position:fixed;inset:0;display:grid;place-items:center;z-index:50;pointer-events:none;opacity:0;visibility:hidden;transition:opacity .25s var(--bezier), visibility 0s linear .25s}
  .modal .backdrop{position:absolute;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(2px);opacity:0;transition:opacity .25s var(--bezier)}
  .modal .dialog{position:relative;width:min(92vw, 960px);height:min(80vh, 640px);border-radius:16px;overflow:hidden;border:1px solid var(--line);background:#0d1222;transform:translateY(8px) scale(.98);opacity:0;transition:transform .3s var(--bezier), opacity .25s ease}
  .modal.show{pointer-events:auto;opacity:1;visibility:visible;transition:opacity .25s var(--bezier), visibility 0s}
  .modal.show .backdrop{opacity:1}.modal.show .dialog{opacity:1;transform:translateY(0) scale(1)}
  .picker-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--line)}
  .picker-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(160px,1fr));gap:10px;padding:12px;overflow:auto;height:calc(100% - 46px)}
  .tile{border:1px solid #2c3556;background:#0b0f1e;border-radius:12px;padding:8px;cursor:pointer;display:flex;flex-direction:column;gap:6px}
  .tile:hover{border-color:#4c5fa8}.thumb{width:100%;aspect-ratio:16/9;background:#0a0d18;border-radius:8px;display:grid;place-items:center;overflow:hidden}
  .thumb img{max-width:100%;max-height:100%}.meta{font-size:12px;color:#9aa2b8;display:flex;justify-content:space-between;gap:6px}
</style>
</head>
<body data-csrf="<?= htmlspecialchars($CSRF, ENT_QUOTES) ?>">
  <div class="topbar">
    <div class="brand">Egg Manager — <small><?= htmlspecialchars($site_name) ?></small></div>
    <div class="nav">
      <a class="muted" href="/admin/privacy.php">Privacy</a>
      <a class="muted" href="/admin/change_password.php">Change password</a>
      <a class="danger" href="/admin/logout.php">Logout</a>
    </div>
  </div>

  <div class="wrap">
    <!-- SIDEBAR: Egg list -->
    <aside class="panel side">
      <div class="sec-title">
        <h2>Eggs</h2>
        <button class="btn small brand" id="btnNew" type="button">+ New</button>
      </div>
      <input type="text" id="eggSearch" placeholder="Search..." style="width:100%;padding:10px;border-radius:10px;border:1px solid #2a2f42;background:#0b1020;color:#f0f4ff;margin:6px 0 10px">
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#9aa2b8">
        <input type="checkbox" id="filterDrafts"> Show drafts only
      </label>

      <?php if (!$eggs): ?>
        <div class="empty">No eggs yet. Click <strong>New</strong> to create your first one.</div>
      <?php else: ?>
        <ul class="list" id="eggList">
          <?php foreach ($eggs as $e):
            $meta = load_egg($e) ?? [];
            $title = $meta['title'] ?? '';
            $isDraft = !empty($meta['draft']);
          ?>
          <li class="<?= $e === $slug ? 'active' : '' ?>" data-slug="<?= htmlspecialchars($e) ?>" data-title="<?= htmlspecialchars($title ?: $e) ?>" data-draft="<?= $isDraft ? '1' : '0' ?>">
            <div>
              <div class="title"><?= htmlspecialchars(($title ?: $e) . ($isDraft ? ' [draft]' : '')) ?></div>
              <div class="slug"><?= htmlspecialchars($e) ?></div>
            </div>
            <a class="btn small" href="?slug=<?= urlencode($e) ?>">Edit</a>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </aside>

    <!-- MAIN: Editor -->
    <section class="panel main">
      <div class="sec-title">
        <h2><?= $slug ? 'Edit egg' : 'Create egg' ?></h2>
        <?php if ($slug): ?>
          <div style="display:flex; gap:8px">
            <a class="btn small" href="/admin/visual.php?slug=<?= urlencode($slug) ?>" target="_blank">Visual Editor</a>
          </div>
        <?php endif; ?>
      </div>

      <form id="eggForm" method="post" action="/admin/save.php" enctype="multipart/form-data" autocomplete="off" novalidate>
        <?= csrf_input() ?>
        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>" id="fldSlug">

        <div class="row">
          <div class="col">
            <label for="fldTitle">Title</label>
            <input type="text" id="fldTitle" name="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>" placeholder="e.g., USB of Doom">
            <label for="fldCaption">Caption</label>
            <input type="text" id="fldCaption" name="caption" value="<?= htmlspecialchars($data['caption'] ?? '') ?>" placeholder="Short teaser under the media">
            <label for="fldAlt">ALT text</label>
            <input type="text" id="fldAlt" name="alt" value="<?= htmlspecialchars($data['alt'] ?? '') ?>" placeholder="Describe the main image for accessibility">
            <label><input type="checkbox" id="fldDraft" name="draft" value="1" <?= !empty($data['draft'])?'checked':''; ?>> Draft (hidden from visitors)</label>
            <label for="fldPub">Published at (optional)</label>
            <input type="text" id="fldPub" name="published_at" value="<?= htmlspecialchars($data['published_at'] ?? '') ?>" placeholder="YYYY-MM-DD or leave blank">
          </div>
          <div class="col">
            <label for="fldBody">Story / Body (basic HTML allowed)</label>
            <textarea id="fldBody" name="body" placeholder="<p>That time we..."><?= htmlspecialchars($data['body'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- IMAGE -->
        <div style="margin-top:8px">
          <label>Primary Image</label>
          <div class="media-block">
            <input type="text" id="fldImage" name="image_url" value="<?= htmlspecialchars($data['image'] ?? '') ?>" placeholder="/assets/uploads/xyz.webp">
            <div class="media-actions">
              <label class="btn small"><input type="file" id="upImage" name="image_file" accept="image/*">Upload</label>
              <button class="btn small" type="button" data-pick="image">Choose</button>
              <button class="btn small" type="button" id="clearImage">Clear</button>
            </div>
          </div>
          <div class="drop" id="dropImage">Drag & drop image here</div>
          <div class="preview" id="prevImage"><?php if(!empty($data['image'])): ?><img src="<?= htmlspecialchars($data['image']) ?>" alt="preview"><?php endif; ?></div>
        </div>

        <!-- AUDIO -->
        <div style="margin-top:10px">
          <label>Audio (optional)</label>
          <div class="media-block">
            <input type="text" id="fldAudio" name="audio_url" value="<?= htmlspecialchars($data['audio'] ?? '') ?>" placeholder="/assets/uploads/laugh.mp3">
            <div class="media-actions">
              <label class="btn small"><input type="file" id="upAudio" name="audio_file" accept="audio/*">Upload</label>
              <button class="btn small" type="button" data-pick="audio">Choose</button>
              <button class="btn small" type="button" id="clearAudio">Clear</button>
            </div>
          </div>
          <div class="drop" id="dropAudio">Drag & drop audio here</div>
          <div class="preview" id="prevAudio"><?php if(!empty($data['audio'])): ?><audio controls src="<?= htmlspecialchars($data['audio']) ?>"></audio><?php endif; ?></div>
        </div>

        <!-- VIDEO -->
        <div style="margin-top:10px">
          <label>Video (optional)</label>
          <div class="media-block">
            <input type="text" id="fldVideo" name="video_url" value="<?= htmlspecialchars($data['video'] ?? '') ?>" placeholder="/assets/uploads/clip.mp4">
            <div class="media-actions">
              <label class="btn small"><input type="file" id="upVideo" name="video_file" accept="video/*">Upload</label>
              <button class="btn small" type="button" data-pick="video">Choose</button>
              <button class="btn small" type="button" id="clearVideo">Clear</button>
            </div>
          </div>
          <div class="drop" id="dropVideo">Drag & drop video here</div>
          <div class="preview" id="prevVideo"><?php if(!empty($data['video'])): ?><video controls src="<?= htmlspecialchars($data['video']) ?>"></video><?php endif; ?></div>
        </div>

        <div class="actions">
          <?php if ($slug): ?>
            <button class="btn danger" type="button" id="btnDelete">Delete</button>
            <button class="btn" type="button" id="btnRename">Rename</button>
          <?php endif; ?>
          <button class="btn brand" type="submit" id="btnSave">Save changes</button>
        </div>
      </form>
    </section>
  </div>

  <!-- Media Picker Modal -->
  <div class="modal" id="picker" aria-hidden="true" role="dialog" aria-label="Media Picker">
    <div class="backdrop"></div>
    <div class="dialog">
      <div class="picker-head">
        <div style="font-weight:700">Media Picker</div>
        <div>
          <select id="pickType">
            <option value="image">Images</option>
            <option value="audio">Audio</option>
            <option value="video">Video</option>
            <option value="all">All</option>
          </select>
          <button class="btn small" id="pickClose" type="button">Close</button>
        </div>
      </div>
      <div class="picker-grid" id="pickerGrid"></div>
    </div>
  </div>

  <!-- Load CSP-safe external JS -->
  <script src="/admin/admin.js" defer></script>
</body>
</html>

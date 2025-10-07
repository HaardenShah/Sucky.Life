<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';

if(isset($_POST['password'])){
  if(password_verify($_POST['password'], $ADMIN_PASSWORD_HASH)){
    $_SESSION['authed'] = true; 
    if($FIRST_RUN){ header('Location: setpwd.php?first=1'); exit; }
    header('Location: index.php'); exit;
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
<title>Egg Manager</title>
<style>
  :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:var(--bg); color:var(--fg)}
  header,footer{padding:12px 16px; background:#101421; border-bottom:1px solid var(--line)}
  main{display:grid; grid-template-columns:280px 1fr; gap:18px; padding:18px}
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
  .preview{margin-top:8px; display:flex; gap:8px; align-items:center}
  .preview img{max-height:90px; border-radius:8px; border:1px solid var(--line)}
  .pill{display:inline-block; padding:2px 8px; border:1px solid var(--line); border-radius:999px; font-size:12px; color:var(--muted)}
</style></head>
<body>
<header>
  <strong>Egg Manager</strong>
  <span style="float:right;">
    <?php if($authed): ?><a href="setpwd.php">Change password</a> &nbsp;|&nbsp; <a href="?logout=1">Logout</a><?php endif; ?>
  </span>
</header>

<?php if(!$authed): ?>
  <main style="display:block; max-width:420px; margin:80px auto;">
    <div class="card">
      <?php if(!empty($error)) echo '<p style="color:#ff6b6b">'.$error.'</p>'; ?>
      <?php if($FIRST_RUN): ?>
        <p class="muted">Default password is <code>sucky-life</code>. You will be asked to change it after logging in.</p>
      <?php endif; ?>
      <form method="post">
        <label>Password</label>
        <input type="password" name="password" required>
        <div style="margin-top:10px"><button type="submit">Enter</button></div>
      </form>
    </div>
  </main>
<?php else: ?>
  <main>
    <aside class="card">
      <h3>All Eggs</h3>
      <ul>
        <?php foreach($eggs as $s): ?>
          <li>
            <a href="?slug=<?=$s?>" class="pill"><?=$s?></a>
            <span class="actions">
              <button onclick="renameEgg('<?=$s?>')">Rename</button>
              <button onclick="deleteEgg('<?=$s?>')" style="border-color:#5d2a2a;background:#261416">Delete</button>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
      <hr>
      <form method="get">
        <label>Create new egg</label>
        <input name="slug" placeholder="e.g., usb" required>
        <div style="margin-top:8px"><button type="submit">Create</button></div>
        <p class="muted">Use letters, numbers, dashes, underscores.</p>
      </form>
    </aside>

    <section class="card">
      <h3><?= $slug ? 'Edit: '.htmlspecialchars($slug) : 'Select or create an egg' ?></h3>
      <?php if($slug): ?>
        <form id="eggForm" method="post" action="save.php" enctype="multipart/form-data">
          <input type="hidden" name="slug" value="<?=htmlspecialchars($slug)?>">
          <div class="row">
            <div>
              <label>Title</label>
              <input name="title" value="<?=htmlspecialchars($current['title'] ?? '')?>" placeholder="Short title">
            </div>
            <div>
              <label>Image ALT (for accessibility)</label>
              <input name="alt" value="<?=htmlspecialchars($current['alt'] ?? '')?>" placeholder="Describe the image">
            </div>
          </div>
          <div class="row">
            <div>
              <label>Caption</label>
              <input name="caption" value="<?=htmlspecialchars($current['caption'] ?? '')?>" placeholder="One-liner under the image">
            </div>
            <div>
              <label>Current Image</label>
              <input value="<?=htmlspecialchars($current['image'] ?? '')?>" disabled>
            </div>
          </div>
          <div>
            <label>Story (you can paste text, links, or basic HTML)</label>
            <textarea name="body" rows="6" placeholder="Tell the story…"><?=(htmlspecialchars($current['body'] ?? ''))?></textarea>
          </div>

          <div class="drop" id="drop">
            <p><strong>Drag & drop</strong> an image here, <strong>paste</strong> one, or <strong>click</strong> to choose a file.</p>
            <input type="file" id="file" name="image" accept="image/*" style="display:none">
            <div class="preview" id="preview"></div>
            <p class="muted">We’ll automatically convert to WebP for speed. Originals are not kept.</p>
          </div>

          <div class="row">
            <div>
              <label>OR Image URL</label>
              <input name="image_url" placeholder="https://…">
            </div>
            <div>
              <label>&nbsp;</label>
              <button type="submit">Save</button>
              <a class="muted" target="_blank" href="../eggs/egg.php?slug=<?=urlencode($slug)?>">Preview →</a>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </main>
<?php endif; ?>

<footer><small>© <?=date('Y')?> sucky.life admin</small></footer>

<script>
  function deleteEgg(slug){
    if(!confirm(`Delete "${slug}"? This removes the egg (image files stay).`)) return;
    fetch('delete.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'slug='+encodeURIComponent(slug)})
      .then(r=>r.text()).then(()=>location.href='index.php');
  }
  function renameEgg(slug){
    const ns = prompt('New slug:', slug); if(!ns || ns===slug) return;
    fetch('rename.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'slug='+encodeURIComponent(slug)+'&new_slug='+encodeURIComponent(ns)})
      .then(r=>r.text()).then(()=>location.href='index.php?slug='+encodeURIComponent(ns));
  }

  const drop = document.getElementById('drop');
  const file = document.getElementById('file');
  const preview = document.getElementById('preview');
  if(drop && file){
    drop.addEventListener('click', ()=> file.click());
    drop.addEventListener('dragover', e=>{ e.preventDefault(); drop.classList.add('drag'); });
    drop.addEventListener('dragleave', ()=> drop.classList.remove('drag'));
    drop.addEventListener('drop', e=>{ e.preventDefault(); drop.classList.remove('drag'); if(e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]); });
    document.addEventListener('paste', e=>{ const it = e.clipboardData && e.clipboardData.items; if(!it) return; for(const i of it){ if(i.kind==='file'){ handleFile(i.getAsFile()); break; } } });
    file.addEventListener('change', ()=>{ if(file.files[0]) handleFile(file.files[0]); });
  }
  function handleFile(f){
    const url = URL.createObjectURL(f);
    preview.innerHTML = '';
    const img = new Image(); img.onload = ()=> URL.revokeObjectURL(url); img.src=url; img.alt='preview';
    preview.appendChild(img);
    const dt = new DataTransfer(); dt.items.add(f); file.files = dt.files;
  }
</script>
</body></html>

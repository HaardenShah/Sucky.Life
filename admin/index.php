<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';

// If needs setup, force to setup
if($NEEDS_SETUP){ header('Location: /admin/setup.php'); exit; }

if(isset($_POST['password'])){
  if(password_verify($_POST['password'], $ADMIN_PASSWORD_HASH)){
    $_SESSION['authed'] = true; 
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
<title>Egg Manager — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; --bezier:cubic-bezier(.22,.61,.36,1) }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:var(--bg); color:var(--fg)}
  header,footer{padding:12px 16px; background:#101421; border-bottom:1px solid var(--line)}
  main{display:grid; grid-template-columns:320px 1fr; gap:18px; padding:18px}
  .card{background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px}
  a{color:var(--brand)}
  input,textarea{width:100%; padding:10px; border-radius:10px; border:1px solid var(--line); background:#0c0f19; color:var(--fg)}
  label{font-weight:600; font-size:13px}
  .row{display:grid; gap:10px; grid-template-columns:1fr 1fr}
  button{padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:#1a2030; color:var(--fg); cursor:pointer; transition:transform .18s var(--bezier)}
  button:hover{transform:translateY(-1px)}
  ul{list-style:none; padding:0; margin:0}
  li{margin:0 0 8px; display:flex; align-items:center; justify-content:space-between; gap:8px}
  .muted{color:var(--muted); font-size:12px}
  .actions button{font-size:12px; padding:6px 8px}
  .drop{border:2px dashed #3a4363; border-radius:12px; padding:12px; text-align:center; background:#0b0f1a}
  .drop.drag{background:#0e1424; border-color:#6573a3}
  .preview{margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap}
  .preview img{max-height:90px; border-radius:8px; border:1px solid var(--line)}
  .pill{display:inline-block; padding:2px 8px; border:1px solid var(--line); border-radius:999px; font-size:12px; color:var(--muted)}
  .help{font-size:12px; color:var(--muted); margin-top:6px}
  .sectionTitle{margin:14px 0 6px; font-weight:700; color:#e9e9e9}

  /* Media Picker modal */
  .modal{position:fixed; inset:0; display:grid; place-items:center; z-index:1000; pointer-events:none; opacity:0; visibility:hidden; transition:opacity .25s var(--bezier), visibility 0s linear .25s}
  .modal.show{pointer-events:auto; opacity:1; visibility:visible; transition:opacity .25s var(--bezier), visibility 0s}
  .backdrop{position:absolute; inset:0; background:rgba(0,0,0,.55); backdrop-filter: blur(2px); }
  .dialog{position:relative; width:min(92vw,1000px); height:min(86vh,700px); background:#0b0f1a; border:1px solid var(--line); border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 28px 80px rgba(0,0,0,.6)}
  .dialog header{display:flex; align-items:center; gap:10px; padding:10px; border-bottom:1px solid var(--line); background:#0f1423}
  .dialog header input{flex:1}
  .tabs{display:flex; gap:6px}
  .tabs button{padding:8px 12px}
  .tabs .active{background:#1a2032}
  .grid{flex:1; overflow:auto; padding:12px; display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:12px}
  .tile{border:1px solid var(--line); border-radius:10px; background:#0f1423; padding:8px; cursor:pointer; transition:transform .18s var(--bezier), border-color .2s}
  .tile:hover{transform:translateY(-2px); border-color:#3d4670}
  .thumb{height:120px; background:#0b0f1a; border-radius:8px; display:grid; place-items:center; overflow:hidden; margin-bottom:8px}
  .thumb img{max-width:100%; max-height:100%; display:block}
  .meta{font-size:12px; color:#cfd3df; word-break:break-all}
  .audioDemo{width:100%}
  .dialog footer{padding:10px; border-top:1px solid var(--line); display:flex; justify-content:flex-end; gap:8px; background:#0f1423}
</style></head>
<body>
<header>
  <strong>Egg Manager — <?= htmlspecialchars($SITE_NAME) ?></strong>
  <span style="float:right;">
    <?php if($authed): ?><a href="setpwd.php">Change password</a> &nbsp;|&nbsp; <a href="?logout=1">Logout</a><?php endif; ?>
  </span>
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
      <p class="muted" style="margin-top:8px"><a href="/admin/setup.php">Run setup again</a> (overwrites settings)</p>
    </div>
  </main>
<?php else: ?>
  <main>
    <aside class="card">
      <h3>All Eggs</h3>
      <ul>
        <?php if(!$eggs): ?>
          <li class="muted">No eggs yet — create one below.</li>
        <?php else: foreach($eggs as $s): ?>
          <li>
            <a href="?slug=<?=$s?>" class="pill"><?=$s?></a>
            <span class="actions">
              <button onclick="renameEgg('<?=$s?>')">Rename</button>
              <button onclick="deleteEgg('<?=$s?>')" style="border-color:#5d2a2a;background:#261416">Delete</button>
            </span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
      <hr>
      <form method="get">
        <label>Create new egg</label>
        <input name="slug" placeholder="e.g., usb" required>
        <div style="margin-top:8px"><button type="submit">Create</button></div>
        <p class="muted">Use letters, numbers, dashes, underscores.</p>
      </form>
      <hr>
      <a href="visual.php<?= $slug ? ('?slug='.urlencode($slug)) : '' ?>" class="pill">🎯 Open Visual Editor</a>
      <p class="muted" style="margin-top:8px">Click in the preview to place an egg.</p>
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
              <input id="currentImagePath" value="<?=htmlspecialchars($current['image'] ?? '')?>" disabled>
            </div>
          </div>

          <div>
            <label>Story (you can paste text, links, or basic HTML)</label>
            <textarea name="body" rows="6" placeholder="Tell the story…"><?=(htmlspecialchars($current['body'] ?? ''))?></textarea>
          </div>

          <div class="row">
            <div>
              <label>Position</label>
              <input value="<?=
                 (isset($current['pos_left']) && isset($current['pos_top']))
                 ? ('Left: '.$current['pos_left'].'vw, Top: '.$current['pos_top'].'vh')
                 : 'Not placed yet' ?>" disabled>
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
              <a class="pill" href="visual.php?slug=<?=urlencode($slug)?>">Set in Visual Editor →</a>
            </div>
          </div>

          <div class="sectionTitle">Image</div>
          <div class="drop" id="dropImg">
            <p><strong>Drag & drop</strong> an image here, <strong>paste</strong> one, or <strong>click</strong> to choose a file.</p>
            <input type="file" id="fileImg" name="image" accept="image/*" style="display:none">
            <div class="preview" id="previewImg"><?php if(!empty($current['image'])): ?><img src="<?=htmlspecialchars($current['image'])?>" alt="current image"/><?php endif; ?></div>
            <p class="help">Auto-converts to WebP when supported by PHP GD.</p>
            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap">
              <button type="button" id="btnPickImage">📂 Choose from uploads</button>
            </div>
          </div>

          <div class="row">
            <div>
              <label>OR Image URL</label>
              <input name="image_url" id="image_url" placeholder="https://…">
            </div>
            <div></div>
          </div>

          <div class="sectionTitle">Audio (optional)</div>
          <div class="drop" id="dropAudio">
            <p><strong>Drag & drop</strong> an audio file here, or <strong>click</strong> to choose one.</p>
            <input type="file" id="fileAudio" name="audio" accept=".mp3,.m4a,.aac,.wav,.ogg,.oga,.webm" style="display:none">
            <div class="preview" id="previewAudio">
              <?php if(!empty($current['audio'])): ?>
                <span class="pill">Current: <?=htmlspecialchars(basename($current['audio']))?></span>
                <audio class="audioDemo" controls src="<?=htmlspecialchars($current['audio'])?>"></audio>
              <?php endif; ?>
            </div>
            <p class="help">Supported: mp3, m4a/aac, wav, ogg/oga, webm. No transcoding.</p>
            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap">
              <button type="button" id="btnPickAudio">📂 Choose from uploads</button>
            </div>
          </div>

          <div class="row">
            <div>
              <label>OR Audio URL</label>
              <input name="audio_url" id="audio_url" placeholder="https://…/sound.mp3">
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
              <button type="submit">Save</button>
              <a class="muted" target="_blank" href="../eggs/egg.php?slug=<?=urlencode($slug)?>">Preview →</a>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </main>
<?php endif; ?>

<footer><small>© <?=date('Y')?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></small></footer>

<!-- Media Picker Modal -->
<div class="modal" id="mediaModal" aria-hidden="true">
  <div class="backdrop" id="mediaBackdrop"></div>
  <div class="dialog" role="dialog" aria-label="Choose a file">
    <header>
      <strong style="margin-right:8px">Media</strong>
      <div class="tabs">
        <button type="button" data-tab="all" class="tabBtn active">All</button>
        <button type="button" data-tab="image" class="tabBtn">Images</button>
        <button type="button" data-tab="audio" class="tabBtn">Audio</button>
      </div>
      <input id="mediaSearch" placeholder="Search by name…">
      <button type="button" id="mediaClose">Close</button>
    </header>
    <div class="grid" id="mediaGrid"></div>
    <footer>
      <button type="button" id="mediaUse" disabled>Use selected</button>
    </footer>
  </div>
</div>

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

  // Image drop/paste
  const dropImg = document.getElementById('dropImg');
  const fileImg = document.getElementById('fileImg');
  const previewImg = document.getElementById('previewImg');
  if(dropImg && fileImg){
    dropImg.addEventListener('click', ()=> fileImg.click());
    dropImg.addEventListener('dragover', e=>{ e.preventDefault(); dropImg.classList.add('drag'); });
    dropImg.addEventListener('dragleave', ()=> dropImg.classList.remove('drag'));
    dropImg.addEventListener('drop', e=>{ e.preventDefault(); dropImg.classList.remove('drag'); if(e.dataTransfer.files[0]) handleImg(e.dataTransfer.files[0]); });
    document.addEventListener('paste', e=>{ const it = e.clipboardData && e.clipboardData.items; if(!it) return; for(const i of it){ if(i.kind==='file' && i.type.startsWith('image/')){ handleImg(i.getAsFile()); break; } } });
    fileImg.addEventListener('change', ()=>{ if(fileImg.files[0]) handleImg(fileImg.files[0]); });
  }
  function handleImg(f){
    const url = URL.createObjectURL(f);
    previewImg.innerHTML = '';
    const img = new Image(); img.onload = ()=> URL.revokeObjectURL(url); img.src=url; img.alt='preview';
    previewImg.appendChild(img);
    const dt = new DataTransfer(); dt.items.add(f); fileImg.files = dt.files;
    // clear URL field if we selected a file
    const iu = document.getElementById('image_url'); if(iu) iu.value='';
  }

  // Audio drop
  const dropAudio = document.getElementById('dropAudio');
  const fileAudio = document.getElementById('fileAudio');
  const previewAudio = document.getElementById('previewAudio');
  if(dropAudio && fileAudio){
    dropAudio.addEventListener('click', ()=> fileAudio.click());
    dropAudio.addEventListener('dragover', e=>{ e.preventDefault(); dropAudio.classList.add('drag'); });
    dropAudio.addEventListener('dragleave', ()=> dropAudio.classList.remove('drag'));
    dropAudio.addEventListener('drop', e=>{ e.preventDefault(); dropAudio.classList.remove('drag'); if(e.dataTransfer.files[0]) handleAudio(e.dataTransfer.files[0]); });
    fileAudio.addEventListener('change', ()=>{ if(fileAudio.files[0]) handleAudio(fileAudio.files[0]); });
  }
  function handleAudio(f){
    previewAudio.innerHTML = '';
    const tag = document.createElement('span'); tag.className='pill'; tag.textContent = 'Selected: ' + (f.name || 'audio');
    previewAudio.appendChild(tag);
    const dt = new DataTransfer(); dt.items.add(f); fileAudio.files = dt.files;
    // clear URL field if we selected a file
    const au = document.getElementById('audio_url'); if(au) au.value='';
  }

  // =========================
  // Media Picker (images & audio from /assets/uploads)
  // =========================
  let mediaContext = 'image'; // which field are we picking for
  let selectedItem = null;
  const mediaModal = document.getElementById('mediaModal');
  const mediaBackdrop = document.getElementById('mediaBackdrop');
  const mediaClose = document.getElementById('mediaClose');
  const mediaGrid = document.getElementById('mediaGrid');
  const mediaUse = document.getElementById('mediaUse');
  const mediaSearch = document.getElementById('mediaSearch');
  const tabBtns = document.querySelectorAll('.tabBtn');

  document.getElementById('btnPickImage')?.addEventListener('click', ()=> openMedia('image'));
  document.getElementById('btnPickAudio')?.addEventListener('click', ()=> openMedia('audio'));

  mediaBackdrop.addEventListener('click', closeMedia);
  mediaClose.addEventListener('click', closeMedia);
  mediaUse.addEventListener('click', useSelected);
  tabBtns.forEach(b=> b.addEventListener('click', ()=> setTab(b.dataset.tab)));
  mediaSearch.addEventListener('input', renderMedia);

  let allMedia = []; // [{url, name, type, size, mtime}]
  let currentTab = 'all';

  function openMedia(kind){
    mediaContext = kind; // 'image' or 'audio'
    selectedItem = null;
    mediaUse.disabled = true;
    mediaSearch.value = '';
    setTab(kind === 'audio' ? 'audio' : 'image'); // prefilter to sensible tab
    fetch('media_list.php', {cache:'no-store'})
      .then(r=>r.json()).then(json=>{
        allMedia = json.items || [];
        renderMedia();
        mediaModal.classList.add('show');
        mediaModal.setAttribute('aria-hidden','false');
      }).catch(()=>{ allMedia=[]; renderMedia(); mediaModal.classList.add('show'); });
  }
  function closeMedia(){
    mediaModal.classList.remove('show');
    mediaModal.setAttribute('aria-hidden','true');
  }
  function setTab(tab){
    currentTab = tab; tabBtns.forEach(b=> b.classList.toggle('active', b.dataset.tab===tab)); renderMedia();
  }
  function renderMedia(){
    const q = mediaSearch.value.trim().toLowerCase();
    const list = allMedia.filter(it=>{
      if(currentTab!=='all' && it.type!==currentTab) return false;
      if(q && !it.name.toLowerCase().includes(q)) return false;
      // If we're picking for image/audio, hide the other type unless "all" is chosen
      if(mediaContext==='image' && currentTab==='all' && it.type!=='image') return false;
      if(mediaContext==='audio' && currentTab==='all' && it.type!=='audio') return false;
      return true;
    });
    // newest first
    list.sort((a,b)=> (b.mtime||0)-(a.mtime||0));

    mediaGrid.innerHTML = '';
    for(const it of list){
      const tile = document.createElement('div'); tile.className='tile'; tile.dataset.url = it.url; tile.dataset.type = it.type;
      if(it.type==='image'){
        tile.innerHTML = `
          <div class="thumb"><img src="${it.url}" alt=""></div>
          <div class="meta">${escapeHtml(it.name)}</div>`;
      } else {
        tile.innerHTML = `
          <div class="thumb"><span>🎵</span></div>
          <div class="meta">${escapeHtml(it.name)}</div>
          <audio class="audioDemo" controls src="${it.url}"></audio>`;
      }
      tile.addEventListener('click', ()=>{
        [...mediaGrid.children].forEach(c=> c.style.outline='none');
        tile.style.outline = '2px solid var(--brand)';
        selectedItem = it; mediaUse.disabled = false;
      });
      mediaGrid.appendChild(tile);
    }
    if(!list.length){
      const empty = document.createElement('div');
      empty.className='muted';
      empty.textContent = 'No files found.';
      mediaGrid.appendChild(empty);
    }
  }
  function useSelected(){
    if(!selectedItem) return;
    if(mediaContext==='image'){
      // put into URL field & preview
      const iu = document.getElementById('image_url'); if(iu){ iu.value = selectedItem.url; }
      previewImg.innerHTML = `<img src="${selectedItem.url}" alt="preview">`;
      // clear file input
      if(fileImg){ fileImg.value=''; }
      document.getElementById('currentImagePath')?.setAttribute('value', selectedItem.url);
    } else if(mediaContext==='audio'){
      const au = document.getElementById('audio_url'); if(au){ au.value = selectedItem.url; }
      previewAudio.innerHTML = `
        <span class="pill">Selected: ${escapeHtml(selectedItem.name)}</span>
        <audio class="audioDemo" controls src="${selectedItem.url}"></audio>`;
      if(fileAudio){ fileAudio.value=''; }
    }
    closeMedia();
  }
  function escapeHtml(s){ return s.replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

</script>
</body></html>
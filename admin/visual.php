<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';

if(empty($_SESSION['authed'])){ http_response_code(403); exit('Forbidden'); }

$eggs = list_eggs();
$slug = $_GET['slug'] ?? ($eggs[0] ?? '');
?><!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Visual Editor — <?= htmlspecialchars($SITE_NAME) ?></title>
<style>
  :root{ --bar:#0f1423; --line:#23283a; --brand:#ffcc00; --fg:#f4f6ff; --muted:#a9afbf; --bezier:cubic-bezier(.22,.61,.36,1) }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{margin:0; background:#0a0d16; color:var(--fg); overflow:hidden; font-family:system-ui,Segoe UI,Roboto,Inter,Arial}

  /* Fullscreen iframe canvas */
  .stage{position:fixed; inset:0; background:#000}
  iframe#frame{position:absolute; inset:0; width:100%; height:100%; border:0}

  /* Floating control bar */
  .controls{
    position:fixed; left:12px; right:12px; top:12px;
    display:flex; align-items:center; gap:10px; padding:10px;
    background:rgba(15,20,35,.86); border:1px solid var(--line);
    border-radius:14px; backdrop-filter: blur(8px); box-shadow:0 10px 40px rgba(0,0,0,.35);
    z-index:10;
  }
  select,button,input{
    background:#0b1020; color:var(--fg); border:1px solid var(--line); border-radius:10px; padding:8px 10px; font-size:14px;
  }
  button{cursor:pointer; transition:transform .18s var(--bezier), background .2s, border-color .2s}
  button:hover{transform:translateY(-1px)}
  .cta{border-color:var(--brand); background:rgba(255,204,0,.1)}
  .muted{color:var(--muted); font-size:12px}
  .sp{flex:1}

  /* On-canvas marker + crosshair + HUD */
  .marker{position:fixed; width:18px; height:18px; border-radius:50%; background:rgba(255,204,0,.95); box-shadow:0 6px 30px rgba(0,0,0,.45); transform:translate(-50%,-50%) scale(.9); z-index:9; pointer-events:none; transition: transform .18s var(--bezier)}
  .marker.show{transform:translate(-50%,-50%) scale(1)}
  .hud{
    position:fixed; transform:translate(-50%, -120%); background:rgba(0,0,0,.72); border:1px solid rgba(255,255,255,.16);
    color:#eaeefc; font-size:12px; padding:6px 8px; border-radius:10px; pointer-events:none; white-space:nowrap; z-index:9
  }
  .gridToggle{display:flex; align-items:center; gap:6px}
  .grid{position:fixed; inset:0; background-image: linear-gradient(to right, rgba(255,255,255,.06) 1px, transparent 1px),
                                     linear-gradient(to bottom, rgba(255,255,255,.06) 1px, transparent 1px);
        background-size: 5vw 5vw, 5vh 5vh; pointer-events:none; z-index:2; opacity:.0; transition:opacity .2s}
  .grid.on{opacity:.35}

  /* Footer hint */
  .foot{position:fixed; bottom:10px; left:12px; right:12px; display:flex; justify-content:space-between; color:var(--muted); font-size:12px; z-index:10}
</style>
</head>
<body>
  <div class="stage">
    <iframe id="frame" src="../index.php?from=editor" title="Homepage Preview" loading="eager"></iframe>
    <div class="grid" id="grid"></div>
    <div class="marker" id="marker" style="left:-9999px; top:-9999px"></div>
    <div class="hud" id="hud" style="left:-9999px; top:-9999px">0vw, 0vh</div>
  </div>

  <div class="controls" role="toolbar" aria-label="Placement Controls">
    <strong style="margin-right:6px">🎯 Visual Editor</strong>
    <label class="muted">Egg</label>
    <select id="eggSel">
      <?php foreach($eggs as $e): ?>
        <option value="<?=htmlspecialchars($e)?>" <?= $e===$slug?'selected':'' ?>><?=htmlspecialchars($e)?></option>
      <?php endforeach; ?>
    </select>

    <span class="sp"></span>

    <span class="gridToggle">
      <input type="checkbox" id="toggleGrid">
      <label for="toggleGrid" class="muted">Grid</label>
    </span>

    <button type="button" id="btnCenter">Center</button>
    <button type="button" id="btnSave" class="cta">Save position</button>
    <a href="./index.php?slug=<?=urlencode($slug)?>" target="_blank"><button type="button">Back to Admin</button></a>
  </div>

  <div class="foot">
    <span class="muted">Tip: click anywhere on the page to place the egg. We save **viewport units** (vw/vh) so it’s device-accurate.</span>
    <span id="posReadout" class="muted"></span>
  </div>

<script>
  const frame = document.getElementById('frame');
  const marker = document.getElementById('marker');
  const hud = document.getElementById('hud');
  const grid = document.getElementById('grid');
  const eggSel = document.getElementById('eggSel');
  const posReadout = document.getElementById('posReadout');
  const btnCenter = document.getElementById('btnCenter');
  const btnSave = document.getElementById('btnSave');
  const toggleGrid = document.getElementById('toggleGrid');

  let pos = { vw: null, vh: null };
  let currentSlug = eggSel.value;

  eggSel.addEventListener('change', ()=>{
    currentSlug = eggSel.value;
    // optional: fetch current to show marker
    fetch('../eggs/data/'+encodeURIComponent(currentSlug)+'.json?ts='+(Date.now()))
      .then(r=>r.json()).then(j=>{
        if(typeof j.pos_left === 'number' && typeof j.pos_top === 'number'){
          setMarker(j.pos_left, j.pos_top);
        } else {
          hideMarker();
        }
      }).catch(hideMarker);
  });

  toggleGrid.addEventListener('change', ()=> grid.classList.toggle('on', toggleGrid.checked));

  btnCenter.addEventListener('click', ()=>{
    setMarker(50, 50);
  });

  btnSave.addEventListener('click', ()=>{
    if(pos.vw==null || pos.vh==null){ flash('Click the page to place the egg first.'); return; }
    const body = new URLSearchParams({ slug: currentSlug, pos_left: pos.vw, pos_top: pos.vh });
    fetch('save_position.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
      .then(r=>r.json()).then(j=>{
        if(j && j.ok){ flash('Saved ✓'); }
        else { flash('Save failed'); }
      }).catch(()=> flash('Save failed'));
  });

  function flash(text){
    btnSave.textContent = text;
    setTimeout(()=> btnSave.textContent='Save position', 900);
  }

  function setMarker(vw, vh){
    pos = { vw: +vw, vh: +vh };
    marker.style.left = vw + 'vw';
    marker.style.top  = vh + 'vh';
    marker.classList.add('show');
    hud.style.left = vw + 'vw';
    hud.style.top  = vh + 'vh';
    hud.textContent = vw.toFixed(2) + 'vw, ' + vh.toFixed(2) + 'vh';
    posReadout.textContent = 'Current: ' + hud.textContent;
  }
  function hideMarker(){
    pos = {vw:null, vh:null};
    marker.style.left = '-9999px';
    hud.style.left = '-9999px';
    posReadout.textContent = 'Not placed';
  }

  // Receive precise vw/vh from the homepage (editor mode)
  window.addEventListener('message', (ev)=>{
    try{
      if(!ev.data || ev.data.type !== 'egg-editor-click') return;
      const {vw, vh} = ev.data;
      if(typeof vw === 'number' && typeof vh === 'number'){
        setMarker(vw, vh);
      }
    }catch(_){}
  }, false);

  // Load initial
  (function init(){
    if(currentSlug){
      fetch('../eggs/data/'+encodeURIComponent(currentSlug)+'.json?ts='+(Date.now()))
        .then(r=>r.json()).then(j=>{
          if(typeof j.pos_left === 'number' && typeof j.pos_top === 'number'){
            setMarker(j.pos_left, j.pos_top);
          } else { hideMarker(); }
        }).catch(hideMarker);
    }
  })();
</script>
</body>
</html>
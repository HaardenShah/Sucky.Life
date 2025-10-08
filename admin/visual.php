<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';
if(empty($_SESSION['authed'])){ header('Location: index.php'); exit; }
$eggs = list_eggs();
$slug = $_GET['slug'] ?? ($eggs[0] ?? '');
$current = $slug ? load_egg($slug) : null;
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Visual Egg Editor</title>
<style>
  :root{ --bg:#0f1115; --card:#141823; --line:#23283a; --fg:#f1f1f1; --muted:#a9afbf; --brand:#ffcc00; }
  *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,Segoe UI,Roboto,Inter,Arial; background:var(--bg); color:var(--fg)}
  header,footer{padding:12px 16px; background:#101421; border-bottom:1px solid var(--line)}
  main{display:grid; grid-template-columns:320px 1fr; gap:18px; padding:18px; height: calc(100vh - 58px)}
  .card{background:var(--card); border:1px solid var(--line); border-radius:12px; padding:12px; height:100%; overflow:auto}
  .muted{color:var(--muted); font-size:12px}
  select,input,button{width:100%; padding:10px; border-radius:10px; border:1px solid var(--line); background:#0c0f19; color:var(--fg)}
  button{cursor:pointer; transition: transform .18s cubic-bezier(.22,.61,.36,1)}
  button:hover{transform: translateY(-1px)}
  .row{display:grid; grid-template-columns:1fr 1fr; gap:10px}
  .frameWrap{position:relative; height:100%; border-radius:14px; overflow:hidden; border:1px solid var(--line); background:#000}
  iframe{position:absolute; inset:0; width:100%; height:100%; border:0}
  .overlay{position:absolute; inset:0; pointer-events:none}
  .clickCatcher{position:absolute; inset:0; cursor: crosshair; background:transparent; pointer-events:auto}
  .marker{position:absolute; width:28px; height:28px; border-radius:50%; border:1px dashed rgba(255,255,255,.25); background:rgba(255,255,255,.08);
          transform:translate(-50%,-50%) scale(.96); transition:transform .28s cubic-bezier(.22,.61,.36,1), box-shadow .25s ease;
          box-shadow:0 10px 30px rgba(0,0,0,.45)}
  .marker.show{transform:translate(-50%,-50%) scale(1.06)}
</style>
</head>
<body>
<header><strong>Visual Editor</strong> — click on the preview to place the egg</header>
<main>
  <aside class="card">
    <form id="controls">
      <label>Choose egg</label>
      <select id="egg">
        <?php foreach($eggs as $s): ?>
          <option value="<?=$s?>" <?=$s===$slug?'selected':''?>><?=$s?></option>
        <?php endforeach; ?>
      </select>
      <div style="height:10px"></div>
      <div class="row">
        <div>
          <label>Left (vw)</label>
          <input id="left" type="number" step="0.1" value="<?= isset($current['pos_left']) ? htmlspecialchars($current['pos_left']) : '' ?>" placeholder="e.g., 18">
        </div>
        <div>
          <label>Top (vh)</label>
          <input id="top" type="number" step="0.1" value="<?= isset($current['pos_top']) ? htmlspecialchars($current['pos_top']) : '' ?>" placeholder="e.g., 62">
        </div>
      </div>
      <div style="height:10px"></div>
      <button id="placeBtn" type="button">🎯 Click in preview to place</button>
      <div style="height:8px"></div>
      <button id="saveBtn" type="button">💾 Save Position</button>
      <p class="muted" id="status"></p>
      <hr>
      <a href="index.php" class="muted">← Back to Admin</a>
    </form>
  </aside>

  <section class="frameWrap">
    <iframe id="frame" src="../index.php?from=editor" title="Homepage Preview" loading="eager"></iframe>
    <div class="overlay">
      <div id="marker" class="marker" style="display:none"></div>
    </div>
    <div id="catcher" class="clickCatcher" style="display:none"></div>
  </section>
</main>

<script>
  const frame = document.getElementById('frame');
  const catcher = document.getElementById('catcher');
  const marker = document.getElementById('marker');
  const eggSel = document.getElementById('egg');
  const leftInput = document.getElementById('left');
  const topInput = document.getElementById('top');
  const statusEl = document.getElementById('status');
  const placeBtn = document.getElementById('placeBtn');
  const saveBtn = document.getElementById('saveBtn');

  eggSel.addEventListener('change', ()=> {
    const slug = eggSel.value;
    location.href = 'visual.php?slug=' + encodeURIComponent(slug);
  });

  placeBtn.addEventListener('click', ()=>{
    status('Click anywhere on the preview…');
    catcher.style.display = 'block';
  });

  catcher.addEventListener('click', (e)=>{
    // Click position inside the iframe box -> % of its width/height -> vw/vh equivalent.
    const rect = frame.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;   // 0..1
    const y = (e.clientY - rect.top) / rect.height;   // 0..1
    const leftVW = +(x * 100).toFixed(2);
    const topVH  = +(y * 100).toFixed(2);
    leftInput.value = leftVW;
    topInput.value = topVH;
    showMarker(leftVW, topVH);
    catcher.style.display = 'none';
    status(`Position: ${leftVW}vw, ${topVH}vh (not saved yet)`);
  });

  function showMarker(lvw, tvh){
    marker.style.display = 'block';
    marker.style.left = lvw + 'vw';
    marker.style.top  = tvh + 'vh';
    marker.classList.remove('show');
    requestAnimationFrame(()=> marker.classList.add('show'));
  }

  saveBtn.addEventListener('click', ()=>{
    const slug = eggSel.value;
    const lvw = parseFloat(leftInput.value);
    const tvh = parseFloat(topInput.value);
    if(!(isFinite(lvw) && isFinite(tvh))) return status('Please set a valid position.');
    const body = new URLSearchParams({slug, pos_left: lvw, pos_top: tvh});
    fetch('save_position.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
      .then(r=>r.json()).then(j=>{
        if(j.ok){
          status('Saved. Refreshing preview…');
          // bust cache
          frame.src = '../index.html?from=editor&ts=' + Date.now();
        } else {
          status('Error: ' + (j.error || 'Unknown'));
        }
      }).catch(()=> status('Network error'));
  });

  function status(t){ statusEl.textContent = t; }
</script>
</body></html>

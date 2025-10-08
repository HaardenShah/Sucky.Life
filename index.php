<?php
/**
 * sucky.life — Homepage
 * - Visitor gate aware
 * - Session view-token for egg iframes (no direct links)
 * - Screech audio + tears tied to audio state
 * - Hotspots from eggs/list.php → open modal (iframe)
 * - Visual Editor bridge: when loaded with ?from=editor, intercept clicks
 *   and post exact vw/vh back to admin/visual.php
 *
 * Keep this file saved as UTF-8 (no BOM).
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/admin/config.php';

if ($NEEDS_SETUP) {
  header('Location: /admin/setup.php');
  exit;
}

/* Optional visitor gate */
if (!empty($GATE_ON) && $GATE_ON === true) {
  if (empty($_SESSION['visitor_ok'])) {
    $ret = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /gate.php?return={$ret}");
    exit;
  }
}

/* Session-bound view token for egg iframes */
if (empty($_SESSION['egg_view_token'])) {
  $_SESSION['egg_view_token'] = bin2hex(random_bytes(16));
}
$EGG_VIEW_TOKEN = $_SESSION['egg_view_token'];

/* Editor mode (when embedded by admin/visual.php) */
$EDITOR_MODE = (isset($_GET['from']) && $_GET['from'] === 'editor');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($SITE_NAME) ?> — Main</title>
<meta name="description" content="Inside jokes for the crew. Hidden eggs. Maximum drama." />
<style>
  :root{
    --yellow:#ffcc00; --bg:#0e0f12; --fg:#f8f8f8; --dim:#b6b6b6; --accent:#ff4d4d;
    --bezier:cubic-bezier(.22,.61,.36,1);
  }
  html,body{height:100%} *{box-sizing:border-box}
  body{margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial;
       color:var(--fg); background:var(--bg); overflow:hidden;}
  .bg{position:fixed; inset:0; background:#000 center/cover no-repeat;
      filter:contrast(1.05) saturate(1.05) brightness(0.9); transform:scale(1.02);}
  .overlay{position:fixed; inset:0; background:radial-gradient(1200px 600px at 50% 10%, transparent, rgba(0,0,0,.55) 60%, rgba(0,0,0,.8)); pointer-events:none;}

  header{position:fixed; top:16px; left:16px; right:16px; display:flex; justify-content:space-between; align-items:center; gap:16px; mix-blend-mode:lighten; z-index:5}
  .brand{font-weight:800; letter-spacing:.5px}
  .controls{display:flex; gap:8px}
  button{appearance:none; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.06); color:var(--fg);
         padding:12px 16px; border-radius:14px; font-weight:600; cursor:pointer; backdrop-filter:blur(6px);
         transition: transform .18s var(--bezier), background .25s ease, border-color .25s ease;}
  button:hover{transform:translateY(-1px) scale(1.01); background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.35)}
  .cta{border-color:var(--yellow); background:rgba(255,204,0,.08)}
  .pulse{display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--yellow); margin-left:8px; box-shadow:0 0 0 0 rgba(255,204,0,.6); animation:pulse 1.6s infinite}
  @keyframes pulse{to{box-shadow:0 0 0 16px rgba(255,204,0,0)}}

  #btnMute{display:inline-flex; align-items:center; gap:8px}
  #btnMute .icon{width:14px; height:14px; display:inline-block; background:currentColor;
    -webkit-mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/></svg>') no-repeat center/contain;
            mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/></svg>') no-repeat center/contain;
  }
  #btnMute.muted .icon{
    -webkit-mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/><path fill="white" d="M16 8l5 5m0-5l-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>') no-repeat center/contain;
            mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/><path fill="white" d="M16 8l5 5m0-5l-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>') no-repeat center/contain;
  }

  main{position:fixed; inset:0; display:grid; place-items:center; text-align:center; padding:24px; z-index:4}
  h1{font-size:clamp(32px,6vw,68px); margin:0 0 14px; text-shadow:0 6px 30px rgba(0,0,0,.55)}
  .lip-text{font-size:clamp(16px,2.2vw,22px); color:var(--dim); font-style:italic; text-align:center; cursor:pointer; user-select:none; transition: color .2s ease, transform .15s ease, text-shadow .2s ease;}
  .lip-text:hover{ color:#ff7aa2; transform:scale(1.02) }
  .lip-text.playing{ color:var(--yellow); text-shadow:0 0 12px rgba(255,204,0,.35); animation:wobble 1.4s var(--bezier) infinite, vibrate .18s linear infinite }
  @keyframes wobble{0%{transform:rotate(0)}20%{transform:rotate(-2deg) translateY(-1px)}40%{transform:rotate(1.6deg)}60%{transform:rotate(-1deg) translateY(1px)}80%{transform:rotate(1deg)}100%{transform:rotate(0)}}
  @keyframes vibrate{0%{transform:translate(0,0)}25%{transform:translate(0.2px,-0.2px)}50%{transform:translate(-0.2px,0.2px)}75%{transform:translate(0.2px,0.2px)}100%{transform:translate(0,0)}}

  .tears{position:fixed; inset:0; pointer-events:none; z-index:3}
  .tear{position:absolute; width:10px; height:14px; background:linear-gradient(#9cd3ff,#4aa3ff); border-radius:50% 50% 60% 60%;
        filter:blur(.2px); opacity:.9; animation:fall 2.8s linear infinite;
        transform:translate(var(--pushX,0px), var(--pushY,0px)); will-change:transform;}
  @keyframes fall{to{transform:translateY(110vh) rotate(12deg); opacity:.95}}

  .egg-spot{position:fixed; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,.06);
            outline:1px dashed rgba(255,255,255,.12); transform:translate(-50%,-50%) scale(.96);
            cursor:help; transition:transform .28s var(--bezier), outline-color .25s, background .25s, box-shadow .25s; box-shadow:0 6px 20px rgba(0,0,0,.35); z-index:4}
  .egg-spot:hover{transform:translate(-50%,-50%) scale(1.06); outline-color:rgba(255,255,255,.25); background:rgba(255,255,255,.12); box-shadow:0 10px 30px rgba(0,0,0,.45)}
  .egg-note{position:fixed; transform:translate(-50%,-12px) scale(.98); padding:10px 12px; border-radius:12px; white-space:nowrap; background:rgba(0,0,0,.72); color:#f9f9f9; font-size:13px;
            border:1px solid rgba(255,255,255,.18); opacity:0; pointer-events:none; transition:opacity .25s ease, transform .28s var(--bezier); z-index:4}
  .egg-spot:hover ~ .egg-note[data-for]:not([hidden]){opacity:1; transform:translate(-50%,-16px) scale(1)}

  .modal{position:fixed; inset:0; display:grid; place-items:center; z-index:50; pointer-events:none; opacity:0; visibility:hidden; transition:opacity .28s var(--bezier), visibility 0s linear .28s}
  .modal .backdrop{position:absolute; inset:0; background:rgba(0,0,0,.55); backdrop-filter: blur(2px); opacity:0; transition:opacity .28s var(--bezier)}
  .modal .dialog{position:relative; width:min(92vw,900px); height:min(80vh,580px); border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,.2); box-shadow:0 30px 80px rgba(0,0,0,.6); background:#0b0b0b;
                 transform:translateY(12px) scale(.98); opacity:0; transition:transform .34s var(--bezier), opacity .3s ease}
  .modal .dialog header{position:absolute; top:8px; right:8px; z-index:2}
  .modal .dialog iframe{width:100%; height:100%; display:block; background:#0b0b0b}
  .modal.show{pointer-events:auto; opacity:1; visibility:visible; transition:opacity .28s var(--bezier), visibility 0s}
  .modal.show .backdrop{opacity:1}
  .modal.show .dialog{transform:translateY(0) scale(1); opacity:1}
  .modal.closing .backdrop{opacity:0}
  .modal.closing .dialog{transform:translateY(10px) scale(.985); opacity:0}

  footer{position:fixed; bottom:10px; left:0; right:0; text-align:center; font-size:12px; color:#c4c4c4; opacity:.8; z-index:2}

  /* Editor-mode cosmetics */
  <?php if ($EDITOR_MODE): ?>
  html, body { user-select:none !important; }
  body.editor-mode { cursor: crosshair; }
  .editor-banner{position:fixed; top:10px; right:10px; z-index:999999; padding:8px 10px; border-radius:10px; background:rgba(255, 204, 0, .12); border:1px solid rgba(255,204,0,.35);
                 color:#ffec9a; font: 600 12px/1.2 system-ui, -apple-system, Segoe UI, Inter, Arial; pointer-events:none;}
  <?php endif; ?>
</style>
</head>
<body>
  <div class="bg" style="background-image:url('assets/friend.jpg');"></div>
  <div class="overlay"></div>

  <header>
    <div class="brand"><?= htmlspecialchars($SITE_NAME) ?></div>
    <div class="controls">
      <button id="btnPlay" class="cta" aria-label="Play">Make it extra sucky <span class="pulse"></span></button>
      <button id="btnMute" aria-label="Mute/Unmute" hidden><span class="icon" aria-hidden="true"></span><span class="label">Unmute</span></button>
    </div>
  </header>

  <main>
    <div>
      <h1>*curls lip* Life&rsquo;s just sooo hard, bro&hellip;</h1>
      <p id="screechToggle" class="lip-text" role="button" tabindex="0" aria-pressed="false"><em>Press here to unleash the screech.</em></p>
      <div class="hint" style="margin-top:14px; font-size:14px; color:#cfcfcf; opacity:.8">(Mobile needs one tap to allow audio.)</div>
    </div>
  </main>

  <audio id="screech" preload="auto" src="assets/screech.mp3"></audio>
  <footer>© <?= date('Y') ?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></footer>
  <div class="tears" id="tears"></div>

  <div class="modal" id="eggModal" aria-hidden="true">
    <div class="backdrop" id="modalBackdrop"></div>
    <div class="dialog">
      <header><button id="closeModal" aria-label="Close">✖</button></header>
      <iframe id="eggFrame" src="about:blank" title="Inside joke window" loading="lazy" referrerpolicy="no-referrer"></iframe>
    </div>
  </div>

<script>
  /* ========== Audio + tears ========== */
  const audio   = document.getElementById('screech');
  const btnPlay = document.getElementById('btnPlay');
  const btnMute = document.getElementById('btnMute');
  const tears   = document.getElementById('tears');
  let tearTimer = null;

  function spawnTear(){
    const t = document.createElement('div');
    t.className = 'tear';
    const x = Math.random()*100, dur = 1.8 + Math.random()*2.2;
    t.style.left = x+'vw'; t.style.top = '-20px'; t.style.animationDuration = dur+'s';
    t.style.opacity = (0.7 + Math.random()*0.3).toFixed(2);
    tears.appendChild(t);
    setTimeout(()=>t.remove(), dur*1000);
  }
  function startTears(){ if(tearTimer) return; for(let i=0;i<4;i++) spawnTear(); tearTimer = setInterval(spawnTear, 220); }
  function stopTears(){ if(tearTimer){ clearInterval(tearTimer); tearTimer=null; } }
  function setUI(playing){
    if(playing){
      btnPlay.innerHTML = 'Stop the suffering';
      btnMute.hidden = false;
      btnMute.classList.toggle('muted', audio.muted);
      btnMute.querySelector('.label').textContent = audio.muted ? 'Unmute' : 'Mute';
    } else {
      btnPlay.innerHTML = 'Make it extra sucky <span class="pulse"></span>';
      btnMute.hidden = true;
    }
    updateLipText();
  }
  async function playAudio(){ audio.currentTime = Math.random()*1.2; audio.loop = true; audio.muted = false; await audio.play(); startTears(); setUI(true); }
  function stopAudio(){ audio.pause(); audio.currentTime = 0; stopTears(); setUI(false); }
  function updateTears(){ (!audio.paused && !audio.muted) ? startTears() : stopTears(); }
  btnPlay.addEventListener('click', async ()=>{ try { if(audio.paused) await playAudio(); else stopAudio(); } catch {} });
  btnMute.addEventListener('click', ()=>{ audio.muted = !audio.muted; btnMute.classList.toggle('muted', audio.muted); btnMute.querySelector('.label').textContent = audio.muted?'Unmute':'Mute'; updateTears(); updateLipText(); });
  (async()=>{ try{ audio.muted=true; audio.loop=true; await audio.play(); audio.pause(); audio.currentTime=0; }catch(_){}})();
  ['play','pause','volumechange','ended'].forEach(ev => audio.addEventListener(ev, updateTears));

  const lipText = document.getElementById('screechToggle');
  function toggleScreech(){ if(audio.paused){ playAudio().catch(()=>{}); } else { stopAudio(); } }
  lipText.addEventListener('click', toggleScreech);
  lipText.addEventListener('keydown', e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggleScreech(); }});
  function updateLipText(){
    const playing = !audio.paused && !audio.ended;
    const active  = playing && !audio.muted;
    lipText.classList.toggle('playing', active);
    lipText.setAttribute('aria-pressed', playing ? 'true' : 'false');
    lipText.innerHTML = playing ? (audio.muted ? '<em>Muted. Click to stop the screech.</em>' : '<em>Click to silence the screech.</em>') : '<em>Press here to unleash the screech.</em>';
  }
  updateLipText();

  /* ========== Modal ========== */
  const eggModal = document.getElementById('eggModal');
  const eggFrame = document.getElementById('eggFrame');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const closeModalBtn = document.getElementById('closeModal');
  let modalOpen = false;

  function openEgg(src){
    if(!src || modalOpen) return;
    modalOpen = true;
    const sep = src.includes('?') ? '&' : '?';
    eggFrame.src = src + sep + 'vt=<?= htmlspecialchars($EGG_VIEW_TOKEN) ?>';
    eggModal.classList.remove('closing'); eggModal.classList.add('show'); eggModal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
  }
  function hideEgg(){
    if(!modalOpen) return;
    modalOpen = false;
    eggModal.classList.add('closing'); eggModal.classList.remove('show'); eggModal.setAttribute('aria-hidden','true');
    const done = ()=>{ eggModal.removeEventListener('transitionend', done); eggFrame.src='about:blank'; document.body.style.overflow=''; eggModal.classList.remove('closing'); };
    eggModal.addEventListener('transitionend', done); setTimeout(done, 380);
  }
  modalBackdrop.addEventListener('click', hideEgg);
  closeModalBtn.addEventListener('click', hideEgg);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape' && eggModal.classList.contains('show')) hideEgg(); });

  /* ========== Hotspots ========== */
  fetch('eggs/list.php', {cache:'no-store'})
    .then(r=>r.json())
    .then(items=>{
      (items||[])
        .filter(i => typeof i.pos_left==='number' && typeof i.pos_top==='number')
        .forEach(addHotspot);
    })
    .catch(()=>{});

  function addHotspot(item){
    const spot = document.createElement('div');
    spot.className='egg-spot';
    spot.style.left = (item.pos_left)+'vw';
    spot.style.top  = (item.pos_top)+'vh';
    spot.title = item.slug;
    spot.dataset.slug = item.slug;
    spot.addEventListener('click', ()=> openEgg('eggs/egg.php?slug='+encodeURIComponent(item.slug)));
    document.body.appendChild(spot);

    const note = document.createElement('div');
    note.className='egg-note';
    note.dataset.for = item.slug;
    note.style.left = (item.pos_left)+'vw';
    note.style.top  = (item.pos_top - 2)+'vh';
    note.textContent = item.title || item.caption || item.slug;
    document.body.appendChild(note);
  }

  /* ========== Cursor repel ========== */
  const repelRadius=120, maxPush=50; let decayTimer;
  function repel(e){
    const els = document.getElementsByClassName('tear');
    for(const el of els){
      const r=el.getBoundingClientRect(), cx=r.left+r.width/2, cy=r.top+r.height/2;
      const dx=cx-e.clientX, dy=cy-e.clientY, dist=Math.hypot(dx,dy);
      if(dist < repelRadius){
        const s=(1 - dist/repelRadius), nx=dx/(dist||1), ny=dy/(dist||1);
        const px=Math.max(Math.min(nx*maxPush*s*1.4, maxPush), -maxPush);
        const py=Math.max(Math.min(ny*maxPush*s*1.4, maxPush), -maxPush);
        el.style.setProperty('--pushX', px.toFixed(1)+'px');
        el.style.setProperty('--pushY', py.toFixed(1)+'px');
      }
    }
    clearTimeout(decayTimer);
    decayTimer = setTimeout(()=>{ for(const el of document.getElementsByClassName('tear')){ el.style.setProperty('--pushX','0px'); el.style.setProperty('--pushY','0px'); } }, 140);
  }
  window.addEventListener('mousemove', repel);
  window.addEventListener('touchmove', ev=>{ if(ev.touches && ev.touches[0]) repel({clientX:ev.touches[0].clientX, clientY:ev.touches[0].clientY}); });

  /* ========== Editor Mode Bridge ========== */
  <?php if ($EDITOR_MODE): ?>
  (function(){
    document.body.classList.add('editor-mode');

    // prevent native interactions; treat clicks as placement
    const swallow = (e)=>{ if(e.target && (e.target.closest('a,button,[role=button],input,textarea,video,audio'))) { e.preventDefault(); e.stopPropagation(); } };
    document.addEventListener('click', swallow, true);
    document.addEventListener('mousedown', swallow, true);
    document.addEventListener('selectstart', e=>e.preventDefault(), true);

    document.addEventListener('click', function(e){
      const vw = (e.clientX / window.innerWidth)  * 100;
      const vh = (e.clientY / window.innerHeight) * 100;
      try{ window.parent.postMessage({type:'egg-editor-click', vw, vh}, '*'); }catch(_){}
      e.preventDefault(); e.stopPropagation();
    }, true);

    const banner = document.createElement('div');
    banner.className = 'editor-banner';
    banner.textContent = 'Visual Editor: click to place hotspot';
    document.body.appendChild(banner);
  })();
  <?php endif; ?>
</script>
</body>
</html>
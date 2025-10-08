<?php
/**
 * sucky.life — Homepage (Developer-friendly, UTF-8 safe)
 *
 * Responsibilities
 *  - First-run redirect to admin/setup
 *  - Render hero + controls + tappable animated instruction text
 *  - Loop/stop the “screech” audio (mute toggle)
 *  - Spawn “tears” only while audio is playing and unmuted
 *  - Cursor “repel” effect pushes tears away from the pointer
 *  - Load egg hotspots (vw/vh) and open egg modal (iframe) on click
 *  - Deep link: ?egg=slug opens a specific egg on load
 *  - Visual Editor bridge: if ?from=editor, clicking posts vw/vh to parent
 *
 * Notes
 *  - Keep this file saved as UTF-8 (without BOM). We also set headers below.
 *  - Avoid raw emoji in text; we use SVG masks for icons so every font works.
 */

header('Content-Type: text/html; charset=utf-8'); // prevent � diamonds
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/admin/config.php';
if ($NEEDS_SETUP) { header('Location: /admin/setup.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title><?= htmlspecialchars($SITE_NAME) ?> — Main</title>
  <meta name="description" content="Inside jokes for the crew. Featuring our guy who swears the universe is out to get him." />

  <!--
    Design tokens + global styles
    Keep CSS scoped and predictable. Minimal animations use a soft cubic-bezier.
  -->
  <style>
    :root{
      --yellow:#ffcc00; --bg:#0e0f12; --fg:#f8f8f8; --dim:#b6b6b6; --accent:#ff4d4d;
      --bezier:cubic-bezier(.22,.61,.36,1);
    }
    html,body{height:100%} *{box-sizing:border-box}
    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial;
      color:var(--fg); background:var(--bg);
      /* We lock body scroll to avoid scroll-bleed when the modal is open.
         JS re-enables it on modal close. */
      overflow:hidden;
    }

    /* Background image + subtle vignette */
    .bg{
      position:fixed; inset:0;
      background:#000 center/cover no-repeat;
      filter:contrast(1.05) saturate(1.05) brightness(0.9);
      transform:scale(1.02);
    }
    .overlay{
      position:fixed; inset:0;
      background:radial-gradient(1200px 600px at 50% 10%, transparent, rgba(0,0,0,.55) 60%, rgba(0,0,0,.8));
      pointer-events:none;
    }

    /* Header: site name + audio controls */
    header{
      position:fixed; top:16px; left:16px; right:16px;
      display:flex; justify-content:space-between; align-items:center; gap:16px;
      /* Lighten makes white text pop over dark imagery without hard edges */
      mix-blend-mode:lighten;
      z-index:5;
    }
    .brand{font-weight:800; letter-spacing:.5px}
    .brand span{color:var(--yellow)}
    .controls{display:flex; gap:8px}

    /* Buttons: glassy, with gentle hover lift */
    button{
      appearance:none;
      border:1px solid rgba(255,255,255,.2);
      background:rgba(255,255,255,.06);
      color:var(--fg);
      padding:12px 16px;
      border-radius:14px;
      font-weight:600;
      cursor:pointer;
      backdrop-filter: blur(6px);
      transition: transform .18s var(--bezier), background .25s ease, border-color .25s ease;
    }
    button:hover{transform:translateY(-1px) scale(1.01); background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.35)}
    button:active{transform:translateY(0) scale(.995)}

    .cta{border-color:var(--yellow); background:rgba(255,204,0,.08)}
    .cta .pulse{
      display:inline-block; width:10px; height:10px; border-radius:50%;
      background:var(--yellow); margin-left:8px;
      box-shadow:0 0 0 0 rgba(255,204,0,.6);
      animation:pulse 1.6s infinite;
    }
    @keyframes pulse{to{box-shadow:0 0 0 16px rgba(255,204,0,0)}}

    /* Mute button uses a tiny SVG mask so we avoid emoji/font issues */
    #btnMute{display:inline-flex; align-items:center; gap:8px}
    #btnMute .icon{
      width:14px; height:14px; display:inline-block; background:currentColor;
      -webkit-mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/></svg>') no-repeat center/contain;
              mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/></svg>') no-repeat center/contain;
    }
    #btnMute.muted .icon{
      -webkit-mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/><path fill="white" d="M16 8l5 5m0-5l-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>') no-repeat center/contain;
              mask:url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="white" d="M7 10v4h3l4 4V6l-4 4H7z"/><path fill="white" d="M16 8l5 5m0-5l-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>') no-repeat center/contain;
    }

    /* Hero content */
    main{position:fixed; inset:0; display:grid; place-items:center; text-align:center; padding:24px; z-index:4}
    h1{font-size:clamp(32px,6vw,68px); margin:0 0 14px; text-shadow:0 6px 30px rgba(0,0,0,.55)}

    /* Instruction text doubles as a secondary play/stop control */
    .lip-text{
      font-size:clamp(16px,2.2vw,22px);
      color:var(--dim); font-style:italic; text-align:center;
      cursor:pointer; user-select:none;
      transition: color .2s ease, transform .15s ease, text-shadow .2s ease;
    }
    .lip-text:hover{ color:#ff7aa2; transform:scale(1.02) }
    .lip-text:active{ transform:scale(0.985) }
    /* Animated “alive” state while actually playing & unmuted */
    .lip-text.playing{ color:var(--yellow); text-shadow:0 0 12px rgba(255,204,0,.35); animation:wobble 1.4s var(--bezier) infinite, vibrate .18s linear infinite }
    @keyframes wobble{0%{transform:rotate(0)}20%{transform:rotate(-2deg) translateY(-1px)}40%{transform:rotate(1.6deg)}60%{transform:rotate(-1deg) translateY(1px)}80%{transform:rotate(1deg)}100%{transform:rotate(0)}}
    @keyframes vibrate{0%{transform:translate(0,0)}25%{transform:translate(0.2px,-0.2px)}50%{transform:translate(-0.2px,0.2px)}75%{transform:translate(0.2px,0.2px)}100%{transform:translate(0,0)}}

    .hint{margin-top:14px; font-size:14px; color:#cfcfcf; opacity:.8}

    /* Tears system (CSS does the falling; JS only spawns/removes) */
    .tears{position:fixed; inset:0; pointer-events:none; z-index:3}
    .tear{
      position:absolute; width:10px; height:14px;
      background:linear-gradient(#9cd3ff,#4aa3ff);
      border-radius:50% 50% 60% 60%;
      filter:blur(.2px); opacity:.9;
      animation:fall 2.8s linear infinite;
      /* repulsion vector injected via CSS vars */
      transform:translate(var(--pushX,0px), var(--pushY,0px));
      will-change:transform;
    }
    @keyframes fall{to{transform:translateY(110vh) rotate(12deg); opacity:.95}}

    /* Egg hotspots + hover note */
    .egg-spot{
      position:fixed; width:28px; height:28px; border-radius:50%;
      background:rgba(255,255,255,.06); outline:1px dashed rgba(255,255,255,.12);
      transform:translate(-50%,-50%) scale(.96); cursor:help;
      transition:transform .28s var(--bezier), outline-color .25s ease, background .25s ease, box-shadow .25s ease;
      box-shadow:0 6px 20px rgba(0,0,0,.35); z-index:4;
    }
    .egg-spot:hover{transform:translate(-50%,-50%) scale(1.06); outline-color:rgba(255,255,255,.25); background:rgba(255,255,255,.12); box-shadow:0 10px 30px rgba(0,0,0,.45)}
    .egg-note{
      position:fixed; transform:translate(-50%,-12px) scale(.98);
      padding:10px 12px; border-radius:12px; white-space:nowrap;
      background:rgba(0,0,0,.72); color:#f9f9f9; font-size:13px;
      border:1px solid rgba(255,255,255,.18); opacity:0; pointer-events:none;
      transition:opacity .25s ease, transform .28s var(--bezier); z-index:4;
    }
    .egg-spot:hover ~ .egg-note[data-for]:not([hidden]){opacity:1; transform:translate(-50%,-16px) scale(1)}

    /* Egg modal (iframe) — open/close via class toggles for buttery transitions */
    .modal{position:fixed; inset:0; display:grid; place-items:center; z-index:50; pointer-events:none; opacity:0; visibility:hidden; transition:opacity .28s var(--bezier), visibility 0s linear .28s}
    .modal .backdrop{position:absolute; inset:0; background:rgba(0,0,0,.55); backdrop-filter: blur(2px); opacity:0; transition:opacity .28s var(--bezier)}
    .modal .dialog{
      position:relative; width:min(92vw,900px); height:min(80vh,580px);
      border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,.2);
      box-shadow:0 30px 80px rgba(0,0,0,.6); background:#0b0b0b;
      transform:translateY(12px) scale(.98); opacity:0;
      transition:transform .34s var(--bezier), opacity .3s ease;
    }
    .modal .dialog header{position:absolute; top:8px; right:8px; z-index:2}
    .modal .dialog button{background:rgba(255,255,255,.14)}
    .modal .dialog iframe{width:100%; height:100%; display:block; background:#0b0b0b}
    .modal.show{pointer-events:auto; opacity:1; visibility:visible; transition:opacity .28s var(--bezier), visibility 0s}
    .modal.show .backdrop{opacity:1}
    .modal.show .dialog{transform:translateY(0) scale(1); opacity:1}
    .modal.closing{pointer-events:none}
    .modal.closing .backdrop{opacity:0}
    .modal.closing .dialog{transform:translateY(10px) scale(.985); opacity:0}

    /* Footer */
    footer{position:fixed; bottom:10px; left:0; right:0; text-align:center; font-size:12px; color:#c4c4c4; opacity:.8; z-index:2}
  </style>
</head>
<body>
  <!-- Background hero image (edit assets/friend.jpg in /assets/) -->
  <div class="bg" id="bg" style="background-image:url('assets/friend.jpg');"></div>
  <div class="overlay"></div>

  <!-- Header: site name + audio controls -->
  <header>
    <div class="brand"><?= htmlspecialchars($SITE_NAME) ?><span><?= $SITE_DOMAIN ? '' : '' ?></span></div>
    <div class="controls">
      <!-- Primary CTA toggles play/stop -->
      <button id="btnPlay" class="cta" aria-label="Play the visceral screech">
        Make it extra sucky <span class="pulse"></span>
      </button>
      <!-- Mute/Unmute (only shown while playing) -->
      <button id="btnMute" aria-label="Mute/Unmute" hidden>
        <span class="icon" aria-hidden="true"></span>
        <span class="label">Unmute</span>
      </button>
    </div>
  </header>

  <!-- Center-stage copy. Text also acts as a play/stop control. -->
  <main>
    <div>
      <h1>*curls lip* Life&rsquo;s just sooo hard, bro&hellip;</h1>

      <p id="screechToggle" class="lip-text" role="button" tabindex="0" aria-pressed="false">
        <em>Press here to unleash the screech.</em>
      </p>

      <div class="hint">
        (Mobile browsers block autoplay with sound. Tap once and we&rsquo;ll do the rest.)
      </div>
    </div>
  </main>

  <!-- Screech audio (replace assets/screech.mp3 to customize) -->
  <audio id="screech" preload="auto" src="assets/screech.mp3"></audio>

  <footer>© <?= date('Y') ?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></footer>

  <!-- Tears mount point (JS injects .tear nodes here) -->
  <div class="tears" id="tears"></div>

  <!-- Egg modal: iframe loads eggs/egg.php?slug=... -->
  <div class="modal" id="eggModal" aria-hidden="true" aria-label="Inside joke">
    <div class="backdrop" id="modalBackdrop"></div>
    <div class="dialog">
      <header><button id="closeModal" aria-label="Close">✖</button></header>
      <iframe id="eggFrame" src="about:blank" title="Inside joke window" loading="lazy" referrerpolicy="no-referrer"></iframe>
    </div>
  </div>

  <script>
    /* -------------------------------------------------------------
     * Tears: spawn while audio is playing & unmuted
     * ----------------------------------------------------------- */
    const tears = document.getElementById('tears');
    let tearTimer = null;

    function spawnTear(){
      if(!tears) return;
      const t = document.createElement('div');
      t.className = 'tear';

      // Randomized X and fall time for natural look
      const x   = Math.random() * 100;
      const dur = 1.8 + Math.random() * 2.2; // 1.8–4s

      t.style.left = x + 'vw';
      t.style.top  = '-20px';
      t.style.animationDuration = dur + 's';
      t.style.opacity = (0.7 + Math.random() * 0.3).toFixed(2);

      tears.appendChild(t);
      setTimeout(() => t.remove(), dur * 1000);
    }
    function startTears(){
      if(tearTimer) return;
      for(let i=0;i<4;i++) spawnTear();      // seed a few instantly
      tearTimer = setInterval(spawnTear, 220);
    }
    function stopTears(){
      if(tearTimer){ clearInterval(tearTimer); tearTimer = null; }
    }

    /* -------------------------------------------------------------
     * Audio controls (Play/Stop/Mute) + UI sync
     * ----------------------------------------------------------- */
    const audio   = document.getElementById('screech');
    const btnPlay = document.getElementById('btnPlay');
    const btnMute = document.getElementById('btnMute');

    // Tiny haptic nudge on mobile (best effort)
    function haptics(){ try{ if(navigator.vibrate) navigator.vibrate([20,40,20]); }catch(_){} }

    // Keep controls + instruction text in sync with state
    function setUI(playing){
      if(playing){
        btnPlay.innerHTML = 'Stop the suffering';
        btnPlay.setAttribute('aria-label','Stop');
        btnMute.hidden = false;
        btnMute.classList.toggle('muted', audio.muted);
        btnMute.querySelector('.label').textContent = audio.muted ? 'Unmute' : 'Mute';
      } else {
        btnPlay.innerHTML = 'Make it extra sucky <span class="pulse"></span>';
        btnPlay.setAttribute('aria-label','Play');
        btnMute.hidden = true;
      }
      updateLipText();
    }

    // Begin looping; unmute; start tears
    async function playAudio(){
      audio.currentTime = Math.random() * 1.2; // tiny variance keeps it organic
      audio.loop   = true;
      audio.muted  = false;
      await audio.play();                      // might reject if gesture missing
      startTears();
      setUI(true);
    }

    // Stop + reset; stop tears
    function stopAudio(){
      audio.pause();
      audio.currentTime = 0;
      stopTears();
      setUI(false);
    }

    // Tears follow audio state; muted == no tears
    function updateTears(){ (!audio.paused && !audio.muted) ? startTears() : stopTears(); }

    // Header controls
    btnPlay.addEventListener('click', async ()=>{
      try { haptics(); if(audio.paused) await playAudio(); else stopAudio(); }
      catch { /* Autoplay blocked → next user interaction will succeed */ }
    });
    btnMute.addEventListener('click', ()=>{
      audio.muted = !audio.muted;
      btnMute.classList.toggle('muted', audio.muted);
      btnMute.querySelector('.label').textContent = audio.muted ? 'Unmute' : 'Mute';
      updateTears(); updateLipText();
    });

    // Pre-warm autoplay policy silently (harmless if blocked)
    (async()=>{ try{ audio.muted=true; audio.loop=true; await audio.play(); audio.pause(); audio.currentTime=0; }catch(_){}})();

    ['play','pause','volumechange','ended'].forEach(ev => audio.addEventListener(ev, updateTears));

    /* -------------------------------------------------------------
     * Cursor repel: tears are pushed away from pointer
     * ----------------------------------------------------------- */
    const repelRadius = 120;       // px radius where push applies
    const maxPush     = 50;        // px max offset
    let decayTimer;

    function repel(e){
      const els = document.getElementsByClassName('tear');
      for(const el of els){
        const r  = el.getBoundingClientRect();
        const cx = r.left + r.width/2, cy = r.top + r.height/2;
        const dx = cx - e.clientX,     dy = cy - e.clientY;
        const dist = Math.hypot(dx, dy);
        if(dist < repelRadius){
          const s = (1 - dist/repelRadius);     // 0..1 closeness
          const nx = dx / (dist || 1);          // normalized direction
          const ny = dy / (dist || 1);
          const px = Math.max(Math.min(nx * maxPush * s * 1.4,  maxPush), -maxPush);
          const py = Math.max(Math.min(ny * maxPush * s * 1.4,  maxPush), -maxPush);
          el.style.setProperty('--pushX', px.toFixed(1)+'px');
          el.style.setProperty('--pushY', py.toFixed(1)+'px');
        }
      }
      clearTimeout(decayTimer);
      decayTimer = setTimeout(()=>{
        for(const el of document.getElementsByClassName('tear')){
          el.style.setProperty('--pushX','0px');
          el.style.setProperty('--pushY','0px');
        }
      }, 140);
    }
    window.addEventListener('mousemove', repel);
    window.addEventListener('touchmove', ev=>{
      if(ev.touches && ev.touches[0]) repel({clientX:ev.touches[0].clientX, clientY:ev.touches[0].clientY});
    });

    /* -------------------------------------------------------------
     * Modal (egg viewer)
     * ----------------------------------------------------------- */
    const eggModal      = document.getElementById('eggModal');
    const eggFrame      = document.getElementById('eggFrame');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const closeModalBtn = document.getElementById('closeModal');
    let modalOpen = false;

    function openEgg(src){
      if(!src || modalOpen) return;
      modalOpen = true;
      eggFrame.src = src;
      eggModal.classList.remove('closing');
      eggModal.classList.add('show');
      eggModal.setAttribute('aria-hidden','false');
      document.body.style.overflow = 'hidden'; // lock scroll during modal
      haptics();
    }
    function hideEgg(){
      if(!modalOpen) return;
      modalOpen = false;
      eggModal.classList.add('closing');
      eggModal.classList.remove('show');
      eggModal.setAttribute('aria-hidden','true');
      const done = ()=>{
        eggModal.removeEventListener('transitionend', done);
        eggFrame.src = 'about:blank';      // release media/resources
        document.body.style.overflow = ''; // restore scroll
        eggModal.classList.remove('closing');
      };
      eggModal.addEventListener('transitionend', done);
      setTimeout(done, 380); // safety in case transitionend doesn’t fire
    }
    modalBackdrop.addEventListener('click', hideEgg);
    closeModalBtn.addEventListener('click', hideEgg);
    document.addEventListener('keydown', e=>{ if(e.key==='Escape' && eggModal.classList.contains('show')) hideEgg(); });

    /* -------------------------------------------------------------
     * Hotspots: fetch list from eggs/list.php and render markers
     * Each item: { slug, title, caption, pos_left (vw), pos_top (vh) }
     * ----------------------------------------------------------- */
    fetch('eggs/list.php', {cache:'no-store'})
      .then(r=>r.json())
      .then(items=>{
        (items||[])
          .filter(i=>typeof i.pos_left==='number' && typeof i.pos_top==='number')
          .forEach(addHotspot);
      })
      .catch(()=>{/* fail silently; site still usable */});

    function addHotspot(item){
      // Click target
      const spot = document.createElement('div');
      spot.className = 'egg-spot';
      spot.style.left = item.pos_left + 'vw';
      spot.style.top  = item.pos_top  + 'vh';
      spot.title = item.slug;
      spot.dataset.slug = item.slug;
      spot.addEventListener('click', ()=> openEgg('eggs/egg.php?slug='+encodeURIComponent(item.slug)));
      document.body.appendChild(spot);

      // Hover label (optional)
      const note = document.createElement('div');
      note.className = 'egg-note';
      note.dataset.for = item.slug;
      note.style.left = item.pos_left + 'vw';
      note.style.top  = (item.pos_top - 2) + 'vh';
      note.textContent = item.title || item.caption || item.slug;
      document.body.appendChild(note);
    }

    /* -------------------------------------------------------------
     * Deep link: /?egg=slug opens a specific egg after load
     * ----------------------------------------------------------- */
    (function(){
      const eg = new URLSearchParams(location.search).get('egg');
      if(eg) setTimeout(()=> openEgg('eggs/egg.php?slug='+encodeURIComponent(eg)), 300);
    })();

    /* -------------------------------------------------------------
     * Tappable instruction text (secondary play/stop control)
     * ----------------------------------------------------------- */
    const lipText = document.getElementById('screechToggle');

    function toggleScreech(){
      if(audio.paused){
        playAudio().catch(()=>{/* user gesture required; next tap succeeds */});
      } else {
        stopAudio();
      }
    }
    // Click/Touch
    lipText.addEventListener('click', toggleScreech);
    // Keyboard accessible
    lipText.addEventListener('keydown', e=>{
      if(e.key==='Enter' || e.key===' '){ e.preventDefault(); toggleScreech(); }
    });

    // Keep label + animation synced with audio state
    function updateLipText(){
      const playing = !audio.paused && !audio.ended;
      const active  = playing && !audio.muted;
      lipText.classList.toggle('playing', active);
      lipText.setAttribute('aria-pressed', playing ? 'true' : 'false');
      lipText.innerHTML = playing
        ? (audio.muted ? '<em>Muted. Click to stop the screech.</em>' : '<em>Click to silence the screech.</em>')
        : '<em>Press here to unleash the screech.</em>';
    }
    ['play','pause','ended','volumechange'].forEach(ev => audio.addEventListener(ev, updateLipText));
    updateLipText(); // initial paint

    /* -------------------------------------------------------------
     * Visual Editor bridge
     * When loaded as /index.php?from=editor, clicks post exact vw/vh
     * to parent admin/visual.php (no offset surprises).
     * ----------------------------------------------------------- */
    (function(){
      const fromEditor = new URLSearchParams(location.search).get('from') === 'editor';
      if(!fromEditor) return;

      function ping(x,y){
        const d = document.createElement('div');
        d.style.cssText = 'position:fixed;width:14px;height:14px;border-radius:50%;background:rgba(255,204,0,.95);box-shadow:0 6px 20px rgba(0,0,0,.45);transform:translate(-50%,-50%);z-index:61;left:'+x+'px;top:'+y+'px';
        document.body.appendChild(d);
        setTimeout(()=>d.remove(), 450);
      }
      function onPlace(ev){
        const vw = (ev.clientX / window.innerWidth ) * 100;
        const vh = (ev.clientY / window.innerHeight) * 100;
        try { window.parent.postMessage({type:'egg-editor-click', vw, vh}, '*'); } catch(_) {}
        ping(ev.clientX, ev.clientY);
      }
      // Do not intercept modal/buttons to keep UX sane inside editor
      document.addEventListener('click', function(e){
        if(e.target.closest('.modal') || e.target.closest('button')) return;
        onPlace(e);
        e.preventDefault();
        e.stopPropagation();
      }, true);
    })();
  </script>
</body>
</html>
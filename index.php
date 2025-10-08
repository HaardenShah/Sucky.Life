<?php
require __DIR__ . '/admin/config.php';
if($NEEDS_SETUP){
  header('Location: /admin/setup.php'); exit;
}
// $SITE_NAME and $SITE_DOMAIN now available
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($SITE_NAME) ?> — Main</title>
  <meta name="description" content="Inside jokes for the crew. Featuring our guy who swears the universe is out to get him." />
  <style>
    :root{
      --yellow:#ffcc00; --bg:#0e0f12; --fg:#f8f8f8; --dim:#b6b6b6; --accent:#ff4d4d; --bezier:cubic-bezier(.22,.61,.36,1);
    }
    html,body{height:100%} *{box-sizing:border-box}
    body{margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial; color:var(--fg); background:var(--bg); overflow:hidden}
    .bg{position:fixed; inset:0; background:#000 center/cover no-repeat; filter:contrast(1.05) saturate(1.05) brightness(0.9); transform:scale(1.02)}
    .overlay{position:fixed; inset:0; background:radial-gradient(1200px 600px at 50% 10%, transparent, rgba(0,0,0,.55) 60%, rgba(0,0,0,.8)); pointer-events:none}
    header{position:fixed; top:16px; left:16px; right:16px; display:flex; justify-content:space-between; align-items:center; gap:16px; mix-blend-mode:lighten; z-index:5}
    .brand{font-weight:800; letter-spacing:.5px}
    .brand span{color:var(--yellow)}
    .controls{display:flex; gap:8px}
    button{appearance:none; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.06); color:var(--fg); padding:12px 16px; border-radius:14px; font-weight:600; cursor:pointer; backdrop-filter: blur(6px); transition: transform .18s var(--bezier), background .25s ease, border-color .25s ease}
    button:hover{transform:translateY(-1px) scale(1.01); background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.35)}
    button:active{transform:translateY(0) scale(.995)}
    .cta{border-color:var(--yellow); background:rgba(255,204,0,.08)}
    .cta .pulse{display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--yellow); margin-left:8px; box-shadow:0 0 0 0 rgba(255,204,0,.6); animation:pulse 1.6s infinite}
    @keyframes pulse{to{box-shadow:0 0 0 16px rgba(255,204,0,0)}}
    main{position:fixed; inset:0; display:grid; place-items:center; text-align:center; padding:24px; z-index:4}
    h1{font-size:clamp(32px,6vw,68px); margin:0 0 10px; text-shadow:0 6px 30px rgba(0,0,0,.55)}
    .lip{font-size:clamp(16px,2.2vw,22px); color:var(--dim); font-style:italic; display:inline-flex; align-items:center; gap:10px}
    .lip svg{width:44px; height:44px; filter: drop-shadow(0 4px 18px rgba(0,0,0,.45))}
    .hint{margin-top:14px; font-size:14px; color:#cfcfcf; opacity:.8}
    .tears{position:fixed; inset:0; pointer-events:none; z-index:3}
    .tear{position:absolute; width:10px; height:14px; background:linear-gradient(#9cd3ff,#4aa3ff); border-radius:50% 50% 60% 60%; filter:blur(.2px); opacity:.9; animation:fall 2.8s linear infinite; transform:translate(var(--pushX,0px), var(--pushY,0px)); will-change:transform}
    @keyframes fall{to{transform:translateY(110vh) rotate(12deg); opacity:.95}}
    .egg-spot{position:fixed; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,.06); outline:1px dashed rgba(255,255,255,.12); cursor:help; transform:translate(-50%,-50%) scale(.96); transition:transform .28s var(--bezier), outline-color .25s ease, background .25s ease, box-shadow .25s ease; box-shadow:0 6px 20px rgba(0,0,0,.35); z-index:4}
    .egg-spot:hover{transform:translate(-50%,-50%) scale(1.06); outline-color:rgba(255,255,255,.25); background:rgba(255,255,255,.12); box-shadow:0 10px 30px rgba(0,0,0,.45)}
    .egg-note{position:fixed; transform:translate(-50%,-12px) scale(.98); padding:10px 12px; border-radius:12px; white-space:nowrap; background:rgba(0,0,0,.72); color:#f9f9f9; font-size:13px; border:1px solid rgba(255,255,255,.18); opacity:0; pointer-events:none; transition:opacity .25s ease, transform .28s var(--bezier); z-index:4}
    .egg-spot:hover ~ .egg-note[data-for]:not([hidden]){opacity:1; transform:translate(-50%,-16px) scale(1)}
    .modal{position:fixed; inset:0; display:grid; place-items:center; z-index:50; pointer-events:none; opacity:0; visibility:hidden; transition:opacity .28s var(--bezier), visibility 0s linear .28s}
    .modal .backdrop{position:absolute; inset:0; background:rgba(0,0,0,.55); backdrop-filter: blur(2px); opacity:0; transition:opacity .28s var(--bezier)}
    .modal .dialog{position:relative; width:min(92vw, 880px); height:min(80vh, 560px); border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,.2); box-shadow:0 30px 80px rgba(0,0,0,.6); transform:translateY(12px) scale(.98); opacity:0; transition:transform .34s var(--bezier), opacity .3s ease; background:#0b0b0b}
    .modal .dialog header{position:absolute; top:8px; right:8px; z-index:2}
    .modal .dialog button{background:rgba(255,255,255,.14)}
    .modal .dialog iframe{width:100%; height:100%; display:block; background:#0b0b0b}
    .modal.show{pointer-events:auto; opacity:1; visibility:visible; transition:opacity .28s var(--bezier), visibility 0s}
    .modal.show .backdrop{opacity:1}
    .modal.show .dialog{transform:translateY(0) scale(1); opacity:1}
    .modal.closing{pointer-events:none}
    .modal.closing .backdrop{opacity:0}
    .modal.closing .dialog{transform:translateY(10px) scale(.985); opacity:0}
    footer{position:fixed; bottom:10px; left:0; right:0; text-align:center; font-size:12px; color:#c4c4c4; opacity:.8; z-index:2}
  </style>
</head>
<body>
  <div class="bg" id="bg" style="background-image:url('assets/friend.jpg');"></div>
  <div class="overlay"></div>

  <header>
    <div class="brand"><?= htmlspecialchars($SITE_NAME) ?><span><?= $SITE_DOMAIN ? '' : '' ?></span></div>
    <div class="controls">
      <button id="btnPlay" class="cta" aria-label="Play the visceral screech">Make it extra sucky <span class="pulse"></span></button>
      <button id="btnMute" aria-label="Mute/Unmute" hidden>🔊 Unmute</button>
    </div>
  </header>

  <main>
    <div>
      <h1>*curls lip* “Life’s just sooo hard, bro…”</h1>
      <div class="lip" aria-live="polite" aria-atomic="true">
        <svg viewBox="0 0 128 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M8 40 Q64 8 120 40 Q64 56 8 40Z" fill="#ff7aa2" stroke="#000" stroke-opacity=".25"/>
        </svg>
        <span id="statusText">Press the button to unleash the screech.</span>
      </div>
      <div class="hint">(Mobile browsers block autoplay with sound. Tap once and we’ll do the rest.)</div>
    </div>
  </main>

  <audio id="screech" preload="auto" src="assets/screech.mp3"></audio>

  <footer>© <?=date('Y')?> <?= htmlspecialchars($SITE_DOMAIN ?: $SITE_NAME) ?></footer>

  <div class="tears" id="tears"></div>

  <div class="modal" id="eggModal" aria-hidden="true" aria-label="Inside joke">
    <div class="backdrop" id="modalBackdrop"></div>
    <div class="dialog">
      <header><button id="closeModal" aria-label="Close">✖</button></header>
      <iframe id="eggFrame" src="about:blank" title="Inside joke window" loading="lazy" referrerpolicy="no-referrer"></iframe>
    </div>
  </div>

  <script>
    // Tears control (spawn only when audio playing & unmuted)
    const tears = document.getElementById('tears'); let tearTimer=null;
    function spawnTear(){ if(!tears) return; const t=document.createElement('div'); t.className='tear'; const x=Math.random()*100, d=1.8+Math.random()*2.2; t.style.left=x+'vw'; t.style.top='-20px'; t.style.animationDuration=d+'s'; t.style.opacity=0.7+Math.random()*0.3; tears.appendChild(t); setTimeout(()=>t.remove(), d*1000); }
    function startTears(){ if(tearTimer) return; for(let i=0;i<4;i++) spawnTear(); tearTimer=setInterval(spawnTear,220); }
    function stopTears(){ if(tearTimer){ clearInterval(tearTimer); tearTimer=null; } }

    // Audio: Play/Stop toggle + mute
    const audio = document.getElementById('screech'); const btnPlay=document.getElementById('btnPlay'); const btnMute=document.getElementById('btnMute'); const statusText=document.getElementById('statusText');
    function haptics(){ try{ if(navigator.vibrate) navigator.vibrate([20,40,20]); }catch(_){} }
    function setUI(playing){ if(playing){ btnPlay.innerHTML='Stop the suffering'; btnPlay.setAttribute('aria-label','Stop'); btnMute.hidden=false; btnMute.textContent=audio.muted?'🔊 Unmute':'🔇 Mute'; statusText.textContent=audio.muted?'Muted. (Coward.)':'Visceral screech engaged. (You asked for this.)'; } else { btnPlay.innerHTML='Make it extra sucky <span class="pulse"></span>'; btnPlay.setAttribute('aria-label','Play'); btnMute.hidden=true; statusText.textContent='Press the button to unleash the screech.'; } }
    async function playAudio(){ audio.currentTime=Math.random()*1.2; audio.loop=true; audio.muted=false; await audio.play(); startTears(); setUI(true); }
    function stopAudio(){ audio.pause(); audio.currentTime=0; stopTears(); setUI(false); }
    function updateTears(){ (!audio.paused && !audio.muted) ? startTears() : stopTears(); }
    btnPlay.addEventListener('click', async ()=>{ try{ if(audio.paused){ haptics(); await playAudio(); } else { haptics(); stopAudio(); } }catch{ statusText.textContent='Tap once more to allow audio (your browser blocked autoplay).'; } });
    btnMute.addEventListener('click', ()=>{ audio.muted=!audio.muted; btnMute.textContent=audio.muted?'🔊 Unmute':'🔇 Mute'; statusText.textContent=audio.muted?'Muted. (Coward.)':'Visceral screech engaged.'; updateTears(); });
    (async()=>{ try{ audio.muted=true; audio.loop=true; await audio.play(); audio.pause(); audio.currentTime=0; }catch(_){}})();
    ['play','pause','volumechange','ended'].forEach(ev=> audio.addEventListener(ev, updateTears));

    // Modal animations
    const eggModal=document.getElementById('eggModal'), eggFrame=document.getElementById('eggFrame'), modalBackdrop=document.getElementById('modalBackdrop'), closeModal=document.getElementById('closeModal'); let modalOpen=false;
    function openEgg(src){ if(!src || modalOpen) return; modalOpen=true; eggFrame.src=src; eggModal.classList.remove('closing'); eggModal.classList.add('show'); eggModal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; haptics(); }
    function hideEgg(){ if(!modalOpen) return; modalOpen=false; eggModal.classList.add('closing'); eggModal.classList.remove('show'); eggModal.setAttribute('aria-hidden','true'); const done=()=>{ eggModal.removeEventListener('transitionend',done); eggFrame.src='about:blank'; document.body.style.overflow=''; eggModal.classList.remove('closing'); }; eggModal.addEventListener('transitionend',done); setTimeout(done,380); }
    modalBackdrop.addEventListener('click', hideEgg); closeModal.addEventListener('click', hideEgg); document.addEventListener('keydown', e=>{ if(e.key==='Escape' && eggModal.classList.contains('show')) hideEgg(); });

    // Tear/cursor repel
    const repelRadius=120, maxPush=50; let decayTimer;
    function repel(e){ const els=document.getElementsByClassName('tear'); for(const el of els){ const r=el.getBoundingClientRect(); const cx=r.left+r.width/2, cy=r.top+r.height/2; const dx=cx-e.clientX, dy=cy-e.clientY, dist=Math.hypot(dx,dy); if(dist<repelRadius){ const s=(1-dist/repelRadius); const px=Math.max(Math.min((dx/dist)*maxPush*s*1.4, maxPush), -maxPush); const py=Math.max(Math.min((dy/dist)*maxPush*s*1.4, maxPush), -maxPush); el.style.setProperty('--pushX', px.toFixed(1)+'px'); el.style.setProperty('--pushY', py.toFixed(1)+'px'); } } clearTimeout(decayTimer); decayTimer=setTimeout(()=>{ for(const el of document.getElementsByClassName('tear')){ el.style.setProperty('--pushX','0px'); el.style.setProperty('--pushY','0px'); } },140); }
    window.addEventListener('mousemove', repel); window.addEventListener('touchmove', ev=>{ if(ev.touches && ev.touches[0]) repel({clientX:ev.touches[0].clientX, clientY:ev.touches[0].clientY}); });

    // Deep link auto-open (?egg=slug)
    (function(){ const p=new URLSearchParams(location.search); const eg=p.get('egg'); if(eg){ setTimeout(()=>openEgg('eggs/egg.php?slug='+encodeURIComponent(eg)), 300);} })();

    // Dynamic hotspots
    fetch('eggs/list.php',{cache:'no-store'}).then(r=>r.json()).then(items=>{
      items.filter(i=>typeof i.pos_left==='number' && typeof i.pos_top==='number').forEach(addHotspot);
    }).catch(()=>{});
    function addHotspot(item){
      const spot=document.createElement('div'); spot.className='egg-spot'; spot.style.left=item.pos_left+'vw'; spot.style.top=item.pos_top+'vh'; spot.title=item.slug; spot.dataset.slug=item.slug;
      spot.addEventListener('click', ()=>openEgg('eggs/egg.php?slug='+encodeURIComponent(item.slug))); document.body.appendChild(spot);
      const note=document.createElement('div'); note.className='egg-note'; note.dataset.for=item.slug; note.style.left=item.pos_left+'vw'; note.style.top=(item.pos_top-2)+'vh'; note.textContent=item.title||item.caption||item.slug; document.body.appendChild(note);
    }

    // --- Editor-mode: only when loaded with ?from=editor ---
  (function(){
    const fromEditor = new URLSearchParams(location.search).get('from') === 'editor';
    if(!fromEditor) return;

    // Subtle hint overlay (doesn't affect layout)
    const cross = document.createElement('div');
    cross.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:60';
    document.body.appendChild(cross);

    function ping(x,y){
      const dot = document.createElement('div');
      dot.style.cssText = 'position:fixed;width:14px;height:14px;border-radius:50%;background:rgba(255,204,0,.95);box-shadow:0 6px 20px rgba(0,0,0,.45);transform:translate(-50%,-50%);z-index:61';
      dot.style.left = x + 'px'; dot.style.top = y + 'px';
      document.body.appendChild(dot);
      setTimeout(()=> dot.remove(), 450);
    }

    function onPlace(ev){
      // Prefer client coords (viewport), then compute vw/vh from window size
      const x = ev.clientX, y = ev.clientY;
      const vw = (x / window.innerWidth) * 100;
      const vh = (y / window.innerHeight) * 100;

      // Tell parent admin overlay
      try{ window.parent.postMessage({ type:'egg-editor-click', vw, vh }, '*'); }catch(_){}

      // Visual feedback
      ping(x,y);
    }

    // Capture click anywhere (avoid swallowing regular clicks if needed)
    document.addEventListener('click', function(e){
      // Don’t hijack modal/close buttons while in editor
      if(e.target.closest('.modal') || e.target.closest('button')) return;
      onPlace(e);
      e.preventDefault();
      e.stopPropagation();
    }, true);
  })();
  </script>
</body>
</html>
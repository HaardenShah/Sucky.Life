/**
 * /assets/site.js — Homepage logic (CSP-safe)
 * - Screech audio + tears
 * - Modal (egg iframe)
 * - Robust hotspots with diagnostics
 * - Coerces egg positions (strings → numbers) so hotspots always render
 */

(function(){
  // ---- Elements
  const audio    = document.getElementById('screech');
  const btnPlay  = document.getElementById('btnPlay');
  const btnMute  = document.getElementById('btnMute');
  const tears    = document.getElementById('tears');
  const lipText  = document.getElementById('screechToggle');

  const eggModal = document.getElementById('eggModal');
  const eggFrame = document.getElementById('eggFrame');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const closeModalBtn = document.getElementById('closeModal');

  /* ---------- tiny debug HUD ---------- */
  function hud(msg){
    let h = document.getElementById('eggHUD');
    if(!h){
      h = document.createElement('div');
      h.id = 'eggHUD';
      Object.assign(h.style, {
        position:'fixed', top:'10px', left:'50%', transform:'translateX(-50%)',
        zIndex:'100', background:'rgba(15,23,42,.9)', color:'#e2e8f0',
        padding:'6px 10px', border:'1px solid #334155', borderRadius:'10px',
        font:'600 12px system-ui, -apple-system, Segoe UI, Inter, Arial'
      });
      document.body.appendChild(h);
      setTimeout(()=>{ if(h) h.remove(); }, 4000);
    }
    h.textContent = msg;
  }

  // ==============================
  // Screech + tears
  // ==============================
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
      btnPlay.textContent = 'Stop the suffering';
      btnMute.hidden = false;
      btnMute.classList.toggle('muted', audio.muted);
      btnMute.querySelector('.label').textContent = audio.muted ? 'Unmute' : 'Mute';
    } else {
      btnPlay.innerHTML = 'Make it extra sucky <span class="pulse"></span>';
      btnMute.hidden = true;
    }
    updateLipText();
  }
  async function playAudio(){
    audio.currentTime = Math.random()*1.2;
    audio.loop = true; audio.muted = false;
    try{ await audio.play(); }catch(e){ /* mobile autoplay gate */ }
    startTears(); setUI(true);
  }
  function stopAudio(){ audio.pause(); audio.currentTime = 0; stopTears(); setUI(false); }
  function updateTears(){ (!audio.paused && !audio.muted) ? startTears() : stopTears(); }

  btnPlay.addEventListener('click', async ()=>{ if(audio.paused){ await playAudio(); } else { stopAudio(); } });
  btnMute.addEventListener('click', ()=>{ audio.muted = !audio.muted; btnMute.classList.toggle('muted', audio.muted); btnMute.querySelector('.label').textContent = audio.muted?'Unmute':'Mute'; updateTears(); updateLipText(); });

  // prewarm: some browsers need a user gesture later; this keeps it quiet and ready
  (async()=>{ try{ audio.muted=true; audio.loop=true; await audio.play(); audio.pause(); audio.currentTime=0; }catch(_){}})();
  ['play','pause','volumechange','ended'].forEach(ev => audio.addEventListener(ev, updateTears));

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

  // ==============================
  // Modal (egg iframe)
  // ==============================
  let modalOpen = false;

  function openEgg(src){
    if(!src || modalOpen) return;
    modalOpen = true;

    console.debug('[eggs] opening', src);
    eggFrame.src = src;

    eggModal.classList.remove('closing'); eggModal.classList.add('show'); eggModal.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';

    const slow = setTimeout(()=>console.warn('[eggs] iframe slow to load →', src), 2000);
    eggFrame.onload = ()=> clearTimeout(slow);
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

  // ==============================
  // Hotspots (robust)
  // ==============================
  fetch('eggs/list.php?ts=' + Date.now(), { cache:'no-store' })
    .then(r=>{
      if(!r.ok){
        console.error('[eggs] list fetch failed', r.status);
        hud('eggs: list fetch failed ' + r.status);
        return [];
      }
      return r.json().catch(()=>{
        console.error('[eggs] list parse error');
        hud('eggs: list parse error');
        return [];
      });
    })
    .then(items=>{
      console.debug('[eggs] raw items:', items);
      if(!Array.isArray(items) || !items.length){
        console.warn('[eggs] no eggs returned from list.php (draft? missing pos?)');
        hud('0 eggs loaded');
        return;
      }
      let count = 0;
      for(const it of items){
        const left = Number(it.pos_left);
        const top  = Number(it.pos_top);
        if (Number.isFinite(left) && Number.isFinite(top)){
          addHotspot({ slug: String(it.slug||''), title: it.title||it.caption||it.slug||'', pos_left: left, pos_top: top });
          count++;
        } else {
          console.warn('[eggs] skipped invalid pos for', it.slug, it.pos_left, it.pos_top);
        }
      }
      console.debug('[eggs] hotspots ready (count):', count);
      hud(count + ' egg' + (count===1?'':'s') + ' loaded');
    })
    .catch(err=>{
      console.error('[eggs] list error', err);
      hud('eggs: list error');
    });

  function addHotspot(item){
    const spot = document.createElement('div');
    spot.className = 'egg-spot';
    spot.style.left = (item.pos_left)+'vw';
    spot.style.top  = (item.pos_top)+'vh';
    spot.title = item.slug;
    spot.dataset.slug = item.slug;
    spot.style.pointerEvents = 'auto';
    spot.style.cursor = 'help';
    spot.setAttribute('role','button');
    spot.setAttribute('tabindex','0');

    spot.addEventListener('click', (ev)=>{
      ev.preventDefault(); ev.stopPropagation();
      openEgg('eggs/egg.php?slug='+encodeURIComponent(item.slug));
    });
    spot.addEventListener('keydown', (e)=>{
      if(e.key==='Enter' || e.key===' '){
        e.preventDefault();
        openEgg('eggs/egg.php?slug='+encodeURIComponent(item.slug));
      }
    });

    document.body.appendChild(spot);

    const note = document.createElement('div');
    note.className='egg-note';
    note.dataset.for = item.slug;
    note.style.left = (item.pos_left)+'vw';
    note.style.top  = (item.pos_top - 2)+'vh';
    note.textContent = item.title || item.slug;
    note.style.pointerEvents = 'none';
    document.body.appendChild(note);
  }

  // Delegated click as safety net (capture phase)
  document.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('.egg-spot');
    if (!btn) return;
    ev.preventDefault();
    const slug = btn.dataset.slug;
    if (!slug) return console.warn('[eggs] .egg-spot missing slug dataset');
    openEgg('eggs/egg.php?slug='+encodeURIComponent(slug));
  }, true);

  // ==============================
  // Cursor repel on tears
  // ==============================
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

})();
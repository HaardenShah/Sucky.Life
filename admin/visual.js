// admin/visual.js — CSP-safe Visual Editor logic

(function(){
  function qs(id){ return document.getElementById(id); }

  const root   = document.getElementById('editorRoot');
  const slug   = root?.dataset.slug || '';
  const csrf   = root?.dataset.csrf || '';
  const initL  = root?.dataset.left;
  const initT  = root?.dataset.top;

  const hit    = qs('hit');
  const dot    = qs('dot');
  const btnSave= qs('btnSave');
  const coord  = qs('coord');
  const toast  = qs('toast');

  if (!hit || !dot || !btnSave || !coord) return;

  let pendingVW = null, pendingVH = null;

  // Preload any existing position
  if (initL !== '' && initT !== '' && !isNaN(parseFloat(initL)) && !isNaN(parseFloat(initT))) {
    pendingVW = parseFloat(initL);
    pendingVH = parseFloat(initT);
    placeDot(pendingVW, pendingVH);
    btnSave.disabled = false;
    coord.textContent = `vw: ${pendingVW.toFixed(2)}, vh: ${pendingVH.toFixed(2)}`;
  }

  // Make overlay guaranteed hit-testable, just in case CSS is altered
  hit.style.background = 'rgba(0,0,0,0.001)';

  function placeDot(vw, vh){
    const px = window.innerWidth  * vw / 100;
    const py = window.innerHeight * vh / 100;
    dot.style.left = px + 'px';
    dot.style.top  = py + 'px';
    dot.style.display = 'block';
  }

  function pingAt(px, py){
    const r = document.createElement('div');
    r.className = 'ping';
    r.style.left = px + 'px';
    r.style.top  = py + 'px';
    document.body.appendChild(r);
    r.addEventListener('animationend', ()=> r.remove());
  }

  function getXY(evt){
    if (typeof evt.clientX === 'number') return {x:evt.clientX, y:evt.clientY};
    if (evt.touches && evt.touches[0])   return {x:evt.touches[0].clientX, y:evt.touches[0].clientY};
    return {x:0, y:0};
  }

  function showToast(msg){
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(()=> toast.classList.remove('show'), 900);
  }

  function handlePlace(evt){
    const p  = getXY(evt);
    const vw = (p.x / window.innerWidth)  * 100;
    const vh = (p.y / window.innerHeight) * 100;
    pendingVW = vw; pendingVH = vh;
    placeDot(vw, vh);
    pingAt(p.x, p.y);
    btnSave.disabled = false;
    coord.textContent = `vw: ${vw.toFixed(2)}, vh: ${vh.toFixed(2)}`;
    showToast('tap registered');
    evt.preventDefault();
  }

  // Attach multiple listeners to be extra-safe
  hit.addEventListener('pointerdown', handlePlace, {passive:false});
  hit.addEventListener('click',       handlePlace, {passive:false});
  hit.addEventListener('mousedown',   handlePlace, {passive:false});
  hit.addEventListener('touchstart',  handlePlace, {passive:false});

  // Save handler
  btnSave.addEventListener('click', ()=>{
    if (pendingVW==null || pendingVH==null) return;
    btnSave.disabled = true;
    const body = new URLSearchParams({
      slug: slug,
      pos_left: pendingVW,
      pos_top:  pendingVH,
      csrf: csrf
    });
    fetch('/admin/save.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body
    }).then(r=>r.json())
     .then(j=>{
        if (j && j.ok) {
          dot.classList.remove('pop'); void dot.offsetWidth; dot.classList.add('pop');
          showToast('position saved');
        } else {
          alert('Save failed. Please try again.');
        }
     })
     .catch(()=> alert('Network error saving position.'))
     .finally(()=>{ btnSave.disabled = false; });
  });

  // On load, signal ready (useful if CSP blocked inline JS before)
  showToast('editor ready');
})();

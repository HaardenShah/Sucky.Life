// admin/admin.js (CSP-safe; no inline handlers)
(function(){
  'use strict';

  const $  = (s)=>document.querySelector(s);
  const $$ = (s)=>Array.from(document.querySelectorAll(s));
  const CSRF = document.body.getAttribute('data-csrf') || '';

  function showToast(m){ console.log('[admin]', m); }

  async function jsonFetch(url, opts = {}) {
    try {
      const r = await fetch(url, opts);
      const status = r.status;
      let data = null, text = '';
      try { data = await r.json(); }
      catch { text = await r.text(); }
      const ok = !!(data && typeof data === 'object' && (data.ok === true || data.success === true));
      return ok ? { ok:true, data, status } : { ok:false, data, error:(data && (data.error||data.message)) || text || 'Request failed', status };
    } catch (e) {
      return { ok:false, error:String(e||'Network error'), status:0 };
    }
  }

  /* ---------- New Egg ---------- */
  function createNewEgg(){
    try{
      const title = window.prompt('Title for the new egg?');
      if (!title) return;

      const csrf = (document.body.getAttribute('data-csrf') ||
        document.querySelector('input[name=csrf]')?.value || '').trim();
      if (!csrf) {
        alert('Missing CSRF token. Try refreshing the page and logging in again.');
        return;
      }

      const fd = new FormData();
      fd.set('slug','');
      fd.set('title', title);
      fd.set('caption','');
      fd.set('alt','');
      fd.set('body','');
      fd.set('draft','1');
      fd.set('csrf', csrf);

      const btn = $('#btnNew'); if (btn) btn.disabled = true;

      fetch('/admin/save.php', { method:'POST', body: fd })
        .then(r => r.json().catch(()=>({ok:false,error:'Invalid JSON from server'})))
        .then(j => {
          if (j && j.ok && j.slug) {
            location.href = '?slug=' + encodeURIComponent(j.slug);
          } else {
            alert('Could not create egg.' + (j && j.error ? '\n\nDetails: ' + j.error : ''));
          }
        })
        .catch(err => alert('Error creating egg.\n\n' + err))
        .finally(()=>{ if (btn) btn.disabled = false; });

    } catch (e) {
      alert('New egg failed.\n\n' + e);
    }
  }

  /* ---------- Previews ---------- */
  const fldImage=$('#fldImage'), fldAudio=$('#fldAudio'), fldVideo=$('#fldVideo');
  const prevImage=$('#prevImage'), prevAudio=$('#prevAudio'), prevVideo=$('#prevVideo');

  function updatePreview(kind){
    if(kind==='image'){ prevImage && (prevImage.innerHTML = fldImage?.value ? `<img src="${fldImage.value}" alt="preview">` : ''); }
    if(kind==='audio'){ prevAudio && (prevAudio.innerHTML = fldAudio?.value ? `<audio controls src="${fldAudio.value}"></audio>` : ''); }
    if(kind==='video'){ prevVideo && (prevVideo.innerHTML = fldVideo?.value ? `<video controls src="${fldVideo.value}"></video>` : ''); }
  }

  function wireDrop(zoneSel, inputSel){
    const zone=$(zoneSel), input=$(inputSel);
    if(!zone || !input) return;
    ['dragenter','dragover'].forEach(ev=> zone.addEventListener(ev, e=>{
      e.preventDefault(); e.dataTransfer.dropEffect='copy'; zone.classList.add('drag');
    }));
    ['dragleave','drop'].forEach(ev=> zone.addEventListener(ev, e=>{
      e.preventDefault(); zone.classList.remove('drag');
    }));
    zone.addEventListener('drop', e=>{
      const f=e.dataTransfer.files?.[0]; if(!f) return;
      input.files=e.dataTransfer.files;
      input.dispatchEvent(new Event('change',{bubbles:true}));
    });
  }

  /* ---------- Media Picker ---------- */
  let pickTarget=null;
  const picker=$('#picker'), pickerGrid=$('#pickerGrid'), pickType=$('#pickType');

  async function loadMedia(kind){
    if (!pickerGrid) return;
    pickerGrid.innerHTML='<div class="muted">Loading...</div>';
    const r = await jsonFetch('/admin/media_list.php?kind='+encodeURIComponent(kind||'all'));
    if(!r.ok || !r.data){ pickerGrid.innerHTML='<div class="danger">Failed to load uploads.</div>'; return; }

    const items = r.data.files || [];
    if(!items.length){ pickerGrid.innerHTML='<div class="muted">Nothing here yet.</div>'; return; }

    pickerGrid.innerHTML='';
    for(const it of items){
      const type=it.type||'file', url=it.url, name=it.name||url.split('/').pop();
      const t=document.createElement('div'); t.className='tile';
      t.innerHTML = `
        <div class="thumb">
          ${ type==='image' ? `<img src="${url}" alt="">` :
             type==='audio' ? `<div class="muted">🎵 Audio</div>` :
             type==='video' ? `<div class="muted">🎬 Video</div>` :
                              `<div class="muted">File</div>` }
        </div>
        <div class="meta"><span title="${name}">${name}</span><span>${type}</span></div>
      `;
      t.addEventListener('click', ()=>{
        if(pickTarget==='image' && fldImage){ fldImage.value=url; updatePreview('image'); }
        if(pickTarget==='audio' && fldAudio){ fldAudio.value=url; updatePreview('audio'); }
        if(pickTarget==='video' && fldVideo){ fldVideo.value=url; updatePreview('video'); }
        picker?.classList.remove('show'); picker?.setAttribute('aria-hidden','true');
      });
      pickerGrid.appendChild(t);
    }
  }

  /* ---------- Save (AJAX) ---------- */
  async function onSave(e){
    e.preventDefault();
    const form = e.currentTarget;
    const btn  = $('#btnSave');
    if (btn) btn.disabled = true;

    const fd=new FormData(form);
    if (!fd.get('csrf') && CSRF) fd.set('csrf', CSRF);

    const r = await jsonFetch('/admin/save.php', { method:'POST', body:fd });

    if (btn) btn.disabled = false;

    if (r.ok) {
      const newSlug = (r.data && r.data.slug) || fd.get('slug');
      showToast('Saved ✓');
      const currentSlug = JSON.parse(document.getElementById('__currentSlug').textContent);
      if (newSlug && newSlug !== currentSlug) {
        location.href='?slug='+encodeURIComponent(newSlug);
      } else {
        location.reload();
      }
    } else {
      alert('Save failed.' + (r.error ? ('\n\nDetails: ' + r.error) : ''));
    }
  }

  /* ---------- Rename ---------- */
  async function onRename(){
    const current = JSON.parse(document.getElementById('__currentSlug').textContent);
    const next = prompt('New slug (lowercase, spaces → dashes):', current);
    if (!next || next === current) return;

    const body = new URLSearchParams({ slug: current, new_slug: next, csrf: CSRF });
    const r = await jsonFetch('/admin/rename.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body
    });

    if (r.ok && r.data) {
      const target = r.data.slug || next;
      location.href='?slug='+encodeURIComponent(target);
    } else {
      alert((r && r.error) ? r.error : 'Rename failed.');
    }
  }

  /* ---------- Delete ---------- */
  async function onDelete(){
    const current = JSON.parse(document.getElementById('__currentSlug').textContent);
    if (!current) return;

    if (!confirm(`Delete "${current}"? This cannot be undone.`)) return;

    const body = new URLSearchParams({ slug: current, csrf: CSRF });
    const r = await jsonFetch('/admin/delete.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body
    });

    if (r.ok) {
      const first = (document.querySelector('#eggList li[data-slug]')?.getAttribute('data-slug')) || '';
      location.href = first ? ('?slug='+encodeURIComponent(first)) : location.pathname;
    } else {
      alert((r && r.error) ? r.error : 'Delete failed.');
    }
  }

  /* ---------- Search + Draft filter ---------- */
  function wireSearchAndFilter(){
    const list = $('#eggList');
    const search = $('#eggSearch');
    const draftsOnly = $('#filterDrafts');
    if (!list || !search || !draftsOnly) return;

    function applyFilter(){
      const q = (search.value || '').toLowerCase().trim();
      const wantDraftsOnly = draftsOnly.checked;

      $$('#eggList li[data-slug]').forEach(li=>{
        const title = (li.getAttribute('data-title') || '').toLowerCase();
        const slug  = (li.getAttribute('data-slug') || '').toLowerCase();
        const isDraft = li.getAttribute('data-draft') === '1';

        const matchText = !q || title.includes(q) || slug.includes(q);
        const matchDraft = !wantDraftsOnly || isDraft;

        li.style.display = (matchText && matchDraft) ? '' : 'none';
      });
    }

    search.addEventListener('input', applyFilter);
    draftsOnly.addEventListener('change', applyFilter);
    applyFilter();
  }

  /* ---------- Boot ---------- */
  document.addEventListener('DOMContentLoaded', ()=>{
    // expose current slug for JS (CSP-safe: JSON inside element)
    let currentSlugScript = document.getElementById('__currentSlug');
    if (!currentSlugScript) {
      currentSlugScript = document.createElement('script');
      currentSlugScript.type = 'application/json';
      currentSlugScript.id = '__currentSlug';
      currentSlugScript.textContent = JSON.stringify(<?= json_encode($slug ?? '') ?>);
      // injected into head keeps it simple; but any place is fine
      document.head.appendChild(currentSlugScript);
    }

    $('#btnNew')?.addEventListener('click', createNewEgg);

    // Previews
    $('#clearImage')?.addEventListener('click', ()=>{ if(fldImage){ fldImage.value=''; updatePreview('image'); } });
    $('#clearAudio')?.addEventListener('click', ()=>{ if(fldAudio){ fldAudio.value=''; updatePreview('audio'); } });
    $('#clearVideo')?.addEventListener('click', ()=>{ if(fldVideo){ fldVideo.value=''; updatePreview('video'); } });
    fldImage?.addEventListener('change', ()=>updatePreview('image'));
    fldAudio?.addEventListener('change', ()=>updatePreview('audio'));
    fldVideo?.addEventListener('change', ()=>updatePreview('video'));
    $('#upImage')?.addEventListener('change', e=>{ if(e.target.files?.[0]){ fldImage.value=''; updatePreview('image'); }});
    $('#upAudio')?.addEventListener('change', e=>{ if(e.target.files?.[0]){ fldAudio.value=''; updatePreview('audio'); }});
    $('#upVideo')?.addEventListener('change', e=>{ if(e.target.files?.[0]){ fldVideo.value=''; updatePreview('video'); }});

    // Drag&drop
    wireDrop('#dropImage','#upImage');
    wireDrop('#dropAudio','#upAudio');
    wireDrop('#dropVideo','#upVideo');

    // Media picker
    $$('[data-pick]').forEach(btn=> btn.addEventListener('click', ()=>{
      pickTarget=btn.getAttribute('data-pick');
      picker?.classList.add('show');
      picker?.setAttribute('aria-hidden','false');
      loadMedia(pickTarget);
    }));
    $('#pickClose')?.addEventListener('click', ()=>{ picker?.classList.remove('show'); picker?.setAttribute('aria-hidden','true'); });
    $('.modal .backdrop')?.addEventListener('click', ()=>{ picker?.classList.remove('show'); picker?.setAttribute('aria-hidden','true'); });
    pickType?.addEventListener('change', ()=> loadMedia(pickType.value));

    // Save / Rename / Delete
    $('#eggForm')?.addEventListener('submit', onSave);
    $('#btnRename')?.addEventListener('click', onRename);
    $('#btnDelete')?.addEventListener('click', onDelete);

    // Search/filter
    wireSearchAndFilter();
  });
})();

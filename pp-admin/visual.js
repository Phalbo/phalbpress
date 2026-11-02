/* Modifica visuale della home: pannello a destra, anteprima a sinistra. */
(() => {
  const root = document.querySelector('[data-vis]');
  if (!root) return;
  const base = root.dataset.base;
  const csrf = root.dataset.csrf;
  const frame = root.querySelector('[data-vis-frame]');
  const panel = root.querySelector('[data-vis-panel]');
  const list = root.querySelector('[data-vis-list]');
  const status = root.querySelector('[data-vis-status]');
  const origin = location.origin;
  let lang = root.dataset.lang;
  const pageSel = root.parentElement.querySelector('[data-vis-page]');
  let path = '/' + lang + '/';
  let current = 0;
  let scrollY = 0;
  let scrollToCurrent = false;

  const say = (msg, error) => { status.textContent = msg; status.classList.toggle('flash--error', !!error); };
  const post = async (path, data = {}) => {
    const fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) Object.entries(data).forEach(([k, v]) => (Array.isArray(v) ? v.forEach((x) => fd.append(k + '[]', x)) : fd.append(k, v)));
    fd.set('csrf', csrf);
    const r = await fetch(base + '/' + path, { method: 'POST', body: fd, credentials: 'same-origin' });
    const j = await r.json().catch(() => ({ error: 'Risposta non valida (' + r.status + ')' }));
    if (!r.ok || j.error) throw new Error(j.error || 'Errore ' + r.status);
    return j;
  };
  const html = async (path) => {
    const r = await fetch(base + '/' + path, { credentials: 'same-origin' });
    if (!r.ok) throw new Error('Errore ' + r.status);
    return r.text();
  };

  /* ---------- anteprima ---------- */
  const isHome = () => path === '/' + lang + '/';
  const homeTools = root.querySelector('[data-vis-home]');
  const pageHint = root.querySelector('[data-vis-hint-page]');
  const loadFrame = () => {
    if (homeTools) homeTools.hidden = !isHome();
    if (pageHint) pageHint.hidden = isHome();
    frame.src = root.dataset.preview + '?edit=1&path=' + encodeURIComponent(path) + '&t=' + Date.now();
  };
  const toFrame = (msg) => frame.contentWindow && frame.contentWindow.postMessage(Object.assign({ pp: true }, msg), origin);
  window.addEventListener('message', (ev) => {
    if (ev.origin !== origin || !ev.data || !ev.data.pp) return;
    if (ev.data.type === 'ready') {
      if (scrollToCurrent && current) toFrame({ type: 'select', id: current, scroll: true });
      else { toFrame({ type: 'scroll', y: scrollY }); if (current) toFrame({ type: 'select', id: current }); }
      scrollToCurrent = false;
    }
    if (ev.data.type === 'scroll') scrollY = ev.data.y;
    if (ev.data.type === 'select') open(ev.data.id);
    if (ev.data.type === 'part') openPart(ev.data);
  });

  root.parentElement.querySelectorAll('[data-device]').forEach((b) => b.addEventListener('click', () => {
    root.parentElement.querySelectorAll('[data-device]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    root.classList.toggle('vis--phone', b.dataset.device === 'phone');
  }));
  const langSel = root.parentElement.querySelector('[data-vis-lang]');
  if (langSel) langSel.addEventListener('change', () => {
    // stessa pagina nell'altra lingua: la home sì, le altre si riaprono dall'elenco (gli indirizzi cambiano con la lingua)
    location.search = '?lang=' + encodeURIComponent(langSel.value);
  });
  if (pageSel) pageSel.addEventListener('change', () => {
    path = pageSel.value; scrollY = 0; current = 0;
    panel.querySelectorAll('[data-vis-form],[data-vis-layout]').forEach((f) => f.remove());
    loadFrame();
  });

  /* ---------- elenco delle fasce ---------- */
  const refreshList = async () => {
    list.innerHTML = await html('list');
    list.querySelectorAll('.vis-item').forEach((li) => li.classList.toggle('is-current', Number(li.dataset.id) === current));
  };
  const ids = () => Array.from(list.querySelectorAll('.vis-item')).map((li) => li.dataset.id);
  const saveOrder = async () => {
    try { await post('order', { ids: ids() }); say('Ordine salvato.'); loadFrame(); }
    catch (e) { say(e.message, true); refreshList(); }
  };
  list.addEventListener('click', (ev) => {
    const pick = ev.target.closest('[data-pick]');
    if (pick) { scrollToCurrent = true; open(Number(pick.dataset.pick), true); return; }
    const mv = ev.target.closest('[data-move]');
    if (!mv) return;
    const li = mv.closest('.vis-item');
    if (mv.dataset.move === '-1' && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
    else if (mv.dataset.move === '1' && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
    else return;
    mv.focus();
    saveOrder();
  });
  let dragged = null;
  list.addEventListener('dragstart', (ev) => { dragged = ev.target.closest('.vis-item'); if (dragged) { dragged.classList.add('is-dragging'); ev.dataTransfer.effectAllowed = 'move'; } });
  list.addEventListener('dragover', (ev) => {
    if (!dragged) return;
    ev.preventDefault();
    const over = ev.target.closest('.vis-item');
    if (!over || over === dragged) return;
    const r = over.getBoundingClientRect();
    over.parentNode.insertBefore(dragged, ev.clientY > r.top + r.height / 2 ? over.nextSibling : over);
  });
  list.addEventListener('dragend', () => { if (!dragged) return; dragged.classList.remove('is-dragging'); dragged = null; saveOrder(); });

  /* ---------- pannello di una fascia ---------- */
  const show = (form) => {
    const tone = (form.querySelector('[name="meta[tone]"]:checked') || {}).value || '';
    const img = (form.querySelector('[name="meta[bg_image]"]') || {}).value || '';
    const vis = (k, on) => { const f = form.querySelector('[data-field="' + k + '"]'); if (f) f.hidden = !on; };
    vis('bg_color', tone === 'custom');
    ['bg_focus', 'bg_overlay', 'parallax'].forEach((k) => vis(k, img !== ''));
  };
  let timer = null;
  const save = (form, reopen) => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      say('Salvo…');
      try {
        await post('save/' + form.dataset.id, new FormData(form));
        say('Salvato. Anche il sito pubblico è aggiornato.');
        loadFrame();
        if (reopen) open(Number(form.dataset.id));
      } catch (e) { say(e.message, true); }
    }, 350);
  };

  const open = async (id, scroll) => {
    current = id;
    list.querySelectorAll('.vis-item').forEach((li) => li.classList.toggle('is-current', Number(li.dataset.id) === id));
    if (scroll) toFrame({ type: 'select', id, scroll: true });
    try { panel.innerHTML = await html('panel/' + id); }
    catch (e) { const p = document.createElement('p'); p.className = 'flash flash--error'; p.textContent = e.message; panel.replaceChildren(p); return; }
    const form = panel.querySelector('[data-vis-form]');
    if (!form) return;
    show(form);
    form.addEventListener('change', (ev) => { show(form); save(form, ev.target.name === 'meta[bg_image]'); });
    form.addEventListener('submit', (ev) => { ev.preventDefault(); save(form); });
    // immagine di fondo: libreria e "togli"
    const imgInput = form.querySelector('[name="meta[bg_image]"]');
    if (imgInput) {
      const row = imgInput.parentElement;
      const clear = document.createElement('button');
      clear.type = 'button'; clear.className = 'btn btn--small'; clear.textContent = 'Togli';
      clear.addEventListener('click', () => { imgInput.value = ''; imgInput.dispatchEvent(new Event('change', { bubbles: true })); });
      row.appendChild(clear);
      form.querySelectorAll('[data-pick-image]').forEach((b) => b.addEventListener('click', async () => {
        if (!window.ppPickImage) return;
        const p = await window.ppPickImage();
        if (p) { imgInput.value = p; imgInput.dispatchEvent(new Event('change', { bubbles: true })); }
      }));
    }
    const del = form.querySelector('[data-vis-delete]');
    if (del) del.addEventListener('click', async () => {
      if (!window.confirm('Eliminare questa fascia in tutte le lingue? Non si può annullare.')) return;
      try {
        await post('delete/' + id);
        current = 0;
        panel.innerHTML = '<p class="hint">Fascia eliminata.</p>';
        say('Fascia eliminata.');
        await refreshList();
        loadFrame();
      } catch (e) { say(e.message, true); }
    });
    const toggle = form.querySelector('[data-vis-toggle]');
    if (toggle) toggle.addEventListener('click', async () => {
      try { const r = await post('status/' + id); say(r.status === 'published' ? 'La fascia ora si vede sul sito.' : 'Fascia nascosta: sul sito non si vede più.'); await refreshList(); open(id); loadFrame(); }
      catch (e) { say(e.message, true); }
    });
  };

  /* ---------- disposizione di una parte di pagina ---------- */
  let partOpen = null;
  const openPart = async (d) => {
    partOpen = d;
    const q = 'layout?type=' + encodeURIComponent(d.ptype) + '&part=' + encodeURIComponent(d.part) + '&entry=' + (d.entry || 0);
    try { panel.innerHTML = await html(q); }
    catch (e) { const p = document.createElement('p'); p.className = 'flash flash--error'; p.textContent = e.message; panel.replaceChildren(p); return; }
    const form = panel.querySelector('[data-vis-layout]');
    if (!form) return;
    const send = async (extra = {}) => {
      const fd = new FormData(form);
      fd.set('type', form.dataset.type); fd.set('part', form.dataset.part); fd.set('entry', form.dataset.entry);
      Object.entries(extra).forEach(([k, v]) => fd.set(k, v));
      say('Salvo…');
      try { await post('layout', fd); say('Salvato. Anche il sito pubblico è aggiornato.'); loadFrame(); openPart(partOpen); }
      catch (e) { say(e.message, true); }
    };
    form.addEventListener('change', (ev) => { if (ev.target.name === 'value') send(); });
    const reset = form.querySelector('[data-vis-reset]');
    if (reset) reset.addEventListener('click', () => {
      const own = form.querySelector('[name=scope][value=page]');
      if (own && own.checked) send({ scope: 'page', value: '' });
      else send({ scope: 'type', value: '' });
    });
  };

  /* ---------- nuova fascia ---------- */
  root.querySelector('[data-vis-add]').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    try {
      const r = await post('add', { kind: ev.target.kind.value, after: current || 0 });
      say('Fascia aggiunta, nascosta. Scrivi i testi con “Modifica i testi”, poi “Mostra”.');
      await refreshList();
      scrollToCurrent = true;
      await open(r.id);
      loadFrame();
    } catch (e) { say(e.message, true); }
  });

  refreshList().catch((e) => say(e.message, true));
  loadFrame();
})();

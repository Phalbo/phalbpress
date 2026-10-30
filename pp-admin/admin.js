/* PhalbPress — gestione */
(() => {
  const body = document.body;
  const CSRF = body.dataset.csrf;
  const BASE = body.dataset.base;
  const ROOT = body.dataset.root || '';

  const api = async (path, data = {}) => {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(`${BASE}/api/${path}`, { method: 'POST', body: fd, headers: { 'X-CSRF': CSRF }, credentials: 'same-origin' });
    let j = {};
    try { j = await r.json(); } catch (e) { j = { error: 'Risposta non valida dal server' }; }
    if (!r.ok || j.error) throw new Error(j.error || `Errore ${r.status}`);
    return j;
  };

  /* conferme */
  document.addEventListener('click', (ev) => {
    const b = ev.target.closest('[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) ev.preventDefault();
  });

  /* copia percorso */
  document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'Copiato'; setTimeout(() => (b.textContent = 'Copia percorso'), 1400); }
    catch (e) { prompt('Percorso:', b.dataset.copy); }
  }));

  /* finestra di scelta (immagini / documenti) */
  const dialog = (title, render) => new Promise((resolve) => {
    const d = document.createElement('dialog');
    d.innerHTML = `<div class="dlg"><div class="panel__head"><h2>${title}</h2><button type="button" class="btn btn--small" data-close>Chiudi</button></div><div class="dlg__list"><p class="muted">Carico…</p></div></div>`;
    document.body.appendChild(d);
    const list = d.querySelector('.dlg__list');
    const done = (v) => { d.close(); d.remove(); resolve(v); };
    d.querySelector('[data-close]').addEventListener('click', () => done(null));
    d.addEventListener('cancel', () => done(null));
    d.showModal();
    render(list, done);
  });

  const pickImage = () => dialog('Scegli un’immagine', async (list, done) => {
    try {
      const { items } = await api('media');
      const imgs = items.filter((i) => i.image);
      if (!imgs.length) { list.innerHTML = '<p class="muted">Nessuna immagine: caricale da “Immagini e file”.</p>'; return; }
      list.innerHTML = '<div class="pick-grid"></div>';
      const g = list.firstChild;
      imgs.forEach((i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.title = i.name;
        b.innerHTML = `<img src="${ROOT}/${i.path}" alt="">`;
        b.addEventListener('click', () => done(i.path));
        g.appendChild(b);
      });
    } catch (e) { list.innerHTML = `<p class="flash flash--error">${e.message}</p>`; }
  });

  const pickDoc = () => dialog('Inserisci un documento', async (list, done) => {
    try {
      const { items } = await api('docs');
      if (!items.length) { list.innerHTML = '<p class="muted">Nessun documento: crealo da “Documenti”.</p>'; return; }
      list.innerHTML = '<div class="pick-list"></div>';
      items.forEach((i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = `${i.code ? i.code + ' · ' : ''}${i.title || 'Senza titolo'}`;
        b.addEventListener('click', () => done(i.id));
        list.firstChild.appendChild(b);
      });
    } catch (e) { list.innerHTML = `<p class="flash flash--error">${e.message}</p>`; }
  });

  document.querySelectorAll('[data-pick-image]').forEach((b) => b.addEventListener('click', async () => {
    const p = await pickImage();
    if (p) { const inp = b.closest('.field, .panel').querySelector('[data-image-path]'); inp.value = p; }
  }));

  /* slug automatico dal titolo, finché non lo si cambia a mano */
  const slugify = (s) => s.toLowerCase().replace(/ß/g, 'ss').replace(/[æ]/g, 'ae').replace(/[ø]/g, 'o').replace(/[đ]/g, 'dj').replace(/[ł]/g, 'l')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
  document.querySelectorAll('[data-slug]').forEach((inp) => {
    const form = inp.closest('form');
    const src = form && form.querySelector('[data-slug-source]');
    if (!src) return;
    src.addEventListener('input', () => { if (inp.dataset.auto === '1') inp.value = slugify(src.value); });
    inp.addEventListener('input', () => { inp.dataset.auto = inp.value === '' ? '1' : '0'; });
    inp.addEventListener('blur', () => { inp.value = slugify(inp.value); });
    const regen = form.querySelector('[data-slug-regen]');
    if (regen) regen.addEventListener('click', () => { inp.value = slugify(src.value); inp.dataset.auto = '1'; });
  });

  /* editor */
  document.querySelectorAll('[data-editor]').forEach((ed) => {
    const area = ed.querySelector('.editor__area');
    const src = ed.querySelector('.editor__src');
    const htmlBtn = ed.querySelector('.editor__html');
    let saved = null;
    const keep = () => { const s = window.getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) saved = s.getRangeAt(0).cloneRange(); };
    const restore = () => { area.focus(); if (saved) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(saved); } };
    area.addEventListener('keyup', keep); area.addEventListener('mouseup', keep); area.addEventListener('blur', keep);
    const insertBlock = (html) => { restore(); document.execCommand('insertHTML', false, html); };
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

    ed.querySelector('.editor__bar').addEventListener('click', async (ev) => {
      const b = ev.target.closest('button');
      if (!b) return;
      const cmd = b.dataset.cmd;
      if (cmd === 'html') {
        const on = src.hidden;
        if (on) { src.value = area.innerHTML; src.hidden = false; area.hidden = true; }
        else { area.innerHTML = src.value; src.hidden = true; area.hidden = false; }
        b.setAttribute('aria-pressed', String(on));
        return;
      }
      if (!area.hidden) restore();
      if (cmd === 'link') {
        const u = prompt('Indirizzo del link (https://…, mailto:…, o /it/pagina/)');
        if (u) { restore(); document.execCommand('createLink', false, u); }
      } else if (cmd === 'image') {
        const p = await pickImage();
        if (p) { const alt = prompt('Testo alternativo (cosa mostra l’immagine)') || ''; insertBlock(`<figure><img src="${ROOT}/${p}" alt="${alt.replace(/"/g, '&quot;')}"></figure><p></p>`); }
      } else if (cmd === 'doc') {
        const id = await pickDoc();
        if (id) insertBlock(`<p>[doc:${id}]</p><p></p>`);
      } else if (cmd === 'video') {
        const v = prompt('Link o ID del video YouTube');
        const m = v && (v.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([A-Za-z0-9_-]{6,20})/) || v.match(/^([A-Za-z0-9_-]{6,20})$/));
        if (m) insertBlock(`<p>[video:${m[1]}]</p><p></p>`);
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, `<${b.dataset.arg}>`);
      } else {
        document.execCommand(cmd, false, null);
      }
      keep();
    });

    area.addEventListener('paste', (ev) => {
      // incolla come testo semplice con paragrafi, niente stili da Word
      const t = ev.clipboardData.getData('text/plain');
      if (!t) return;
      ev.preventDefault();
      const html = t.split(/\n{2,}/).map((p) => `<p>${p.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])).replace(/\n/g, '<br>')}</p>`).join('');
      document.execCommand('insertHTML', false, html);
    });

    ed.closest('form').addEventListener('submit', () => { if (src.hidden) src.value = area.innerHTML; });
  });

  /* avviso se si esce senza salvare */
  document.querySelectorAll('[data-form]').forEach((f) => {
    let dirty = false;
    f.addEventListener('input', () => (dirty = true));
    f.addEventListener('submit', () => (dirty = false));
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  /* traduci una lingua */
  document.querySelectorAll('[data-translate-one]').forEach((b) => b.addEventListener('click', async () => {
    b.disabled = true;
    const label = b.textContent;
    b.textContent = 'Traduco…';
    try {
      await api('translate', { kind: b.dataset.kind, id: b.dataset.id, lang: b.dataset.lang });
      window.onbeforeunload = null;
      location.reload();
    } catch (e) { alert(e.message); b.disabled = false; b.textContent = label; }
  }));

  /* coda di traduzione */
  const runQueue = async (box, params) => {
    const status = box.querySelector('[data-queue-status]');
    const list = box.querySelector('[data-queue-list]');
    const stopBtn = box.querySelector('[data-queue-stop]');
    let stopped = false;
    if (stopBtn) { stopBtn.hidden = false; stopBtn.onclick = () => (stopped = true); }
    list.innerHTML = '';
    status.textContent = 'Cerco cosa tradurre…';
    let jobs = [];
    try { ({ jobs } = await api('jobs', params)); } catch (e) { status.textContent = e.message; return; }
    if (!jobs.length) { status.textContent = 'Tutto è già tradotto e aggiornato.'; if (stopBtn) stopBtn.hidden = true; return 0; }
    let ok = 0, ko = 0;
    for (let i = 0; i < jobs.length; i++) {
      if (stopped) break;
      const j = jobs[i];
      status.textContent = `${i + 1} di ${jobs.length}…`;
      const li = document.createElement('li');
      li.innerHTML = `<span>${j.label} → <strong>${j.lang.toUpperCase()}</strong></span><span class="muted">in corso</span>`;
      list.prepend(li);
      try {
        const r = await api('translate', { kind: j.kind, id: j.id, lang: j.lang, last: i === jobs.length - 1 ? 1 : '' });
        li.lastChild.textContent = r.label; li.lastChild.className = 'badge badge--' + r.status; ok++;
      } catch (e) { li.lastChild.textContent = e.message; li.lastChild.className = 'badge badge--outdated'; ko++; if (/configurata|chiave|401|403/i.test(e.message)) break; }
    }
    status.textContent = `${stopped ? 'Fermato. ' : 'Finito. '}${ok} tradotti${ko ? `, ${ko} errori` : ''}.`;
    if (stopBtn) stopBtn.hidden = true;
    return ok;
  };

  const q = document.querySelector('[data-queue]');
  if (q) q.querySelector('[data-queue-start]').addEventListener('click', () => runQueue(q, {
    lang: q.querySelector('[data-queue-lang]').value,
    include_auto: q.querySelector('[data-queue-auto]').checked ? 1 : '',
  }));

  const aq = document.querySelector('[data-autoqueue]');
  if (aq) runQueue(aq, { kind: aq.dataset.kind, id: aq.dataset.id, force: 1 }).then((n) => {
    if (n) { const u = new URL(location.href); u.searchParams.delete('translate'); setTimeout(() => location.replace(u), 900); }
  });

  /* identità: codice esadecimale accanto al selettore colore */
  document.querySelectorAll('[data-color]').forEach((inp) => inp.addEventListener('input', () => {
    const c = document.querySelector(`[data-hex="${inp.dataset.color}"]`);
    if (c) c.textContent = inp.value;
  }));

  /* SEO: contatore caratteri (mostra anche la lunghezza del valore automatico) */
  document.querySelectorAll('[data-count]').forEach((el) => {
    const out = el.parentElement.querySelector('.counter');
    const max = +el.dataset.count;
    const upd = () => {
      const v = el.value || el.placeholder || '';
      out.textContent = `${v.length}/${max}${el.value ? '' : ' · automatico'}`;
      out.classList.toggle('over', v.length > max);
    };
    el.addEventListener('input', upd); upd();
  });
})();

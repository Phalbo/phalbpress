/* PhalbPress — comportamenti comuni a tutti i temi. Niente librerie, niente cookie.
   Senza JavaScript il sito resta usabile: menu visibile, lingue in un <details>, contenuti già mostrati. */
(() => {
  const root = document.documentElement;
  root.classList.remove('no-js');
  root.classList.add('js');
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- menu sui telefoni ---------- */
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const head = document.querySelector('[data-head]');
  const setNav = (open) => {
    if (!toggle) return;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('nav-open', open);
    if (open) { const first = nav && nav.querySelector('a'); if (first) first.focus(); }
  };
  if (toggle) toggle.addEventListener('click', () => setNav(toggle.getAttribute('aria-expanded') !== 'true'));
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) setNav(false); });

  /* ---------- sottomenu ---------- */
  document.querySelectorAll('[data-sub]').forEach((b) => {
    const item = b.closest('.nav__item');
    b.addEventListener('click', () => {
      const open = b.getAttribute('aria-expanded') !== 'true';
      document.querySelectorAll('[data-sub][aria-expanded="true"]').forEach((o) => { if (o !== b) { o.setAttribute('aria-expanded', 'false'); o.closest('.nav__item').classList.remove('is-open'); } });
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
      item.classList.toggle('is-open', open);
    });
  });

  /* ---------- lingue a tendina: si chiude cliccando fuori o con Esc ---------- */
  const langs = document.querySelector('[data-langs]');
  document.addEventListener('click', (e) => {
    if (langs && langs.open && !langs.contains(e.target)) langs.open = false;
    if (!e.target.closest('.nav__item--sub')) document.querySelectorAll('[data-sub][aria-expanded="true"]').forEach((o) => { o.setAttribute('aria-expanded', 'false'); o.closest('.nav__item').classList.remove('is-open'); });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (langs && langs.open) { langs.open = false; langs.querySelector('summary').focus(); }
    if (toggle && toggle.getAttribute('aria-expanded') === 'true') { setNav(false); toggle.focus(); }
    document.querySelectorAll('[data-sub][aria-expanded="true"]').forEach((o) => { o.setAttribute('aria-expanded', 'false'); o.closest('.nav__item').classList.remove('is-open'); o.focus(); });
  });

  /* ---------- testata: stato "scorso" ---------- */
  if (head) {
    const onScroll = () => head.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- movimento del tema: comparse a cascata, entrata dell'apertura, numeri che contano ----------
     Il tema lo regola in theme.json ("motion": { "marks": {selettore: [tipo, ritardo tra fratelli in ms]}, "count": ms, "tuck": bool }),
     che arriva qui come data-motion sul body; "motion": false lo spegne. Con "meno movimento" non si muove niente. */
  let motion = {};
  try { motion = JSON.parse(document.body.dataset.motion || '{}'); } catch (e) { motion = {}; }
  const MARKS = motion === false ? {} : Object.assign({
    '.step': ['up', 120], '.figure': ['up', 110], '.card': ['up', 110], '.partner': ['up', 70], '.story': ['up', 0],
    '.gallery__item': ['zoom', 80], '.latest__col': ['up', 140], '.collection__item': ['up', 60], '.statement__fact': ['up', 0],
    '.doctable tbody tr': ['up', 40], '.pp-embed': ['zoom', 0], '.lead-item': ['up', 0], '.results__head': ['up', 0], '.toc--inline': ['up', 0], '.event': ['up', 70], '.pp-gallery__item': ['zoom', 60], '.quote__fig': ['up', 0]
  }, (motion && motion.marks) || {});
  Object.entries(MARKS).forEach(([sel, val]) => {
    if (!val) return;
    const [kind, step] = val;
    document.querySelectorAll(sel).forEach((el) => {
      if (el.closest('.hero') || el.dataset.fx) return;
      el.dataset.fx = kind;
      const sibs = el.parentElement ? Array.from(el.parentElement.children).filter((c) => c.matches(sel)) : [el];
      el.style.setProperty('--fx-d', Math.min(Math.max(0, sibs.indexOf(el)), 6) * (step || 0) + 'ms');
    });
  });
  const go = () => document.body.classList.add('fx-go');
  if (reduce) go(); else requestAnimationFrame(() => requestAnimationFrame(go));
  const nums = Array.from(document.querySelectorAll('.figure__num[data-count]'));
  if (motion !== false && !(motion && motion.count === false) && !reduce && nums.length && 'IntersectionObserver' in window) {
    const count = (el) => {
      const target = parseInt(el.dataset.count, 10);
      const tpl = el.textContent;
      const digits = tpl.match(/[0-9][0-9.,\s]*[0-9]|[0-9]/);
      if (!target || !digits) return;
      const sep = (digits[0].match(/[.,\s]/) || [''])[0];
      const fmt = (n) => (sep ? String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep) : String(n));
      const t0 = performance.now();
      const dur = (motion && motion.count) || 1700;
      el.setAttribute('aria-label', tpl);
      const tick = (now) => {
        const p = Math.min(1, (now - t0) / dur);
        el.textContent = tpl.replace(digits[0], fmt(Math.round(target * (1 - Math.pow(1 - p, 3)))));
        if (p < 1) requestAnimationFrame(tick); else { el.textContent = tpl; el.removeAttribute('aria-label'); }
      };
      requestAnimationFrame(tick);
    };
    const ioN = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.isIntersecting) { count(en.target); ioN.unobserve(en.target); } }), { threshold: 0.6 });
    nums.forEach((n) => ioN.observe(n));
  }
  if (head && motion && motion.tuck) {
    let last = window.scrollY;
    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      head.classList.toggle('is-tucked', y > last && y > 240 && !document.body.classList.contains('nav-open') && !head.contains(document.activeElement));
      last = y;
    }, { passive: true });
  }

  /* ---------- comparsa dei contenuti ---------- */
  const reveals = document.querySelectorAll('.reveal, [data-fx]');
  if (!reduce && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    reveals.forEach((el) => io.observe(el));
  } else reveals.forEach((el) => el.classList.add('is-in'));

  /* ---------- frase d'apertura in tutte le lingue ---------- */
  document.querySelectorAll('[data-hero]').forEach((hero) => {
    let lines = {};
    try { lines = JSON.parse(hero.dataset.lines || '{}'); } catch (e) { return; }
    const title = hero.querySelector('[data-hero-title]');
    const l1 = hero.querySelector('[data-l1]');
    const l2 = hero.querySelector('[data-l2]');
    const show = (code) => {
      const x = lines[code];
      if (!x) return;
      hero.querySelectorAll('[data-say]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.say === code ? 'true' : 'false'));
      const swap = () => { l1.textContent = x.l1; if (l2) l2.textContent = x.l2; title.setAttribute('lang', x.lang); title.classList.remove('is-swapping'); document.dispatchEvent(new Event('pp-hero-swap')); };
      if (reduce) return swap();
      title.classList.add('is-swapping');
      setTimeout(swap, 220);
    };
    hero.querySelectorAll('[data-say]').forEach((b) => b.addEventListener('click', () => show(b.dataset.say)));
  });

  /* ---------- filtri degli elenchi (categorie, paesi) ---------- */
  document.querySelectorAll('[data-filter]').forEach((f) => {
    f.hidden = false;
    const key = f.dataset.filter === 'country' ? 'country' : 'cat';
    const list = f.parentElement.querySelector('[data-filter-items]') || f.parentElement;
    f.addEventListener('click', (e) => {
      const b = e.target.closest('button');
      if (!b) return;
      f.querySelectorAll('button').forEach((x) => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
      list.querySelectorAll(`[data-${key}], .card, .story`).forEach((it) => { it.hidden = b.dataset.f !== '' && it.dataset[key] !== b.dataset.f; });
    });
  });

  /* ---------- modulo di contatto: prova che a compilarlo è una persona ---------- */
  document.querySelectorAll('[data-pp-form]').forEach((form) => {
    const t0 = Date.now();
    form.addEventListener('submit', () => {
      form.querySelector('[name=pp_e]').value = String(Math.round((Date.now() - t0) / 1000));
      form.querySelector('[name=pp_k]').value = form.dataset.k;
    });
  });
  const hash = location.hash;
  if (hash === '#pp-form-ok' || hash === '#pp-form-err') { const m = document.querySelector(hash); if (m) m.focus(); }

  /* ---------- copia link ---------- */
  document.querySelectorAll('[data-share-copy]').forEach((b) => {
    if (!navigator.clipboard) return;
    b.hidden = false;
    b.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(b.dataset.shareCopy); const t = b.textContent; b.textContent = b.dataset.done; setTimeout(() => (b.textContent = t), 1600); } catch (e) {}
    });
  });
  /* ---------- parallasse delle immagini di fondo (Modifica visuale): solo se chi guarda non ha chiesto meno movimento ---------- */
  const plx = Array.from(document.querySelectorAll('.chapter__bg[data-parallax] img'));
  if (plx.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    let ticking = false;
    const move = () => {
      ticking = false;
      const vh = window.innerHeight;
      plx.forEach((img) => {
        const r = img.parentElement.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        // da -1 (fascia sotto lo schermo) a 1 (fascia sopra): l'immagine si sposta al massimo del 14% della sua fascia
        const p = Math.max(-1, Math.min(1, (r.top + r.height / 2 - vh / 2) / (vh / 2 + r.height / 2)));
        img.style.transform = 'translate3d(0,' + (p * r.height * 0.14).toFixed(1) + 'px,0)';
      });
    };
    const ask = () => { if (!ticking) { ticking = true; requestAnimationFrame(move); } };
    window.addEventListener('scroll', ask, { passive: true });
    window.addEventListener('resize', ask);
    move();
  }

  let ui = {};
  try { ui = JSON.parse(document.body.dataset.ui || '{}'); } catch (e) { ui = {}; }

  /* ---------- foto ingrandite (lightbox): gallerie nel testo, galleria della home, immagini degli articoli ----------
     <dialog> nativo: Esc chiude, il focus resta dentro e torna dove era; frecce per scorrere. Senza JS i link aprono il file. */
  const groups = [];
  document.querySelectorAll('[data-lightbox-group], .gallery__list, .prose').forEach((g) => {
    const items = [];
    g.querySelectorAll('a[data-lightbox], .gallery__item > img, figure > img').forEach((el) => {
      if (el.closest('[data-lightbox-group]') && el.closest('[data-lightbox-group]') !== g && !el.matches('a')) return;
      if (g.classList.contains('prose') && el.closest('[data-lightbox-group]')) return;
      let trigger = el;
      const imgEl = el.matches('img') ? el : el.querySelector('img');
      if (!imgEl) return;
      if (el.matches('img')) {
        // un'immagine sola: la si avvolge in un pulsante, così si apre anche da tastiera
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'pp-lb-trigger'; b.setAttribute('aria-label', (ui.open || 'Enlarge') + (imgEl.alt ? ': ' + imgEl.alt : ''));
        el.parentNode.insertBefore(b, el); b.appendChild(el); trigger = b;
      }
      const src = el.matches('a') ? el.getAttribute('href') : (imgEl.currentSrc || imgEl.src).replace(/-960(\.[a-z0-9]+)(\?.*)?$/i, '$1$2');
      items.push({ trigger, src, alt: imgEl.alt || '', caption: (el.dataset && el.dataset.caption) || (imgEl.closest('figure') && imgEl.closest('figure').querySelector('figcaption') ? imgEl.closest('figure').querySelector('figcaption').textContent : '') });
    });
    if (items.length) groups.push(items);
  });
  if (groups.length && 'HTMLDialogElement' in window) {
    const dlg = document.createElement('dialog');
    dlg.className = 'pp-lightbox';
    dlg.innerHTML = '<figure class="pp-lightbox__fig"><img alt=""><figcaption></figcaption></figure>'
      + '<button type="button" class="pp-lightbox__btn pp-lightbox__prev"><span aria-hidden="true">‹</span><span class="sr-only"></span></button>'
      + '<button type="button" class="pp-lightbox__btn pp-lightbox__next"><span aria-hidden="true">›</span><span class="sr-only"></span></button>'
      + '<button type="button" class="pp-lightbox__btn pp-lightbox__close"><span aria-hidden="true">×</span><span class="sr-only"></span></button>'
      + '<p class="pp-lightbox__count" aria-live="polite"></p>';
    document.body.appendChild(dlg);
    const im = dlg.querySelector('img'), cap = dlg.querySelector('figcaption'), cnt = dlg.querySelector('.pp-lightbox__count');
    const [bp, bn, bc] = ['.pp-lightbox__prev', '.pp-lightbox__next', '.pp-lightbox__close'].map((q) => dlg.querySelector(q));
    bp.querySelector('.sr-only').textContent = ui.prev || 'Previous'; bn.querySelector('.sr-only').textContent = ui.next || 'Next'; bc.querySelector('.sr-only').textContent = ui.close || 'Close';
    let cur = null, idx = 0, back = null;
    const show = (i) => {
      idx = (i + cur.length) % cur.length;
      const it = cur[idx];
      dlg.classList.remove('is-shown');
      im.onload = () => requestAnimationFrame(() => dlg.classList.add('is-shown'));
      im.src = it.src; im.alt = it.alt; cap.textContent = it.caption || it.alt; cap.hidden = !cap.textContent;
      const many = cur.length > 1; bp.hidden = bn.hidden = !many; cnt.textContent = many ? (idx + 1) + ' / ' + cur.length : '';
    };
    groups.forEach((items) => items.forEach((it, i) => it.trigger.addEventListener('click', (ev) => {
      ev.preventDefault(); cur = items; back = it.trigger; show(i); dlg.showModal(); bc.focus();
    })));
    bp.addEventListener('click', () => show(idx - 1));
    bn.addEventListener('click', () => show(idx + 1));
    bc.addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', (ev) => { if (ev.target === dlg) dlg.close(); });
    dlg.addEventListener('keydown', (ev) => { if (ev.key === 'ArrowLeft') show(idx - 1); if (ev.key === 'ArrowRight') show(idx + 1); });
    dlg.addEventListener('close', () => { im.removeAttribute('src'); if (back) back.focus(); });
    let x0 = null;
    dlg.addEventListener('touchstart', (ev) => { x0 = ev.touches[0].clientX; }, { passive: true });
    dlg.addEventListener('touchend', (ev) => { if (x0 === null) return; const dx = ev.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) show(idx + (dx < 0 ? 1 : -1)); x0 = null; });
  }

  /* ---------- apertura con più foto: scorrono da sole, con pausa; con "meno movimento" restano ferme ---------- */
  document.querySelectorAll('[data-slides]').forEach((fig) => {
    const slides = Array.from(fig.querySelectorAll('img'));
    if (slides.length < 2) return;
    let i = 0, timer = null;
    slides.forEach((s, n) => s.classList.toggle('is-active', n === 0));
    const go = (n) => { slides[i].classList.remove('is-active'); i = (n + slides.length) % slides.length; slides[i].classList.add('is-active'); };
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'hero__pause';
    const play = () => { stop(); timer = setInterval(() => go(i + 1), 6000); btn.setAttribute('aria-pressed', 'false'); btn.textContent = ui.pause || 'Pause'; };
    const stop = () => { clearInterval(timer); timer = null; btn.setAttribute('aria-pressed', 'true'); btn.textContent = ui.play || 'Play'; };
    btn.addEventListener('click', () => (timer ? stop() : play()));
    fig.closest('section').appendChild(btn);
    if (reduce) stop(); else play();
    document.addEventListener('visibilitychange', () => { if (document.hidden && timer) { clearInterval(timer); timer = 'paused'; } else if (!document.hidden && timer === 'paused') play(); });
  });

  /* ---------- titoli: niente parole spezzate; se una parola non entra nella colonna, il titolo si riduce un po' ---------- */
  const heads = Array.from(document.querySelectorAll('main h1, main h2, main h3')).filter((h) => !h.classList.contains('figure__val') && !h.classList.contains('sr-only'));
  // durante il calcolo niente transizioni (un tema con "transition: all" o il movimento ridotto le farebbero leggere in ritardo)
  const fitHeads = () => heads.forEach((h) => {
    h.classList.add('pp-fitting');
    h.style.fontSize = '';
    if (h.scrollWidth > h.clientWidth + 1) {
      let size = parseFloat(getComputedStyle(h).fontSize);
      const min = size * 0.5;
      while (h.scrollWidth > h.clientWidth + 1 && size > min) { size *= 0.94; h.style.fontSize = size + 'px'; }
    }
    h.classList.remove('pp-fitting');
  });
  fitHeads();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitHeads);
  let fitTimer;
  window.addEventListener('resize', () => { clearTimeout(fitTimer); fitTimer = setTimeout(fitHeads, 150); });
  document.addEventListener('pp-hero-swap', fitHeads);
  // la colonna di un titolo può cambiare dopo il primo calcolo (foto che arrivano tardi, font, rivelazioni): si ricalcola quando cambia la sua larghezza
  if ('ResizeObserver' in window) {
    const widths = new WeakMap();
    let roTimer;
    const ro = new ResizeObserver((entries) => {
      const changed = entries.some((en) => { const w = Math.round(en.contentRect.width); const old = widths.get(en.target); widths.set(en.target, w); return old !== undefined && old !== w; });
      if (changed) { clearTimeout(roTimer); roTimer = setTimeout(fitHeads, 100); }
    });
    heads.forEach((h) => ro.observe(h));
  }
  window.addEventListener('load', fitHeads);
  if (document.fonts && document.fonts.addEventListener) document.fonts.addEventListener('loadingdone', () => { clearTimeout(fitTimer); fitTimer = setTimeout(fitHeads, 50); });
})();

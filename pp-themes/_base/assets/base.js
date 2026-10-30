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

  /* ---------- comparsa dei contenuti ---------- */
  const reveals = document.querySelectorAll('.reveal');
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
      const swap = () => { l1.textContent = x.l1; if (l2) l2.textContent = x.l2; title.setAttribute('lang', x.lang); title.classList.remove('is-swapping'); };
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
})();

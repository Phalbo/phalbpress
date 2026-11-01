/* SME Star — movimento del tema. Niente librerie; senza JavaScript (o con "meno movimento") tutto è già visibile.
   - comparse allo scroll con direzioni e ritardi a cascata (data-fx sugli elementi, classe is-in quando entrano)
   - numeri che contano fino al valore quando la fascia arriva sullo schermo
   - apertura che entra al caricamento (classe fx-go sul body) */
(() => {
  const FX = {"marks": {".step": ["up", 120], ".figure": ["zoom", 140], ".card": ["right", 140], ".partner": ["up", 80], ".story": ["right", 0], ".gallery__item": ["right", 90], ".latest__col": ["up", 160], ".collection__item": ["up", 70], ".statement__fact": ["right", 0], ".doctable tbody tr": ["up", 40], ".pp-embed": ["up", 0]}, "countMs": 1600, "headHide": true};
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const body = document.body;

  /* ---------- a cosa dare la comparsa ---------- */
  const mark = (sel, kind, step) => {
    document.querySelectorAll(sel).forEach((el) => {
      if (el.closest('.hero') || el.dataset.fx) return;
      el.dataset.fx = kind;
      // ritardo a cascata tra fratelli dello stesso gruppo
      const sibs = el.parentElement ? Array.from(el.parentElement.children).filter((c) => c.matches(sel)) : [el];
      const i = Math.max(0, sibs.indexOf(el));
      el.style.setProperty('--fx-d', Math.min(i, 6) * step + 'ms');
    });
  };
  Object.entries(FX.marks).forEach(([sel, [kind, step]]) => mark(sel, kind, step));

  const items = Array.from(document.querySelectorAll('[data-fx]'));
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach((el) => el.classList.add('is-in'));
  } else {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });
    items.forEach((el) => io.observe(el));
  }

  /* ---------- apertura ---------- */
  const go = () => body.classList.add('fx-go');
  if (reduce) go(); else requestAnimationFrame(() => requestAnimationFrame(go));

  /* ---------- numeri che contano ---------- */
  const nums = Array.from(document.querySelectorAll('.figure__num[data-count]'));
  const count = (el) => {
    const target = parseInt(el.dataset.count, 10);
    const tpl = el.textContent;
    if (!target || reduce) return;
    const digits = tpl.match(/[0-9][0-9.,\s]*[0-9]|[0-9]/);
    if (!digits) return;
    const sep = (digits[0].match(/[.,\s]/) || [''])[0];
    const fmt = (n) => { const s = String(n); return sep ? s.replace(/\B(?=(\d{3})+(?!\d))/g, sep) : s; };
    const t0 = performance.now();
    const dur = FX.countMs;
    el.setAttribute('aria-label', tpl);
    const tick = (now) => {
      const p = Math.min(1, (now - t0) / dur);
      const e = 1 - Math.pow(1 - p, 3);
      el.textContent = tpl.replace(digits[0], fmt(Math.round(target * e)));
      if (p < 1) requestAnimationFrame(tick); else { el.textContent = tpl; el.removeAttribute('aria-label'); }
    };
    requestAnimationFrame(tick);
  };
  if (nums.length && 'IntersectionObserver' in window) {
    const io2 = new IntersectionObserver((entries) => entries.forEach((en) => {
      if (en.isIntersecting) { count(en.target); io2.unobserve(en.target); }
    }), { threshold: 0.6 });
    nums.forEach((n) => io2.observe(n));
  }

  /* ---------- testata che cambia allo scroll ---------- */
  const head = document.querySelector('[data-head]');
  if (head && FX.headHide) {
    let last = window.scrollY;
    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      const down = y > last && y > 240 && !body.classList.contains('nav-open') && !head.contains(document.activeElement);
      head.classList.toggle('is-tucked', down);
      last = y;
    }, { passive: true });
  }

})();

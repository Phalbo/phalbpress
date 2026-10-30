/* Tipografico — la testata prende il colore della fascia che ha sotto. */
(() => {
  const head = document.querySelector('[data-head]');
  if (!head) return;
  const chapters = [...document.querySelectorAll('.chapter, .site-foot')];
  let ticking = false;
  const setInk = () => {
    ticking = false;
    const y = 36;
    for (const c of chapters) {
      const r = c.getBoundingClientRect();
      if (r.top <= y && r.bottom > y) {
        const cs = getComputedStyle(c);
        head.style.setProperty('--head-ink', cs.color);
        head.style.setProperty('--head-bg', cs.backgroundColor);
        return;
      }
    }
  };
  setInk();
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(setInk); } }, { passive: true });
})();

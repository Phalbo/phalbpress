/* Tech / Futurista — i numeri si contano quando entrano nello schermo, le barre misurano rispetto al più grande. */
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('.figures').forEach((sec) => {
    const figs = [...sec.querySelectorAll('.figure')];
    const nums = figs.map((f) => parseFloat((f.querySelector('[data-count]') || {}).dataset?.count || '0') || 0);
    const max = Math.max(...nums, 1);
    figs.forEach((f, i) => { f.style.setProperty('--pct', Math.max(6, Math.round((nums[i] / max) * 100))); if (!reduce) f.style.setProperty('--grow', 0); });
    if (reduce || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((en) => en.forEach((e) => {
      if (!e.isIntersecting) return;
      io.disconnect();
      figs.forEach((f, i) => {
        f.style.setProperty('--grow', 1);
        const el = f.querySelector('[data-count]');
        const target = nums[i];
        if (!el || !target) return;
        const text = el.textContent;
        const t0 = performance.now();
        const step = (t) => {
          const k = Math.min(1, (t - t0) / 1100);
          el.textContent = text.replace(/[\d.,]+/, String(Math.round(target * (1 - Math.pow(1 - k, 3)))));
          if (k < 1) requestAnimationFrame(step); else el.textContent = text;
        };
        requestAnimationFrame(step);
      });
    }), { threshold: 0.4 });
    io.observe(sec);
  });
})();

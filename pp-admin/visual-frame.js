/* Modifica visuale: gira dentro l'anteprima della home. Le fasce si cliccano, i link non portano altrove. */
(() => {
  const origin = location.origin;
  const send = (msg) => parent.postMessage(Object.assign({ pp: true }, msg), origin);
  document.documentElement.classList.add('pp-editing');
  const reveal = () => document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-in'));
  reveal();
  document.addEventListener('DOMContentLoaded', reveal);

  const select = (id, scroll) => {
    document.querySelectorAll('[data-pp-block].pp-sel').forEach((e) => e.classList.remove('pp-sel'));
    const el = document.querySelector('[data-pp-block="' + id + '"]');
    if (!el) return;
    el.classList.add('pp-sel');
    if (scroll) el.scrollIntoView({ block: 'center' });
  };

  document.addEventListener('click', (ev) => {
    const el = ev.target.closest('[data-pp-block]');
    if (ev.target.closest('a, button, summary, input, select, textarea, label')) ev.preventDefault();
    if (!el) return;
    ev.preventDefault();
    select(el.dataset.ppBlock, false);
    send({ type: 'select', id: Number(el.dataset.ppBlock) });
  }, true);
  document.addEventListener('submit', (ev) => ev.preventDefault(), true);

  window.addEventListener('message', (ev) => {
    if (ev.origin !== origin || !ev.data || !ev.data.pp) return;
    if (ev.data.type === 'select') select(ev.data.id, ev.data.scroll);
    if (ev.data.type === 'scroll') window.scrollTo(0, ev.data.y || 0);
  });

  let t;
  window.addEventListener('scroll', () => { clearTimeout(t); t = setTimeout(() => send({ type: 'scroll', y: Math.round(window.scrollY) }), 150); }, { passive: true });
  window.addEventListener('load', () => send({ type: 'ready' }));
})();

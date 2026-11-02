/* PhalbPress — consenso per i contenuti esterni (video) e caricamento al clic.
   Nessun cookie: la scelta resta nel localStorage del browser ("pp-consent") e scade dopo 6 mesi. */
(() => {
  const root = document.querySelector('[data-pp-consent-root]');
  if (!root) return;
  const cfg = JSON.parse(root.dataset.config || '{}');
  const T = cfg.t || {};
  const KEY = 'pp-consent';
  const names = Object.keys(cfg.services || {});
  const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  const read = () => {
    try {
      const v = JSON.parse(localStorage.getItem(KEY) || 'null');
      if (!v || v.v !== cfg.version || Date.now() - v.ts > (cfg.months || 6) * 30 * 864e5) return null;
      return v;
    } catch (e) { return null; }
  };
  const write = (services) => {
    const v = { v: cfg.version, ts: Date.now(), s: services };
    try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) {}
    state = v;
    document.dispatchEvent(new CustomEvent('pp-consent', { detail: v }));
    loadAllowed();
  };
  let state = read();
  const has = (svc) => !!(state && state.s && state.s[svc]);
  const all = (val) => Object.fromEntries(names.map((n) => [n, val]));

  /* banner */
  const banner = document.createElement('section');
  banner.className = 'pp-banner';
  banner.setAttribute('role', 'dialog');
  banner.setAttribute('aria-labelledby', 'pp-banner-title');
  banner.hidden = true;
  banner.innerHTML = `
    <div class="pp-banner__in">
      <button type="button" class="pp-banner__x" data-act="reject" aria-label="${esc(T.cookie_close)}">×</button>
      <h2 id="pp-banner-title" class="pp-banner__title">${esc(T.cookie_title)}</h2>
      <p class="pp-banner__text">${esc(T.cookie_text)} <a href="${esc(cfg.policy)}">${esc(T.cookie_policy_link)}</a></p>
      <fieldset class="pp-banner__choices" hidden>
        ${names.map((n) => `<label class="pp-check"><input type="checkbox" value="${esc(n)}"> <span><strong>${esc(cfg.services[n].name)}</strong> · ${esc(cfg.services[n].provider)}</span></label>`).join('')}
      </fieldset>
      <div class="pp-banner__actions">
        <button type="button" class="pp-btn" data-act="reject">${esc(T.cookie_reject)}</button>
        <button type="button" class="pp-btn pp-btn--ghost" data-act="custom">${esc(T.cookie_customize)}</button>
        <button type="button" class="pp-btn" data-act="accept">${esc(T.cookie_accept)}</button>
      </div>
    </div>`;
  document.body.appendChild(banner);
  const choices = banner.querySelector('.pp-banner__choices');
  const customBtn = banner.querySelector('[data-act="custom"]');

  const open = () => {
    choices.querySelectorAll('input').forEach((i) => (i.checked = has(i.value)));
    banner.hidden = false;
    banner.querySelector('.pp-btn').focus({ preventScroll: true });
  };
  const close = () => { banner.hidden = true; };
  banner.addEventListener('click', (e) => {
    const b = e.target.closest('[data-act]');
    if (!b) return;
    const act = b.dataset.act;
    if (act === 'accept') { write(all(true)); close(); }
    else if (act === 'reject') { write(all(false)); close(); }
    else if (act === 'custom') {
      if (choices.hidden) { choices.hidden = false; customBtn.textContent = T.cookie_save; }
      else { write(Object.fromEntries([...choices.querySelectorAll('input')].map((i) => [i.value, i.checked]))); close(); choices.hidden = true; customBtn.textContent = T.cookie_customize; }
    }
  });
  banner.addEventListener('keydown', (e) => { if (e.key === 'Escape') { write(all(false)); close(); } });
  document.addEventListener('click', (e) => { if (e.target.closest('[data-pp-consent-open]')) { e.preventDefault(); open(); } });
  if (!state) open();

  /* contenuti incorporati: si caricano solo con il consenso */
  const load = (fig) => {
    if (fig.dataset.loaded) return;
    fig.dataset.loaded = '1';
    const f = document.createElement('iframe');
    f.src = fig.dataset.src + (fig.dataset.src.includes('?') ? '&' : '?') + 'autoplay=' + (fig.dataset.clicked ? '1' : '0');
    if (fig.dataset.kind === 'map') f.src = fig.dataset.src;
    else { f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; f.allowFullscreen = true; }
    f.title = fig.dataset.title || 'Video';
    f.loading = 'lazy';
    fig.querySelector('.pp-embed__box').replaceChildren(f);
  };
  const loadAllowed = () => document.querySelectorAll('[data-pp-embed]').forEach((fig) => {
    if (has(fig.dataset.service) && fig.dataset.clicked) load(fig);
  });
  document.querySelectorAll('[data-pp-embed]').forEach((fig) => {
    const svc = fig.dataset.service;
    const name = (cfg.services[svc] || {}).name || svc;
    fig.querySelector('[data-pp-play]')?.addEventListener('click', (e) => {
      e.preventDefault();
      fig.dataset.clicked = '1';
      if (has(svc)) return load(fig);
      const box = fig.querySelector('.pp-embed__box');
      const k = fig.dataset.kind === 'map' ? 'map' : 'video';
      box.innerHTML = `<div class="pp-embed__ask" role="group">
        <p>${esc((T[k + '_consent'] || '').replace('{service}', name))}</p>
        <p class="pp-embed__btns"><button type="button" class="pp-btn">${esc(T[k + '_accept'])}</button>
        <a class="pp-btn pp-btn--ghost" href="${esc(fig.dataset.external)}" target="_blank" rel="noopener">${esc((T[k + '_external'] || '').replace('{service}', name))}</a></p></div>`;
      box.querySelector('button').addEventListener('click', () => {
        write({ ...(state ? state.s : all(false)), [svc]: true });
        load(fig);
      });
      box.querySelector('button').focus();
    });
  });
  window.ppConsent = { has, open };
})();

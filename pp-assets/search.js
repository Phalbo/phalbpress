/* PhalbPress — ricerca nel browser su un indice JSON statico. */
(() => {
  const box = document.querySelector('[data-pp-search]');
  if (!box) return;
  const form = box.querySelector('form');
  const input = box.querySelector('input[type=search]');
  const out = box.querySelector('[data-results]');
  const status = box.querySelector('[data-status]');
  const fold = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ß/g, 'ss').replace(/đ/g, 'd');
  let index = null;
  const load = async () => {
    if (index) return index;
    const r = await fetch(box.dataset.index, { credentials: 'omit' });
    const rows = await r.json();
    index = rows.map((x) => ({ ...x, _t: fold(x.t), _x: fold(x.x), _b: fold(x.b) }));
    return index;
  };
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const snippet = (row, words) => {
    const src = row.b || row.x || '';
    const f = fold(src);
    let at = -1;
    for (const w of words) { at = f.indexOf(w); if (at >= 0) break; }
    if (at < 0) return esc(row.x || src.slice(0, 180));
    const start = Math.max(0, at - 70);
    let s = esc((start ? '… ' : '') + src.slice(start, start + 200) + (src.length > start + 200 ? ' …' : ''));
    words.forEach((w) => { if (w.length > 1) s = s.replace(new RegExp(`(${w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'), '<mark>$1</mark>'); });
    return s;
  };
  const run = async (q) => {
    const words = fold(q).split(/\s+/).filter((w) => w.length > 1);
    out.innerHTML = '';
    if (!words.length) { status.textContent = ''; return; }
    const rows = await load();
    const hits = [];
    rows.forEach((r) => {
      let score = 0;
      for (const w of words) {
        const s = (r._t.includes(w) ? 10 : 0) + (r._x.includes(w) ? 4 : 0) + (r._b.includes(w) ? 1 : 0);
        if (!s) return;
        score += s;
      }
      hits.push([score, r]);
    });
    hits.sort((a, b) => b[0] - a[0]);
    status.textContent = hits.length ? box.dataset.count.replace('{n}', hits.length) : box.dataset.none;
    out.innerHTML = hits.slice(0, 50).map(([, r]) => `<li class="pp-hit"><a href="${esc(r.u)}"><span class="pp-hit__kind">${esc(r.k)}${r.d ? ' · ' + esc(r.d) : ''}</span><span class="pp-hit__title">${esc(r.t)}</span></a><p>${snippet(r, words)}</p></li>`).join('');
  };
  const q = new URLSearchParams(location.search).get('q') || '';
  input.value = q;
  if (q) run(q);
  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    const v = input.value.trim();
    history.replaceState(null, '', v ? `?q=${encodeURIComponent(v)}` : location.pathname);
    run(v);
  });
  let t;
  input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => run(input.value), 220); });
})();

/* People's Choice — nastro di testo a contorno nelle fasce scure: ripete il titolo della fascia, solo decorazione (aria-hidden).
   Il resto del movimento (comparse, numeri, apertura) è quello comune di base.js, regolato in theme.json. */
(() => {
  document.querySelectorAll('.site-main > .chapter--ink:not(.chapter--has-bg), .site-main > .chapter--custom[data-ink="light"]').forEach((sec) => {
    const h = sec.querySelector('h2, h1');
    const word = h ? h.textContent.trim().replace(/\s+/g, ' ') : '';
    if (!word || word.length > 60) return;
    const tape = document.createElement('div');
    tape.className = 'vo-tape';
    tape.setAttribute('aria-hidden', 'true');
    const run = document.createElement('span');
    run.className = 'vo-tape__run';
    run.textContent = (word + ' · ').repeat(4);
    tape.appendChild(run);
    tape.appendChild(run.cloneNode(true));
    sec.classList.add('has-tape');
    sec.insertBefore(tape, sec.firstChild);
  });
})();

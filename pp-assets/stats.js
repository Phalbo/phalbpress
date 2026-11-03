/* PhalbPress — statistiche delle visite configurate in Privacy e cookie. Nessun codice in linea: i dati sono negli attributi
   dello script. Matomo senza cookie; Google Analytics 4 solo dopo il consenso (pp.js), e si ferma se lo si revoca. */
(() => {
  const el = document.getElementById('pp-stats');
  if (!el) return;
  const d = el.dataset;
  const add = (src) => { const s = document.createElement('script'); s.async = true; s.src = src; document.head.appendChild(s); };

  if (d.matomo && d.matomoId) {
    const q = (window._paq = window._paq || []);
    q.push(['disableCookies']);
    q.push(['trackPageView']);
    q.push(['enableLinkTracking']);
    q.push(['setTrackerUrl', d.matomo + 'matomo.php']);
    q.push(['setSiteId', d.matomoId]);
    add(d.matomo + 'matomo.js');
  }

  if (d.ga4) {
    const id = d.ga4;
    let started = false;
    const allowed = () => !!(window.ppConsent && window.ppConsent.has('ga4'));
    const start = () => {
      window['ga-disable-' + id] = false;
      if (started) return;
      started = true;
      window.dataLayer = window.dataLayer || [];
      window.gtag = function gtag() { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', id);
      add('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id));
    };
    if (allowed()) start();
    document.addEventListener('pp-consent', (e) => {
      const ok = !!(e.detail && e.detail.s && e.detail.s.ga4);
      if (ok) start(); else window['ga-disable-' + id] = true;
    });
  }
})();

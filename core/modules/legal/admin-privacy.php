<header class="head"><h1>Privacy e cookie</h1>
  <div class="head__actions">
    <a class="btn" target="_blank" rel="noopener" href="<?= e(PP::link(Legal::slug('privacy', I18n::$master), I18n::$master)) ?>">Privacy policy ↗</a>
    <a class="btn" target="_blank" rel="noopener" href="<?= e(PP::link(Legal::slug('cookies', I18n::$master), I18n::$master)) ?>">Cookie policy ↗</a>
  </div>
</header>
<div class="cols">
  <section class="panel stack">
    <h2>Servizi esterni trovati</h2>
    <p class="hint">Il sito non usa cookie propri per i visitatori. La scansione cerca nei contenuti di tutte le lingue i servizi esterni incorporati (video YouTube, Vimeo…): se ce ne sono, compare il banner di consenso e la cookie policy li elenca. Si ripete da sola a ogni salvataggio.</p>
    <?php $found = $scan['found'] ?? []; ?>
    <?php if (!$found): ?>
      <p><span class="badge badge--ok">Nessun servizio esterno</span> Niente banner cookie: non serve.</p>
    <?php else: ?>
      <ul class="rows">
        <?php foreach ($found as $k => $refs): $s = $services[$k] ?? ['name' => $k, 'provider' => '']; ?>
          <li><span><strong><?= e($s['name']) ?></strong> <span class="muted"><?= e($s['provider']) ?></span><br>
            <span class="small muted">in: <?php foreach (array_slice($refs, 0, 8) as $ref): if (str_starts_with($ref, 'entry:')): $id = (int)substr($ref, 6); ?><a href="<?= e(Admin::u('entry/' . $id . '/' . I18n::$master)) ?>">contenuto #<?= $id ?></a> <?php else: ?><?= e($ref) ?> <?php endif; endforeach; ?><?= count($refs) > 8 ? '…' : '' ?></span></span>
            <span class="badge badge--auto">Banner attivo</span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="small muted">Ultima scansione: <?= e($scan['at'] ?? '—') ?></p>
    <form method="post"><?= Admin::csrfField() ?><button class="btn" name="action" value="scan">Scansiona ora</button></form>
  </section>
  <section class="panel">
    <h2>Dati per la privacy policy</h2>
    <form method="post" class="stack" data-form>
      <?= Admin::csrfField() ?>
      <label class="field">Titolare del trattamento<input type="text" name="controller" value="<?= e(str_starts_with($cfg['controller'], '[') ? '' : $cfg['controller']) ?>" placeholder="es. Edutask Institute per il consorzio VOICE Europe"></label>
      <label class="field">Indirizzo<input type="text" name="address" value="<?= e(str_starts_with($cfg['address'], '[') ? '' : $cfg['address']) ?>"></label>
      <label class="field">Email per la privacy<input type="email" name="email" value="<?= e($cfg['email']) ?>"></label>
      <label class="field">Responsabile protezione dati (DPO), se c’è<input type="text" name="dpo" value="<?= e($cfg['dpo']) ?>"></label>
      <label class="field">Fornitore di hosting<input type="text" name="hosting" value="<?= e($cfg['hosting']) ?>"></label>
      <label class="field">Data di aggiornamento<input type="date" name="updated" value="<?= e($cfg['updated']) ?>"></label>
      <button class="btn btn--primary" name="action" value="save">Salva</button>
    </form>
    <p class="hint">I testi completi di privacy e cookie policy sono in Testi del sito → Legale, in tutte le lingue. Sono un modello solido per un sito senza tracciamento: falli comunque vedere a chi si occupa della privacy per il consorzio.</p>
  </section>
<section class="panel" id="statistiche">
  <h2>Statistiche delle visite</h2>
  <p class="hint">Scrivi solo l’identificativo: il codice su tutte le pagine lo mettiamo noi, e privacy e cookie policy si aggiornano da sole. Matomo e Plausible non usano cookie: niente banner. Google Analytics invece parte solo dopo il consenso del visitatore, e compare nel banner.</p>
  <form method="post" class="stack" data-form>
    <?= Admin::csrfField() ?>
    <?php $bad = fn($k, $ok) => trim((string)($stats[$k] ?? '')) !== '' && $ok === '' ? ' <span class="badge badge--out">non valido</span>' : ''; ?>
    <fieldset class="stack"><legend>Matomo <span class="muted">(senza cookie)</span></legend>
      <label class="field">Indirizzo del tuo Matomo<?= $bad('matomo_url', $statsOk['matomo_url']) ?><input type="url" name="matomo_url" value="<?= e($stats['matomo_url'] ?? '') ?>" placeholder="https://statistiche.esempio.it/"></label>
      <label class="field">Numero del sito in Matomo<input type="number" name="matomo_id" min="1" value="<?= e($stats['matomo_id'] ?? '') ?>" placeholder="1"></label>
    </fieldset>
    <fieldset class="stack"><legend>Plausible <span class="muted">(senza cookie)</span></legend>
      <label class="field">Dominio come l’hai scritto su Plausible<?= $bad('plausible', $statsOk['plausible']) ?><input type="text" name="plausible" value="<?= e($stats['plausible'] ?? '') ?>" placeholder="esempio.it"></label>
      <label class="field">Indirizzo di Plausible <span class="muted">(solo se lo installi tu; vuoto = plausible.io)</span><input type="url" name="plausible_host" value="<?= e($stats['plausible_host'] ?? '') ?>" placeholder="https://plausible.io"></label>
    </fieldset>
    <fieldset class="stack"><legend>Google Analytics 4 <span class="muted">(con il consenso)</span></legend>
      <label class="field">ID di misurazione<?= $bad('ga4', $statsOk['ga4']) ?><input type="text" name="ga4" value="<?= e($stats['ga4'] ?? '') ?>" placeholder="G-XXXXXXXXXX" pattern="[Gg]\-[A-Za-z0-9]{4,16}"></label>
      <p class="hint">Lo trovi in Google Analytics → Amministrazione → Stream di dati. Chi rifiuta i cookie non viene contato.</p>
    </fieldset>
    <button class="btn btn--primary" name="action" value="stats">Salva le statistiche</button>
  </form>
</section>
</div>

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
</div>

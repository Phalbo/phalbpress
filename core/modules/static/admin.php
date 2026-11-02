<header class="head"><h1>Sistema</h1></header>
<div class="cols">
  <section class="panel">
    <h2>Sicurezza</h2>
    <ul class="rows">
      <?php foreach ($checks as $c): ?>
        <li><span><?= $c['ok'] ? '<span class="badge badge--ok">OK</span>' : '<span class="badge badge--outdated">Da fare</span>' ?> <?= e($c['label']) ?>
          <?php if (!$c['ok'] && $c['fix']): ?><br><span class="small muted"><?= e($c['fix']) ?></span><?php endif; ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="panel stack">
    <h2>Sito statico</h2>
    <p class="hint">Con il sito statico attivo, dopo ogni salvataggio le pagine pubbliche vengono riscritte come file HTML (qualche secondo dopo, senza far aspettare chi salva): chi visita il sito non esegue PHP e non tocca il database. La gestione e i download dei documenti restano dinamici.</p>
    <p><?= $enabled ? '<span class="badge badge--ok">Attivo</span>' : '<span class="badge badge--missing">Spento</span>' ?>
      <?php if ($last): ?><span class="small muted">Ultima generazione: <?= e($last['at']) ?> · <?= (int)$last['files'] ?> file · <?= e($last['seconds']) ?> s</span><?php endif; ?>
      <?php if ($enabled && StaticSite::dirty()): ?><br><span class="badge badge--auto">In corso</span> <span class="small muted">le pagine si stanno rigenerando dopo l’ultimo salvataggio: ricarica tra qualche secondo</span><?php endif; ?></p>
    <form method="post" class="row">
      <?= Admin::csrfField() ?>
      <?php if ($enabled): ?>
        <button class="btn btn--primary" name="action" value="build">Rigenera ora</button>
        <button class="btn" name="action" value="disable" data-confirm="Tornare al sito dinamico?">Disattiva</button>
      <?php else: ?>
        <button class="btn btn--primary" name="action" value="enable">Attiva il sito statico</button>
      <?php endif; ?>
    </form>
  </section>
</div>
<section class="panel">
  <h2>Contenuti programmati</h2>
  <?php $cronAt = (int)Settings::get('cron_at', 0); $up = Scheduler::upcoming(); ?>
  <p class="hint">Perché un contenuto programmato compaia (o sparisca) all'ora giusta anche col sito statico, imposta su SiteGround
    <strong>Site Tools → Devs → Cron Jobs</strong>, ogni 15 minuti, il comando:</p>
  <p><code>php <?= e(PP_ROOT) ?>/cron.php</code></p>
  <p class="hint">Senza, il sito si aggiorna comunque al primo accesso alla gestione dopo l'ora prevista. Ultimo controllo: <?= $cronAt ? e(date('d.m.Y H:i', $cronAt)) : 'mai' ?>.</p>
  <?php if ($up): ?><ul class="rows"><?php foreach ($up as $u): ?>
    <li><a href="<?= e(Admin::u('entry/' . $u['id'] . '/' . I18n::$master)) ?>"><?= e($u['title'] ?: '#' . $u['id']) ?></a>
      <span class="muted small"><?= $u['publish_at'] > Content::now() ? 'esce il ' . e(substr($u['publish_at'], 0, 16)) : '' ?><?= $u['unpublish_at'] > Content::now() ? ' · si toglie il ' . e(substr($u['unpublish_at'], 0, 16)) : '' ?></span></li>
  <?php endforeach; ?></ul><?php endif; ?>
</section>
<section class="panel">
  <div class="panel__head"><h2>Versioni e moduli</h2>
    <form method="post"><?= Admin::csrfField() ?><button class="btn btn--small" name="action" value="migrate">Controlla aggiornamenti del database</button></form></div>
  <p class="hint">PhalbPress <?= PP::VERSION ?> · PHP <?= PHP_VERSION ?> · database <?= e(DB::driver()) ?>. Per aggiornare il motore usa <a href="<?= e(Admin::u('update')) ?>">Aggiornamenti</a>. Le modifiche al database si applicano da sole al primo accesso.</p>
  <table class="table">
    <thead><tr><th scope="col">Modulo</th><th scope="col">Versione</th><th scope="col">Tipo</th></tr></thead>
    <tbody>
      <?php foreach ($modules as $m): ?>
        <tr><td><strong><?= e($m['label'] ?? $m['name']) ?></strong> <span class="muted small"><?= e($m['name']) ?></span></td><td><?= e($m['version'] ?? '') ?></td>
          <td><?= $m['scope'] === 'site' ? '<span class="badge badge--auto">Solo questo sito</span>' : '<span class="badge badge--master">Core</span>' ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

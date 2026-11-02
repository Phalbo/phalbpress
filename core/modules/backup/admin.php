<header class="head"><h1>Backup e modelli</h1></header>
<?php if (!$zip): ?><p class="flash flash--error">Il server non ha l’estensione zip di PHP: chiedi di attivarla (su SiteGround è attiva di serie).</p><?php endif; ?>
<div class="cols">
<section class="panel stack">
  <h2>Crea</h2>
  <form method="post" class="stack"><?= Admin::csrfField() ?>
    <label class="check"><input type="radio" name="kind" value="full" checked> <span><strong>Backup completo</strong>: tutto il database (anche utenti e richieste) e i file caricati.</span></label>
    <label class="check"><input type="radio" name="kind" value="template"> <span><strong>Modello</strong>: contenuti, testi, menu, identità visiva e immagini, senza utenti, richieste e registro. Per far partire un altro sito da questo.</span></label>
    <label class="check"><input type="checkbox" name="uploads" value="1" checked> Includi immagini e documenti caricati</label>
    <button class="btn btn--primary" name="action" value="export">Crea il file .zip</button>
  </form>
  <p class="hint">Il codice (tema e moduli in <code>site/</code>) e <code>config.php</code> non entrano mai nel .zip: si copiano a mano. Così un .zip non può portare password o programmi sul server.</p>
  <p class="hint">SiteGround fa già un backup giornaliero dell’intero spazio: questo serve per avere una copia tua, o per spostare il sito.</p>
</section>
<section class="panel stack">
  <h2>Ripristina</h2>
  <form method="post" enctype="multipart/form-data" class="stack"><?= Admin::csrfField() ?>
    <label class="field">Da un file .zip<input type="file" name="zip" accept=".zip"></label>
    <?php if ($files): ?><label class="field">oppure da uno qui sotto<select name="name"><option value="">—</option><?php foreach ($files as $f): ?><option value="<?= e($f['name']) ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <label class="check"><input type="radio" name="mode" value="keep" checked> Tieni gli utenti e il registro di questo sito</label>
    <label class="check"><input type="radio" name="mode" value="full"> Sostituisci anche gli utenti (ripristino completo)</label>
    <label class="field">Per confermare scrivi RIPRISTINA<input type="text" name="confirm" autocomplete="off" required></label>
    <button class="btn btn--danger" name="action" value="restore">Ripristina</button>
  </form>
  <p class="hint">Prima di ripristinare viene fatta da sola una copia di sicurezza del database attuale.</p>
</section>
</div>
<section class="panel">
  <h2>File sul server</h2>
  <?php if (!$files): ?><p class="empty">Nessuno.</p><?php else: ?>
  <ul class="rows"><?php foreach ($files as $f): ?>
    <li><span><strong><?= e($f['name']) ?></strong> <span class="muted small"><?= Media::human($f['size']) ?> · <?= e(date('d.m.Y H:i', $f['mtime'])) ?></span></span>
      <span class="row"><a class="btn btn--small" href="<?= e(Admin::u('backup/download/' . $f['name'])) ?>">Scarica</a>
      <form method="post"><?= Admin::csrfField() ?><input type="hidden" name="name" value="<?= e($f['name']) ?>"><button class="btn btn--small btn--danger" name="action" value="delete" data-confirm="Eliminare il file?">Elimina</button></form></span></li>
  <?php endforeach; ?></ul>
  <?php endif; ?>
</section>
<section class="panel stack panel--danger">
  <h2>Azzera il sito</h2>
  <p>Cancella contenuti, testi, menu, impostazioni, redirect, richieste e registro, e fa ripartire il sito come appena installato. <strong>Il codice in <code>site/</code> e <code>config.php</code> non si toccano</strong>; il tuo utente resta, con la sua password.</p>
  <form method="post" class="stack"><?= Admin::csrfField() ?>
    <fieldset class="stack">
      <legend>Prima</legend>
      <label class="check"><input type="checkbox" name="backup" value="1"> Fai prima un backup completo (database e file: con molte foto ci vuole qualche minuto)</label>
    </fieldset>
    <fieldset class="stack">
      <legend>Cosa tenere</legend>
      <label class="check"><input type="checkbox" name="keep_uploads" value="1" checked> <span>Immagini e file caricati. Se poi reimporti da WordPress, le foto già importate vengono ritrovate e non rifatte.</span></label>
      <?php if ($hasImport): ?><label class="check"><input type="checkbox" name="keep_import" value="1" checked> <span>I file da importare in <code>data/wp-import/</code> (esportazione, dump e zip delle foto)</span></label><?php endif; ?>
      <label class="check"><input type="checkbox" name="keep_backups" value="1" checked> I backup sul server</label>
      <label class="check"><input type="checkbox" name="keep_users" value="1" checked> Gli altri utenti (redattori, traduttori…)</label>
    </fieldset>
    <fieldset class="stack">
      <legend>Da dove ripartire</legend>
      <?php if ($hasSeed): ?><label class="check"><input type="radio" name="start" value="seed" checked> <span>I contenuti iniziali del sito (<code>site/seed.php</code>)</span></label><?php endif; ?>
      <?php if ($hasDemo): ?><label class="check"><input type="radio" name="start" value="demo"> I contenuti di esempio</label><?php endif; ?>
      <label class="check"><input type="radio" name="start" value="empty"<?= $hasSeed ? '' : ' checked' ?>> Vuoto</label>
    </fieldset>
    <label class="field">La tua password<input type="password" name="password" autocomplete="current-password" required></label>
    <label class="field">Per confermare scrivi AZZERA<input type="text" name="confirm" autocomplete="off" required></label>
    <button class="btn btn--danger" name="action" value="wipe">Azzera il sito</button>
  </form>
  <p class="hint">Non si torna indietro, se non con un backup. Il sito ripartito è nascosto ai motori di ricerca, come dopo l’installazione.<?php if ($hasImport): ?> Alla fine si apre Importa da WordPress.<?php endif; ?></p>
</section>

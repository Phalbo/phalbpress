<header class="head"><h1>Aggiornamenti</h1></header>
<div class="cols">
  <section class="panel stack">
    <h2>Motore</h2>
    <p>Installata: <strong>PhalbPress <?= e(PP::VERSION) ?></strong>
      <?php if (!empty($info['version'])): ?> · ultima disponibile: <strong><?= e($info['version']) ?></strong><?= $info['published'] ? ' del ' . e($info['published']) : '' ?><?php endif; ?></p>
    <?php if (!empty($stale)): ?><div class="flash flash--error"><p><strong>I file sono aggiornati, ma il server usa ancora la versione di prima che tiene in memoria.</strong>
      Di solito si sistema da solo in pochi minuti. Se no, su SiteGround: Site Tools → Devs → PHP Manager → cambia versione di PHP e poi rimetti quella di prima: la memoria si svuota.</p></div><?php endif; ?>
    <?php if ($error): ?><p><span class="badge badge--outdated">Non raggiungibile</span> <span class="small"><?= e($error) ?></span></p><?php endif; ?>
    <?php if ($available): ?>
      <p><span class="badge badge--outdated">Da aggiornare</span></p>
      <?php if (trim((string)$info['notes']) !== ''): ?><div class="panel"><h3>Cosa cambia</h3><pre class="small" style="white-space:pre-wrap"><?= e($info['notes']) ?></pre></div><?php endif; ?>
      <form method="post" class="row">
        <?= Admin::csrfField() ?>
        <button class="btn btn--primary" name="action" value="update" data-confirm="Aggiornare il motore a <?= e($info['version']) ?>? Prima viene fatta una copia del database.">Aggiorna a <?= e($info['version']) ?></button>
        <?php if (!empty($info['url'])): ?><a class="btn" href="<?= e($info['url']) ?>" target="_blank" rel="noopener">Vedi la release</a><?php endif; ?>
      </form>
    <?php elseif (!$error): ?>
      <p><span class="badge badge--ok">Aggiornato</span></p>
    <?php endif; ?>
    <form method="post"><?= Admin::csrfField() ?><button class="btn btn--small" name="action" value="check">Controlla adesso</button>
      <?php if (!empty($info['checked'])): ?><span class="small muted">Ultimo controllo: <?= e(date('d.m.Y H:i', (int)$info['checked'])) ?></span><?php endif; ?></form>
    <p class="hint">Gli aggiornamenti arrivano dalle release di <code><?= e(Updater::repo()) ?></code> su GitHub e si installano solo se la firma corrisponde alla chiave del motore.
      Vengono sostituite solo le cartelle <code>core/</code>, <code>pp-admin/</code>, <code>pp-assets/</code>, <code>pp-themes/</code> e i file <code>index.php</code> e <code>cron.php</code>:
      <code>site/</code>, <code>uploads/</code> e <code>data/</code> non si toccano. Prima di ogni aggiornamento viene fatta una copia del database (Backup e modelli): immagini e file caricati non si toccano, quindi non si copiano.</p>
  </section>
  <section class="panel stack">
    <h2>Ultimo aggiornamento</h2>
    <?php if (!empty($last['to'])): ?>
      <p><?= !empty($last['rolled_back']) ? 'Rimessa la versione' : 'Da ' . e($last['from']) . ' a' ?> <strong><?= e($last['to']) ?></strong> · <?= e(substr((string)$last['at'], 0, 16)) ?></p>
      <?php if (!empty($last['backup'])): ?><p class="small">Copia di sicurezza: <code><?= e($last['backup']) ?></code></p><?php endif; ?>
      <?php if (!empty($last['htaccess'])): ?><p><span class="badge badge--outdated">Da controllare</span> <span class="small">La nuova versione ha un <code>.htaccess</code> diverso dal tuo, che non è stato toccato:
        confrontalo con <code>data/updates/<?= e($last['htaccess']) ?></code> dal File Manager e copia le righe nuove.</span></p><?php endif; ?>
      <?php if (!empty($last['prev']) && empty($last['rolled_back'])): ?>
        <form method="post"><?= Admin::csrfField() ?><button class="btn btn--small btn--danger" name="action" value="rollback"
          data-confirm="Rimettere la versione <?= e($last['from']) ?>?">Torna alla <?= e($last['from']) ?></button></form>
        <p class="hint">Se dopo l'aggiornamento la gestione non si apre più: dal File Manager di SiteGround sposta le cartelle da <code>data/updates/<?= e($last['prev']) ?>/</code> alla cartella del sito, al posto di quelle attuali.</p>
      <?php endif; ?>
    <?php else: ?><p class="muted">Nessun aggiornamento fatto da qui.</p><?php endif; ?>
    <h3>Requisiti</h3>
    <ul class="rows">
      <?php foreach ($reqs as $r): ?><li><span><?= $r['ok'] ? '<span class="badge badge--ok">OK</span>' : '<span class="badge badge--outdated">Manca</span>' ?> <?= e($r['label']) ?></span></li><?php endforeach; ?>
    </ul>
  </section>
</div>
<?php if ($siteUpload): ?>
<section class="panel stack">
  <h2>Sito del progetto</h2>
  <p>Carica una versione nuova della cartella <code>site/</code> (lo zip del sito: configurazione, contenuti iniziali, testi, moduli e tema del progetto), senza FTP.
    <strong><code>config.php</code> non si tocca</strong>; i file che nella versione nuova non ci sono più vengono tolti. Prima viene fatta una copia della versione di adesso (in Backup e modelli).</p>
  <form method="post" enctype="multipart/form-data" class="stack"><?= Admin::csrfField() ?>
    <label class="field">Lo zip del sito<input type="file" name="site" accept=".zip" required></label>
    <?php if (!$follows): ?>
      <label class="check"><input type="checkbox" name="follow" value="1" checked> <span>Prendi le impostazioni dal <code>config.sample.php</code> nuovo. Il tuo <code>config.php</code> adesso è una copia completa, quindi le novità dello zip non arriverebbero:
        così tiene solo database e indirizzi, come lo scrive l'installazione (il vecchio resta in <code>data/backups/</code>).</span></label>
    <?php else: ?>
      <p class="hint">Il tuo <code>config.php</code> prende già tutto da <code>config.sample.php</code>: le impostazioni nuove dello zip valgono subito.</p>
    <?php endif; ?>
    <label class="field">La tua password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn--primary" name="action" value="site" data-confirm="Sostituire la cartella site/ con lo zip? config.php resta, della versione di adesso viene fatta una copia.">Carica il sito</button>
  </form>
  <p class="hint">Lo zip porta codice PHP sul server: carica solo zip preparati per il tuo progetto. Pagine e fasce già nel sito restano come sono; per ripartire dai contenuti iniziali nuovi: Backup e modelli → Azzera il sito.
    Per spegnere questa funzione: <code>'site_upload' => false</code> nel config.</p>
</section>
<?php endif; ?>

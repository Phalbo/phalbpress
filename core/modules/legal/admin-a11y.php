<?php $count = array_count_values(array_column($issues, 'sev')); ?>
<header class="head"><h1>Accessibilità</h1>
  <div class="head__actions"><a class="btn" target="_blank" rel="noopener" href="<?= e(PP::link(Legal::slug('accessibility', I18n::$master), I18n::$master)) ?>">Dichiarazione ↗</a></div>
</header>
<section class="panel">
  <div class="panel__head"><h2>Controlli sui contenuti</h2>
    <span><span class="badge badge--outdated"><?= $count['error'] ?? 0 ?> da correggere</span> <span class="badge badge--auto"><?= $count['warn'] ?? 0 ?> da verificare</span> <span class="badge badge--missing"><?= $count['info'] ?? 0 ?> note</span></span></div>
  <p class="hint">Testi alternativi, ordine dei titoli, link, tabelle, PDF con tag, contrasto dei colori, in tutte le lingue. Il tema rispetta già tastiera, focus visibile, lingua delle pagine, movimento ridotto e dimensioni del testo.</p>
  <?php if (!$issues): ?><p><span class="badge badge--ok">Nessun problema trovato</span></p><?php else: ?>
  <form method="get" class="row filters" role="search">
    <label class="field">Gravità<select name="sev"><option value="">Tutte</option>
      <?php foreach (['error' => 'Da correggere', 'warn' => 'Da verificare', 'info' => 'Note'] as $k => $l): ?><option value="<?= $k ?>" <?= $sev === $k ? 'selected' : '' ?>><?= $l ?> (<?= (int)($count[$k] ?? 0) ?>)</option><?php endforeach; ?></select></label>
    <label class="field grow">Cerca<input type="search" name="q" value="<?= e($q) ?>" placeholder="es. testo alternativo, nome della pagina"></label>
    <button class="btn" type="submit">Filtra</button>
    <?php if ($sev !== '' || $q !== ''): ?><a class="btn btn--small" href="<?= e(Admin::u('accessibility')) ?>">Azzera</a><?php endif; ?>
  </form>
  <?= $pager ?>
  <?php if (!$shown): ?><p class="empty">Nessun controllo corrisponde ai filtri.</p><?php endif; ?>
  <table class="table">
    <tbody>
    <?php foreach ($shown as $i): ?>
      <tr><td><span class="badge badge--<?= ['error' => 'outdated', 'warn' => 'auto', 'info' => 'missing'][$i['sev']] ?>"><?= ['error' => 'Correggi', 'warn' => 'Verifica', 'info' => 'Nota'][$i['sev']] ?></span></td>
        <td><?= e($i['msg']) ?><div class="small muted"><?= e($i['where']) ?></div></td>
        <td class="right"><a href="<?= e($i['link']) ?>">Apri</a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?= $pager ?>
  <?php endif; ?>
</section>
<section class="panel">
  <h2>Dichiarazione di accessibilità</h2>
  <form method="post" class="stack" data-form>
    <?= Admin::csrfField() ?>
    <div class="row">
      <label class="field">Stato
        <select name="a11y_status">
          <?php foreach (['full' => 'Conforme', 'partial' => 'Parzialmente conforme', 'none' => 'Non conforme'] as $k => $v): ?><option value="<?= $k ?>" <?= $cfg['a11y_status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Data dell’ultima revisione<input type="date" name="a11y_date" value="<?= e($cfg['a11y_date']) ?>"></label>
      <label class="field grow">Email per le segnalazioni<input type="email" name="a11y_email" value="<?= e($cfg['a11y_email']) ?>"></label>
    </div>
    <label class="field">Barriere note, una per riga (lingua originale; traduzioni in Testi del sito → Legale)
      <textarea name="issues" rows="4"><?= e(I18n::has('a11y_issues', I18n::$master) ? t('a11y_issues', I18n::$master) : '') ?></textarea></label>
    <p class="hint">I PDF senza tag vengono aggiunti da soli all’elenco delle barriere.</p>
    <button class="btn btn--primary">Salva</button>
  </form>
</section>

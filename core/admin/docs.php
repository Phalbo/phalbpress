<header class="head">
  <h1>Documenti</h1>
  <div class="head__actions"><a class="btn btn--primary" href="<?= e(Admin::u('doc/new')) ?>">Aggiungi documento</a></div>
</header>
<p class="hint">Un documento ha un file comune a tutte le lingue e, se serve, una versione propria per lingua. Il link pubblico resta sempre lo stesso: quando sostituisci il file, si aggiorna ovunque. Nei testi lo inserisci con il pulsante “Documento” dell’editor.</p>
<section class="panel">
<?php if (!$rows): ?>
  <p class="empty">Nessun documento. <a href="<?= e(Admin::u('doc/new')) ?>">Aggiungi il primo</a>.</p>
<?php else: ?>
  <table class="table">
    <thead><tr><th scope="col">Codice</th><th scope="col">Titolo</th><th scope="col">Categoria</th><th scope="col">Stato</th><th scope="col">Testi</th><th scope="col">File per lingua</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="nowrap"><?= e($r['code']) ?></td>
        <td><a class="strong" href="<?= e(Admin::u('doc/' . $r['id'] . '/' . I18n::$master)) ?>"><?= e($r['title'] ?: 'Senza titolo') ?></a>
          <div class="muted small"><?= $r['file'] ? e(basename($r['file'])) : 'nessun file' ?> · [doc:<?= (int)$r['id'] ?>]</div></td>
        <td><?= e(PP::$config['doc_categories'][$r['category']] ?? $r['category']) ?></td>
        <td><?= $r['status'] === 'published' ? '<span class="pill pill--on">Pubblicato</span>' : '<span class="pill">Bozza</span>' ?></td>
        <td><div class="dots"><?php foreach ($r['statuses'] as $l => $s): ?><a href="<?= e(Admin::u('doc/' . $r['id'] . '/' . $l)) ?>" class="dot dot--<?= e($s) ?>" title="<?= e(I18n::name($l) . ': ' . I18n::statusLabel($s)) ?>"><?= e(I18n::label($l)) ?><span class="sr-only">: <?= e(I18n::statusLabel($s)) ?></span></a><?php endforeach; ?></div></td>
        <td class="small"><?= $r['files'] ? e(strtoupper(implode(' ', $r['files']))) : '<span class="muted">solo comune</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</section>

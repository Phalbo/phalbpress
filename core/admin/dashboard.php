<header class="head">
  <h1>Panoramica</h1>
  <div class="head__actions">
    <a class="btn" href="<?= e(Admin::u('entry/new/news')) ?>">Nuova news</a>
    <a class="btn" href="<?= e(Admin::u('doc/new')) ?>">Nuovo documento</a>
  </div>
</header>

<section class="panel">
  <div class="panel__head">
    <h2>Stato delle traduzioni</h2>
    <a href="<?= e(Admin::u('translate')) ?>" class="btn btn--primary">Aggiorna le traduzioni</a>
  </div>
  <p class="hint">Si scrive in <?= e(I18n::name(I18n::$master)) ?>. Ogni volta che cambi un testo originale, le altre lingue diventano “Da aggiornare”.</p>
  <table class="table">
    <thead><tr><th scope="col">Lingua</th><th scope="col" class="num">Riviste</th><th scope="col" class="num">Automatiche da rivedere</th><th scope="col" class="num">Da aggiornare</th><th scope="col" class="num">Mancanti</th><th scope="col"><span class="sr">Azioni</span></th></tr></thead>
    <tbody>
    <?php foreach ($matrix as $l => $m): $tot = array_sum($m); $done = $tot ? round(($m['ok'] + $m['auto']) / $tot * 100) : 0; ?>
      <tr>
        <th scope="row"><?= e(I18n::name($l)) ?> <span class="muted"><?= e(I18n::label($l)) ?></span>
          <span class="meter" aria-label="<?= $done ?>% tradotto"><span style="width:<?= $done ?>%"></span></span></th>
        <td class="num"><?= $m['ok'] ?></td>
        <td class="num <?= $m['auto'] ? 'is-auto' : '' ?>"><?= $m['auto'] ?></td>
        <td class="num <?= $m['outdated'] ? 'is-outdated' : '' ?>"><?= $m['outdated'] ?></td>
        <td class="num <?= $m['missing'] ? 'is-missing' : '' ?>"><?= $m['missing'] ?></td>
        <td class="right"><a href="<?= e(Admin::u('strings/' . $l)) ?>">Testi del sito</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<div class="cols">
  <section class="panel">
    <h2>Modificati di recente</h2>
    <?php if (!$recent): ?><p class="muted">Ancora niente.</p><?php endif; ?>
    <ul class="rows">
      <?php foreach ($recent as $r): ?>
        <li><a href="<?= e(Admin::u('entry/' . $r['id'] . '/' . I18n::$master)) ?>"><?= e($r['title'] ?: 'Senza titolo') ?></a>
          <span class="muted"><?= e(PP::$config['types'][$r['type']]['one'] ?? $r['type']) ?> · <?= e(substr($r['updated_at'], 0, 16)) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="panel">
    <h2>Contenuti</h2>
    <ul class="rows">
      <?php foreach (PP::$config['types'] as $k => $t): ?>
        <li><a href="<?= e(Admin::u('entries/' . $k)) ?>"><?= e($t['label']) ?></a><span class="num"><?= $counts[$k] ?></span></li>
      <?php endforeach; ?>
      <li><a href="<?= e(Admin::u('docs')) ?>">Documenti</a><span class="num"><?= $counts['docs'] ?></span></li>
    </ul>
    <h2 class="mt">Attività</h2>
    <ul class="rows">
      <?php foreach ($log as $l): ?>
        <li><span><?= e($l['action']) ?> <span class="muted"><?= e($l['ref']) ?></span></span><span class="muted"><?= e(substr($l['at'], 5, 11)) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

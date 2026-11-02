<header class="head"><h1>SEO</h1></header>
<section class="panel">
  <form method="post" class="row" style="align-items:center">
    <?= Admin::csrfField() ?><input type="hidden" name="action" value="visibility">
    <label class="check grow"><input type="checkbox" name="hide" value="1" <?= Seo::hidden() ? 'checked' : '' ?>>
      <strong>Scoraggia i motori di ricerca</strong> <span class="muted">— mentre il sito è in lavorazione: nessuna pagina viene indicizzata (noindex) e robots.txt chiude il sito. Toglilo quando il sito è pronto.</span></label>
    <button class="btn btn--primary" type="submit">Salva</button>
  </form>
</section>
<nav class="tabs" aria-label="Lingue">
  <?php foreach (I18n::codes() as $l): ?>
    <a href="<?= e(Admin::u('seo/' . $l)) ?>" class="tab" <?= $l === $lang ? 'aria-current="page"' : '' ?>><span class="tab__code"><?= e(I18n::label($l)) ?></span><span class="tab__state"><?= e(I18n::name($l)) ?></span></a>
  <?php endforeach; ?>
</nav>
<p class="hint">Titolo e descrizione si compilano da soli dal titolo e dal sommario di ogni pagina, in ogni lingua. Scrivi qui solo se vuoi cambiarli: un campo vuoto torna automatico. Canonical, hreflang, Open Graph, dati strutturati e sitemap.xml sono sempre generati.</p>
<?php $kindNames = ['home' => 'Home', 'page' => 'Pagine', 'news' => 'Elenco news', 'news-item' => 'News', 'stories' => 'Elenco storie', 'stories-item' => 'Storie e lavori', 'stories-cat' => 'Categorie',
    'results' => 'Documenti', 'partners' => 'Partner', 'events' => 'Elenco eventi', 'events-item' => 'Eventi', 'legal' => 'Pagine legali', 'search' => 'Ricerca']; ?>
<form method="get" class="row filters" role="search">
  <label class="field">Tipo<select name="kind"><option value="">Tutti</option>
    <?php foreach ($kinds as $k => $n): ?><option value="<?= e($k) ?>" <?= $kind === $k ? 'selected' : '' ?>><?= e($kindNames[$k] ?? $k) ?> (<?= (int)$n ?>)</option><?php endforeach; ?></select></label>
  <label class="field grow">Cerca nell’indirizzo<input type="search" name="q" value="<?= e($q) ?>"></label>
  <button class="btn" type="submit">Filtra</button>
  <?php if ($kind !== '' || $q !== ''): ?><a class="btn btn--small" href="<?= e(Admin::u('seo/' . $lang)) ?>">Azzera</a><?php endif; ?>
</form>
<?= $pager ?>
<?php if (!$rows): ?><p class="empty">Nessuna pagina corrisponde ai filtri.</p><?php endif; ?>
<form method="post" data-form>
  <?= Admin::csrfField() ?>
  <section class="panel">
    <table class="table seo">
      <thead><tr><th scope="col">Pagina</th><th scope="col">Titolo <span class="muted">(max <?= Seo::TITLE_MAX ?>)</span></th><th scope="col">Descrizione <span class="muted">(max <?= Seo::DESC_MAX ?>)</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $ref => $r): $t = $r['custom']['title'] ?? ''; $d = $r['custom']['description'] ?? ''; $eff = $t ?: $r['auto']['title']; ?>
        <tr>
          <td class="small"><a href="<?= e(PP::link($r['path'], $lang)) ?>" target="_blank" rel="noopener">/<?= e($lang . '/' . $r['path']) ?></a>
            <?php if (($dupes[$eff] ?? 0) > 1): ?><div><span class="badge badge--outdated">Titolo duplicato</span></div><?php endif; ?></td>
          <td><input type="text" name="seo[<?= e($ref) ?>][title]" value="<?= e($t) ?>" placeholder="<?= e($r['auto']['title']) ?>" data-count="<?= Seo::TITLE_MAX ?>" lang="<?= e($lang) ?>">
            <div class="counter small muted"></div></td>
          <td><textarea name="seo[<?= e($ref) ?>][desc]" rows="2" placeholder="<?= e($r['auto']['desc']) ?>" data-count="<?= Seo::DESC_MAX ?>" lang="<?= e($lang) ?>"><?= e($d) ?></textarea>
            <div class="counter small muted"></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= $pager ?>
  </section>
  <div class="sticky-bar"><button class="btn btn--primary" type="submit">Salva</button></div>
</form>

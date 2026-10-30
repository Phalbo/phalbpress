<header class="head"><h1>SEO</h1></header>
<nav class="tabs" aria-label="Lingue">
  <?php foreach (I18n::codes() as $l): ?>
    <a href="<?= e(Admin::u('seo/' . $l)) ?>" class="tab" <?= $l === $lang ? 'aria-current="page"' : '' ?>><span class="tab__code"><?= e(I18n::label($l)) ?></span><span class="tab__state"><?= e(I18n::name($l)) ?></span></a>
  <?php endforeach; ?>
</nav>
<p class="hint">Titolo e descrizione si compilano da soli dal titolo e dal sommario di ogni pagina, in ogni lingua. Scrivi qui solo se vuoi cambiarli: un campo vuoto torna automatico. Canonical, hreflang, Open Graph, dati strutturati e sitemap.xml sono sempre generati.</p>
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
  </section>
  <div class="sticky-bar"><button class="btn btn--primary" type="submit">Salva</button></div>
</form>

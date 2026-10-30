<header class="head"><h1>Testi del sito</h1></header>
<?php $tabBase = Admin::u('strings'); $statuses = []; foreach (I18n::codes() as $l) $statuses[$l] = $l === I18n::$master ? 'master' : ''; ?>
<nav class="tabs" aria-label="Lingue">
  <?php foreach (I18n::codes() as $l): ?>
    <a href="<?= e($tabBase . '/' . $l) ?>" <?= $l === $lang ? 'aria-current="page"' : '' ?> class="tab"><span class="tab__code"><?= e(I18n::label($l)) ?></span><span class="tab__state"><?= e(I18n::name($l)) ?></span></a>
  <?php endforeach; ?>
</nav>
<p class="hint">Titoli di sezione, pulsanti, testi della home e del footer. <?= $lang === I18n::$master ? 'Questa è la lingua originale: se cambi un testo, le traduzioni diventano “Da aggiornare”.' : 'A sinistra l’originale, a destra la traduzione.' ?></p>
<form method="post" data-form>
  <?= Admin::csrfField() ?>
  <?php foreach ($groups as $g => $items): ?>
    <section class="panel">
      <h2><?= e($g) ?></h2>
      <div class="strings">
      <?php foreach ($items as $k => $it): ?>
        <div class="str">
          <div class="str__key"><code><?= e($k) ?></code><?php if ($lang !== I18n::$master): ?> <?= Admin::badge($it['status']) ?><?php endif; ?>
            <?php if ($it['note']): ?><div class="hint"><?= e($it['note']) ?></div><?php endif; ?>
            <?php if ($lang !== I18n::$master): ?><div class="str__src"><?= $it['block'] ? '<details><summary>Mostra l’originale</summary><div class="prose">' . Sanitizer::html($it['master']) . '</div></details>' : ($it['html'] ? Sanitizer::inline($it['master']) : e($it['master'])) ?></div><?php endif; ?>
          </div>
          <div class="str__val">
            <input type="hidden" name="o[<?= e($k) ?>]" value="<?= e($it['value']) ?>">
            <?php if ($it['long'] || $it['html']): ?>
              <textarea name="s[<?= e($k) ?>]" rows="<?= $it['block'] ? 14 : ($it['html'] ? 7 : 3) ?>" lang="<?= e($lang) ?>"><?= e($it['value']) ?></textarea>
            <?php else: ?>
              <input type="text" name="s[<?= e($k) ?>]" value="<?= e($it['value']) ?>" lang="<?= e($lang) ?>">
            <?php endif; ?>
            <?php if ($it['block']): ?><div class="hint">HTML: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;, &lt;a href&gt;. Lascia invariati i segnaposto tra graffe, es. {controller}.</div><?php elseif ($it['html']): ?><div class="hint">Ammessi: &lt;mark&gt; evidenziato, &lt;strong&gt;, &lt;em&gt;, &lt;a href&gt;</div><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <div class="sticky-bar">
    <button class="btn btn--primary" type="submit" name="action" value="save">Salva le modifiche</button>
    <?php if ($lang !== I18n::$master): ?><button class="btn" type="submit" name="action" value="review_all">Salva e segna tutte come riviste</button><?php endif; ?>
  </div>
</form>

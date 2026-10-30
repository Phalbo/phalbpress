<?php /* Modifica visuale: anteprima della home + pannello. Variabili: $lang, $langs, $kinds */ ?>
<header class="head"><h1>Modifica visuale</h1>
  <div class="row">
    <label class="vis-lang">Lingua <select data-vis-lang>
      <?php foreach ($langs as $l): ?><option value="<?= e($l) ?>" <?= $l === $lang ? 'selected' : '' ?>><?= e(strtoupper($l)) ?></option><?php endforeach; ?>
    </select></label>
    <div class="vis-device" role="group" aria-label="Larghezza dell'anteprima">
      <button type="button" class="btn btn--small" data-device="desktop" aria-pressed="true">Computer</button>
      <button type="button" class="btn btn--small" data-device="phone" aria-pressed="false">Telefono</button>
    </div>
    <a class="btn btn--small" href="<?= e(PP::link('', $lang)) ?>" target="_blank" rel="noopener">Apri il sito ↗</a>
  </div>
</header>
<div class="vis" data-vis data-base="<?= e(Admin::u('visual')) ?>" data-preview="<?= e(Admin::u('preview')) ?>" data-lang="<?= e($lang) ?>" data-csrf="<?= e(Auth::csrf()) ?>">
  <div class="vis__stage"><iframe class="vis__frame" title="Anteprima della home" data-vis-frame></iframe></div>
  <aside class="vis__side panel stack" aria-label="Fasce della home">
    <div data-vis-panel><p class="hint">Clicca una fascia nell'anteprima, o scegline una qui sotto, per cambiarne il fondo, l'immagine, lo spazio e la larghezza.
      I testi si cambiano dalla scheda della fascia; l'aspetto vale per tutte le lingue.</p></div>
    <h2 class="vis__h">Fasce</h2>
    <ol class="vis-list" data-vis-list></ol>
    <form class="row vis-add" data-vis-add>
      <label class="field">Aggiungi una fascia <select name="kind">
        <?php foreach ($kinds as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
      </select></label>
      <button class="btn btn--small" type="submit">Aggiungi</button>
    </form>
    <p class="hint">Una fascia nuova nasce nascosta: scrivi i testi, poi mostrala.</p>
    <p class="small muted" role="status" data-vis-status></p>
  </aside>
</div>
<script src="<?= e(PP::url('pp-admin/visual.js')) ?>?v=<?= PP::VERSION ?>" defer></script>

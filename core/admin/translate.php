<header class="head"><h1>Traduzioni</h1></header>
<?php if (!$enabled): ?>
  <section class="panel">
    <h2>Traduzione automatica non attiva</h2>
    <p>Per attivarla inserisci la chiave API in <code>site/config.php</code> → <code>translation.api_key</code>. Senza chiave puoi comunque tradurre a mano da ogni contenuto (schede lingua) e da “Testi del sito”.</p>
  </section>
<?php endif; ?>
<section class="panel" data-queue>
  <h2>Aggiorna tutto ciò che manca o è cambiato</h2>
  <p class="hint">Traduce dall’originale <?= e(I18n::name(I18n::$master)) ?> i contenuti, i documenti e i testi del sito che sono mancanti o “Da aggiornare”. Le traduzioni restano segnate come “Automatiche, da rivedere” finché qualcuno non le salva.</p>
  <div class="row">
    <label class="field">Lingua
      <select data-queue-lang><option value="">Tutte</option>
        <?php foreach (I18n::others() as $l): ?><option value="<?= e($l) ?>"><?= e(I18n::name($l)) ?></option><?php endforeach; ?>
      </select></label>
    <label class="check"><input type="checkbox" data-queue-auto> Rifai anche le automatiche non ancora riviste</label>
    <button class="btn btn--primary" type="button" data-queue-start <?= $enabled ? '' : 'disabled' ?>>Avvia</button>
    <button class="btn" type="button" data-queue-stop hidden>Ferma</button>
  </div>
  <p class="hint" data-queue-status></p>
  <ul class="rows" data-queue-list></ul>
</section>

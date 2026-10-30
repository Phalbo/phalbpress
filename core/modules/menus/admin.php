<header class="head"><h1>Menu</h1></header>
<nav class="tabs" aria-label="Menu">
  <?php foreach (Menus::NAMES as $k => $l): ?><a class="tab" href="<?= e(Admin::u('menus/' . $k)) ?>" <?= $k === $name ? 'aria-current="page"' : '' ?>><span class="tab__code"><?= e($l) ?></span></a><?php endforeach; ?>
</nav>
<section class="panel">
  <p class="hint"><?= $custom ? 'Menu personalizzato.' : ($name === 'main' ? 'Ora è attivo il menu automatico: le pagine con “Mostra nel menu” e le sezioni fisse. Salvando, diventa personalizzato.' : 'Nessuna voce: il footer mostra solo i link legali.') ?>
    L’etichetta vuota usa il titolo della pagina (già tradotto). Per i link esterni scrivi l’etichetta almeno in <?= e(I18n::name(I18n::$master)) ?>.
    “Sotto” mette la voce dentro quella precedente (sottomenu).</p>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <div class="menu-rows">
    <?php $rows[] = ['kind' => '', 'ref' => '', 'url' => '', 'label' => []]; $rows[] = ['kind' => '', 'ref' => '', 'url' => '', 'label' => []];
    foreach ($rows as $i => $r): ?>
      <fieldset class="menu-row panel">
        <legend class="small"><?= $r['kind'] ? 'Voce ' . ($i + 1) : 'Nuova voce' ?></legend>
        <div class="row">
          <label class="field">Posizione<input type="number" name="m[<?= $i ?>][order]" value="<?= ($i + 1) * 10 ?>" step="1" size="4"></label>
          <label class="field">Tipo<select name="m[<?= $i ?>][kind]">
            <option value="">—</option>
            <option value="page" <?= $r['kind'] === 'page' ? 'selected' : '' ?>>Pagina</option>
            <option value="section" <?= $r['kind'] === 'section' ? 'selected' : '' ?>>Sezione</option>
            <option value="url" <?= $r['kind'] === 'url' ? 'selected' : '' ?>>Link</option></select></label>
          <label class="field">Pagina<select name="m[<?= $i ?>][page]"><option value="">—</option>
            <?php foreach ($pages as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $r['kind'] === 'page' && (int)$r['ref'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['title'] ?: '#' . $p['id']) ?><?= $p['status'] !== 'published' ? ' (bozza)' : '' ?></option><?php endforeach; ?></select></label>
          <label class="field">Sezione<select name="m[<?= $i ?>][section]"><option value="">—</option>
            <?php foreach ($sections as $k => $sk): ?><option value="<?= e($k) ?>" <?= $r['kind'] === 'section' && $r['ref'] === $k ? 'selected' : '' ?>><?= e(I18n::t($sk, I18n::$master) ?: $k) ?></option><?php endforeach; ?></select></label>
          <label class="field grow">Link<input type="text" name="m[<?= $i ?>][url]" value="<?= e($r['url'] ?? '') ?>" placeholder="https://…"></label>
        </div>
        <div class="row">
          <?php foreach (I18n::codes() as $l): ?><label class="field">Etichetta <?= e(I18n::label($l)) ?><input type="text" name="m[<?= $i ?>][label][<?= e($l) ?>]" value="<?= e($r['label'][$l] ?? '') ?>" size="12" lang="<?= e($l) ?>"></label><?php endforeach; ?>
        </div>
        <div class="row">
          <label class="check"><input type="checkbox" name="m[<?= $i ?>][parent]" value="1" <?= !empty($r['parent']) ? 'checked' : '' ?>> Sotto la voce precedente</label>
          <label class="check"><input type="checkbox" name="m[<?= $i ?>][new]" value="1" <?= !empty($r['new']) ? 'checked' : '' ?>> Apri in una nuova scheda</label>
          <?php if ($r['kind']): ?><label class="check"><input type="checkbox" name="m[<?= $i ?>][remove]" value="1"> Togli</label><?php endif; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
    </div>
    <div class="row">
      <button class="btn btn--primary" name="action" value="save">Salva il menu</button>
      <?php if ($custom): ?><button class="btn" name="action" value="reset" data-confirm="Tornare al menu automatico?">Torna al menu automatico</button><?php endif; ?>
    </div>
  </form>
</section>

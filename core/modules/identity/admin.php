<?php $hintLogo = 'SVG (consigliato: viene ripulito e resta nitido a ogni misura) oppure PNG trasparente, almeno 600 px di larghezza.'; ?>
<header class="head"><h1>Identità visiva</h1></header>
<form method="post" enctype="multipart/form-data" data-form class="stack">
  <?= Admin::csrfField() ?>
  <div class="cols">
    <section class="panel stack">
      <h2>Logo e nome</h2>
      <label class="field">Nome del sito<input type="text" name="site_name" value="<?= e($s['site_name']) ?>"></label>
      <label class="field">Claim (nella lingua originale; le traduzioni in Testi del sito → Identità)<input type="text" name="claim" value="<?= e(I18n::has('claim', I18n::$master) ? t('claim', I18n::$master) : '') ?>"></label>
      <div class="logo-row">
        <div class="logo-box"><?= Identity::logo(false, 'logo-prev') ?: '<span class="muted">nessun logo</span>' ?></div>
        <div class="stack grow">
          <label class="field">Logo (per fondi chiari)<input type="file" name="logo" accept=".svg,.png,.webp"></label>
          <p class="hint"><?= e($hintLogo) ?></p>
          <?php if ($s['logo']): ?><label class="check"><input type="checkbox" name="remove_logo" value="1"> Togli il logo</label><?php endif; ?>
          <label class="check"><input type="checkbox" name="logo_has_name" value="1" <?= !empty($s['logo_has_name']) ? 'checked' : '' ?>> Il logo contiene già il nome (in testata non si ripete)</label>
          <label class="field">Altezza del logo in testata <span class="muted">(px; vuoto = quella del tema. Sul telefono al massimo 44)</span>
            <input type="number" name="logo_h" min="0" max="160" step="2" value="<?= (int)($s['logo_h'] ?? 0) ?: '' ?>" placeholder="es. 56"></label>
        </div>
      </div>
      <div class="logo-row">
        <div class="logo-box logo-box--dark"><?= ($s['logo_dark'] || $s['logo_dark_svg']) ? Identity::logo(true, 'logo-prev') : '<span class="muted">usa il logo principale</span>' ?></div>
        <div class="stack grow">
          <label class="field">Logo per fondi scuri (facoltativo)<input type="file" name="logo_dark" accept=".svg,.png,.webp"></label>
          <?php if ($s['logo_dark']): ?><label class="check"><input type="checkbox" name="remove_logo_dark" value="1"> Togli</label><?php endif; ?>
        </div>
      </div>
      <label class="field">Favicon (icona del browser)<input type="file" name="favicon" accept=".svg,.png"></label>
      <p class="hint">SVG o PNG quadrato 512×512. Se manca e il logo è SVG, si usa il logo.</p>
      <label class="field">Immagine di condivisione (social, WhatsApp)<input type="file" name="share_image" accept="image/*"></label>
      <p class="hint">1200×630 px. Si usa quando una pagina non ha una sua immagine.</p>
      <?php if ($s['share_image']): ?><img src="<?= e(PP::upload($s['share_image'])) ?>" alt="" class="thumb" style="max-width:320px"><?php endif; ?>
    </section>

    <section class="panel stack">
      <h2>Colori</h2>
      <div class="swatches">
        <?php foreach (Identity::COLORS as $k => [$label, $_]): ?>
          <label class="swatch"><input type="color" name="colors[<?= e($k) ?>]" value="<?= e($s['colors'][$k]) ?>" data-color="<?= e($k) ?>">
            <span><?= e($label) ?><br><code data-hex="<?= e($k) ?>"><?= e($s['colors'][$k]) ?></code></span></label>
        <?php endforeach; ?>
      </div>
      <p class="hint">Il colore del testo sopra i campi colorati (nero o bianco) viene scelto da solo per avere più contrasto.</p>
      <h2 class="mt">Contrasto (accessibilità WCAG AA)</h2>
      <table class="table">
        <tbody>
        <?php foreach ($checks as $c): $ok = $c['ratio'] >= $c['min']; ?>
          <tr><td><span class="contrast" style="background:<?= e($c['bg']) ?>;color:<?= e($c['fg']) ?>">Aa</span> <?= e($c['label']) ?></td>
            <td class="num nowrap"><?= number_format($c['ratio'], 2, ',', '') ?>:1</td>
            <td><?= $ok ? '<span class="badge badge--ok">OK</span>' : '<span class="badge badge--outdated">Troppo basso (min ' . $c['min'] . ')</span>' . ($c['suggest'] ? ' <span class="small">sfondo suggerito <code>' . e($c['suggest']) . '</code> <span class="contrast" style="background:' . e($c['suggest']) . ';color:' . e($c['fg']) . '">Aa</span></span>' : '') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="hint">Il controllo si aggiorna dopo il salvataggio.</p>
    </section>
  </div>

  <section class="panel stack">
    <h2>Caratteri</h2>
    <div class="cols">
      <label class="field">Titoli
        <select name="font_display" data-font-select="display">
          <?php foreach ($fonts as $name => $f): ?><option value="<?= e($name) ?>" <?= $s['font_display'] === $name ? 'selected' : '' ?>><?= e($name) ?><?= !empty($f['custom']) ? ' (caricato)' : '' ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Testo
        <select name="font_text" data-font-select="text">
          <?php foreach ($fonts as $name => $f): ?><option value="<?= e($name) ?>" <?= $s['font_text'] === $name ? 'selected' : '' ?>><?= e($name) ?><?= !empty($f['custom']) ? ' (caricato)' : '' ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <p class="hint">Tutti i font sono ospitati sul sito (nessuna richiesta a Google Fonts, che il Garante considera un trasferimento di dati). Atkinson Hyperlegible Next è disegnato per la massima leggibilità; Source Serif 4 copre anche il cirillico.</p>
    <div class="row">
      <label class="field">Foto negli elenchi <span class="muted">(schede, portfolio, gallerie)</span>
        <select name="photo_fx">
          <?php foreach (['' => 'Come le mostra il tema', 'none' => 'A colori', 'mono' => 'In bianco e nero', 'tint' => 'Virate nel colore principale'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['photo_fx'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Passandoci sopra
        <select name="photo_hover">
          <?php foreach (['' => 'Come fa il tema', 'color' => 'Tornano a colori', 'zoom' => 'Si ingrandiscono appena', 'none' => 'Niente'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['photo_hover'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <p class="hint">Le foto aperte (la pagina di un lavoro, l’apertura della home) restano sempre a colori.</p>
    <label class="field">Intestazioni delle pagine interne <span class="muted">(titolo in alto di pagine, news, portfolio)</span>
      <select name="band_h">
        <?php foreach (['' => 'Come il tema', 'compact' => 'Basse: titolo di misura, si arriva subito al contenuto', 'tall' => 'Alte'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['band_h'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select></label>
    <fieldset class="row">
      <legend class="field">Pulsanti</legend>
      <label class="check"><input type="radio" name="buttons" value="pill" <?= $s['buttons'] === 'pill' ? 'checked' : '' ?>> Arrotondati</label>
      <label class="check"><input type="radio" name="buttons" value="soft" <?= $s['buttons'] === 'soft' ? 'checked' : '' ?>> Angoli morbidi</label>
      <label class="check"><input type="radio" name="buttons" value="square" <?= $s['buttons'] === 'square' ? 'checked' : '' ?>> Squadrati</label>
    </fieldset>
  </section>

  <div class="sticky-bar"><button class="btn btn--primary" type="submit" name="action" value="save">Salva l’identità visiva</button></div>
</form>

<section class="panel">
  <h2>Aggiungi un font</h2>
  <form method="post" enctype="multipart/form-data" class="row">
    <?= Admin::csrfField() ?>
    <label class="field">Nome della famiglia<input type="text" name="family" placeholder="es. Titillium Web" required></label>
    <label class="field">File .woff2<input type="file" name="font" accept=".woff2" required></label>
    <label class="field">Pesi<input type="text" name="weight" value="400" size="8" title="es. 400, oppure 100 900 per i font variabili"></label>
    <label class="field">Stile<select name="style"><option value="normal">Normale</option><option value="italic">Corsivo</option></select></label>
    <label class="field">Tipo<select name="fallback"><option value="sans">Senza grazie</option><option value="serif">Con grazie</option></select></label>
    <button class="btn" name="action" value="font_upload">Carica</button>
  </form>
  <p class="hint">Carica un file per ogni stile (normale, corsivo). Verifica che la licenza permetta l’uso sul web.</p>
</section>

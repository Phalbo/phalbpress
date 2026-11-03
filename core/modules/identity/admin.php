<?php $hintLogo = 'SVG (consigliato: viene ripulito e resta nitido a ogni misura) oppure PNG trasparente, almeno 600 px di larghezza.'; ?>
<header class="head"><h1>Identità visiva</h1></header>
<?php $tv = PP::themes()[PP::theme()] ?? []; $variants = $tv['palettes'] ?? []; ?>
<?php if ($variants): ?>
<section class="panel stack">
  <h2>Varianti di colore del tema <?= e($tv['name'] ?? PP::theme()) ?></h2>
  <p class="hint">Colori e caratteri pensati per questo tema. Una variante sostituisce colori, font e pulsanti qui sotto; poi puoi ritoccarli. Prima guardala in anteprima.</p>
  <ul class="variants">
    <?php foreach ($variants as $i => $v): ?>
    <li class="variant">
      <span class="variant__sw"><?php foreach (['ground', 'ink', 'accent', 'field1', 'field2', 'dark', 'hero2'] as $k): if (!empty($v['palette'][$k])): ?><i style="background:<?= e($v['palette'][$k]) ?>" title="<?= e(Identity::COLORS[$k][0] ?? $k) ?>"></i><?php endif; endforeach; ?></span>
      <strong><?= e($v['name'] ?? ('Variante ' . ($i + 1))) ?></strong>
      <?php if (!empty($v['note'])): ?><span class="small muted"><?= e($v['note']) ?></span><?php endif; ?>
      <?php if (!empty($v['fonts'])): ?><span class="small">Font: <?= e(implode(' + ', array_unique(array_filter([$v['fonts']['display'] ?? '', $v['fonts']['text'] ?? ''])))) ?></span><?php endif; ?>
      <form method="post" class="row"><?= Admin::csrfField() ?><input type="hidden" name="variant" value="<?= (int)$i ?>">
        <a class="btn btn--small" href="<?= e(Admin::u('preview') . '?theme=' . rawurlencode(PP::theme()) . '&variant=' . (int)$i) ?>" target="_blank" rel="noopener">Anteprima ↗</a>
        <button class="btn btn--small btn--primary" name="action" value="variant">Applica</button></form>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php if (Settings::get('identity_previous')): ?><form method="post"><?= Admin::csrfField() ?><button class="btn btn--small" name="action" value="variant_undo">Torna ai colori di prima</button></form><?php endif; ?>
</section>
<?php endif; ?>
<?php $pers = Palette::personal(); $pc = $pers ?: ['primary' => $s['colors']['accent'], 'secondary' => '', 'tertiary' => '', 'mood' => 'armonico']; ?>
<section class="panel stack personal" id="personale" data-personal>
  <h2>Colori personali</h2>
  <p class="hint">Scegli il colore principale (quello del logo, per esempio) e, se vuoi, un secondo e un terzo. Calcoliamo noi gli altri colori per il tema <?= e($tv['name'] ?? PP::theme()) ?>, con i contrasti giusti per leggere bene. La scelta resta anche se cambi tema: i colori si ricalcolano per quello nuovo.</p>
  <?php if ($pers): ?><p class="small"><span class="badge badge--ok">In uso</span> I colori del sito vengono da questa scelta.</p><?php endif; ?>
  <form method="post" class="stack" data-personal-form>
    <?= Admin::csrfField() ?>
    <div class="personal__colors">
      <label class="field">Colore principale<input type="color" name="p1" value="<?= e($pc['primary']) ?>"></label>
      <div class="field"><label class="check"><input type="checkbox" name="use2" value="1" <?= $pc['secondary'] ? 'checked' : '' ?>> Secondo colore</label><input type="color" name="p2" value="<?= e($pc['secondary'] ?: '#888888') ?>" aria-label="Secondo colore"></div>
      <div class="field"><label class="check"><input type="checkbox" name="use3" value="1" <?= $pc['tertiary'] ? 'checked' : '' ?>> Terzo colore</label><input type="color" name="p3" value="<?= e($pc['tertiary'] ?: '#cccccc') ?>" aria-label="Terzo colore"></div>
    </div>
    <fieldset class="personal__moods"><legend>Carattere <span class="muted">(decide i colori che non scegli tu)</span></legend>
      <?php foreach (Palette::MOODS as $mk => [$ml, $md]): ?>
        <label class="check"><input type="radio" name="mood" value="<?= e($mk) ?>" <?= ($pc['mood'] ?? 'armonico') === $mk ? 'checked' : '' ?>> <strong><?= e($ml) ?></strong> <span class="small muted"><?= e($md) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <div class="personal__result" data-personal-result hidden>
      <span class="variant__sw" data-personal-sw></span>
      <p class="small" role="status" aria-live="polite" data-personal-status></p>
    </div>
    <div class="row">
      <a class="btn" target="_blank" rel="noopener" data-personal-preview data-base="<?= e(Admin::u('preview') . '?theme=' . rawurlencode(PP::theme())) ?>" href="<?= e(Admin::u('preview') . '?' . http_build_query(['theme' => PP::theme(), 'p1' => $pc['primary'], 'use2' => $pc['secondary'] ? 1 : '', 'p2' => $pc['secondary'], 'use3' => $pc['tertiary'] ? 1 : '', 'p3' => $pc['tertiary'], 'mood' => $pc['mood'] ?? 'armonico'])) ?>">Anteprima ↗</a>
      <button class="btn btn--primary" name="action" value="personal">Applica</button>
    </div>
  </form>
</section>
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
          <?php $pfx = ($s['photo_fx'] ?? '') === 'none' ? '' : ($s['photo_fx'] ?? ''); foreach (['' => 'A colori', 'soft' => 'A colori con un velo tenue del colore principale', 'theme' => 'Come le mostra il tema', 'mono' => 'In bianco e nero', 'tint' => 'Virate nel colore principale'] as $k => $l): ?><option value="<?= $k ?>" <?= $pfx === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Forma delle foto negli elenchi <span class="muted">(portfolio, categorie, news)</span>
        <select name="photo_ratio">
          <?php foreach (['' => 'Quadrate', 'layout' => 'Come la disposizione scelta', '4:3' => '4:3', '3:2' => '3:2 (orizzontali)', '16:9' => '16:9 (video)', '3:4' => '3:4 (verticali)', 'auto' => 'Intere, come sono'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['photo_ratio'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Passandoci sopra
        <select name="photo_hover">
          <?php foreach (['' => 'Come fa il tema', 'color' => 'Tornano a colori', 'zoom' => 'Si ingrandiscono appena', 'none' => 'Niente'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['photo_hover'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <p class="hint">Le foto aperte (la pagina di un lavoro, l’apertura della home) restano sempre a colori. Una foto con una forma molto diversa dal riquadro (una copertina verticale in un riquadro quadrato) si vede intera su un fondo tenue invece di essere tagliata; nella scheda del contenuto si può chiedere di tagliarla.</p>
    <div class="row">
      <label class="field">Fondo delle intestazioni senza foto
        <select name="band_deco">
          <?php foreach (['' => 'Il motivo del tema', 'plain' => 'Tinta unita', 'gradient' => 'Sfumato verso il colore principale'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['band_deco'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
      <label class="field">Nome grande nel footer <span class="muted">(i temi che lo mettono)</span>
        <select name="foot_word"><?php foreach (['' => 'Come il tema', 'none' => 'Toglilo'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['foot_word'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
      <label class="field">Loghi di clienti e partner
        <select name="logos_size">
          <?php foreach (['s' => 'Piccoli', '' => 'Medi', 'l' => 'Grandi'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['logos_size'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></label>
    </div>
    <label class="field">Intestazioni delle pagine interne <span class="muted">(titolo in alto di pagine, news, portfolio)</span>
      <select name="band_h">
        <?php foreach (['' => 'Come il tema', 'compact' => 'Basse: titolo di misura, si arriva subito al contenuto', 'tall' => 'Alte'] as $k => $l): ?><option value="<?= $k ?>" <?= ($s['band_h'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select></label>
    <?php $opt = fn(string $name, array $list) => implode('', array_map(fn($k, $l) => '<option value="' . e((string)$k) . '"' . ((string)($s[$name] ?? '') === (string)$k ? ' selected' : '') . '>' . e($l) . '</option>', array_keys($list), $list)); ?>
    <?php $shades = ['' => 'Come il tema'] + array_combine(array_map('strval', range(0, 80, 10)), array_map(fn($n) => $n === 0 ? 'Nessun velo' : "Velo scuro al $n%", range(0, 80, 10))); ?>
    <div class="row">
      <label class="field">Foto delle intestazioni <span class="muted">(in alto nelle pagine)</span>
        <select name="band_fx"><?= $opt('band_fx', ['' => 'Come il tema', 'color' => 'A colori', 'mono' => 'In bianco e nero', 'tint' => 'Virate nel colore principale']) ?></select></label>
      <label class="field">Logo in testata
        <select name="logo_head"><?= $opt('logo_head', ['' => 'Da solo: quello chiaro se la testata è scura', 'day' => 'Sempre quello normale', 'night' => 'Sempre quello per fondi scuri']) ?></select></label>
    </div>
    <h3 class="small-head">Testo sulle foto</h3>
    <label class="field">Foto dell’apertura della home
      <select name="hero_fx"><?= $opt('hero_fx', ['' => 'Come il tema', 'color' => 'A colori', 'mono' => 'In bianco e nero']) ?></select></label>
    <div class="row">
      <label class="field">Apertura della home: velo sulla foto
        <select name="hero_shade"><?= $opt('hero_shade', $shades) ?></select></label>
      <label class="field">Apertura della home: testo
        <select name="hero_text"><?= $opt('hero_text', ['' => 'Come il tema', 'shadow' => 'Con un’ombra leggera', 'panel' => 'Su un fondino scuro']) ?></select></label>
    </div>
    <div class="row">
      <label class="field">Intestazioni con la foto dietro: velo
        <select name="band_shade"><?= $opt('band_shade', $shades) ?></select></label>
      <label class="field">Intestazioni con la foto dietro: testo
        <select name="band_text"><?= $opt('band_text', ['' => 'Come il tema', 'shadow' => 'Con un’ombra leggera', 'panel' => 'Su un fondino scuro']) ?></select></label>
    </div>
    <p class="hint">Se il testo bianco non si legge su una foto chiara: un velo dal 30-40% o l’ombra leggera di solito bastano.</p>
    <h3 class="small-head">Impaginazione</h3>
    <div class="row">
      <label class="field">Spazio tra le fasce
        <select name="space"><?= $opt('space', ['' => 'Come il tema', 'compact' => 'Compatto', 'wide' => 'Ampio']) ?></select></label>
      <?php $bxv = ($s['boxed'] ?? '') === true ? '1' : (($s['boxed'] ?? '') === false ? '' : (string)($s['boxed'] ?? '')); ?>
      <label class="field">Pagina in un riquadro centrale <span class="muted">(su schermi larghi; le foto di apertura restano a tutta larghezza)</span>
        <select name="boxed"><?php foreach (['' => 'Come il tema', '1' => 'Sì', '0' => 'No'] as $k => $l): ?><option value="<?= $k ?>" <?= $bxv === (string)$k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    </div>
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

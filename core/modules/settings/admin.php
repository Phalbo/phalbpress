<header class="head"><h1>Impostazioni del sito</h1></header>
<form method="post" class="stack">
  <?= Admin::csrfField() ?>
  <div class="cols">
    <section class="panel stack">
      <h2>Contatti</h2>
      <label class="field">Email di contatto <span class="muted">(footer, pagina contatti, richieste dal modulo)</span>
        <input type="email" name="contact_email" value="<?= e($c['contact_email'] ?? '') ?>"></label>
      <label class="field">Email del mittente <span class="muted">(da chi partono le email del sito, es. no-reply@dominio)</span>
        <input type="email" name="mail_from" value="<?= e($c['mail_from'] ?? '') ?>"></label>
      <label class="field">Social <span class="muted">(uno per riga: Nome | https://…)</span>
        <textarea name="social" rows="5" placeholder="Instagram | https://www.instagram.com/progetto&#10;LinkedIn | https://www.linkedin.com/company/progetto"><?= e($social) ?></textarea></label>
      <?php $sp = (string)($c['social_place'] ?? 'footer'); ?>
      <label class="field">Icone dei social <span class="muted">(con ogni tema)</span>
        <select name="social_place">
          <option value="footer"<?= $sp === 'footer' ? ' selected' : '' ?>>Nel footer</option>
          <option value="header"<?= $sp === 'header' ? ' selected' : '' ?>>Nella testata</option>
          <option value="both"<?= $sp === 'both' ? ' selected' : '' ?>>In testata e nel footer</option>
        </select></label>
      <label class="field">Finanziatore <span class="muted">(es. European Union: va nei dati strutturati per i motori di ricerca)</span>
        <input type="text" name="funder" value="<?= e($c['funder'] ?? '') ?>"></label>
      <?php $o = (array)($c['owner'] ?? []); $person = ($o['type'] ?? '') === 'person'; ?>
      <h2>Chi c'è dietro il sito</h2>
      <p class="hint">Serve ai dati strutturati che leggono Google e i motori di ricerca AI.</p>
      <label class="field">È il sito di
        <select name="owner_type">
          <option value="organization"<?= $person ? '' : ' selected' ?>>Un'organizzazione o un progetto</option>
          <option value="person"<?= $person ? ' selected' : '' ?>>Una persona (sito personale, portfolio)</option>
        </select></label>
      <label class="field">Nome e cognome <span class="muted">(solo per una persona; vuoto = nome del sito)</span>
        <input type="text" name="owner_name" value="<?= e($o['name'] ?? '') ?>"></label>
      <label class="field">Professione <span class="muted">(es. Regista e motion designer)</span>
        <input type="text" name="owner_job_title" value="<?= e($o['job_title'] ?? '') ?>"></label>
      <label class="field">Lavora per <span class="muted">(organizzazione attuale, facoltativo)</span>
        <input type="text" name="owner_works_for" value="<?= e($o['works_for'] ?? '') ?>"></label>
      <label class="field">Città <span class="muted">(facoltativo)</span>
        <input type="text" name="owner_location" value="<?= e($o['location'] ?? '') ?>"></label>
      <label class="field">Formazione <span class="muted">(scuola o università, facoltativo)</span>
        <input type="text" name="owner_alumni_of" value="<?= e($o['alumni_of'] ?? '') ?>"></label>
      <label class="field">Competenze <span class="muted">(una per riga)</span>
        <textarea name="owner_knows_about" rows="4"><?= e(implode("\n", (array)($o['knows_about'] ?? []))) ?></textarea></label>
      <p class="hint">Nome del sito, logo e colori: <a href="<?= e(Admin::u('identity')) ?>">Identità visiva</a>. Titolare del trattamento e dati per la privacy: <a href="<?= e(Admin::u('privacy')) ?>">Privacy e cookie</a>.</p>
    </section>
    <section class="panel stack">
      <h2>Traduzione automatica</h2>
      <label class="field">Servizio
        <select name="provider">
          <option value=""<?= empty($tr['provider']) ? ' selected' : '' ?>>Nessuno (il pulsante Traduci resta spento)</option>
          <option value="anthropic"<?= ($tr['provider'] ?? '') === 'anthropic' ? ' selected' : '' ?>>Claude (Anthropic): tutte le lingue del progetto</option>
          <option value="deepl"<?= ($tr['provider'] ?? '') === 'deepl' ? ' selected' : '' ?>>DeepL</option>
        </select></label>
      <label class="field">Chiave API <span class="muted"><?= !empty($tr['api_key']) ? '(già salvata: lascia vuoto per tenerla)' : '(nessuna salvata)' ?></span>
        <input type="password" name="api_key" autocomplete="off" value=""></label>
      <?php if (!empty($tr['api_key'])): ?><label class="check"><input type="checkbox" name="forget_key" value="1"> Togli la chiave salvata</label><?php endif; ?>
      <label class="field">Glossario <span class="muted">(parole da non tradurre: nome del progetto, sigle, partner; una per riga)</span>
        <textarea name="glossary" rows="6"><?= e(implode("\n", (array)($tr['glossary'] ?? []))) ?></textarea></label>
    </section>
  </div>
  <p class="hint">Queste impostazioni valgono sopra a <code>site/config.php</code>. Nel file restano il database e la struttura del sito (lingue, sezioni, tipi di contenuto).</p>
  <div><button class="btn btn--primary" type="submit">Salva</button></div>
</form>

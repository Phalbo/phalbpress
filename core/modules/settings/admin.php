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
      <label class="field">Finanziatore <span class="muted">(es. European Union: va nei dati strutturati per i motori di ricerca)</span>
        <input type="text" name="funder" value="<?= e($c['funder'] ?? '') ?>"></label>
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

<header class="head"><h1>AI search</h1></header>
<p class="hint">Sempre più persone cercano con ChatGPT, Claude, Perplexity o Gemini. Queste impostazioni decidono chi può leggere il sito e preparano un riassunto pulito che le AI usano per rispondere e citare le pagine.</p>
<form method="post" class="stack" data-form>
  <?= Admin::csrfField() ?>
  <div class="cols">
    <section class="panel stack">
      <h2>Chi può leggere il sito</h2>
      <label class="check"><input type="radio" name="policy" value="open" <?= $policy === 'open' ? 'checked' : '' ?>> <span><strong>Tutti</strong> — massima diffusione: ricerca e addestramento (consigliato per i progetti di disseminazione)</span></label>
      <label class="check"><input type="radio" name="policy" value="search" <?= $policy === 'search' ? 'checked' : '' ?>> <span><strong>Solo ricerca</strong> — le AI possono trovare e citare le pagine, ma non usarle per addestrarsi</span></label>
      <label class="check"><input type="radio" name="policy" value="closed" <?= $policy === 'closed' ? 'checked' : '' ?>> <span><strong>Nessuna AI</strong> — blocca tutti i crawler AI noti</span></label>
      <p class="hint">Le regole vanno in <a href="<?= e(PP::url('robots.txt')) ?>" target="_blank" rel="noopener">robots.txt</a>. I crawler seri le rispettano.</p>
    </section>
    <section class="panel stack">
      <h2>Cosa devono sapere le AI</h2>
      <label class="field">Riassunto (lingua originale)<textarea name="summary" rows="4"><?= e(I18n::has('ai_summary', I18n::$master) ? t('ai_summary', I18n::$master) : '') ?></textarea></label>
      <p class="hint">2–4 frasi con fatti verificabili: chi, cosa, dove, quando, chi finanzia. Se vuoto si usa il claim.</p>
      <label class="field">Fatti chiave, uno per riga<textarea name="facts" rows="6" placeholder="Coordinated by …&#10;Funded by the EU CERV programme, 2027–2028&#10;Countries: …"><?= e(I18n::has('ai_facts', I18n::$master) ? t('ai_facts', I18n::$master) : '') ?></textarea></label>
    </section>
  </div>
  <div class="sticky-bar"><button class="btn btn--primary">Salva</button></div>
</form>
<section class="panel">
  <div class="panel__head"><h2>Anteprima di llms.txt</h2><a class="btn btn--small" href="<?= e(PP::url('llms-full.txt')) ?>" target="_blank" rel="noopener">Testo completo ↗</a></div>
  <pre class="pre"><?= e($preview) ?></pre>
</section>

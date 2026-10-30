<header class="head"><h1>Utenti</h1></header>
<div class="cols">
<section class="panel">
  <h2>Chi può accedere</h2>
  <ul class="rows">
  <?php foreach ($users as $u): ?>
    <li><span><strong><?= e($u['name'] ?: $u['email']) ?></strong> <span class="muted"><?= e($u['email']) ?> · <?= e(strtolower(Auth::ROLES[$u['role']] ?? $u['role'])) ?><?= $u['role'] === 'translator' && $u['langs'] ? ' (' . e(strtoupper(str_replace(',', ', ', $u['langs']))) . ')' : '' ?></span>
      <?= $u['has2fa'] ? '<span class="badge badge--ok">2 passaggi</span>' : '<span class="badge badge--outdated">senza 2 passaggi</span>' ?></span>
      <?php if (Auth::isAdmin() && $u['has2fa'] && (int)$u['id'] !== (int)$me['id']): ?>
        <form method="post"><?= Admin::csrfField() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn--small" name="action" value="totp_reset" data-confirm="Azzerare l’accesso in due passaggi di questo utente (ha perso il telefono)?">Azzera 2 passaggi</button></form>
      <?php endif; ?>
 <?php if (Auth::isAdmin() && (int)$u['id'] !== (int)$me['id']): ?>
        <form method="post" class="row"><?= Admin::csrfField() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <select name="role" aria-label="Ruolo"><?php foreach (Auth::ROLES as $rk => $rl): ?><option value="<?= $rk ?>" <?= $u['role'] === $rk ? 'selected' : '' ?>><?= e($rl) ?></option><?php endforeach; ?></select>
          <input type="text" name="langs" value="<?= e($u['langs']) ?>" placeholder="lingue: it,es" size="9" aria-label="Lingue del traduttore">
          <button class="btn btn--small" name="action" value="role">Cambia</button></form>
        <form method="post"><?= Admin::csrfField() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn--small btn--danger" name="action" value="remove" data-confirm="Rimuovere questo utente?">Rimuovi</button></form>
      <?php endif; ?></li>
  <?php endforeach; ?>
  </ul>
  <?php if (Auth::isAdmin()): ?>
  <h2 class="mt">Aggiungi utente</h2>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <label class="field">Nome<input type="text" name="name"></label>
    <label class="field">Email<input type="email" name="email" required></label>
    <label class="field">Password <span class="muted">(almeno 12 caratteri)</span><input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
    <label class="field">Ruolo<select name="role">
      <option value="editor">Redattore: scrive, pubblica, gestisce menu, testi, documenti</option>
      <option value="author">Autore: scrive i propri contenuti in bozza</option>
      <option value="translator">Traduttore: rivede le traduzioni delle sue lingue</option>
      <option value="admin">Amministratore: tutto, compresi utenti e sistema</option></select></label>
    <fieldset class="field"><legend>Lingue (solo per i traduttori)</legend><div class="row">
      <?php foreach (I18n::others() as $l): ?><label class="check"><input type="checkbox" name="langs[]" value="<?= e($l) ?>"> <?= e(I18n::label($l)) ?></label><?php endforeach; ?></div></fieldset>
    <button class="btn btn--primary" name="action" value="add">Crea utente</button>
  </form>
  <?php endif; ?>
</section>
<section class="panel">
  <h2 id="totp">Accesso in due passaggi</h2>
  <?php if ($myTotp): ?>
    <p><span class="badge badge--ok">Attivo</span> Dopo la password serve il codice a 6 cifre dell’app.</p>
    <form method="post" class="row"><?= Admin::csrfField() ?>
      <label class="field">Password per disattivarlo<input type="password" name="current" required autocomplete="current-password"></label>
      <button class="btn btn--danger" name="action" value="totp_off">Disattiva</button></form>
  <?php elseif ($newSecret): ?>
    <ol class="steps-list">
      <li>Apri un’app di autenticazione (Google Authenticator, Microsoft Authenticator, Aegis, 2FAS…).</li>
      <li>Aggiungi un account con “Inserisci chiave di configurazione” e copia questa chiave:
        <p class="secret"><code><?= e(trim(chunk_split($newSecret, 4, ' '))) ?></code> <button type="button" class="btn btn--small" data-copy="<?= e($newSecret) ?>">Copia</button></p>
        <p class="small muted">Dal telefono puoi anche toccare <a href="<?= e(Totp::uri($newSecret, $me['email'], PP::$config['site_name'] ?? 'PhalbPress')) ?>">questo link</a>.</p></li>
      <li>Scrivi il codice a 6 cifre che compare nell’app:</li>
    </ol>
    <form method="post" class="row"><?= Admin::csrfField() ?>
      <label class="field">Codice<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" required size="8"></label>
      <button class="btn btn--primary" name="action" value="totp_confirm">Conferma e attiva</button></form>
  <?php else: ?>
    <p>Anche se qualcuno scopre la password, senza il tuo telefono non entra.</p>
    <form method="post"><?= Admin::csrfField() ?><button class="btn btn--primary" name="action" value="totp_start">Attiva</button></form>
  <?php endif; ?>
  <h2 class="mt">Cambia la tua password</h2>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <label class="field">Password attuale<input type="password" name="current" required autocomplete="current-password"></label>
    <label class="field">Nuova password <span class="muted">(almeno 12 caratteri)</span><input type="password" name="new" minlength="12" required autocomplete="new-password"></label>
    <button class="btn btn--primary" name="action" value="password">Aggiorna</button>
  </form>
</section>
</div>

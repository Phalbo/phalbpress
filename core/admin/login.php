<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Accesso · <?= e(PP::$config['site_name']) ?></title>
<link rel="stylesheet" href="<?= e(PP::url('pp-admin/admin.css')) ?>?v=<?= PP::VERSION ?>">
</head>
<body class="login">
<main class="login__box">
  <p class="brand"><span class="brand__mark" aria-hidden="true">P</span><span><strong><?= e(PP::$config['site_name']) ?></strong><small>PhalbPress</small></span></p>
  <h1><?= ['forgot' => 'Password dimenticata', 'reset' => 'Nuova password'][$step] ?? 'Accedi alla gestione' ?></h1>
  <?php if ($error): ?><p class="flash flash--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if (!empty($notice)): ?><p class="flash flash--ok" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($step === 'forgot'): ?>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <p class="hint">Scrivi l’email con cui accedi: ti mandiamo un link per scegliere una nuova password. Il link vale un’ora.</p>
    <label class="field">Email<input type="email" name="email" autocomplete="username" required autofocus></label>
    <button class="btn btn--primary" type="submit">Mandami il link</button>
    <a href="<?= e(Admin::u('login')) ?>">Torna all’accesso</a>
  </form>
  <?php elseif ($step === 'reset'): ?>
  <?php if (!empty($tokenOk)): ?>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <input type="hidden" name="t" value="<?= e($token) ?>">
    <label class="field">Nuova password <span class="muted">(almeno 12 caratteri)</span><input type="password" name="password" minlength="12" required autocomplete="new-password" autofocus></label>
    <button class="btn btn--primary" type="submit">Salva e accedi</button>
  </form>
  <?php else: ?><p><a href="<?= e(Admin::u('login/forgot')) ?>">Chiedi un nuovo link</a></p><?php endif; ?>
  <?php elseif ($step === 'totp'): ?>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <input type="hidden" name="step" value="totp">
    <label class="field">Codice dell’app di autenticazione<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" required autofocus></label>
    <button class="btn btn--primary" type="submit">Verifica</button>
  </form>
  <?php else: ?>
  <form method="post" class="stack">
    <?= Admin::csrfField() ?>
    <label class="field">Email<input type="email" name="email" autocomplete="username" required autofocus></label>
    <label class="field">Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn--primary" type="submit">Entra</button>
    <a href="<?= e(Admin::u('login/forgot')) ?>">Password dimenticata?</a>
  </form>
  <?php endif; ?>
</main>
</body>
</html>

<?php /* Modulo di contatto di base. Il tema può sostituirlo con partials/form.php (stesse variabili: $form $action $key $privacy $email). */ ?>
<?php $id = Forms::anchor($form); $f = $form === 'contact' ? 'pp-f' : 'pp-f-' . $form; ?>
<div class="pp-form" id="<?= e($id) ?>">
  <p class="pp-form__msg pp-form__msg--ok" id="<?= e($id) ?>-ok" role="status" tabindex="-1"><?= e(t('form_ok')) ?></p>
  <p class="pp-form__msg pp-form__msg--err" id="<?= e($id) ?>-err" role="alert" tabindex="-1"><?= e(str_replace('{email}', $email, t('form_error'))) ?></p>
  <form method="post" action="<?= e($action) ?>" data-pp-form>
    <input type="hidden" name="lang" value="<?= e(lang()) ?>">
    <input type="hidden" name="pp_t" value="">
    <input type="hidden" name="pp_k" value="">
    <div class="pp-form__trap" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <p class="pp-field"><label for="<?= e($f) ?>-name"><?= e(t('form_name')) ?> <span class="pp-req">(<?= e(t('form_required')) ?>)</span></label>
      <input id="<?= e($f) ?>-name" type="text" name="name" required maxlength="120" autocomplete="name"></p>
    <p class="pp-field"><label for="<?= e($f) ?>-email"><?= e(t('form_email')) ?> <span class="pp-req">(<?= e(t('form_required')) ?>)</span></label>
      <input id="<?= e($f) ?>-email" type="email" name="email" required maxlength="190" autocomplete="email"></p>
    <p class="pp-field"><label for="<?= e($f) ?>-org"><?= e(t('form_org')) ?></label>
      <input id="<?= e($f) ?>-org" type="text" name="org" maxlength="160" autocomplete="organization"></p>
    <p class="pp-field"><label for="<?= e($f) ?>-msg"><?= e(t('form_message')) ?> <span class="pp-req">(<?= e(t('form_required')) ?>)</span></label>
      <textarea id="<?= e($f) ?>-msg" name="message" rows="6" required minlength="5" maxlength="5000"></textarea></p>
    <p class="pp-check"><input id="<?= e($f) ?>-privacy" type="checkbox" name="privacy" value="1" required>
      <label for="<?= e($f) ?>-privacy"><?= Sanitizer::inline(str_replace('{privacy}', e($privacy), t('form_privacy'))) ?></label></p>
    <noscript><p class="pp-form__msg pp-form__msg--show"><?= e(str_replace('{email}', $email, t('form_nojs'))) ?></p></noscript>
    <p><button type="submit" class="pp-btn pp-btn--solid"><?= e(t('form_send')) ?></button></p>
  </form>
</div>

<?php /* Iscrizione alla newsletter. Il tema può sostituirlo con partials/form-newsletter.php (stesse variabili: $form $action $key $privacy $email). */ ?>
<div class="pp-form pp-form--nl" id="pp-form">
  <p class="pp-form__msg pp-form__msg--ok" id="pp-form-ok" role="status" tabindex="-1"><?= e(t('nl_ok')) ?></p>
  <p class="pp-form__msg pp-form__msg--err" id="pp-form-err" role="alert" tabindex="-1"><?= e(t('nl_error')) ?></p>
  <form method="post" action="<?= e($action) ?>" data-pp-form data-k="<?= e($key) ?>">
    <input type="hidden" name="lang" value="<?= e(lang()) ?>">
    <input type="hidden" name="pp_e" value="0">
    <input type="hidden" name="pp_k" value="">
    <div class="pp-form__trap" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="pp-nl__row">
      <p class="pp-field"><label for="pp-nl-email"><?= e(t('nl_email')) ?></label>
        <input id="pp-nl-email" type="email" name="email" required maxlength="190" autocomplete="email"></p>
      <p class="pp-nl__send"><button type="submit" class="pp-btn pp-btn--solid"><?= e(t('nl_send')) ?></button></p>
    </div>
    <p class="pp-check"><input id="pp-nl-privacy" type="checkbox" name="privacy" value="1" required>
      <label for="pp-nl-privacy"><?= Sanitizer::inline(str_replace('{privacy}', e($privacy), t('nl_privacy'))) ?></label></p>
  </form>
</div>

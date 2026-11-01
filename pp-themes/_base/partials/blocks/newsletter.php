<?php /* Iscrizione alla newsletter: email e consenso. Gli indirizzi arrivano in Gestione → Richieste (esportabili in CSV per Brevo, Mailchimp…). */ ?>
<section class="newsletter <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="newsletter__text">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="newsletter__head reveal"<?= $b['lg'] ?>><?= e($b['title'] ?: t('nl_title')) ?></h2>
    <?php if ($b['excerpt']): ?><p class="newsletter__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <?= class_exists('Forms') ? Forms::render('newsletter') : '' ?>
</section>

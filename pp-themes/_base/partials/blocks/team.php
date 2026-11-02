<?php
/* Squadra: le persone (tipo "person": foto, ruolo, email), nell'ordine scelto in gestione. */
$people = isset(PP::$config['types']['person']) ? Content::entries('person', lang(), 0, 'menu_order ASC, id ASC') : [];
$shape = in_array($m['shape'] ?? '', ['square', 'portrait'], true) ? $m['shape'] : 'round';
?>
<section class="team team--<?= e($shape) ?><?= !empty($m['mono']) ? ' team--mono' : '' ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="team__top">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="team__head reveal"<?= $b['lg'] ?>><?= e($b['title'] ?: t('team_title')) ?></h2>
    <?php if ($b['excerpt']): ?><p class="team__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <ul class="team__list">
    <?php foreach ($people as $p): $pm = $p['meta_arr']; $role = trim((string)($p['x']['role'] ?? ($pm['role'] ?? ''))); $lg = $p['_fallback'] ? ' lang="' . e(I18n::hreflang($p['_lang'])) . '"' : ''; ?>
      <li class="team__item reveal">
        <span class="team__pic<?= e(focus_class($p)) ?>"><?php if ($p['image']): ?><?= img($p['image'], '', '220px') ?><?php else: ?><span class="team__initials" aria-hidden="true"><?= e(mb_substr($p['title'], 0, 1)) ?></span><?php endif; ?></span>
        <h3 class="team__name"<?= $lg ?>><?= e($p['title']) ?></h3>
        <?php if ($role !== ''): ?><p class="team__role"<?= $lg ?>><?= e($role) ?></p><?php endif; ?>
        <?php if (!empty($pm['email']) && filter_var($pm['email'], FILTER_VALIDATE_EMAIL)): ?><p class="team__mail"><a href="mailto:<?= e($pm['email']) ?>"><?= e(t('team_write')) ?><span class="sr-only"> <?= e($p['title']) ?></span></a></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

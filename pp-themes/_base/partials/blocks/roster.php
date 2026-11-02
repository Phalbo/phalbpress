<?php
/* Elenco di persone scritto a mano: "Nome: ruolo" per riga, "# Gruppo" per i titoli, nomi separati da virgola sulla stessa riga. */
$groups = []; $cur = ['title' => '', 'rows' => [], 'names' => []];
foreach (preg_split('~\R~', (string)($x['list'] ?? '')) as $line) {
    $line = trim($line);
    if ($line === '') continue;
    if (str_starts_with($line, '#')) { if ($cur['rows'] || $cur['names'] || $cur['title'] !== '') $groups[] = $cur; $cur = ['title' => trim(ltrim($line, '#')), 'rows' => [], 'names' => []]; continue; }
    if (str_contains($line, ':')) { [$n, $r] = array_map('trim', explode(':', $line, 2)); $cur['rows'][] = [$n, $r]; }
    else foreach (array_filter(array_map('trim', explode(',', $line))) as $n) $cur['names'][] = $n;
}
if ($cur['rows'] || $cur['names'] || $cur['title'] !== '') $groups[] = $cur;
$cols = in_array($m['cols'] ?? '', ['1', '3'], true) ? (int)$m['cols'] : 2;
?>
<section class="roster roster--<?= $cols ?> <?= e(Blocks::toneClass($b['tone'])) ?>" id="<?= e($b['id_attr']) ?>" data-ink="<?= $b['tone'] === 'dark' ? 'light' : 'dark' ?>" aria-labelledby="<?= e($b['id_attr']) ?>-h">
  <div class="roster__top">
    <h2 id="<?= e($b['id_attr']) ?>-h" class="roster__head reveal"<?= $b['lg'] ?>><?= e($b['title']) ?></h2>
    <?php if ($b['excerpt']): ?><p class="roster__intro reveal"<?= $b['lg'] ?>><?= e($b['excerpt']) ?></p><?php endif; ?>
  </div>
  <div class="roster__groups">
    <?php foreach ($groups as $g): ?>
      <div class="roster__group reveal">
        <?php if ($g['title'] !== ''): ?><h3 class="roster__gtitle"<?= $b['lg'] ?>><?= e($g['title']) ?></h3><?php endif; ?>
        <?php if ($g['rows']): ?><dl class="roster__list"><?php foreach ($g['rows'] as [$n, $r]): ?><div class="roster__row"><dt><?= e($n) ?></dt><dd<?= $b['lg'] ?>><?= e($r) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
        <?php if ($g['names']): ?><p class="roster__names"><?= e(implode(', ', $g['names'])) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

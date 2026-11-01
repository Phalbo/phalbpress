<?php
/* Icone dei social (Impostazioni del sito → Social), in testata o nel footer. Icone disegnate in SVG, nome per i lettori di schermo. $where = head|foot */
$social = array_filter(PP::$config['social'] ?? []);
if (!$social) return;
$ic = [
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1" fill="currentColor" stroke="none"/>',
    'facebook' => '<path d="M14 21v-7.5h2.6l.4-3H14V8.6c0-.9.3-1.6 1.6-1.6H17V4.3a20 20 0 0 0-2.4-.1c-2.4 0-4 1.4-4 4.1v2.2H8v3h2.6V21"/>',
    'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="2.5"/><path d="M7.5 10.5V17M7.5 7.3v.1M11 17v-6.5M11 13.5c0-1.7 1-3 2.6-3S16.5 11.6 16.5 13.5V17"/>',
    'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10.2 9.2 15 12l-4.8 2.8z" fill="currentColor"/>',
    'x' => '<path d="M4 4l16 16M20 4 4 20" />',
    'twitter' => '<path d="M4 4l16 16M20 4 4 20" />',
    'bluesky' => '<path d="M12 11.5C10.5 8.5 7 5 4.5 5 3 5 3.5 8 4 9.5 4.7 11.6 7 12.3 9 12c-3 .5-4 2.3-2.5 4 2.6 2.8 4.6.4 5.5-2.2.9 2.6 2.9 5 5.5 2.2 1.5-1.7.5-3.5-2.5-4 2 .3 4.3-.4 5-2.5.5-1.5 1-4.5-.5-4.5-2.5 0-6 3.5-7.5 6.5z"/>',
    'tiktok' => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3c.5 2.7 2.3 4.4 5 4.6"/>',
    'threads' => '<path d="M16.5 11.3c-.4-2.6-2-3.8-4.3-3.8-2.7 0-4.3 1.8-4.3 4.5S9.5 16.5 12 16.5c2 0 3.4-1 3.4-2.6 0-1.4-1.2-2.3-3-2.3-1.6 0-2.6.8-2.6 1.9M19 8.5C17.8 5.3 15.3 3.5 12 3.5c-4.8 0-8 3.5-8 8.5s3.2 8.5 8 8.5c4 0 7-2.4 7-6"/>',
    'mastodon' => '<path d="M20 9.5c0-4.3-2.8-5.5-2.8-5.5C15.8 3.4 13.9 3 12 3s-3.8.4-5.2 1C6.8 4 4 5.2 4 9.5c0 3.7-.2 8 4.3 9.2 1.9.5 3.5.5 4.8.3 1.4-.2 2.4-.9 2.4-.9V16.5s-1.6.5-3.3.4c-1.9-.1-2.4-1.3-2.4-2.4 3.4.8 6.5.5 8.2-.4 2.1-1.1 2-4.7 2-4.7z"/><path d="M8.5 13V8.8c0-1 .8-1.8 1.8-1.8S12 7.8 12 8.8V11M12 11V8.8c0-1 .8-1.8 1.8-1.8s1.7.8 1.7 1.8V13"/>',
    'vimeo' => '<path d="M3 8.5 4.2 10s1.6-1.3 2.2-.6c.5.6 2 7.6 3.1 9 1.1 1.4 3.2.7 5.2-1.7C17.3 13.7 21 9 21 6.3S18.4 3.4 16.7 4.6c-1.6 1.2-2.4 3.4-2.4 3.4 1.6-1 3-1 2.2 1.2-.8 2.3-3 5.3-3.8 5.3s-1.6-6.6-2.4-9-3.4-1.3-7.3 3z"/>',
    'telegram' => '<path d="M21 4 3 11l6 2 2 6 3-4 5 4z"/><path d="m9 13 8-6"/>',
    'whatsapp' => '<path d="M4 20l1.2-4A8 8 0 1 1 8 18.8z"/><path d="M9 9c0 3 2.5 6 6 6l1.2-1.6-2-1-1 .8c-1-.5-2-1.5-2.4-2.4l.8-1-1-2z"/>',
    'github' => '<path d="M9 19c-4 1.5-4-2-6-2.5m12 4.5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/>',
    'flickr' => '<circle cx="7.5" cy="12" r="3.5"/><circle cx="16.5" cy="12" r="3.5" fill="currentColor"/>',
    'spotify' => '<circle cx="12" cy="12" r="9"/><path d="M7.5 9.5c3-1 6.5-.7 9.2.8M8 12.6c2.5-.7 5.2-.4 7.4.8M8.6 15.4c1.9-.5 3.9-.3 5.6.6"/>',
    'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
];
$key = function (string $name, string $url) use ($ic): string {
    $n = strtolower(preg_replace('~[^a-z]~i', '', $name));
    $h = strtolower((string)parse_url($url, PHP_URL_HOST));
    foreach (['instagram', 'facebook', 'linkedin', 'youtube', 'bluesky', 'tiktok', 'threads', 'mastodon', 'vimeo', 'telegram', 'whatsapp', 'github', 'flickr', 'spotify', 'twitter'] as $k) if (str_contains($n, $k) || str_contains($h, $k)) return $k;
    if (str_contains($h, 'youtu.be')) return 'youtube';
    if ($n === 'x' || $h === 'x.com' || str_ends_with($h, '.x.com')) return 'x';
    if (str_contains($h, 't.me')) return 'telegram';
    if (str_contains($h, 'wa.me')) return 'whatsapp';
    return 'link';
};
?>
<ul class="social social--<?= e($where ?? 'foot') ?>" aria-label="<?= e(t('social_label')) ?>">
  <?php foreach ($social as $n => $u): $k = $key((string)$n, (string)$u); $label = is_int($n) ? (parse_url($u, PHP_URL_HOST) ?: $u) : $n; ?>
    <li><a class="social__link" href="<?= e($u) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $ic[$k] ?></svg><span class="sr-only"><?= e($label) ?></span></a></li>
  <?php endforeach; ?>
</ul>

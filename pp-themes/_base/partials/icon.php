<?php
/* Icone a tratto (24×24, linea 1.6, angoli tondi), disegnate per PhalbPress. $icon = chiave; $size in px. Decorative: il testo accanto dice cosa sono. */
$paths = [
    'layers' => '<path d="m12 3.5 8.5 4.5-8.5 4.5L3.5 8z"/><path d="m3.5 12 8.5 4.5 8.5-4.5"/><path d="m3.5 16 8.5 4.5 8.5-4.5"/>',
    'send' => '<path d="M20.5 3.5 3.5 10.6l6.4 2.6 2.6 6.4z"/><path d="m9.9 13.2 10.6-9.7"/>',
    'folders' => '<rect x="3.5" y="4" width="4.6" height="16" rx="1"/><rect x="9.7" y="4" width="4.6" height="16" rx="1"/><rect x="15.9" y="4" width="4.6" height="16" rx="1"/><path d="M5.8 8v2.5M12 8v2.5M18.2 8v2.5"/>',
    'book' => '<path d="M12 6.5C10 5 7 4.5 3.5 5v13.5c3.5-.5 6.5 0 8.5 1.5 2-1.5 5-2 8.5-1.5V5C17 4.5 14 5 12 6.5z"/><path d="M12 6.5V20"/>',
    'chart' => '<path d="M4 20h16"/><rect x="5.5" y="11" width="3" height="6.5" rx=".6"/><rect x="10.5" y="7" width="3" height="10.5" rx=".6"/><rect x="15.5" y="4" width="3" height="13.5" rx=".6"/>',
    'megaphone' => '<path d="M4 10v4h3l8 4.5v-13L7 10z"/><path d="M7 14l1.3 5h2.4L9.5 14.4"/><path d="M18.5 9.5a3.5 3.5 0 0 1 0 5"/>',
    'video' => '<rect x="3.5" y="6" width="12" height="12" rx="2"/><path d="m15.5 10.5 5-3v9l-5-3z"/>',
    'globe' => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.4 2.4 3.5 5.2 3.5 8.5s-1.1 6.1-3.5 8.5c-2.4-2.4-3.5-5.2-3.5-8.5s1.1-6.1 3.5-8.5z"/>',
    'people' => '<circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5c.5-3.3 2.6-5.3 5.5-5.3s5 2 5.5 5.3"/><circle cx="16.5" cy="9.5" r="2.6"/><path d="M16 14.3c2.4 0 4.1 1.6 4.5 4.4"/>',
    'search' => '<circle cx="10.5" cy="10.5" r="6"/><path d="m15 15 5.5 5.5"/>',
    'document' => '<path d="M6 3.5h8l4.5 4.5v12.5H6z"/><path d="M14 3.5V8h4.5M9 12.5h6M9 16h6"/>',
    'idea' => '<path d="M9 17.5h6M10 20.5h4"/><path d="M12 3.5a5.5 5.5 0 0 0-3.2 10c.7.5 1.2 1.3 1.2 2.2v1.8h4v-1.8c0-.9.5-1.7 1.2-2.2A5.5 5.5 0 0 0 12 3.5z"/>',
    'handshake' => '<path d="m3.5 11 3.5-3.5 3 1 2.5-1.5 3 .5L19 11"/><path d="M3.5 11 8 15.5c.8.8 2 .8 2.8 0M19 11l1.5 1.5-5.8 5.8c-.8.8-2 .8-2.8 0L8.5 14"/><path d="m11 13 2-2 3.5 3.5"/>',
    'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    'pen' => '<path d="M4 20l1-4.5L15.5 5a2.1 2.1 0 0 1 3 3L8 18.5z"/><path d="m13.5 7 3 3"/>',
    'target' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
    'scale' => '<path d="M12 4v16M7 20h10M5 7h14"/><path d="M5 7l-2.5 6a2.5 2.5 0 0 0 5 0zM19 7l-2.5 6a2.5 2.5 0 0 0 5 0z"/>',
    'leaf' => '<path d="M5 19c0-8 5-13 14.5-14C19 14 14 19 6 19z"/><path d="M5 19c3-4 6-7 10-9"/>',
];
$name = isset($paths[$icon ?? '']) ? $icon : 'layers';
$size = (int)($size ?? 40);
?><svg class="icon icon--<?= e($name) ?>" viewBox="0 0 24 24" width="<?= $size ?>" height="<?= $size ?>" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $paths[$name] ?></svg>

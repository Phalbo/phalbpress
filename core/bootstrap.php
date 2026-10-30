<?php
define('PP_ROOT', dirname(__DIR__));
define('PP_SITE', PP_ROOT . '/site');
if (PHP_VERSION_ID < 80100) { http_response_code(500); exit('PhalbPress richiede PHP 8.1 o superiore.'); }
foreach (['DB', 'Settings', 'Modules', 'I18n', 'Sanitizer', 'Content', 'Media', 'Auth', 'Translator', 'Scheduler', 'PP'] as $c) require __DIR__ . '/' . $c . '.php';
PP::boot();

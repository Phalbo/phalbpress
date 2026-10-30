<?php
/**
 * PhalbPress — operazione pianificata. Pubblica e toglie i contenuti programmati e rigenera le pagine statiche.
 * Su SiteGround: Site Tools → Devs → Cron Jobs → ogni 15 minuti:
 *   php /home/UTENTE/www/DOMINIO/public_html/cron.php
 * Solo da riga di comando (dal web è bloccato).
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo da riga di comando.'); }
define('PP_CLI', true);
require __DIR__ . '/core/bootstrap.php';
$r = Scheduler::tick(true);
echo date('Y-m-d H:i') . ' ' . ($r ? 'aggiornato: ' . $r : 'niente da fare') . PHP_EOL;
// moduli con lavori periodici (es. controllo degli aggiornamenti del motore)
try { Hooks::fire('cron'); } catch (Throwable $e) { echo 'cron: ' . $e->getMessage() . PHP_EOL; }

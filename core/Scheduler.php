<?php
/**
 * Contenuti programmati (publish_at / unpublish_at). Il sito statico va rigenerato quando un contenuto entra o esce:
 * lo fa cron.php (consigliato, ogni 15 minuti) e, come riserva, ogni accesso alla gestione.
 */
final class Scheduler
{
    /** Ritorna una descrizione se qualcosa è cambiato (e ha rigenerato), altrimenti ''. */
    public static function tick(bool $force = false): string
    {
        $last = (int)Settings::get('cron_at', 0);
        if (!$force && $last > time() - 300) return '';
        $sig = md5(implode(',', array_column(DB::all('SELECT id FROM pp_entries WHERE ' . Content::live() . ' ORDER BY id'), 'id')));
        Settings::set('cron_at', time());
        $prev = Settings::get('cron_sig');
        if ($sig === $prev) return '';
        Settings::set('cron_sig', $sig);
        if ($prev === null) return '';
        try { Hooks::fire('saved', 'schedule'); } catch (Throwable $e) { return 'errore: ' . $e->getMessage(); }
        Auth::log('schedule', Content::now());
        return 'contenuti programmati, pagine rigenerate';
    }

    /** Prossimi cambiamenti in programma (per la pagina Sistema). */
    public static function upcoming(): array
    {
        $now = Content::now();
        return DB::all("SELECT e.id, e.type, e.publish_at, e.unpublish_at, t.title FROM pp_entries e LEFT JOIN pp_entry_tr t ON t.entry_id = e.id AND t.lang = ?
            WHERE e.status = 'published' AND (e.publish_at > ? OR e.unpublish_at > ?) ORDER BY e.publish_at, e.unpublish_at LIMIT 20", [I18n::$master, $now, $now]);
    }
}

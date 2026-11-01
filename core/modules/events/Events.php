<?php
/**
 * Eventi: un tipo di contenuto con data, ora e luogo, una pagina con i prossimi e i passati, la pagina del singolo evento
 * e il file .ics per aggiungerlo al calendario. Si spegne con 'events' => false nel config del sito.
 */
final class Events
{
    public static function enabled(): bool { return (PP::$config['events'] ?? true) !== false; }

    public static function slug(string $lang): string { return PP::section('events', $lang); }

    /** Eventi pubblicati, con la data di inizio (meta "start") ordinata. $when = upcoming | past | all. */
    public static function list(string $lang, string $when = 'all', int $limit = 0): array
    {
        $all = Content::entries('event', $lang, 0, 'pub_date ASC, id ASC');
        $today = date('Y-m-d');
        foreach ($all as &$e) { $e['_start'] = self::start($e); $e['_end'] = (string)($e['meta_arr']['end'] ?? '') ?: $e['_start']; }
        unset($e);
        if ($when === 'upcoming') $all = array_values(array_filter($all, fn($e) => $e['_end'] >= $today));
        if ($when === 'past') $all = array_values(array_filter($all, fn($e) => $e['_end'] < $today));
        usort($all, fn($a, $b) => $when === 'past' ? strcmp($b['_start'], $a['_start']) : strcmp($a['_start'], $b['_start']));
        return $limit ? array_slice($all, 0, $limit) : $all;
    }

    public static function start(array $e): string
    {
        $d = (string)($e['meta_arr']['start'] ?? '');
        return preg_match('~^\d{4}-\d{2}-\d{2}$~', $d) ? $d : (string)$e['pub_date'];
    }

    /** "12 ottobre 2026" oppure "12–14 ottobre 2026", con l'ora se c'è. */
    public static function when(array $e): string
    {
        $s = self::start($e);
        $end = (string)($e['meta_arr']['end'] ?? '');
        $out = fmt_date($s);
        if ($end && $end !== $s) $out .= ' – ' . fmt_date($end);
        $time = trim((string)($e['meta_arr']['time'] ?? ''));
        return $out . ($time !== '' ? ', ' . $time : '');
    }

    /** File .ics di un evento (tutto il giorno, o con l'ora se è scritta come 10:00 o 10:00-12:30). */
    public static function ics(int $id, string $lang): ?string
    {
        $e = Content::byId($id, $lang);
        if (!$e || $e['type'] !== 'event') return null;
        $esc = fn($s) => str_replace(["\\", ";", ",", "\r\n", "\n"], ["\\\\", "\;", "\\,", "\\n", "\\n"], (string)$s);
        $start = self::start($e);
        $end = (string)($e['meta_arr']['end'] ?? '') ?: $start;
        $time = trim((string)($e['meta_arr']['time'] ?? ''));
        $tz = PP::$config['timezone'] ?? 'Europe/Rome';
        if (preg_match('~^(\d{1,2})[:.](\d{2})(?:\s*[-–]\s*(\d{1,2})[:.](\d{2}))?~', $time, $t)) {
            $dt = 'DTSTART;TZID=' . $tz . ':' . str_replace('-', '', $start) . 'T' . sprintf('%02d%02d00', $t[1], $t[2]) . "\r\n";
            $dt .= 'DTEND;TZID=' . $tz . ':' . str_replace('-', '', $end) . 'T' . (isset($t[3]) ? sprintf('%02d%02d00', $t[3], $t[4]) : sprintf('%02d%02d00', min(23, (int)$t[1] + 1), $t[2])) . "\r\n";
        } else {
            $dt = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $start) . "\r\nDTEND;VALUE=DATE:" . date('Ymd', strtotime($end . ' +1 day')) . "\r\n";
        }
        $host = parse_url(PP::origin(), PHP_URL_HOST) ?: 'localhost';
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//PhalbPress//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\n"
            . 'UID:event-' . $id . '@' . $host . "\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n" . $dt
            . 'SUMMARY:' . $esc($e['title']) . "\r\n"
            . (!empty($e['x']['place']) ? 'LOCATION:' . $esc($e['x']['place']) . "\r\n" : '')
            . 'DESCRIPTION:' . $esc($e['excerpt']) . "\r\nURL:" . PP::abs(PP::entryUrl($e)) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    public static function icsUrl(array $e): string { return PP::url('ics/' . lang() . '/' . (int)$e['id'] . '.ics'); }
}

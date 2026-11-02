<?php
/**
 * Controlli automatici di accessibilità sui contenuti (tutte le lingue) e sui documenti.
 */
final class Checks
{
    public static function untaggedPdfs(): int
    {
        $n = 0;
        foreach (self::pdfs() as $p) if (!$p['info']['tagged']) $n++;
        return $n;
    }

    private static function pdfs(): array
    {
        $out = [];
        $rows = DB::all("SELECT d.id, d.code, d.file, d.status FROM pp_docs d WHERE d.status = 'published'");
        foreach ($rows as $d) {
            $files = ['*' => $d['file']];
            foreach (DB::all('SELECT lang, file FROM pp_doc_files WHERE doc_id = ?', [$d['id']]) as $f) $files[$f['lang']] = $f['file'];
            foreach ($files as $l => $f) {
                if (!$f || strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'pdf' || !is_file(PP_ROOT . '/' . $f)) continue;
                $out[] = ['doc' => $d, 'lang' => $l, 'file' => $f, 'info' => Media::pdfInfo(PP_ROOT . '/' . $f)];
            }
        }
        return $out;
    }

    public static function run(): array
    {
        $issues = [];
        $add = function ($sev, $where, $link, $msg) use (&$issues) { $issues[] = compact('sev', 'where', 'link', 'msg'); };
        $generic = '~^\s*(qui|clicca qui|click here|here|leggi|read more|more|di più|link|aqu[ií]|ovde|këtu|tukaj)\s*$~iu';

        foreach (DB::all('SELECT e.id, e.type, e.image, t.lang, t.title, t.body, t.image_alt FROM pp_entries e JOIN pp_entry_tr t ON t.entry_id = e.id') as $r) {
            $where = (PP::$config['types'][$r['type']]['one'] ?? $r['type']) . ' “' . $r['title'] . '” · ' . strtoupper($r['lang']);
            $link = Admin::u('entry/' . $r['id'] . '/' . $r['lang']);
            if ($r['image'] && trim((string)$r['image_alt']) === '' && !in_array($r['type'], ['partner'], true)) $add('warn', $where, $link, 'Immagine principale senza testo alternativo');
            $b = (string)$r['body'];
            if ($b === '') continue;
            preg_match_all('~<img\b[^>]*>~i', $b, $imgs);
            foreach ($imgs[0] as $img) if (!preg_match('~\balt="[^"]+~i', $img)) $add('error', $where, $link, 'Immagine nel testo senza testo alternativo');
            preg_match_all('~<h([1-6])~i', $b, $hs);
            $prev = 1;
            foreach ($hs[1] as $lv) {
                $lv = (int)$lv;
                if ($lv === 1) $add('error', $where, $link, 'Titolo H1 nel testo: la pagina ne ha già uno');
                elseif ($lv > $prev + 1) $add('warn', $where, $link, "Salto di livello nei titoli (da H$prev a H$lv)");
                $prev = $lv;
            }
            preg_match_all('~<a\b[^>]*>(.*?)</a>~si', $b, $as);
            foreach ($as[1] as $txt) {
                $t = trim(strip_tags($txt));
                if ($t === '') $add('error', $where, $link, 'Link senza testo');
                elseif (preg_match($generic, $t)) $add('warn', $where, $link, 'Link con testo generico (“' . $t . '”): scrivi dove porta');
            }
            if (preg_match('~<table\b(?:(?!</table>).)*</table>~si', $b, $tb) && !str_contains($tb[0], '<th')) $add('warn', $where, $link, 'Tabella senza intestazioni');
        }
        foreach (self::pdfs() as $p) {
            $where = 'Documento ' . ($p['doc']['code'] ?: '#' . $p['doc']['id']) . ' · ' . ($p['lang'] === '*' ? 'file comune' : strtoupper($p['lang'])) . ' · ' . basename($p['file']);
            $link = Admin::u('doc/' . $p['doc']['id'] . '/' . ($p['lang'] === '*' ? I18n::$master : $p['lang']));
            if (!$p['info']['tagged']) $add('warn', $where, $link, 'PDF senza tag: i lettori di schermo non ne leggono la struttura. Riesporta con “PDF con tag”.');
            elseif (!$p['info']['lang']) $add('info', $where, $link, 'PDF senza lingua del documento');
        }
        if (class_exists('Identity')) {
            foreach (Identity::checks(Identity::get()['colors']) as $c) {
                if ($c['ratio'] < $c['min']) $add('error', 'Identità visiva', Admin::u('identity'), 'Contrasto insufficiente: ' . $c['label'] . ' (' . $c['ratio'] . ':1, minimo ' . $c['min'] . ')');
            }
        }
        foreach (I18n::others() as $l) {
            $miss = 0;
            foreach (array_keys(I18n::defaults()) as $k) if (trim(t($k, I18n::$master)) !== '' && !I18n::has($k, $l)) $miss++;
            if ($miss) $add('info', 'Testi del sito · ' . strtoupper($l), Admin::u('strings/' . $l), "$miss testi non tradotti (si vede l’inglese)");
        }
        return $issues;
    }

    public static function admin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $keep = [];
            foreach (['a11y_status', 'a11y_date', 'a11y_email'] as $k) $keep[$k] = trim((string)Admin::post($k));
            Settings::set('legal', array_merge(Settings::get('legal', []), $keep));
            if (isset($_POST['issues'])) Content::saveString('a11y_issues', I18n::$master, (string)$_POST['issues']);
            Admin::changed('accessibility');
            Admin::flash('Dichiarazione di accessibilità aggiornata.');
            PP::redirect(Admin::u('accessibility'));
        }
        $all = self::run();
        $sev = (string)($_GET['sev'] ?? '');
        $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $sel = array_values(array_filter($all, fn($i) => ($sev === '' || $i['sev'] === $sev) && ($q === '' || str_contains(mb_strtolower($i['msg'] . ' ' . $i['where']), $q))));
        $pg = Admin::pager($sel, 50);
        Admin::view(__DIR__ . '/admin-a11y.php', ['title' => 'Accessibilità', 'issues' => $all, 'shown' => $pg['items'], 'found' => count($sel), 'pager' => $pg['html'], 'sev' => $sev, 'q' => $q, 'cfg' => Legal::cfg()]);
    }
}

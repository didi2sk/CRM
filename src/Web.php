<?php
declare(strict_types=1);

/** Pomocne funkcie pre webove rozhranie (escapovanie, formatovanie, layout). */
final class Web
{
    public static function h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(array $q): string
    {
        return '?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null));
    }

    public static function money(mixed $v): string
    {
        return $v === null || $v === '' ? '' : number_format((float) $v, 2, ',', "\u{00A0}");
    }

    /** DB uklada UTC; zobrazujeme v Europe/Bratislava. */
    public static function date(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        $v = (string) $v;
        if (strlen($v) === 10) {
            return date('d.m.Y', strtotime($v));
        }
        $d = new DateTimeImmutable($v, new DateTimeZone('UTC'));
        return $d->setTimezone(new DateTimeZone('Europe/Bratislava'))->format('d.m.Y H:i');
    }

    /** Text z HTML poli (popisy) - bez znaciek, bezpecne. */
    public static function text(mixed $v, int $max = 3000): string
    {
        $t = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", (string) $v))));
        return mb_strlen($t) > $max ? mb_substr($t, 0, $max) . '…' : $t;
    }

    public static function layout(string $title, string $body, ?string $user, string $active = ''): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:");
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        $nav = '';
        if ($user !== null) {
            $items = ['' => 'Prehľad', 'companies' => 'Firmy', 'projects' => 'Projekty', 'tasks' => 'Úlohy', 'invoices' => 'Doklady'];
            foreach ($items as $k => $label) {
                $nav .= '<a href="' . self::url(['p' => $k]) . '"' . ($k === $active ? ' class="on"' : '') . '>' . $label . '</a>';
            }
            $nav .= '<form method="post" action="?p=logout" class="logout"><input type="hidden" name="csrf" value="'
                . self::h($_SESSION['csrf'] ?? '') . '"><span>' . self::h($user) . '</span><button>Odhlásiť</button></form>';
        }
        echo '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex"><title>' . self::h($title) . ' – CRM</title><style>' . self::css() . '</style></head><body>'
            . '<header><b>CRM</b><nav>' . $nav . '</nav></header><main>' . $body . '</main></body></html>';
    }

    private static function css(): string
    {
        return <<<CSS
:root{--bg:#f6f7f9;--fg:#1d2330;--mut:#6b7385;--card:#fff;--line:#e3e6ec;--acc:#2456d6;--ok:#1a7f4b;--bad:#b3261e}
@media(prefers-color-scheme:dark){:root{--bg:#14171d;--fg:#e8eaf0;--mut:#9aa3b5;--card:#1d222b;--line:#2c3340;--acc:#7da2ff;--ok:#5ccf93;--bad:#ff8a80}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--fg);font:15px/1.45 system-ui,sans-serif}
header{display:flex;gap:24px;align-items:center;padding:10px 20px;background:var(--card);border-bottom:1px solid var(--line);flex-wrap:wrap}
nav{display:flex;gap:4px;align-items:center;flex:1;flex-wrap:wrap}nav a{padding:6px 12px;border-radius:6px;color:var(--fg);text-decoration:none}
nav a.on,nav a:hover{background:var(--bg);color:var(--acc)}.logout{margin-left:auto;display:flex;gap:10px;align-items:center;color:var(--mut)}
main{max-width:1250px;margin:20px auto;padding:0 16px}h1{font-size:22px;margin:0 0 14px}h2{font-size:16px;margin:24px 0 8px}
a{color:var(--acc)}.card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px 16px;margin-bottom:16px;overflow-x:auto}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:12px 14px}.stat b{display:block;font-size:24px}.stat span{color:var(--mut);font-size:13px}
table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:7px 10px;border-bottom:1px solid var(--line);vertical-align:top}
th{color:var(--mut);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}td.n{text-align:right;white-space:nowrap}
form.f{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}input,select,button{font:inherit;padding:7px 10px;border:1px solid var(--line);border-radius:6px;background:var(--card);color:var(--fg)}
button{cursor:pointer;background:var(--acc);color:#fff;border-color:var(--acc)}.logout button{background:none;color:var(--acc);border-color:var(--line)}
.pager{display:flex;gap:10px;align-items:center;margin-top:12px;color:var(--mut)}.tag{display:inline-block;background:var(--bg);border:1px solid var(--line);border-radius:99px;padding:0 9px;margin:0 4px 2px 0;font-size:12px}
.ok{color:var(--ok)}.bad{color:var(--bad)}.mut{color:var(--mut)}dl{display:grid;grid-template-columns:minmax(150px,230px) 1fr;gap:4px 14px;margin:0}dt{color:var(--mut);word-break:break-word}dd{margin:0;word-break:break-word;white-space:pre-wrap}
.login{max-width:340px;margin:12vh auto}.login input{width:100%;margin-bottom:10px}.err{color:var(--bad);margin-bottom:10px}
details summary{cursor:pointer;color:var(--mut);margin:8px 0}
CSS;
    }
}

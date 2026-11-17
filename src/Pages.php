<?php
declare(strict_types=1);

/** Stranky CRM (len na citanie) nad normalizovanymi tabulkami. */
final class Pages
{
    private const PER_PAGE = 50;
    private array $types = [];   // table => [column => SQL typ], z config/tables.php
    private array $refCache = [];

    public function __construct(private PDO $db, array $tablesCfg)
    {
        foreach ($tablesCfg as $t) {
            foreach ($t['columns'] as $col => [$type]) {
                $this->types[$t['table']][$col] = strtoupper($type);
            }
        }
    }

    /** Konfiguracia entit: zoznam, hladanie, filtre, suvisiace zaznamy. */
    private function entities(): array
    {
        return [
            'companies' => [
                'title' => 'Firmy', 'one' => 'name', 'order' => 'name ASC',
                'cols' => ['name' => 'Názov', 'id_num' => 'IČO', 'city' => 'Mesto', 'email' => 'E-mail', 'phone' => 'Telefón'],
                'search' => ['name', 'alias', 'id_num', 'tax_num', 'city', 'email'],
                'filters' => [],
                'refs' => ['user_id' => 'users', 'target_user_id' => 'users'],
                'related' => [
                    ['Kontakty', 'contacts', 'company_id', 'contacts', 'name ASC',
                        ['name' => 'Meno', 'contact_type_name' => 'Funkcia', 'email' => 'E-mail', 'mobile' => 'Mobil']],
                    ['Projekty', 'projects', 'company_id', 'projects', 'start_date DESC',
                        ['name' => 'Projekt', 'project_status_name' => 'Stav', 'start_date' => 'Od', 'end_date' => 'Do']],
                    ['Doklady', 'invoices', 'to_company_id', 'invoices', 'date_of_issue DESC',
                        ['number' => 'Číslo', 'kind' => 'Typ', 'name' => 'Názov', 'date_of_issue' => 'Vystavené', 'total_vat_cache' => 'Suma s DPH']],
                    ['Úlohy', 'tasks', 'company_id', 'tasks', 'end_time DESC',
                        ['name' => 'Úloha', 'project_name' => 'Projekt', 'end_time' => 'Termín', 'finished' => 'Hotová']],
                ],
            ],
            'contacts' => [
                'title' => 'Kontakty', 'one' => 'name', 'order' => 'name ASC',
                'cols' => ['name' => 'Meno', 'company_name' => 'Firma', 'contact_type_name' => 'Funkcia', 'email' => 'E-mail', 'mobile' => 'Mobil', 'phone' => 'Telefón'],
                'search' => ['name', 'email', 'mobile', 'phone', 'company_name'],
                'filters' => [
                    'type' => ['Funkcia', 'distinct', 'contact_type_name'],
                ],
                'refs' => ['company_id' => 'companies'],
                'related' => [],
            ],
            'projects' => [
                'title' => 'Projekty', 'one' => 'name', 'order' => 'created_at DESC',
                'cols' => ['name' => 'Projekt', 'company_name' => 'Firma', 'project_status_name' => 'Stav', 'start_date' => 'Od', 'end_date' => 'Do', 'finished' => 'Hotový'],
                'search' => ['name', 'company_name', 'order_number'],
                'filters' => [
                    'status' => ['Stav', 'distinct', 'project_status_name'],
                    'done' => ['Dokončenie', 'switch', ['0' => ['Otvorené', 'finished = 0'], '1' => ['Dokončené', 'finished = 1']]],
                ],
                'refs' => ['company_id' => 'companies', 'user_id' => 'users'],
                'related' => [
                    ['Úlohy', 'tasks', 'project_id', 'tasks', 'end_time ASC',
                        ['name' => 'Úloha', 'end_time' => 'Termín', 'task_priority_name' => 'Priorita', 'finished' => 'Hotová']],
                    ['Doklady', 'invoices', 'project_id', 'invoices', 'date_of_issue DESC',
                        ['number' => 'Číslo', 'kind' => 'Typ', 'name' => 'Názov', 'date_of_issue' => 'Vystavené', 'total_vat_cache' => 'Suma s DPH']],
                ],
            ],
            'tasks' => [
                'title' => 'Úlohy', 'one' => 'name', 'order' => 'finished ASC, end_time ASC',
                'cols' => ['name' => 'Úloha', 'project_name' => 'Projekt', 'company_name' => 'Firma', 'end_time' => 'Termín', 'task_priority_name' => 'Priorita', 'finished' => 'Hotová'],
                'search' => ['name', 'project_name', 'company_name'],
                'filters' => [
                    'done' => ['Stav', 'switch', ['0' => ['Otvorené', 'finished = 0'], '1' => ['Hotové', 'finished = 1'],
                        'late' => ['Po termíne', 'finished = 0 AND end_time < UTC_TIMESTAMP()']]],
                ],
                'refs' => ['project_id' => 'projects', 'company_id' => 'companies', 'user_id' => 'users', 'target_user_id' => 'users'],
                'related' => [],
            ],
            'invoices' => [
                'title' => 'Doklady', 'one' => 'name_with_number', 'order' => 'date_of_issue DESC, id DESC',
                'cols' => ['number' => 'Číslo', 'kind' => 'Typ', 'name' => 'Názov', 'to_company_name' => 'Odberateľ', 'date_of_issue' => 'Vystavené', 'total_vat_cache' => 'Suma s DPH', 'paid' => 'Uhradené'],
                'search' => ['number', 'name', 'to_company_name', 'project_name', 'variable_symbol'],
                'filters' => [
                    'kind' => ['Typ dokladu', 'distinct', 'kind'],
                    'paid' => ['Úhrada', 'switch', ['0' => ['Neuhradené', 'paid = 0'], '1' => ['Uhradené', 'paid = 1']]],
                ],
                'refs' => ['to_company_id' => 'companies', 'project_id' => 'projects', 'user_id' => 'users', 'task_id' => 'tasks'],
                'related' => [],
            ],
        ];
    }

    // ---------------------------------------------------------------- dashboard
    public function dashboard(): string
    {
        $one = fn(string $sql) => (int) $this->db->query($sql)->fetchColumn();
        $stats = [
            ['Firmy', $one('SELECT COUNT(*) FROM companies WHERE trash = 0'), 'companies'],
            ['Kontakty', $one('SELECT COUNT(*) FROM contacts WHERE trash = 0'), 'contacts'],
            ['Otvorené projekty', $one('SELECT COUNT(*) FROM projects WHERE trash = 0 AND finished = 0'), 'projects'],
            ['Otvorené úlohy', $one('SELECT COUNT(*) FROM tasks WHERE trash = 0 AND finished = 0'), 'tasks'],
            ['Úlohy po termíne', $one('SELECT COUNT(*) FROM tasks WHERE trash = 0 AND finished = 0 AND end_time < UTC_TIMESTAMP()'), 'tasks&done=late'],
            ['Doklady', $one('SELECT COUNT(*) FROM invoices WHERE trash = 0'), 'invoices'],
            ['Neuhradené doklady', $one("SELECT COUNT(*) FROM invoices WHERE trash = 0 AND paid = 0 AND kind IN ('invoice','proforma')"), 'invoices&paid=0&kind=invoice'],
        ];
        $h = '<h1>Prehľad</h1><div class="grid">';
        foreach ($stats as [$label, $n, $link]) {
            $h .= '<a class="stat" style="text-decoration:none;color:inherit" href="?p=' . $link . '"><b>' . $n . '</b><span>' . Web::h($label) . '</span></a>';
        }
        $h .= '</div>';

        $last = $this->db->query('SELECT finished_at, status FROM caflou_sync_runs ORDER BY id DESC LIMIT 1')->fetch();
        $h .= '<p class="mut">Posledná synchronizácia z Caflou: '
            . ($last ? Web::h(Web::date($last['finished_at'])) . ' (' . Web::h($last['status']) . ')' : 'zatiaľ žiadna') . '</p>';

        $rows = $this->db->query('SELECT id, number, kind, name, to_company_name, date_of_issue, total_vat_cache FROM invoices WHERE trash = 0 ORDER BY created_at DESC LIMIT 10')->fetchAll();
        $h .= '<h2>Najnovšie doklady</h2><div class="card">'
            . $this->table('invoices', $rows, ['number' => 'Číslo', 'kind' => 'Typ', 'name' => 'Názov', 'to_company_name' => 'Odberateľ', 'date_of_issue' => 'Vystavené', 'total_vat_cache' => 'Suma s DPH'], true)
            . '</div>';

        $rows = $this->db->query('SELECT id, name, project_name, end_time FROM tasks WHERE trash = 0 AND finished = 0 AND end_time < UTC_TIMESTAMP() ORDER BY end_time ASC LIMIT 10')->fetchAll();
        if ($rows) {
            $h .= '<h2>Úlohy po termíne</h2><div class="card">'
                . $this->table('tasks', $rows, ['name' => 'Úloha', 'project_name' => 'Projekt', 'end_time' => 'Termín'], true) . '</div>';
        }
        return $h;
    }

    // ---------------------------------------------------------------- zoznam
    public function list(string $key): string
    {
        $cfg = $this->entities()[$key];
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $where = [];
        $params = [];

        if (!isset($_GET['trash'])) {
            $where[] = '(trash = 0 OR trash IS NULL)';
        }
        if ($q !== '') {
            $like = [];
            foreach ($cfg['search'] as $c) {
                $like[] = "`$c` LIKE ?";
                $params[] = '%' . addcslashes($q, '%_\\') . '%';
            }
            $where[] = '(' . implode(' OR ', $like) . ')';
        }

        $formFilters = '';
        $keep = ['p' => $key];
        foreach ($cfg['filters'] as $name => [$label, $type, $arg]) {
            $val = (string) ($_GET[$name] ?? '');
            $opts = [];
            if ($type === 'distinct') {
                $vals = $this->db->query("SELECT DISTINCT `$arg` FROM `$key` WHERE `$arg` IS NOT NULL AND `$arg` <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($vals as $v) {
                    $opts[(string) $v] = (string) $v;
                }
                if ($val !== '' && isset($opts[$val])) {
                    $where[] = "`$arg` = ?";
                    $params[] = $val;
                }
            } else {
                foreach ($arg as $k => [$l, $sql]) {
                    $opts[(string) $k] = $l;
                    if ($val === (string) $k) {
                        $where[] = $sql;   // fixny SQL z konfiguracie, nie z requestu
                    }
                }
            }
            $formFilters .= '<select name="' . $name . '"><option value="">' . Web::h($label) . ': všetky</option>';
            foreach ($opts as $k => $l) {
                $formFilters .= '<option value="' . Web::h($k) . '"' . ($val === (string) $k ? ' selected' : '') . '>' . Web::h($l) . '</option>';
            }
            $formFilters .= '</select>';
            $keep[$name] = $val;
        }
        $keep['q'] = $q;
        if (isset($_GET['trash'])) {
            $keep['trash'] = '1';
        }

        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $cnt = $this->db->prepare("SELECT COUNT(*) FROM `$key` $w");
        $cnt->execute($params);
        $total = (int) $cnt->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $cols = array_unique(array_merge(['id'], array_keys($cfg['cols'])));
        $sel = implode(',', array_map(fn($c) => "`$c`", $cols));
        $st = $this->db->prepare("SELECT $sel FROM `$key` $w ORDER BY {$cfg['order']} LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE));
        $st->execute($params);
        $rows = $st->fetchAll();

        $h = '<h1>' . Web::h($cfg['title']) . '</h1>'
            . '<form class="f" method="get"><input type="hidden" name="p" value="' . $key . '">'
            . '<input type="search" name="q" value="' . Web::h($q) . '" placeholder="Hľadať…" size="28">' . $formFilters
            . '<label class="mut" style="align-self:center"><input type="checkbox" name="trash" value="1"' . (isset($_GET['trash']) ? ' checked' : '') . '> aj kôš</label>'
            . '<button>Hľadať</button></form>'
            . '<div class="card">' . $this->table($key, $rows, $cfg['cols'], true) . '</div>'
            . '<div class="pager"><span>' . $total . ' záznamov</span>';
        if ($page > 1) {
            $h .= '<a href="' . Web::url($keep + ['page' => $page - 1]) . '">← Predošlá</a>';
        }
        $h .= '<span>strana ' . $page . ' / ' . $pages . '</span>';
        if ($page < $pages) {
            $h .= '<a href="' . Web::url($keep + ['page' => $page + 1]) . '">Ďalšia →</a>';
        }
        return $h . '</div>';
    }

    // ---------------------------------------------------------------- detail
    public function detail(string $key, int $id): ?string
    {
        $cfg = $this->entities()[$key];
        $st = $this->db->prepare("SELECT * FROM `$key` WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        $h = '<p><a href="?p=' . $key . '">← ' . Web::h($cfg['title']) . '</a></p><h1>' . Web::h($row[$cfg['one']] ?? ('#' . $id)) . '</h1>';
        if (!empty($row['trash'])) {
            $h .= '<p class="bad">Záznam je v koši v Caflou.</p>';
        }
        if (!empty($row['deleted_in_source'])) {
            $h .= '<p class="bad">Záznam už v Caflou neexistuje (ostal len v zálohe).</p>';
        }

        $h .= '<div class="card"><dl>';
        foreach ($row as $col => $v) {
            if ($v === null || $v === '' || $v === '[]' || in_array($col, ['privacy', 'deleted_in_source', 'synced_at', 'id'], true)) {
                continue;
            }
            $h .= '<dt>' . Web::h($col) . '</dt><dd>' . $this->value($key, $col, $v, $cfg['refs'], $row) . '</dd>';
        }
        $h .= '</dl></div>';

        foreach ($cfg['related'] as [$title, $table, $fk, $entity, $order, $cols]) {
            $sel = implode(',', array_map(fn($c) => "`$c`", array_unique(array_merge(['id'], array_keys($cols)))));
            $q = $this->db->prepare("SELECT $sel FROM `$table` WHERE `$fk` = ? AND (trash = 0 OR trash IS NULL) ORDER BY $order LIMIT 200");
            $q->execute([$id]);
            $rows = $q->fetchAll();
            $h .= '<h2>' . Web::h($title) . ' (' . count($rows) . ')</h2><div class="card">'
                . ($rows ? $this->table($entity, $rows, $cols, true) : '<span class="mut">Žiadne záznamy.</span>') . '</div>';
        }
        return $h;
    }

    // ---------------------------------------------------------------- pomocne
    private function value(string $table, string $col, mixed $v, array $refs, array $row): string
    {
        if (isset($refs[$col])) {
            return $this->ref($refs[$col], (int) $v);
        }
        $type = $this->types[$table][$col] ?? '';
        if ($col === 'tags') {
            $out = '';
            foreach ((array) json_decode((string) $v, true) as $t) {
                $out .= '<span class="tag">' . Web::h($t) . '</span>';
            }
            return $out;
        }
        if (str_starts_with($type, 'TINYINT(1)')) {
            return $v ? '<span class="ok">áno</span>' : '<span class="mut">nie</span>';
        }
        if (str_starts_with($type, 'DECIMAL')) {
            return Web::h(Web::money($v)) . (str_contains($col, 'cache') || str_contains($col, 'incomes') || str_contains($col, 'expenses') ? ' ' . Web::h($row['currency'] ?? '') : '');
        }
        if (str_starts_with($type, 'DATE')) {
            return Web::h(Web::date($v));
        }
        if (str_starts_with($type, 'JSON')) {
            return Web::h(mb_substr((string) $v, 0, 500));
        }
        if (str_contains($type, 'TEXT')) {
            return Web::h(Web::text($v));
        }
        return Web::h($v);
    }

    private function ref(string $table, int $id): string
    {
        if ($id <= 0) {
            return '';
        }
        $nameCol = ['users' => 'name', 'companies' => 'name', 'projects' => 'name', 'tasks' => 'name'][$table];
        $k = "$table:$id";
        if (!array_key_exists($k, $this->refCache)) {
            $st = $this->db->prepare("SELECT `$nameCol` FROM `$table` WHERE id = ?");
            $st->execute([$id]);
            $this->refCache[$k] = $st->fetchColumn();
        }
        $name = $this->refCache[$k];
        if ($name === false) {
            return Web::h('#' . $id) . ' <span class="mut">(nie je v zálohe)</span>';
        }
        return $table === 'users'
            ? Web::h($name)
            : '<a href="?p=' . $table . '&id=' . $id . '">' . Web::h($name) . '</a>';
    }

    private function table(string $entity, array $rows, array $cols, bool $linkFirst): string
    {
        if (!$rows) {
            return '<span class="mut">Žiadne záznamy.</span>';
        }
        $h = '<table><thead><tr>';
        foreach ($cols as $label) {
            $h .= '<th>' . Web::h($label) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $h .= '<tr>';
            $first = true;
            foreach ($cols as $c => $_) {
                $type = $this->types[$entity][$c] ?? '';
                $v = $r[$c] ?? null;
                $num = false;
                if (str_starts_with($type, 'TINYINT(1)')) {
                    $cell = $v === null ? '' : ($v ? '<span class="ok">áno</span>' : '<span class="mut">nie</span>');
                } elseif (str_starts_with($type, 'DECIMAL')) {
                    $cell = Web::h(Web::money($v));
                    $num = true;
                } elseif (str_starts_with($type, 'DATE')) {
                    $cell = Web::h(Web::date($v));
                } else {
                    $cell = Web::h($v);
                }
                if ($first && $linkFirst) {
                    $cell = '<a href="?p=' . $entity . '&id=' . (int) $r['id'] . '">' . ($cell !== '' ? $cell : '(bez názvu)') . '</a>';
                    $first = false;
                }
                $h .= '<td' . ($num ? ' class="n"' : '') . '>' . $cell . '</td>';
            }
            $h .= '</tr>';
        }
        return $h . '</tbody></table>';
    }

    public function entityKeys(): array
    {
        return array_keys($this->entities());
    }
}

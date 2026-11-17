<?php
declare(strict_types=1);

/** Naplna normalizovane tabulky z JSON-u v caflou_records. */
final class TableNormalizer
{
    public function __construct(private PDO $db)
    {
    }

    public static function createSql(array $cfg): string
    {
        $cols = [];
        foreach ($cfg['columns'] as $name => [$type]) {
            $cols[] = "  `$name` $type";
        }
        $cols[] = '  `deleted_in_source` TINYINT(1) NOT NULL DEFAULT 0';
        $cols[] = '  `synced_at` DATETIME NOT NULL';
        $cols[] = '  PRIMARY KEY (`id`)';
        foreach ($cfg['indexes'] ?? [] as $idx) {
            $cols[] = "  KEY `idx_$idx` (`$idx`" . (stripos($cfg['columns'][$idx][0], 'VARCHAR') === 0 ? '(64)' : '') . ')';
        }
        return "CREATE TABLE IF NOT EXISTS `{$cfg['table']}` (\n" . implode(",\n", $cols)
            . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    }

    public static function mapRow(array $cfg, array $item): array
    {
        $row = [];
        foreach ($cfg['columns'] as $name => [, $key, $cast]) {
            $row[$name] = self::cast($item[$key] ?? null, $cast);
        }
        return $row;
    }

    private static function cast(mixed $v, string $cast): mixed
    {
        if ($v === null) {
            return null;
        }
        switch ($cast) {
            case 'int':
                return $v === '' ? null : (int) $v;
            case 'bool':
                return $v ? 1 : 0;
            case 'decimal':
                return $v === '' ? null : (string) $v;
            case 'datetime':
                if ($v === '') {
                    return null;
                }
                try {
                    return (new DateTimeImmutable((string) $v))
                        ->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                } catch (Exception) {
                    return null;
                }
            case 'json':
                return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            default:
                return is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }
    }

    public function createTable(array $cfg): void
    {
        $this->db->exec(self::createSql($cfg));
    }

    public function run(string $entity, array $cfg): array
    {
        $cols = array_keys($cfg['columns']);
        $all = array_merge($cols, ['deleted_in_source', 'synced_at']);
        $quoted = array_map(fn($c) => "`$c`", $all);
        $update = array_map(fn($c) => "`$c`=VALUES(`$c`)", array_diff($all, ['id']));
        $upsert = $this->db->prepare(
            "INSERT INTO `{$cfg['table']}` (" . implode(',', $quoted) . ') VALUES ('
            . implode(',', array_fill(0, count($all), '?')) . ') ON DUPLICATE KEY UPDATE ' . implode(',', $update)
        );

        $stmt = $this->db->prepare('SELECT data, deleted_at FROM caflou_records WHERE entity=?');
        $stmt->execute([$entity]);
        $now = gmdate('Y-m-d H:i:s');
        $n = 0;
        while ($r = $stmt->fetch()) {
            $item = json_decode($r['data'], true);
            if (!is_array($item)) {
                continue;
            }
            $row = array_values(self::mapRow($cfg, $item));
            $row[] = $r['deleted_at'] === null ? 0 : 1;
            $row[] = $now;
            $upsert->execute($row);
            $n++;
        }
        return [$n];
    }
}

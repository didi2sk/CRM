<?php
declare(strict_types=1);

final class Syncer
{
    public function __construct(private PDO $db, private CaflouClient $client)
    {
    }

    public static function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            Env::get('DB_NAME')
        );
        return new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /** @param array<string,array> $entities @param string[] $only */
    public function run(array $entities, array $only = []): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->prepare('INSERT INTO caflou_sync_runs (started_at) VALUES (?)')->execute([$now]);
        $runId = (int) $this->db->lastInsertId();

        $summary = [];
        $failed = 0;
        foreach ($entities as $name => $cfg) {
            if ($only && !in_array($name, $only, true)) {
                continue;
            }
            try {
                [$seen, $new, $changed, $deleted] = $this->syncEntity($name, $cfg, $runId);
                $summary[$name] = "videnych=$seen, novych=$new, zmenenych=$changed, zmazanych=$deleted";
            } catch (Throwable $e) {
                $failed++;
                $summary[$name] = 'CHYBA: ' . $e->getMessage();
            }
            echo sprintf("%-12s %s\n", $name, $summary[$name]);
            if (PHP_SAPI !== 'cli') {
                @flush();
            }
        }

        $status = $failed === 0 ? 'ok' : ($failed < count($summary) ? 'partial' : 'failed');
        $this->db->prepare('UPDATE caflou_sync_runs SET finished_at=?, status=?, summary=? WHERE id=?')
            ->execute([date('Y-m-d H:i:s'), $status, json_encode($summary, JSON_UNESCAPED_UNICODE), $runId]);

        return $failed === 0 ? 0 : 1;
    }

    private function syncEntity(string $name, array $cfg, int $runId): array
    {
        $sel = $this->db->prepare('SELECT data_hash FROM caflou_records WHERE entity=? AND remote_id=?');
        $ins = $this->db->prepare(
            'INSERT INTO caflou_records (entity, remote_id, data, data_hash, last_run_id, first_seen, updated_at)
             VALUES (?,?,?,?,?,?,?)'
        );
        $upd = $this->db->prepare(
            'UPDATE caflou_records SET data=?, data_hash=?, last_run_id=?, updated_at=?, deleted_at=NULL
             WHERE entity=? AND remote_id=?'
        );
        $touch = $this->db->prepare(
            'UPDATE caflou_records SET last_run_id=?, deleted_at=NULL WHERE entity=? AND remote_id=?'
        );

        $seen = $new = $changed = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($this->client->fetchAll($cfg['path'], $cfg['data_key'] ?? null) as $item) {
            if (!isset($item['id'])) {
                throw new RuntimeException("Zaznam bez 'id' v entite $name");
            }
            $id = (string) $item['id'];
            $json = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $hash = sha1($json);
            $seen++;

            $sel->execute([$name, $id]);
            $row = $sel->fetch();
            if (!$row) {
                $ins->execute([$name, $id, $json, $hash, $runId, $now, $now]);
                $new++;
            } elseif ($row['data_hash'] !== $hash) {
                $upd->execute([$json, $hash, $runId, $now, $name, $id]);
                $changed++;
            } else {
                $touch->execute([$runId, $name, $id]);
            }
        }

        // Dostali sme sa sem bez vynimky => cely zoznam bol stiahnuty;
        // zaznamy, ktore sme nevideli, uz v Caflou neexistuju (ostavaju v zalohe, len oznacene).
        $del = $this->db->prepare(
            'UPDATE caflou_records SET deleted_at=? WHERE entity=? AND last_run_id<>? AND deleted_at IS NULL'
        );
        $del->execute([$now, $name, $runId]);

        return [$seen, $new, $changed, $del->rowCount()];
    }
}

CREATE TABLE IF NOT EXISTS caflou_records (
    entity      VARCHAR(64)  NOT NULL,
    remote_id   VARCHAR(64)  NOT NULL,
    data        LONGTEXT     NOT NULL,           -- plna JSON odpoved z API
    data_hash   CHAR(40)     NOT NULL,           -- sha1(data), na detekciu zmien
    last_run_id INT UNSIGNED NOT NULL,           -- posledny beh, v ktorom bol zaznam videny
    first_seen  DATETIME     NOT NULL,
    updated_at  DATETIME     NOT NULL,           -- posledna zmena obsahu
    deleted_at  DATETIME     NULL,               -- zaznam uz v Caflou neexistuje
    PRIMARY KEY (entity, remote_id),
    KEY idx_entity_deleted (entity, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS caflou_sync_runs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    started_at  DATETIME NOT NULL,
    finished_at DATETIME NULL,
    status      VARCHAR(16) NOT NULL DEFAULT 'running',   -- running | ok | partial | failed
    summary     TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

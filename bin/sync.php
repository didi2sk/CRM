#!/usr/bin/env php
<?php
declare(strict_types=1);

// Pouzitie: php bin/sync.php [--init-db] [entita1 entita2 ...]
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer'] as $c) {
    require "$root/src/$c.php";
}

try {
    Env::load("$root/.env");
    $args = array_slice($argv, 1);
    $db = Syncer::connect();

    if (in_array('--init-db', $args, true)) {
        $db->exec(file_get_contents("$root/sql/schema.sql"));
        echo "Schema vytvorena.\n";
        $args = array_values(array_diff($args, ['--init-db']));
        if (!$args) {
            exit(0);
        }
    }

    $entities = require "$root/config/entities.php";
    $syncer = new Syncer($db, CaflouClient::fromEnv());
    exit($syncer->run($entities, $args));
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba: ' . $e->getMessage() . "\n");
    exit(2);
}

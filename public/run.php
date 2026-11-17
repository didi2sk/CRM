<?php
declare(strict_types=1);

// Spustenie zalohy cez web:
//   https://tvoja-domena/run.php?key=RUN_KEY[&entity=tasks,projects][&init=1]
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer'] as $c) {
    require "$root/src/$c.php";
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

try {
    Env::load("$root/.env");
    $runKey = Env::get('RUN_KEY', '');
    $given = (string) ($_GET['key'] ?? '');
    if ($runKey === '' || !hash_equals($runKey, $given)) {
        http_response_code(403);
        exit("Forbidden\n");
    }

    @set_time_limit(0);
    ignore_user_abort(true);
    @ini_set('zlib.output_compression', '0');

    $db = Syncer::connect();
    if (!empty($_GET['init'])) {
        $db->exec(file_get_contents("$root/sql/schema.sql"));
        echo "Schema vytvorena.\n";
    }

    $only = array_filter(array_map('trim', explode(',', (string) ($_GET['entity'] ?? ''))));
    $entities = require "$root/config/entities.php";
    $code = (new Syncer($db, CaflouClient::fromEnv()))->run($entities, $only);
    echo $code === 0 ? "HOTOVO\n" : "SKONCILO S CHYBAMI\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Chyba: ' . $e->getMessage() . "\n";
}

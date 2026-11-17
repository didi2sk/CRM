#!/usr/bin/env php
<?php
declare(strict_types=1);

// Pouzitie: php bin/normalize.php [--init-tables] [--print-sql] [entita ...]
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer', 'TableNormalizer'] as $c) {
    require "$root/src/$c.php";
}

try {
    $args = array_slice($argv, 1);
    $tables = require "$root/config/tables.php";
    $only = array_values(array_filter($args, fn($a) => $a[0] !== '-'));

    if (in_array('--print-sql', $args, true)) {
        foreach ($tables as $name => $cfg) {
            if (!$only || in_array($name, $only, true)) {
                echo TableNormalizer::createSql($cfg), "\n";
            }
        }
        exit(0);
    }

    Env::load("$root/.env");
    $norm = new TableNormalizer(Syncer::connect());
    foreach ($tables as $name => $cfg) {
        if ($only && !in_array($name, $only, true)) {
            continue;
        }
        $norm->createTable($cfg);   // IF NOT EXISTS - bezpecne pri kazdom behu
        if (in_array('--init-tables', $args, true)) {
            echo "$name: tabulka {$cfg['table']} pripravena\n";
            continue;
        }
        [$n] = $norm->run($name, $cfg);
        echo sprintf("%-12s prenesenych zaznamov: %d\n", $name, $n);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba: ' . $e->getMessage() . "\n");
    exit(2);
}

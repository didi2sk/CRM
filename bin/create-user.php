#!/usr/bin/env php
<?php
declare(strict_types=1);

// Pouzitie: php bin/create-user.php meno      (heslo sa spyta; pre existujuce meno ho zmeni)
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer', 'CrmAuth'] as $c) {
    require "$root/src/$c.php";
}

try {
    $username = $argv[1] ?? '';
    if ($username === '') {
        fwrite(STDERR, "Pouzitie: php bin/create-user.php meno\n");
        exit(1);
    }
    echo 'Heslo (min. 10 znakov): ';
    $tty = function_exists('posix_isatty') && posix_isatty(STDIN);
    if ($tty) {
        system('stty -echo');
    }
    $pw = trim((string) fgets(STDIN));
    if ($tty) {
        system('stty echo');
        echo "\n";
    }
    if (mb_strlen($pw) < 10) {
        fwrite(STDERR, "Heslo je prilis kratke.\n");
        exit(1);
    }

    Env::load("$root/.env");
    $db = Syncer::connect();
    CrmAuth::ensureSchema($db);
    // password_changed_at = NULL => pri prvom prihlaseni sa ponukne zmena hesla
    $db->prepare('INSERT INTO crm_users (username, password_hash, created_at, password_changed_at) VALUES (?,?,UTC_TIMESTAMP(),NULL)
                  ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), password_changed_at = NULL')
        ->execute([$username, password_hash($pw, PASSWORD_DEFAULT)]);
    echo "Pouzivatel '$username' ulozeny.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba: ' . $e->getMessage() . "\n");
    exit(2);
}

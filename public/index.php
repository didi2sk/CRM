<?php
declare(strict_types=1);

// Webove CRM (len na citanie). Document root webu = priecinok public/.
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer', 'Web', 'Pages'] as $c) {
    require "$root/src/$c.php";
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_name('crm_sid');
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

try {
    Env::load("$root/.env");
    $db = Syncer::connect();
} catch (Throwable $e) {
    http_response_code(500);
    Web::layout('Chyba', '<div class="card bad">Nepodarilo sa pripojiť k databáze. Skontroluj <code>.env</code>.</div>', null);
    exit;
}

$p = (string) ($_GET['p'] ?? '');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$csrfOk = $post && hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''));

// ---- odhlasenie
if ($p === 'logout' && $post && $csrfOk) {
    $_SESSION = [];
    session_destroy();
    header('Location: ?p=login');
    exit;
}

// ---- prihlasenie
if ($p === 'login') {
    $error = '';
    if ($post) {
        $ok = false;
        if ($csrfOk) {
            try {
                $st = $db->prepare('SELECT id, username, password_hash FROM crm_users WHERE username = ?');
                $st->execute([trim((string) ($_POST['username'] ?? ''))]);
                $u = $st->fetch();
                $hash = $u['password_hash'] ?? '$2y$10$usesomesillystringforsaltuseless.invalidinvalidinvalidinva';
                $ok = password_verify((string) ($_POST['password'] ?? ''), $hash) && $u;
            } catch (Throwable $e) {
                $error = 'Používatelia nie sú nastavení. Spusti: php bin/create-user.php meno';
            }
        }
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user'] = $u['username'];
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            header('Location: ./');
            exit;
        }
        sleep(1);   // spomalenie hadania hesiel
        $error = $error ?: 'Nesprávne meno alebo heslo.';
    }
    Web::layout('Prihlásenie', '<div class="card login"><h1>Prihlásenie</h1>'
        . ($error ? '<div class="err">' . Web::h($error) . '</div>' : '')
        . '<form method="post" action="?p=login"><input type="hidden" name="csrf" value="' . Web::h($_SESSION['csrf']) . '">'
        . '<input name="username" placeholder="Meno" autocomplete="username" autofocus required>'
        . '<input name="password" type="password" placeholder="Heslo" autocomplete="current-password" required>'
        . '<button style="width:100%">Prihlásiť</button></form></div>', null);
    exit;
}

if (empty($_SESSION['user'])) {
    header('Location: ?p=login');
    exit;
}
$user = (string) $_SESSION['user'];

// ---- stranky
$pages = new Pages($db, require "$root/config/tables.php");
try {
    if ($p === '') {
        Web::layout('Prehľad', $pages->dashboard(), $user, '');
    } elseif (in_array($p, $pages->entityKeys(), true)) {
        if (isset($_GET['id'])) {
            $html = $pages->detail($p, (int) $_GET['id']);
            if ($html === null) {
                http_response_code(404);
                Web::layout('Nenájdené', '<div class="card">Záznam neexistuje.</div>', $user, $p);
            } else {
                Web::layout('Detail', $html, $user, $p);
            }
        } else {
            Web::layout('Zoznam', $pages->list($p), $user, $p);
        }
    } else {
        http_response_code(404);
        Web::layout('Nenájdené', '<div class="card">Stránka neexistuje.</div>', $user);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    Web::layout('Chyba', '<div class="card bad">Nastala chyba pri načítaní údajov.</div>', $user);
}

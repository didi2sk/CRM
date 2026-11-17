<?php
declare(strict_types=1);

// Webove CRM (len na citanie). Document root webu = priecinok public/.
$root = dirname(__DIR__);
foreach (['Env', 'CaflouClient', 'Syncer', 'CrmAuth', 'Web', 'Pages'] as $c) {
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

// ---- aktualny ucet (+ doplnenie stlpca password_changed_at pri starsej schéme)
try {
    CrmAuth::ensureSchema($db);
    $st = $db->prepare('SELECT username, password_hash, password_changed_at FROM crm_users WHERE username = ?');
    $st->execute([$user]);
    $me = $st->fetch();
} catch (Throwable $e) {
    error_log('crm_users: ' . $e->getMessage());
    $st = $db->prepare('SELECT username, password_hash FROM crm_users WHERE username = ?');
    $st->execute([$user]);
    $me = $st->fetch();
    if ($me) {
        $me['password_changed_at'] = 'unknown';   // bez stlpca vyzvu nezobrazujeme
    }
}
if (!$me) {   // ucet bol medzitym zruseny
    $_SESSION = [];
    session_destroy();
    header('Location: ?p=login');
    exit;
}
$forceChange = Env::get('FORCE_PASSWORD_CHANGE', '0') === '1';
$needsChange = $me['password_changed_at'] === null;

// ---- zmena hesla
if ($p === 'password') {
    $error = '';
    if ($post) {
        if (!$csrfOk) {
            $error = 'Neplatná požiadavka, skús to znova.';
        } elseif (isset($_POST['skip'])) {
            if ($forceChange && $needsChange) {
                $error = 'Zmena hesla je povinná.';
            } else {
                header('Location: ./');
                exit;
            }
        } else {
            $cur = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['new'] ?? '');
            $new2 = (string) ($_POST['new2'] ?? '');
            if (!password_verify($cur, $me['password_hash'])) {
                $error = 'Aktuálne heslo nie je správne.';
            } elseif (mb_strlen($new) < CrmAuth::MIN_PASSWORD) {
                $error = 'Nové heslo musí mať aspoň ' . CrmAuth::MIN_PASSWORD . ' znakov.';
            } elseif ($new !== $new2) {
                $error = 'Nové heslá sa nezhodujú.';
            } elseif ($new === $cur) {
                $error = 'Nové heslo musí byť iné ako aktuálne.';
            } else {
                $db->prepare('UPDATE crm_users SET password_hash = ?, password_changed_at = UTC_TIMESTAMP() WHERE username = ?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $user]);
                session_regenerate_id(true);
                $_SESSION['pw_prompted'] = true;
                $_SESSION['flash'] = 'Heslo bolo zmenené.';
                header('Location: ./');
                exit;
            }
            sleep(1);
        }
    }
    $csrf = Web::h($_SESSION['csrf']);
    $intro = '';
    if ($needsChange) {
        $intro = '<p class="mut">Používaš prvotné heslo. ' . ($forceChange
            ? 'Pred pokračovaním si musíš nastaviť vlastné heslo.'
            : 'Odporúčame si nastaviť vlastné heslo, ale môžeš to aj preskočiť.') . '</p>';
    }
    $body = '<div class="card login" style="max-width:420px;margin-top:6vh"><h1>Zmena hesla</h1>' . $intro
        . ($error ? '<div class="err">' . Web::h($error) . '</div>' : '')
        . '<form method="post" action="?p=password"><input type="hidden" name="csrf" value="' . $csrf . '">'
        . '<input name="current" type="password" placeholder="Aktuálne heslo" autocomplete="current-password" required>'
        . '<input name="new" type="password" placeholder="Nové heslo (min. ' . CrmAuth::MIN_PASSWORD . ' znakov)" autocomplete="new-password" required>'
        . '<input name="new2" type="password" placeholder="Nové heslo znova" autocomplete="new-password" required>'
        . '<button style="width:100%">Zmeniť heslo</button></form>';
    if (!($forceChange && $needsChange)) {
        $body .= '<form method="post" action="?p=password" style="margin-top:10px"><input type="hidden" name="csrf" value="' . $csrf . '">'
            . '<button name="skip" value="1" style="width:100%;background:none;color:var(--acc)">'
            . ($needsChange ? 'Preskočiť zatiaľ' : 'Zrušiť') . '</button></form>';
    }
    Web::layout('Zmena hesla', $body . '</div>', $user, '');
    exit;
}

// ---- vyzva na zmenu prvotneho hesla
if ($needsChange) {
    if ($forceChange) {
        header('Location: ?p=password');   // povinna zmena: nic ine nepustime
        exit;
    }
    if (empty($_SESSION['pw_prompted'])) {   // nepovinna: ponukneme raz po prihlaseni
        $_SESSION['pw_prompted'] = true;
        header('Location: ?p=password&first=1');
        exit;
    }
    Web::$banner = '<div class="card">Používaš prvotné heslo. <a href="?p=password">Nastaviť vlastné heslo</a></div>';
}

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

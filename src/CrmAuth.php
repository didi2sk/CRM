<?php
declare(strict_types=1);

/** Pomocne funkcie pre ucty CRM (tabulka crm_users). */
final class CrmAuth
{
    public const MIN_PASSWORD = 10;

    /** Vytvori tabulku a doplni stlpec password_changed_at, ak chyba (starsie instalacie). */
    public static function ensureSchema(PDO $db): void
    {
        $db->exec(file_get_contents(dirname(__DIR__) . '/sql/crm_users.sql'));
        if (!$db->query("SHOW COLUMNS FROM crm_users LIKE 'password_changed_at'")->fetch()) {
            $db->exec('ALTER TABLE crm_users ADD COLUMN password_changed_at DATETIME NULL');
        }
    }
}

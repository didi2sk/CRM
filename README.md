# Caflou -> MySQL zaloha

Cisty PHP (8.1+, ext-curl, ext-pdo_mysql), bez zavislosti.

## Instalacia
```bash
cp .env.example .env        # dopln CAFLOU_ACCOUNT_ID, CAFLOU_TOKEN, DB_*
mysql -e "CREATE DATABASE caflou_backup CHARACTER SET utf8mb4"
php bin/sync.php --init-db  # vytvori tabulky
```

## Beh
```bash
php bin/sync.php            # vsetky entity
php bin/sync.php tasks      # len vybrane
```
Cron (kazdu noc o 2:15):
```
15 2 * * * php /cesta/caflou-backup/bin/sync.php >> /var/log/caflou-backup.log 2>&1
```

## Spustenie cez web
Do webrootu patri iba priecinok `public/` (document root = `public/`).
`.env`, `src/`, `sql/` musia byt mimo neho. V `.env` nastav `RUN_KEY` (`php -r "echo bin2hex(random_bytes(24));"`).
```
https://tvoja-domena/run.php?key=RUN_KEY&init=1            # prve spustenie: vytvori tabulky
https://tvoja-domena/run.php?key=RUN_KEY                   # vsetky entity
https://tvoja-domena/run.php?key=RUN_KEY&entity=tasks      # len vybrane (oddelene ciarkou)
```
Pozor na casovy limit hostingu - pri velkej zalohe spustaj entitu po entite alebo pouzi cron.

## Normalizovane tabulky (interne pouzitie)
Definicie su v `config/tables.php` (zatial `companies`, dalsie entity pridavame postupne).
```bash
php bin/normalize.php --print-sql     # iba vypise CREATE TABLE
php bin/normalize.php --init-tables   # vytvori tabulky
php bin/normalize.php                 # prenesie data z caflou_records (upsert)
```
Poradie: `sync.php` (stiahne JSON) -> `normalize.php` (naplni tabulky). Casy su v UTC.
Stlpce `deleted_in_source` a `synced_at` sa doplnaju automaticky.

## Ako to funguje
- Kazdy zaznam sa ulozi ako plny JSON do `caflou_records` (`entity`, `remote_id`, `data`).
- Upsert podla `data_hash` - meni sa len ak sa zmenil obsah (`updated_at`).
- Zaznam, ktory v Caflou zmizol, sa nezmaze, iba sa oznaci `deleted_at`.
- Priebeh a vysledok kazdeho behu: `caflou_sync_runs`.
- Citanie: `SELECT JSON_EXTRACT(data,'$.name') FROM caflou_records WHERE entity='companies';`

## Dolezite - over
Struktura URL (`{base}/{account_id}/{entita}`), Bearer autorizacia, nazvy strankovacich
parametrov a zoznam entit v `config/entities.php` su **odhad** a treba ich
skontrolovat proti Postman dokumentacii Caflou API
(https://documenter.getpostman.com/view/4786951/RWMFrTQC). Pripadne ich uprav v `.env`,
`config/entities.php` alebo `CaflouClient::request()`.

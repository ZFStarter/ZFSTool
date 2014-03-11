
То что тесты проходят успешно это ещё ни о чем не говорит (c)

[![Build Status](https://travis-ci.org/naxel/ZFCTool.png?branch=master)](https://travis-ci.org/naxel/ZFCTool)


ZFCTool - Zend Framework 2 command line Tool

------------------------------------------------------------------------------------------------------------

Migrations:

```bash
  ~$ php vendor/bin/zfc.php ls migrations [--module]    - List of exist migrations

  --module    (Optional) Module name

  ~$ php vendor/bin/zfc.php up db <to> [--module]    - Update DB to selected migration

  --module    (Optional) Module name
  to          (Optional) Migration name

  ~$ php vendor/bin/zfc.php down db <to> [--module]    - Downgrade selected migration from DB

  --module    (Optional) Module name
  to          (Optional) Migration name

  ~$ php vendor/bin/zfc.php show migration [--module]    - Show current migration

  --module    (Optional) Module name

  ~$ php vendor/bin/zfc.php gen migration [--module] [--whitelist] [--blacklist] [-c] [-e]    - Generate new migration

  --module       (Optional) Module name
  --whitelist    (Optional) White list of tables
  --blacklist    (Optional) Black list of tables
  -c             (Optional) Create and commit migration
  -e             (Optional) Create empty migration

  ~$ php vendor/bin/zfc.php ci migration <to> [--module]    - Commit selected migration to DB

  --module    (Optional) Module name
  to          To migration

  ~$ php vendor/bin/zfc.php back db [--module] [--step]    - Rollback DB

  --module    (Optional) Module name
  --step      Count of rollback migrations

  ~$ php vendor/bin/zfc.php diff db [--module] [--whitelist] [--blacklist]    - Show generated queries without creating migration

  --module       (Optional) Module name
  --whitelist    (Optional) White list of tables
  --blacklist    (Optional) Black list of tables
```

Dump:

```bash
  ~$ php vendor/bin/zfc.php create dump [--module] [--name] [--whitelist] [--blacklist]    - Creating dump

  --module       (Optional) Module name
  --name         (Optional) Dump file name
  --whitelist    (Optional) White list of tables
  --blacklist    (Optional) Black list of tables

  ~$ php vendor/bin/zfc.php import dump <name> [--module]    - import already created dump

  --module    Module name
  name        Dump file name

```

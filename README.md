# Phoenix PostgreSQL adapter

PostgreSQL adapter for [Phoenix](https://github.com/efabrica-team/phoenix-core) database migrations. It registers the `pgsql` adapter and is built on the shared PDO base in [efabrica/phoenix-pdo](https://github.com/efabrica-team/phoenix-pdo).

## Requirements
- PHP 8.4+
- `ext-pdo` and `ext-pdo_pgsql`
- PostgreSQL 10 or newer (10 and 14 are covered by the test suite, see `.github/workflows`)

## Installation
```
composer require efabrica/phoenix-core efabrica/phoenix-pgsql
```
`efabrica/phoenix-pdo` is installed automatically as a dependency. The package registers the `pgsql` adapter in `Phoenix\Database\Adapter\AdapterFactory` from its composer bootstrap, nothing else has to be wired up.

## Setup in a project
1. Create a configuration file `phoenix.php` (or `phoenix.yml`, `phoenix.neon`, `phoenix.json`) in the project root:
    ```php
    <?php

    return [
        'migration_dirs' => [
            'app' => __DIR__ . '/migrations',
        ],
        'environments' => [
            'local' => [
                'adapter' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'username' => 'postgres',
                'password' => 'secret',
                'db_name' => 'my_app',
                'charset' => 'utf8',
            ],
            'production' => [
                'adapter' => 'pgsql',
                'dsn' => 'pgsql:dbname=my_app;host=db.internal;options=\'--client_encoding=utf8\'',
                'username' => 'migrator',
                'password' => getenv('DB_PASSWORD'),
            ],
        ],
        'default_environment' => 'local',
        'log_table_name' => 'phoenix_log',
    ];
    ```
    `yml` and `neon` config files need `symfony/yaml` resp. `nette/neon` installed. All configuration keys are described in the [configuration docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/configuration/index.md).
2. Create and run migrations with the command runner `vendor/bin/phoenix`:
    ```
    vendor/bin/phoenix create "App\CreateUsers"
    vendor/bin/phoenix migrate
    vendor/bin/phoenix status
    vendor/bin/phoenix rollback
    ```
    Use `-e production` to pick another environment and `-c path/to/config.php` for a config file elsewhere. The log table (`log_table_name`) is created automatically on the first run, you do not have to run `init` yourself. All commands are described in the [commands docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/commands/index.md), how to write migrations in the [migrations docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/migrations/index.md).
3. The database user needs `CREATE` on the `public` schema and ownership of (or privileges on) the tables the migrations change, plus `INSERT`, `UPDATE`, `DELETE`, `SELECT` on the log table. Dropping a table with `enum` / `set` columns deletes rows from `pg_type`, which requires a superuser or the owner of those types. The database itself has to exist, Phoenix does not create it.

## Configuration options
| key | required | description |
|---|---|---|
| `adapter` | yes | `pgsql` |
| `host`, `port` | yes (without `dsn`) | server address, port is optional (default of the driver is 5432) |
| `db_name` | yes (without `dsn`) | database name, it has to exist |
| `dsn` | no | full PDO dsn, e.g. `pgsql:dbname=my_app;host=localhost;options='--client_encoding=utf8'`. When set, `host`, `port`, `db_name` and `charset` are not used for the connection |
| `username`, `password` | no | credentials |
| `charset` | no | client encoding of the connection (`--client_encoding`), default `utf8`. PostgreSQL has one encoding per database, so this does not change how tables are created |
| `collation` | no | accepted for compatibility with the other adapters, the query builder does not write it into DDL |
| `version` | no | server version, read from the server when not set. The PostgreSQL query builder currently generates the same statements on every version |

## What the adapter supports
- tables with comment (`COMMENT ON TABLE`), renaming and copying tables (`CREATE TABLE ... AS TABLE ... WITH [NO] DATA`)
- columns with `null`, `default`, `comment` (`COMMENT ON COLUMN`), autoincrement (`serial`, `smallserial`, `bigserial` and a sequence restart via `setAutoIncrement()`), renaming columns and changing their type (`ALTER COLUMN ... TYPE ... USING` cast)
- primary keys over one or more columns (constraint `<table>_pkey`), including adding a primary key to an existing table
- indexes: normal and `UNIQUE`, index method via `USING` (`btree`, `hash`, ...), `DESC` order per column, prefix length emulated as `SUBSTRING(column FOR n)`
- foreign keys with `ON DELETE` / `ON UPDATE` actions, unique constraints
- `enum` and `set` columns, emulated with a PostgreSQL enum type named `<table>__<column>` (`set` is an array of that type). Adding values to the enum in `changeColumn()` is supported, removing values deletes them from `pg_enum`
- views (`view()`, `replaceView()`, `dropView()`)
- `dump` and `diff` commands read the complete structure of the `public` schema back from `information_schema`

### Column type mapping
| Phoenix type | PostgreSQL type | note |
|---|---|---|
| `string` | `varchar(255)` | length configurable |
| `char` | `char(255)` | |
| `tinytext`, `text`, `mediumtext`, `longtext` | `text` | read back as `text` |
| `tinyinteger`, `smallinteger` | `int2` | read back as `smallinteger` |
| `mediuminteger`, `integer` | `int4` | read back as `integer` |
| `biginteger` | `int8` | |
| `boolean` | `bool` | |
| `bit` | `bit(32)` | |
| `numeric`, `decimal` | `numeric(10,0)` | precision and scale configurable |
| `float`, `double` | `float4`, `float8` | |
| `date`, `time` | `date`, `time` | |
| `datetime`, `timestamp` | `timestamp(6)` | `DEFAULT_VALUE_CURRENT_TIMESTAMP` is supported, read back as `datetime` |
| `timestamptz` | `timestamptz` | |
| `year` | `numeric(4)` | |
| `uuid` | `uuid` | |
| `json` | `json` | |
| `enum`, `set` | `<table>__<column>`, `<table>__<column>[]` | custom enum type, see above |
| `binary`, `varbinary`, `tinyblob`, `blob`, `mediumblob`, `longblob` | `bytea` | read back as `blob` |
| `point`, `line`, `polygon` | the same | |

## Limitations and things to watch
- **Only the `public` schema.** Structure loading, sequences and constraints are looked up in `public`, tables in other schemas are invisible to `dump`, `diff` and `setAutoIncrement()`.
- **Several Phoenix types collapse into one PostgreSQL type** (see the mapping). `dump` and `diff` therefore report `text` for `longtext`, `smallinteger` for `tinyinteger`, `blob` for `varbinary` and so on. Use the PostgreSQL side names in migrations shared between MySQL and PostgreSQL if you want clean diffs, or accept the mapping.
- **`unsigned`, column `charset` and column `collation` are ignored.** PostgreSQL has no unsigned integers and the encoding is per database. The settings are accepted so the same migration can run on MySQL.
- **`FULLTEXT` indexes do not exist.** `Index::TYPE_FULLTEXT` produces `CREATE FULLTEXT INDEX`, which the server rejects. Create a `gin` / `gist` index on a `tsvector` expression with raw SQL instead.
- **Enum types are per table and column.** Renaming a table does not rename its `<table>__<column>` types, and dropping a table removes them with a direct `DELETE FROM pg_type`. Two tables cannot share one enum type through the migration API.
- **`enum` and `set` need a cast when changed.** Changing the type of an existing column uses `USING column::<new type>`, which PostgreSQL cannot do for every combination (e.g. `text` to `enum` with values not in the enum fails).
- **Autoincrement is a sequence.** Only `integer` (`serial`), `smallinteger` (`smallserial`) and `biginteger` (`bigserial`) can be autoincrement, the sequence is `<table>_<column>_seq`. `setAutoIncrement()` needs the table to already have such a sequence.
- **`boolean` default.** A boolean column always gets a default (`false` when none is set).
- **Partitioning, check constraints, exclusion constraints, generated columns, triggers, functions, extensions** are not part of the migration API. Use `$this->execute()` with raw SQL in a migration for those.
- **Foreign key checks** stay on unless the migration calls `$this->checkForeignKeysOff()` (`SET session_replication_role = replica`, which also disables triggers and needs superuser or the `session_replication_role` privilege) and `$this->checkForeignKeysOn()` again.
- **Migrations are not wrapped in a transaction by Phoenix**, even though PostgreSQL DDL is transactional. A failing migration leaves the already executed statements applied and is not logged as executed. All statements of a migration run sequentially on one connection, so a migration can wrap its own statements in `$this->execute('BEGIN')` / `$this->execute('COMMIT')` when atomicity matters (`CREATE INDEX CONCURRENTLY` then cannot be inside).
- The adapter uses `PDO::ERRMODE_SILENT` and turns errors into `DatabaseQueryExecuteException` with the SQLSTATE and the failing query in the message.

## Tests
Tests need a running PostgreSQL, connection is taken from the `PHOENIX_PGSQL_*` environment variables (see `phpunit.xml.dist`). CI runs the suite against PostgreSQL 10 and 14.

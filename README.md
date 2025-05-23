# FaimMedia PHP Migration

![Coverage](https://raw.githubusercontent.com/FaimMedia/php-migration/master/badge-coverage.svg)
[![Pipeline](https://github.com/FaimMedia/php-migration/actions/workflows/pipeline.yml/badge.svg?branch=master)](https://github.com/FaimMedia/php-migration/actions/workflows/pipeline.yml)

Simple PHP migration library to use with any available PHP PDO driver.

## Install & usage

### Add composer

Install this library using composer:

```bash
composer require faimmedia/migration
```

### Run migrations (using CLI)

Use the `./vendor/bin/migrate` command to run the migrations.

Example:

```bash
./vendor/bin/migrate \
	--path=migration/path \
	--dsn="pgsql:host=postgres;dbname=database" \
	--username=postgres
```

To migrate to a specific version, you may also include the `--version` parameter. This will apply or undo only specific versions.

```bash
./vendor/bin/migrate \
	--path=migration/path \
	--dsn="pgsql:host=postgres;dbname=database" \
	--username=postgres \
	--version=0002
```

### Run migrations (from PHP)

```php
<?php

use FaimMedia\Migration\Migration;
use FaimMedia\Migration\Migration\Logger\Color;

/**
 * Initialize
 */
$migration = new Migration([
	'dsn'      => '',
	'username' => 'username',
	'password' => 'my-super-secret-password',
], new Color());

/**
 * Run all migrations
 */
$migration->run();
```

Or you can run a specific set of migrations:  
Mmigrations that are already applied will be rolled back if there are `-down` file present for the specific version and name.

```php
$migration->run('0003');
```

## Development

Start up docker containers:

```bash
docker compose up -d
```

Run tests:

```bash
./bin/test
```

Run migration CLI:

```bash
docker compose exec -T test /app/bin/migrate --dsn=pgsql:host=postgres --username=migrate-test --path=/app/test/sql
```

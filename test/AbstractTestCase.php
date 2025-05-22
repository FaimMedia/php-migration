<?php

namespace FaimMedia\Migration\Test;

use FaimMedia\Migration\Logger\Noop;
use FaimMedia\Migration\Migration;

use PHPUnit\Framework\TestCase;

use PDO;
use ReflectionClass;

/**
 * Abstract Test Case class
 */
abstract class AbstractTestCase extends TestCase
{
	protected Migration $migration;
	protected PDO $pdo;

	/**
	 * Setup
	 */
	public function setUp(): void
	{
		$this->migration = new Migration([
			'dsn'            => 'pgsql:host=postgres;port=5432;dbname=' . PDO_DATABASE . ';user=' . PDO_USERNAME,
			'path'           => TEST_PATH . 'sql/',
			'useTransaction' => false,
		], new Noop());

		$reflection = new ReflectionClass($this->migration);
		$property = $reflection->getProperty('pdo');
		$property->setAccessible(true);

		$this->pdo = $property->getValue($this->migration);

		$this->pdo->beginTransaction();

		register_shutdown_function(function(): void {
			$this->tearDown();
		});
	}

	/**
	 * Get pdo
	 */
	protected function getPdo(): PDO
	{
		return $this->pdo;
	}

	/**
	 * Table exists
	 */
	protected function tableExists(string $tableName): bool
	{
		$query = $this->pdo->query(<<<SQL
			SELECT EXISTS (
				SELECT FROM "information_schema"."tables"
				WHERE "table_schema" = 'public'
				AND "table_name" = '{$tableName}'
			);
		SQL);

		return $query->fetchColumn(0);
	}

	/**
	 * Assert table exists
	 */
	protected function assertTableExists(string $table): void
	{
		parent::assertTrue($this->tableExists($table));
	}

	/**
	 * Assert table does not exists
	 */
	protected function assertTableNotExists(string $table): void
	{
		parent::assertFalse($this->tableExists($table));
	}

	/**
	 * Migration exists
	 */
	protected function migrationExists(
		string $versionNumber,
		string $name,
		?string $startTime = null,
	): array | false
	{
		$query = <<<SQL
			SELECT *
			FROM "migration"
			WHERE "version" = '{$versionNumber}'
			AND "name" = '{$name}'
		SQL;

		if ($startTime) {
			$query .= <<<SQL
				AND "applied" >= '{$startTime}'
			SQL;
		}

		$query = $this->pdo->query($query);

		return $query->fetch(PDO::FETCH_NUM);
	}

	/**
	 * Migration count
	 */
	protected function migrationCount(?string $startTime = null): int
	{
		$query = <<<SQL
			SELECT COUNT(1) c
			FROM "migration"
		SQL;

		if ($startTime) {
			$query .= <<<SQL
				WHERE "applied" >= '{$startTime}'
			SQL;
		}

		$query = $this->pdo->query($query);

		return (int) $query->fetch(PDO::FETCH_COLUMN, 0);
	}

	/**
	 * Teardown
	 */
	public function tearDown(): void
	{
		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->rollBack();
		}
	}
}

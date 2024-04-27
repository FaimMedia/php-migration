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
			'dsn'  => 'pgsql:host=postgres;port=5432;dbname=' . PDO_DATABASE . ';user=' . PDO_USERNAME,
			'path' => TEST_PATH . 'sql/',
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
	 * Teardown
	 */
	public function tearDown(): void
	{
		if ($this->getPdo()->inTransaction()) {
			$this->getPdo()->rollBack();
		}
	}
}

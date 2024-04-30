<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;

use PDO;

use FaimMedia\Migration\Exception;

/**
 * Table test
 */
class TableTest extends AbstractTestCase
{
	/**
	 * Setup
	 */
	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * Test table creation
	 */
	public function testDefaultTableCreation(): void
	{
		$this->checkTable('migration');
	}

	/**
	 * Test custom table creation
	 */
	public function testCustomTableCreation(): void
	{
		$customName = 'custom_migration';

		$this->migration->setTableName($customName);
		$this->migration->createMigrationTable();
		$this->migration->setTableName();
		$this->checkTable($customName);
	}

	/**
	 * Check table
	 */
	public function checkTable(string $tableName): void
	{
		$exists = parent::tableExists($tableName);

		parent::assertTrue($exists);

		$query = $this->pdo->query(<<<SQL
			SELECT "column_name", "udt_name", "character_maximum_length", "column_default", "is_nullable"
			FROM "information_schema"."columns"
			WHERE "table_name" = '{$tableName}'
			ORDER BY "ordinal_position" ASC;
		SQL);

		$result = $query->fetchAll(PDO::FETCH_ASSOC);

		parent::assertEquals(count($result), 3);

		parent::assertSame($result[0]['column_name'], 'version');
		parent::assertSame($result[1]['column_name'], 'name');
		parent::assertSame($result[2]['column_name'], 'applied');

		parent::assertSame($result[0]['udt_name'], 'int2');
		parent::assertSame($result[1]['udt_name'], 'varchar');
		parent::assertSame($result[2]['udt_name'], 'timestamp');

		parent::assertSame($result[0]['character_maximum_length'], null);
		parent::assertSame($result[1]['character_maximum_length'], 255);
		parent::assertSame($result[2]['character_maximum_length'], null);

		parent::assertSame($result[0]['column_default'], null);
		parent::assertSame($result[1]['column_default'], null);
		parent::assertSame($result[2]['column_default'], null);

		parent::assertSame($result[0]['is_nullable'], 'NO');
		parent::assertSame($result[1]['is_nullable'], 'NO');
		parent::assertSame($result[2]['is_nullable'], 'NO');
	}

	/**
	 * Test import file
	 */
	public function testImportFile(): void
	{
		$startTime = date('c');

		$this->migration->importFile(
			0001,
			'test',
		);

		$resultset = $this->migrationExists('0001', 'test', $startTime);

		parent::assertNotFalse($resultset);
	}

	/**
	 * Test downgrade file
	 */
	public function testDownloadFile(): void
	{
		$this->migration->importFile(
			0001,
			'test',
		);

		parent::assertTrue(parent::tableExists('test'));

		$this->migration->downgradeFile(
			0001,
			'test',
		);

		parent::assertFalse(parent::tableExists('test'));

		$resultset = $this->migrationExists('0001', 'test');

		parent::assertFalse($resultset);
	}

	/**
	 * Test invalid file
	 */
	public function testInvalidFile(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::MISSING_FILE);

		$this->migration->importFile(9999, 'test');
	}

	/**
	 * Rolling back not applied migration
	 */
	public function testNonExistingMigration(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::MIGRATION_NOT_APPLIED);

		$this->migration->downgradeFile(0001, 'test');
	}

	/**
	 * Test empty migration rollback
	 */
	public function testEmptyFile(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::EMPTY_FILE);

		$this->migration->run('0003');
		$this->migration->downgradeFile(0002, '2test');
	}

	/**
	 * Already applied migration
	 */
	public function testAlreadyAppliedMigration(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::MIGRATION_ALREADY_APPLIED);

		/**
		 * Apply first time
		 */
		$this->migration->importFile(0001, 'test');

		/**
		 * Apply second time: Exception
		 */
		$this->migration->importFile(0001, 'test');
	}
}

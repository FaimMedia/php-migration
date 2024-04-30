<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;

use FaimMedia\Migration\Exception;

/**
 * Full migration test
 */
class MigrationTest extends AbstractTestCase
{
	/**
	 * Setup
	 */
	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * Test migration version 0
	 */
	public function testVersion0Migration(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::VERSION_NUMBER);

		$this->migration->run('0000');
	}

	/**
	 * Test migration version 0 that should always run
	 */
	public function testAlwaysRunMigration(): void
	{
		$this->migration->run('0001');

		parent::assertTrue(parent::tableExists('always_run'));
	}

	/**
	 * Test migration version 0 when table is removed
	 */
	public function testAlwaysRunMigrationWhenTableRemoval(): void
	{
		$this->testAlwaysRunMigration();

		$this->pdo->query('DROP TABLE "always_run"');

		parent::assertFalse(parent::tableExists('always_run'));

		$this->migration->run("0001");

		parent::assertTrue(parent::tableExists('always_run'));
	}

	/**
	 * Test migration version 0 when value is changed
	 */
	public function testAlwaysRunMigrationWhenValueIsChanged(): void
	{
		$defaultValue = 'Test value 2';

		$this->testAlwaysRunMigration();

		parent::assertSame($defaultValue, $this->getValueFromAlwaysRun());

		$randomValue = 'New random string: ' . md5(uniqid(time()));

		$prepare = $this->pdo->prepare('UPDATE "always_run" SET "col2" = ? WHERE "col1" = ?');
		$prepare->execute([
			$randomValue,
			'key2',
		]);

		parent::assertSame($randomValue, $this->getValueFromAlwaysRun());

		$this->testAlwaysRunMigration();

		parent::assertSame($defaultValue, $this->getValueFromAlwaysRun());
	}

	/**
	 * Get value from always run key2 table
	 */
	protected function getValueFromAlwaysRun(): ?string
	{
		$prepare = $this->pdo->prepare('SELECT "col2" FROM "always_run" WHERE "col1" = ?');
		$prepare->execute([
			'key2',
		]);

		return $prepare->fetchColumn(0);
	}

	/**
	 * Full migration test
	 */
	public function testFullMigration(): void
	{
		$this->migration->run();

		parent::assertSame($this->migrationCount(), 4);

		parent::assertTrue(parent::tableExists('test'));
		parent::assertTrue(parent::tableExists('test_1'));
		parent::assertTrue(parent::tableExists('test_2'));
		parent::assertTrue(parent::tableExists('test_3'));
		parent::assertTrue(parent::tableExists('test_4'));
		parent::assertTrue(parent::tableExists('new_table'));
	}

	/**
	 * Test partial migration
	 */
	public function testVersion1Migration(): void
	{
		$this->migration->run('0001');

		parent::assertSame($this->migrationCount(), 1);

		parent::assertTrue(parent::tableExists('test'));

		parent::assertFalse(parent::tableExists('test_1'));
		parent::assertFalse(parent::tableExists('test_2'));
		parent::assertFalse(parent::tableExists('test_3'));
		parent::assertFalse(parent::tableExists('test_4'));
		parent::assertFalse(parent::tableExists('new_table'));
	}

	/**
	 * Test migration and downgrade to version 2
	 */
	public function testVersion2Downgrade(): void
	{
		$this->testFullMigration();

		parent::assertSame($this->migrationCount(), 4);

		$this->migration->run('0002');

		parent::assertSame($this->migrationCount(), 3);

		parent::assertFalse(parent::tableExists('new_table'));
	}

	/**
	 * Test migration and downgrade to version 1
	 */
	public function testVersion1Downgrade(): void
	{
		$this->testVersion2Downgrade();

		parent::assertSame($this->migrationCount(), 3);

		$this->migration->run('0001');

		parent::assertSame($this->migrationCount(), 1);

		/**
		 * Migration version 2 has empty down files, so tables should still exist
		 */
		parent::assertTrue(parent::tableExists('test_1'));
		parent::assertTrue(parent::tableExists('test_2'));
		parent::assertTrue(parent::tableExists('test_3'));
		parent::assertTrue(parent::tableExists('test_4'));

		parent::assertFalse(parent::tableExists('new_table'));
	}
}

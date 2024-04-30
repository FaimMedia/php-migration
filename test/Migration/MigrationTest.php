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

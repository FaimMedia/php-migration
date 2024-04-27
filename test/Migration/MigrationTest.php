<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;

use PDO;

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

		parent::assertTrue(parent::tableExists('test'));
		parent::assertTrue(parent::tableExists('test_1'));
		parent::assertTrue(parent::tableExists('test_2'));
		parent::assertTrue(parent::tableExists('test_3'));
		parent::assertTrue(parent::tableExists('test_4'));
	}
}

<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;

use FaimMedia\Migration\Exception;

/**
 * Import random file test
 */
class ImportTest extends AbstractTestCase
{
	/**
	 * Random sql file 1
	 */
	public function testRandomFile1(): void
	{
		$this->migration->importSqlFile(TEST_PATH . 'sql/.random-1.sql');

		parent::assertTrue(parent::tableExists('my_new_table'));
		parent::assertFalse(parent::tableExists('my_random_table'));
	}

	/**
	 * Random sql file 2
	 */
	public function testRandomFile2(): void
	{
		$this->migration->importSqlFile(TEST_PATH . 'sql/.random-2.sql');

		parent::assertFalse(parent::tableExists('my_new_table'));
		parent::assertTrue(parent::tableExists('my_random_table'));
	}

	/**
	 * Test non existing file
	 */
	public function testNonExistingSqlFile(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::MISSING_FILE);

		$this->migration->importSqlFile(TEST_PATH . 'sql/.random-99.sql');
	}

	/**
	 * Test empty file
	 */
	public function testEmptySqlFile(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::EMPTY_FILE);

		$this->migration->importSqlFile(TEST_PATH . 'sql/.random-3.sql');
	}
}

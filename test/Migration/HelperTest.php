<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;
use PDO;

/**
 * Helper Test class for PDO helper methods
 */
final class HelperTest extends AbstractTestCase
{
	/**
	 * Test PDO select
	 */
	public function testPdoSelect(): void
	{
		$result = $this->migration->query(
			'SELECT 1+1 AS test',
			[],
			true,
		);

		parent::assertIsArray($result);
		parent::assertCount(1, $result);
		parent::assertArrayHasKey('test', $result[0]);
		parent::assertSame(2, $result[0]['test']);
	}

	/**
	 * Test PDO insert (prepare)
	*/
	public function testPdoInsert(): void
	{
		$this->migration->run('0000');

		$result = $this->migration->query(
			<<<SQL
				INSERT INTO "always_run" ("col1", "col2")
				VALUES (:bind1, :bind2)
			SQL,
			[
				'bind1' => 'key99',
				'bind2' => 'val99',
			],
		);

		parent::assertTrue($result);
	}

	/**
	 * Test PDO driver
	 */
	public function testPdo(): void
	{
		$this->migration->run('0000');

		$pdo = $this->migration->getPdo();

		parent::assertInstanceOf(PDO::class, $pdo);

		$stmt = $pdo->prepare(
			'SELECT * FROM "always_run" WHERE "col1" = :col',
		);

		$result = $stmt->execute([
			'col' => 'key1',
		]);

		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		parent::assertIsArray($result);
		parent::assertArrayHasKey('col1', $result);
		parent::assertArrayHasKey('col2', $result);
	}
}

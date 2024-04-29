<?php

namespace FaimMedia\Migration\Test\Migration;

use FaimMedia\Migration\Test\AbstractTestCase;

use FaimMedia\Migration\Exception;

/**
 * Test version exceptions
 */
class VersionTest extends AbstractTestCase
{
	/**
	 * Setup
	 */
	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * Test invalid number
	 */
	public function testExceptionVersionNumber(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::VERSION_NUMBER);

		$this->migration->validateVersion(3);
	}

	/**
	 * Test non existing path
	 */
	public function testExceptionNonExistingPath(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::FOLDER_STRUCTURE);

		$this->migration->validateVersion('0999');
	}

	/**
	 * Full migration test
	 */
	public function testExceptionEmptyPath(): void
	{
		parent::expectException(Exception::class);
		parent::expectExceptionCode(Exception::FOLDER_EMPTY);

		$versionNumber = '0999';
		$parentPath = sys_get_temp_dir() . '/' . substr(md5(uniqid()), 0, 10);
		$randomPath = $parentPath . '/' . $versionNumber;

		mkdir($randomPath, 0775, true);

		$this->migration->setPath($parentPath);
		$this->migration->validateVersion('0999');
	}
}

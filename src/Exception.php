<?php

declare(strict_types = 1);

namespace FaimMedia\Migration;

use Exception as BaseException;

/**
 * Migration Exception class
 */
class Exception extends BaseException
{
	/**
	 * Path option is missing or invalid
	 */
	public const PATH = -1;

	/**
	 * Folder structure is not correct
	 */
	public const FOLDER_STRUCTURE = -2;

	/**
	 * File does not exists, or points to the wrong direction
	 */
	public const MISSING_FILE = -3;

	/**
	 * Trying to rollback a migration that isn't applied
	 */
	public const MIGRATION_NOT_APPLIED = -11;

	/**
	 * Trying to apply a migration thats already applied
	 */
	public const MIGRATION_ALREADY_APPLIED = -12;
}

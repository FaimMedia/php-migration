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
	 * The sql file is empty and cannot used for migration
	 */
	public const EMPTY_FILE = -4;

	/**
	 * The migration folder does not contain any sql files
	 */
	public const FOLDER_EMPTY = -5;

	/**
	 * Invalid version number provided
	 */
	public const VERSION_NUMBER = -6;

	/**
	 * Version number 0000 provided is not valid for downgrade
	 */
	public const DOWNGRADE_VERSION_NUMBER = -7;

	/**
	 * Trying to rollback a migration that isn't applied
	 */
	public const MIGRATION_NOT_APPLIED = -11;

	/**
	 * Trying to apply a migration thats already applied
	 */
	public const MIGRATION_ALREADY_APPLIED = -12;
}

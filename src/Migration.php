<?php

declare(strict_types = 1);

namespace FaimMedia\Migration;

use PDO;
use PDOStatement;

use PDOException;
use FaimMedia\Migration\Exception;

/**
 * Migration class
 */
class Migration
{
	public const DEFAULT_TABLE_NAME = 'migration';

	protected PDO $pdo;

	protected string $tableName = self::DEFAULT_TABLE_NAME;
	protected string $path;

	/**
	 * Constructor
	 */
	public function __construct(array $options)
	{
		$this->pdo = new PDO(
			$options['dsn'],
			$option['username'] ?? null,
			$option['password'] ?? null,
			[
				PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_AUTOCOMMIT => 0,
			]
		);

		if (!isset($options['path'])) {
			throw new Exception('Path option is missing', Exception::PATH);
		}

		$path = $options['path'];
		if (!file_exists($path) || !is_dir($path)) {
			throw new Exception(
				'The path `' . $path . '` does not exist or is not a directory',
				Exception::PATH,
			);
		}

		$this->path = trim($path, '/') . '/';

		if (isset($options['tableName'])) {
			$this->tableName = $options['tableName'];
		}

		if (!$this->getStatus()) {
			throw new Exception('Connection failed');
		}

		$this->createMigrationTable();
	}

	/**
	 * Set table name
	 */
	public function setTableName(string $tableName = self::DEFAULT_TABLE_NAME): void
	{
		$this->tableName = $tableName;
	}

	/**
	 * Get status
	 */
	public function getStatus(): bool
	{
		try {
            return (bool) $this->pdo->query('SELECT 1+1');
        } catch (PDOException $e) {
            return false;
        }

		return true;
	}

	/**
	 * Create migration table
	 */
	public function createMigrationTable(): void
	{
		$this->pdo->query(<<<SQL
			CREATE TABLE IF NOT EXISTS "{$this->tableName}" (
				"version" int2 PRIMARY KEY,
				"name" VARCHAR(255) NOT NULL,
				"applied" TIMESTAMP NOT NULL,
				UNIQUE("version", "name")
			);
		SQL);
	}

	/**
	 * Run migrations
	 */
	public function run(): void
	{

	}

	/**
	 * Import file
	 */
	public function importFile(
		int $version,
		string $fileName,
		bool $downgrade = false,
	): bool
	{
		$versionNumber = str_pad((string) $version, 4, '0', STR_PAD_LEFT);
		$file = $this->path . $versionNumber . '/'
			. $fileName . ($downgrade ? '-down' : '') . '.sql';

		if (!file_exists($file)) {
			throw new Exception(
				'File `' . $file . '` does not exist and cannot be imported',
				Exception::MISSING_FILE,
			);
		}

		/**
		 * Check if migration exists
		 */
		$prepare = $this->pdo->prepare(<<<SQL
			SELECT COUNT(1)
			FROM "{$this->tableName}"
			WHERE "version" = ?
			AND "name" = ?
		SQL);

		$prepare->execute([
			$versionNumber,
			$fileName,
		]);

		$exists = (bool) $prepare->fetch(PDO::FETCH_COLUMN, 0);

		/**
		 * Check if migration is applied before downgrading
		 */
		if ($downgrade && !$exists) {
			throw new Exception(
				'Migration ' . $versionNumber . '-' . $fileName . ' is not applied',
				Exception::MIGRATION_NOT_APPLIED,
			);
		}

		if (!$downgrade && $exists) {
			throw new Exception(
				'Migration ' . $versionNumber . '-' . $fileName . ' is already applied',
				Exception::MIGRATION_ALREADY_APPLIED,
			);
		}

		$this->output(($downgrade ? 'Downgrading' : 'Migrating') . ' file ' . $fileName . '…');

		/**
		 * We skip transactional usage, if an transaction is already triggered
		 */
		$useTransaction = !$this->pdo->inTransaction();

		if ($useTransaction) {
			$this->pdo->beginTransaction();
		}

		$fopen = fopen($file, 'r');
		$content = fread($fopen, filesize($file));
		fclose($fopen);

		try {
			$this->pdo->exec($content);
		} catch (PDOException $e) {
			$this->output('ERROR', true, 'red');
			$this->output(' - PDO Error: ' . $e->getMessage());

			if ($useTransaction) {
				$this->pdo->rollBack();
			}

			throw $e;
		}

		/**
		 * Remove row from applied
		 */
		if ($downgrade) {
			$prepare = $this->pdo->prepare(<<<SQL
				DELETE FROM "{$this->tableName}"
				WHERE "version" = ? AND "name" = ?
			SQL);
			$prepare->execute([
				$versionNumber,
				$fileName,
			]);
		}

		/**
		 * Insert version and file
		 */
		if (!$downgrade) {
			$prepare = $this->pdo->prepare(<<<SQL
				INSERT INTO "{$this->tableName}"
				VALUES (?, ?, ?)
			SQL);

			$prepare->execute([
				$versionNumber,
				$fileName,
				date('c'),
			]);
		}

		if ($useTransaction) {
			$this->pdo->commit();
		}

		$this->output($downgrade ? 'DOWNGRADED' : 'MIGRATED', true, 'green');

		return true;
	}

	/**
	 * Downgrade file
	 */
	public function downgradeFile(int $version, string $fileName): bool
	{
		return $this->importFile($version, $fileName, true);
	}

	/**
	 * Output info
	 */
	protected function output(
		string $message,
		bool $previousLine = false,
		string $color = null,
	): void
	{
		if ($previousLine) {
			echo chr(27) . "[u";
			echo chr(27) . "[A";
			echo " ";
		}

		echo $message;

		if (!$previousLine) {
			echo chr(27) . "[s";
		}

		echo "\n";
	}
}

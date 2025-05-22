<?php

declare(strict_types = 1);

namespace FaimMedia\Migration;

use FaimMedia\Migration\Logger\{
    ColorEnum,
    LoggerInterface,
	Color,
};

use PDO;

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
	protected array $structure;
	protected int $sleep = 0;
	protected bool $useTransaction = true;

	/**
	 * Constructor
	 */
	public function __construct(
		array $options,
		protected LoggerInterface $logger = new Color(),
	)
	{
		$this->pdo = new PDO(
			$options['dsn'],
			$options['username'] ?? null,
			$options['password'] ?? null,
			[
				PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_AUTOCOMMIT => 0,
			]
		);

		if (!isset($options['path'])) {
			throw new Exception('Path option is missing', Exception::PATH);
		}

		$this->setPath($options['path']);

		if (isset($options['tableName'])) {
			$this->tableName = $options['tableName'];
		}

		$this->useTransaction = $options['useTransaction'] ?? true;

		if (!$this->getStatus()) {
			throw new Exception('Connection failed');
		}

		$this->createMigrationTable();
		$this->getStructure();
	}

	/**
	 * Set table name
	 */
	public function setTableName(string $tableName = self::DEFAULT_TABLE_NAME): void
	{
		$this->tableName = $tableName;
	}

	/**
	 * Set path
	 */
	public function setPath(string $path): void
	{
		if (!file_exists($path) || !is_dir($path)) {
			throw new Exception(
				'The path `' . $path . '` does not exist or is not a directory',
				Exception::PATH,
			);
		}

		$this->path = rtrim($path, '/') . '/';
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
				"version" int2 NOT NULL,
				"name" VARCHAR(255) NOT NULL,
				"applied" TIMESTAMP NOT NULL,
				PRIMARY KEY ("version", "name")
			);
		SQL);
	}

	/**
	 * Run with transaction
	 */
	public function run(?string $versionNumber = null): void
	{
		if ($this->useTransaction) {
			$this->pdo->beginTransaction();
		}

		try {
			$this->runMigration($versionNumber);
		} catch (Exception $e) {
			if ($this->useTransaction) {
				$this->pdo->rollBack();
			}

			throw $e;
		}

		if ($this->useTransaction) {
			$this->pdo->commit();
		}
	}

	/**
	 * Run migrations
	 */
	protected function runMigration(?string $versionNumber = null): void
	{
		if ($versionNumber) {
			$this->validateVersion($versionNumber);
		}

		$this->logger->output('Starting migration', false, ColorEnum::CYAN);

		/**
		 * Check downgrade
		 */
		if ($versionNumber !== null) {
			$this->logger->output('Migrating to version number ' . $versionNumber, false, ColorEnum::CYAN);

			$migrations = $this->getMigrationsForDowngrade($versionNumber);
			foreach ($migrations as $version => $names) {
				$this->logger->output('Downgrading version ' . $version, false, ColorEnum::MAGENTA);

				foreach ($names as $name) {
					try {
						$this->downgradeFile((int) $version, $name);
					} catch (Exception $e) {
						if ($e->getCode() === Exception::MISSING_FILE || $e->getCode() === Exception::EMPTY_FILE) {
							$this->deleteMigrationRow($version, $name);
							$this->logger->output('NON EXISTING', true, ColorEnum::RED);
							continue;
						}

						throw $e;
					}
				}
			}
		}

		$structure = $this->getStructure();

		$applied = 0;
		foreach ($structure as $version => $names) {
			if ($versionNumber !== null && $version > $versionNumber) {
				break;
			}

			$this->logger->output('Applying version ' . $version, false, ColorEnum::MAGENTA);

			foreach ($names as $name) {
				try {
					$this->importFile((int) $version, $name);
					$applied++;
				} catch (Exception $e) {
					if ($e->getCode() === Exception::MIGRATION_ALREADY_APPLIED) {
						$this->logger->output('ALREADY APPLIED', true, ColorEnum::YELLOW);
						continue;
					}

					throw $e;
				}
			}
		}

		if (!$applied) {
			$this->logger->output('Everything is up-to-date', false, ColorEnum::GREEN);
		} else {
			$this->logger->output('Applied ' . $applied . ' file(s)', false, ColorEnum::GREEN);
		}
	}

	/**
	 * Check version number
	 *
	 * @throws Exception
	 */
	public function validateVersion(string $versionNumber): bool
	{
		if (!ctype_digit($versionNumber) || strlen($versionNumber) !== 4) {
			throw new Exception(
				'Version number should be a string of 4 digits, example: 0001',
				Exception::VERSION_NUMBER,
			);
		}

		$path = $this->path . '/' . $versionNumber;
		if (!file_exists($path) || !is_dir($path)) {
			throw new Exception(
				'Version number ' . $versionNumber . ' does not exists, or is not a folder',
				Exception::FOLDER_STRUCTURE,
			);
		}

		if (!glob($path . '/*.sql')) {
			throw new Exception(
				'The folder for version number ' . $versionNumber . ' is empty and cannot be applied',
				Exception::FOLDER_EMPTY,
			);
		}

		return true;
	}

	/**
	 * Get folders and file structure
	 *
	 * @throws Exception
	 */
	public function getStructure(): array
	{
		if (isset($this->structure)) {
			return $this->structure;
		}

		$files = glob($this->path . '*/*.sql');

		$structure = [];
		foreach ($files as $file) {
			$relative = substr($file, strlen($this->path));

			$versionNumber = dirname($relative);
			$baseName = basename($relative, '.sql');

			/**
			 * Always run 0000 version, so skip valid version number check
			 */
			if ($versionNumber === '0000') {
				$structure[$versionNumber][] = $baseName;
				continue;
			}

			try {
				$this->validateVersion($versionNumber);
			} catch (Exception $e) {
				if ($e->getCode() === Exception::VERSION_NUMBER) {
					throw new Exception(
						'Folder structure for version should be 4 digits, example: 0001',
						Exception::FOLDER_STRUCTURE,
					);
				}

				throw $e;
			}

			if (substr($baseName, -5) === '-down') {
				continue;
			}

			$structure[$versionNumber][] = $baseName;
		}

		if (!$structure) {
			throw new Exception(
				'No migration files present in `' . $this->path . '`',
				Exception::FOLDER_EMPTY,
			);
		}

		$this->structure = $structure;

		return $this->structure;
	}

	/**
	 * Import file
	 *
	 * @throws Exception
	 */
	public function importFile(
		int $version,
		string $fileName,
		bool $downgrade = false,
	): bool
	{
		$versionNumber = $this->versionPad($version);
		$file = $this->path . $versionNumber . '/'
			. $fileName . ($downgrade ? '-down' : '') . '.sql';

		if ($versionNumber === '0000' && $downgrade) {
			throw new Exception(
				'Version 0000 cannot be used with downgrades',
				Exception::DOWNGRADE_VERSION_NUMBER,
			);
		}

		if (!file_exists($file)) {
			throw new Exception(
				'File `' . $file . '` does not exist and cannot be imported',
				Exception::MISSING_FILE,
			);
		}

		$this->logger->output(' - ' . ($downgrade ? 'Downgrading' : 'Migrating') . ' file ' . $fileName . '…');

		usleep($this->sleep);

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

		/**
		 * We skip transactional usage, if an transaction is already triggered
		 */
		$useTransaction = !$this->pdo->inTransaction();

		if ($useTransaction) {
			$this->pdo->beginTransaction();
		}

		$this->importSqlFile($file);

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
		if (!$downgrade && $versionNumber !== '0000') {
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

		$this->logger->output($downgrade ? 'DOWNGRADED' : 'MIGRATED', true, ColorEnum::GREEN);

		return true;
	}

	/**
	 * Import file
	 */
	public function importSqlFile(string $fileName): void
	{
		if (!file_exists($fileName)) {
			throw new Exception(
				'File `' . $fileName . '` does not exist and cannot be imported',
				Exception::MISSING_FILE,
			);
		}

		$fopen = fopen($fileName, 'r');
		$content = trim(fread($fopen, max(filesize($fileName), 0, 1)));
		fclose($fopen);

		if (!$content) {
			throw new Exception(
				'SQL file is empty: `' . $fileName . '`',
				Exception::EMPTY_FILE,
			);
		}

		try {
			$this->pdo->exec($content);
		} catch (PDOException $e) {
			$this->logger->output('ERROR', true, ColorEnum::RED);
			$this->logger->output(' - PDO Error: ' . $e->getMessage());

			if ($this->pdo->inTransaction()) {
				$this->pdo->rollBack();
			}

			throw $e;
		}
	}

	/**
	 * Delete migration row
	 */
	protected function deleteMigrationRow(string | int $versionNumber, string $name): void
	{
		$prepare = $this->pdo->prepare(<<<SQL
			DELETE FROM "{$this->tableName}"
			WHERE "version" = ? AND "name" = ?
		SQL);
		$prepare->execute([
			$versionNumber,
			$name,
		]);
	}

	/**
	 * Get applied migrations for downgrade
	 */
	public function getMigrationsForDowngrade(string $versionNumber): array
	{
		$prepare = $this->pdo->prepare(<<<SQL
			SELECT *
			FROM "{$this->tableName}"
			WHERE "version" > ?
			ORDER BY "version" DESC, "name" DESC
		SQL);

		$prepare->execute([
			$versionNumber,
		]);

		$resultset = $prepare->fetchAll(PDO::FETCH_ASSOC);

		$structure = [];
		foreach ($resultset as $row) {
			$structure[$this->versionPad($row['version'])][] = $row['name'];
		}

		return $structure;
	}

	/**
	 * Downgrade file
	 */
	public function downgradeFile(int $version, string $fileName): bool
	{
		return $this->importFile($version, $fileName, true);
	}

	/**
	 * Version pad
	 */
	public function versionPad(int | string $version): string
	{
		return str_pad((string) $version, 4, '0', STR_PAD_LEFT);
	}

	/**
 	 * Execute query with connected PDO driver
	 *
	 * @throws PDOException
   	 */
	public function query(
		string $statement,
		array $bind = [],
		bool $select = false,
	): array | bool
	{
		$prepare = $this->pdo->prepare($statement);

		$result = $prepare->execute($bind);
		if (!$select) {
			return $result;
		}

		return $prepare->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get connected PDO driver for manually executing
	 */
	public function getPdo(): PDO
	{
		return $this->pdo;
	}
}

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
	 * Run migrations
	 */
	public function run(): void
	{
		$this->logger->output('Starting migration', false, ColorEnum::CYAN);

		$structure = $this->getStructure();

		foreach ($structure as $version => $names) {
			$this->logger->output('Applying version ' . $version, false, ColorEnum::MAGENTA);

			foreach ($names as $name) {
				try {
					$this->importFile((int) $version, $name);
				} catch (Exception $e) {
					if ($e->getCode() === Exception::MIGRATION_ALREADY_APPLIED) {
						$this->logger->output('ALREADY APPLIED', true, ColorEnum::YELLOW);
						continue;
					}

					throw $e;
				}
			}
		}
	}

	/**
	 * Get folders and file structure
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

			if (!ctype_digit($versionNumber) || strlen($versionNumber) !== 4) {
				throw new Exception(
					'Folder structure for version should be 4 digits, example: 0001',
					Exception::FOLDER_STRUCTURE,
				);
			}

			if (substr($baseName, -5) === '-down') {
				continue;
			}

			$structure[$versionNumber][] = $baseName;
		}

		$this->structure = $structure;

		return $this->structure;
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

		$this->logger->output(' - ' . ($downgrade ? 'Downgrading' : 'Migrating') . ' file ' . $fileName . '…');

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

		$fopen = fopen($file, 'r');
		$content = trim(fread($fopen, max(filesize($file), 0, 1)));
		fclose($fopen);

		if (!$content) {
			throw new Exception(
				'SQL file is empty: `' . $fileName . '`',
				Exception::EMPTY_FILE,
			);
		}

		/**
		 * We skip transactional usage, if an transaction is already triggered
		 */
		$useTransaction = !$this->pdo->inTransaction();

		if ($useTransaction) {
			$this->pdo->beginTransaction();
		}

		try {
			$this->pdo->exec($content);
		} catch (PDOException $e) {
			$this->logger->output('ERROR', true, ColorEnum::RED);
			$this->logger->output(' - PDO Error: ' . $e->getMessage());

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

		$this->logger->output($downgrade ? 'DOWNGRADED' : 'MIGRATED', true, ColorEnum::GREEN);

		return true;
	}

	/**
	 * Downgrade file
	 */
	public function downgradeFile(int $version, string $fileName): bool
	{
		return $this->importFile($version, $fileName, true);
	}
}

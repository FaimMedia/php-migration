<?php

namespace FaimMedia\Migration;

use PDO;
use PDOStatement;

use PDOException;

/**
 * Migrate class
 */
class Migrate
{
	protected PDO $pdo;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->pdo = new PDO();
	}

	/**
	 * Run migrations
	 */
	public function run(): void
	{

	}
}

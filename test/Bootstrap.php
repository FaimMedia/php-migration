<?php

use FaimMedia\Migration\Logger\{
	Color,
	ColorEnum,
};

define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
define('SOURCE_PATH', ROOT_PATH . 'src/');
define('TEST_PATH', realpath(__DIR__) . '/');

define('PDO_USERNAME', 'migrate-test');
define('PDO_DATABASE', 'migrate-test');

require ROOT_PATH . 'vendor/autoload.php';

$logger = new Color();
$logger->output('Initializing database…', false, ColorEnum::MAGENTA);

$pdo = new PDO(
	'pgsql:host=postgres;port=5432;dbname=' . PDO_DATABASE . ';user=' . PDO_USERNAME,
	null,
	null,
	[
		PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_AUTOCOMMIT => 0,
	],
);

$pdo->beginTransaction();

$pdo->query("SET session_replication_role = 'replica';");

$query = $pdo->query(<<<SQL
	SELECT table_schema, table_name
	FROM information_schema.tables
	WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
	ORDER BY table_schema, table_name
SQL);

foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $result) {
	$pdo->query('DROP TABLE "' . $result['table_schema'] . '"."' . $result['table_name'] . '" CASCADE;');
}

$pdo->query("SET session_replication_role = 'origin';");

$pdo->commit();

#!/usr/bin/env php
<?php

use FaimMedia\Migration\Migration;

define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
define('SOURCE_PATH', ROOT_PATH . 'src/');
define('TEST_PATH', realpath(__DIR__) . '/');

define('PDO_USERNAME', 'migrate-test');
define('PDO_DATABASE', 'migrate-test');

require ROOT_PATH . 'vendor/autoload.php';

$required = [
	'dsn',
	'username',
	'path',
];

$options = getopt('', [
	'dsn:',
	'username:',
	'password::',
	'path:',
	'version::',
]);

$errors = [];
foreach ($required as $field) {
	if (!empty($options[$field])) {
		continue;
	}

	$errors[] = 'Missing required argument --' . $field;
}

if ($errors) {
	echo ' - ' . join(PHP_EOL . ' - ', $errors);
	echo PHP_EOL;
	exit(1);
}

try {
	$migration = new Migration([
		...$options,
	]);

	$migration->run();
} catch (Throwable $e) {
	echo 'An error occurred during migration: ' . $e->getMessage() . PHP_EOL;
	print_r($e->getTrace());

	exit(2);
}

exit(0);

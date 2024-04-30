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
	'tableName::',
	'debug',
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

	$migration->run($options['version'] ?? null);
} catch (Throwable $e) {
	$message = 'An error occurred during migration: ' . $e->getMessage();

	$wraps = explode("\n", wordwrap($message, 75));
	$maxLength = max(array_map('strlen', $wraps)) + 4;

	echo chr(27) . '[41m' . str_repeat(' ', $maxLength) . chr(27) . '[0m' . PHP_EOL;

	foreach ($wraps as $wrap) {
		echo chr(27) . '[41m' . '  ' . str_pad($wrap, $maxLength - 2, ' ') . chr(27) . '[0m' . PHP_EOL;
	}

	echo chr(27) . '[41m' . str_repeat(' ', $maxLength) . chr(27) . '[0m' . PHP_EOL;

	if (($options['debug'] ?? null) !== null) {
		echo 'Stack trace: ' . PHP_EOL;

		$trace = $e->getTrace();
		array_unshift($trace, [
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		]);

		echo json_encode($trace, JSON_PRETTY_PRINT);
	}

	exit(2);
}

exit(0);

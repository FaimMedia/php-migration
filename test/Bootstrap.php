<?php

use App\Engine\Di;

define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
define('SOURCE_PATH', ROOT_PATH . 'src/');
define('TEST_PATH', realpath(__DIR__) . '/');

require ROOT_PATH . 'vendor/autoload.php';

echo 'Initialize...' . PHP_EOL;

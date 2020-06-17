<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	exit;
}

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__.DS.'app'.DS);
$config=include_once ROOT.'config/config.php';
require_once ROOT.'./../vendor/autoload.php';
require_once ROOT.'config/services.php';
\Ubiquity\controllers\Startup::run($config);

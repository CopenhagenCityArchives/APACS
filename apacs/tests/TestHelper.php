<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

define('ROOT_PATH', __DIR__);
define('PATH_LIBRARY', __DIR__ . '/../app/library/');
//define('PATH_SERVICES', __DIR__ . '/../app/services/');
//define('PATH_RESOURCES', __DIR__ . '/../app/resources/');
define('PATH_MODELS', __DIR__ . '/../app/models/');
define('PATH_CONTROLLERS', __DIR__ . '/../app/controllers/');

set_include_path(
	ROOT_PATH . PATH_SEPARATOR . get_include_path()
);

// required for phalcon/incubator
include __DIR__ . "/../vendor/autoload.php";

// use the application autoloader to autoload the classes
// autoload the dependencies found in composer
$loader = new \Phalcon\Loader();

$loader->registerDirs(array(
	ROOT_PATH,
	PATH_MODELS,
	PATH_LIBRARY,
	PATH_CONTROLLERS,
));

/*$loader->registerClasses([
"Entities" => "../app/library/models/Entities.php",
]
);*/

$loader->registerNamespaces(array(
	'Phalcon' => '../vendor/incubator/Library/Phalcon/',
	"Mocks" => "./Mocks/",
));

$loader->register();

$di = new \Phalcon\Di\FactoryDefault;

//Test specific database, Phalcon
$di->setShared('db', function () {
	return new \Phalcon\Db\Adapter\Pdo\Mysql([
		"host" => "db",
		"username" => "root",
		"password" => "1234565",
		"dbname" => "apacs",
		'charset' => 'utf8',
	]);
});

$di->get('db')->execute(file_get_contents("../init/init.sql"));
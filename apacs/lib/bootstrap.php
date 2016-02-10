<?php

use Phalcon\Mvc\Micro\Collection as MicroCollection;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$app = new Phalcon\Mvc\Micro();

try {
	//Register an autoloader
	$loader = new \Phalcon\Loader();
	$loader->registerDirs(array(
		'../../lib/controllers/',
		'../../lib/models/',
		'../../lib/library/',
	))->register();

	include __DIR__ . "/../vendor/autoload.php";

	//Create a DI
	$di = new Phalcon\DI\FactoryDefault();

	require '../../lib/config/config.php';

	//Setup the configuration service
	$di->setShared('configuration', function () use ($di) {
		//Loading the almighty configuration array
		return new ConfigurationLoader('../../lib/config/CollectionsConfiguration.php');
	});

	//Setup the database service
	$di->setShared('db', function () use ($di) {
		return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
	});

/*	$di->set('database', function () use ($di) {
return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
});
 */
	$di->setShared('response', function () {
		return new \Phalcon\Http\Response();
	});

	//Metadata routes collection
	$metadata = new MicroCollection();

	//Set the main handler. ie. a controller instance
	$metadata->setHandler(new MetadataLevelsController());

	//Collection info
	$metadata->get('/collections/{collection:[0-9]+}/info', 'displayinfo');
	$metadata->get('/collections', 'getcollectioninfo');
	$metadata->get('/collections/{collection:[0-9]+}', 'getcollectioninfo');

	//Metadata levels
	$metadata->get('/levels/{collection:[0-9]+}', 'getmetadatalevels');
	$metadata->get('/levels/{collection:[0-9]+}/{metadatalevel}', 'getmetadatalevels');

	//Metadata
	//What about this: $metadata->('/metadata/{collection:[0-9]+}, should get all metadata for all levels?);
	$metadata->get('/metadata/{collection:[0-9]+}/{metadatalevel}', 'getmetadata');

	//Object data
	$metadata->get('/data/{collection:[0-9]+}', 'getobjectdata');

	//Error reports
	$metadata->get('/error/{collection:[0-9]+}/{item:[0-9]+}/{error:[0-9]+}', 'reporterror');

	$app->mount($metadata);

	//Info routes collection
	$info = new MicroCollection();
	$info->setHandler(new CommonInformationsController());

	$info->get('/units', 'GetUnits');
	$info->get('/units/{unitId:[0-9]+}', 'GetUnit');
	//   $info->post('/units', 'ImportUnits');

	$info->get('/pages', 'GetPages');
	$info->get('/pages/{page:[0-9]+}', 'GetPage');
	$info->post('/pages', 'ImportPages');
	$info->get('/pages/nextavailable', 'GetNextAvailablePage');

	$info->get('/taskschema', 'GetTaskFieldsSchema');

	$info->get('/tasks', 'GetTasks');
	$info->get('/tasks/{taskId:[0-9]+}', 'GetTask');
	$info->get('/collections2', 'GetCollections');
	$info->get('/collections2/{collectionId:[0-9]+}', 'GetCollection');

	$app->mount($info);

	//Users routes
	/*     $users = new MicroCollection();
	        $users->setHandler(new UserController());

	        $users->get('/activeusers', 'GetActiveUsers');
	        $users->get('/users', 'GetUsers');
	        $users->get('/users/{userId:{[0-9]+}', 'GetUser');

	        $app->mount($users);
*/
	//Index data routes
	$indexing = new MicroCollection();
	$indexing->setHandler(new IndexDataController());

	$indexing->get('/test', 'test');

	$indexing->get('/datasource/{dataSourceId:[0-9]+}', 'GetDataFromDatasouce');

	$indexing->post('/entries/{taskId:[0-9]+}', 'SaveEntry');
	$indexing->get('/entries', 'GetEntries');

//TODO: Implement endpoint for creating posts
	$indexing->post('/pages/{taskId:[0-9]+', 'CreatePosts');

	$app->mount($indexing);

	//Not found-handling
	$app->notFound(function () use ($app, $di) {
		$di->get('response')->setStatusCode(400, "Not Found");
		$di->get('response')->setContent('<h1>Bad request!</h1>');
	});

	//Access-Control-Allow-Origin header (note: this is not secure!)
	$app->before(function () use ($app, $di) {
		$origin = $app->request->getHeader("ORIGIN") ? $app->request->getHeader("ORIGIN") : '*';

		$di->get('response')->setHeader("Access-Control-Allow-Origin", $origin)
			->setHeader("Access-Control-Allow-Methods", 'GET,PUT,POST,DELETE,OPTIONS')
			->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization')
			->setHeader("Access-Control-Allow-Credentials", true);
	});

	$app->handle();

	//Send any responses collected in the controllers
	$di->get('response')->send();

} catch (Exception $e) {
	$di->get('response')->setStatusCode(500, "Server error");
	$di->get('response')->setContent("Global exception: " . $e->getMessage());
	$di->get('response')->send();
}
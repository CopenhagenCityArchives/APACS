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
		'../../app/controllers/',
		'../../app/models/',
		'../../app/library/',
	))->register();

	include __DIR__ . "/../vendor/autoload.php";

	//Create a DI
	$di = new Phalcon\DI\FactoryDefault();

	require '../../app/config/config.php';

	//Setup the configuration service
	$di->setShared('configuration', function () use ($di) {
		//Loading the almighty configuration array
		return new ConfigurationLoader('../../app/config/CollectionsConfiguration.php');
	});

	//Setup the database service
	$di->setShared('db', function () use ($di) {
		return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
	});

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

	$info->get('/tasksunits', 'GetTasksUnits');
	$info->get('/units/{unitId:[0-9]+}', 'GetUnit');
	//   $info->post('/units', 'ImportUnits');

	$info->get('/pages', 'GetPages');
	$info->get('/pages/{page:[0-9]+}', 'GetPage');
	$info->get('/pages/nextavailable', 'GetNextAvailablePage');

	$info->post('/pages', 'ImportPages');

	$info->get('/posts/{post_id:[0-9]+}', 'GetPostEntries');

	$info->get('/posts/{post:[0-9]+}/image', 'GetPostImage');

	$info->get('/taskschema', 'GetTaskFieldsSchema');
	$info->get('/searchconfig', 'GetSearchConfig');

	$info->get('/tasks', 'GetTasks');
	$info->get('/tasks/{taskId:[0-9]+}', 'GetTask');
	$info->get('/collections2', 'GetCollections');
	$info->get('/collections2/{collectionId:[0-9]+}', 'GetCollection');

	$info->get('/entries/{entry_id:[0-9]+}', 'GetEntry');
	$info->get('/entries', 'GetEntries');

	$info->get('/errorreports', 'GetErrorReports');

	$info->get('/useractivities', 'GetUserActivities');

	$info->get('/activeusers', 'GetActiveUsers');

	$info->get('/users/{id:[0-9]+}', 'GetUser');

	$app->mount($info);

	//Index data routes
	$indexing = new MicroCollection();
	$indexing->setHandler(new IndexDataController());

	$indexing->get('/datasource/{dataSourceId:[0-9]+}', 'GetDataFromDatasouce');

	$indexing->get('/search', 'SolrProxy');

	$indexing->post('/entries', 'SaveEntry');
	$indexing->patch('/entries/{entry_id:[0-9]+}', 'UpdateEntry');

	$indexing->patch('/taskspages', 'UpdateTasksPages');

	$indexing->post('/errorreports', 'ReportError');

	$indexing->patch('/errorreports/{errorreportId:[0-9]+}', 'UpdateErrorReport');

	$indexing->get('/test', 'authCheck');

	$app->mount($indexing);

	//Catch all for preflight checks (typically performed with an OPTIONS request)
	$app->options('/{catch:(.*)}', function () use ($app) {
		$app->response->setStatusCode(200, "OK")->send();
	});

	//Not found-handling
	$app->notFound(function () use ($app, $di) {
		$di->get('response')->setStatusCode(400, "Not Found");
		$di->get('response')->setContent('<h1>Bad request!</h1>');
	});

	//Access-Control-Allow-Origin header (note: this is not secure!)
	$app->before(function () use ($app, $di) {
		$origin = $app->request->getHeader("ORIGIN") ? $app->request->getHeader("ORIGIN") : '*';

		$di->get('response')->setHeader("Access-Control-Allow-Origin", $origin)
			->setHeader("Access-Control-Allow-Methods", 'GET,PUT,PATCH,POST,OPTIONS')
			->setHeader("Access-Control-Request-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-Custom-Header, accept')
			->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-Custom-Header, accept')
			->setHeader("Access-Control-Allow-Credentials", true)
			->setHeader("Accept-Charset", "UTF-8")
			->setHeader("Cache-Control", "no-cache, no-store, must-revalidate")
			->setHeader("Pragma", "no-cache")
			->setHeader("Expires", "0")
			->setContentType("application/json");
	});

	$app->handle();
	//Send any responses collected in the controllers
	$di->get('response')->setHeader('charset', 'utf-8');
	$di->get('response')->send();

} catch (Exception $e) {
	$di->get('response')->setStatusCode(500, "Server error");
	$di->get('response')->setContent("Global exception: " . $e->getMessage());
	$di->get('response')->send();
}
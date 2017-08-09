<?php

use Phalcon\Mvc\Micro\Collection as MicroCollection;

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
	$metadata->get('/collections/{collection:[0-9]+}/info', 'displayCollectionInfo');
	$metadata->get('/info', 'displayAllCollectionsInfo');

	$metadata->get('/collections', 'getcollectioninfoJSON');
	$metadata->get('/collections/{collection:[0-9]+}', 'getcollectioninfoJSON');

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

	//Create post
	$info->post('/posts', 'CreateOrUpdatePost');

	//Update post
	$info->patch('/posts/{id:[0-9]+}', 'CreateOrUpdatePost');

	$info->get('/posts/{post:[0-9]+}/image', 'GetPostImage');

	$info->get('/taskschema', 'GetTaskFieldsSchema');
	$info->get('/searchconfig', 'GetSearchConfig');

	$info->get('/tasks', 'GetTasks');
	$info->get('/tasks/{taskId:[0-9]+}', 'GetTask');
	$info->get('/collections2', 'GetCollections');
	$info->get('/collections2/{collectionId:[0-9]+}', 'GetCollection');

	$info->get('/entries/{entryId:[0-9]+}', 'GetEntry');
	$info->get('/entries', 'GetEntries');

	$info->get('/errorreports', 'GetErrorReports');

	$info->get('/useractivities', 'GetUserActivities');

	$info->get('/activeusers', 'GetActiveUsers');

	$info->get('/users/{id:[0-9]+}', 'GetUser');

	$info->get('/exceptions', 'GetSystemExceptions');

	//Add new collection
	$info->post('/collections', 'CreateOrUpdateCollection');

	//Change existing collection
	//$info->patch('/collection/{id:[0-9]+}', 'CreateOrUpdateCollection');

	//Add or change units
	$info->post('/units', 'CreateOrUpdateUnits');

	$app->mount($info);

	//Index data routes
	$indexing = new MicroCollection();
	$indexing->setHandler(new IndexDataController());

	$indexing->get('/datasource', 'GetDatasourceList');
	$indexing->get('/datasource/{dataSourceId:[0-9]+}', 'GetDataFromDatasouce');

	$indexing->post('/datasource/{dataSourceId:[0-9]+}', 'CreateDatasourceValue');
	$indexing->patch('/datasource/{dataSourceId:[0-9]+}', 'UpdateDatasourceValue');

	$indexing->get('/search', 'SolrProxy');

	$indexing->post('/entries', 'SaveEntry');
	$indexing->put('/entries/{entryId:[0-9]+}', 'SaveEntry');
	//$indexing->patch('/entries/{entry_id:[0-9]+}', 'UpdateEntry');

	$indexing->patch('/taskspages', 'UpdateTasksPages');

	$indexing->post('/errorreports', 'ReportError');

	$indexing->patch('/errorreports/{errorreportId:[0-9]+}', 'UpdateErrorReport');
	$indexing->patch('/errorreports', 'UpdateErrorReports');

	$indexing->get('/test', 'authCheck');

	$app->mount($indexing);

	//Catch all for preflight checks (always performed with an OPTIONS request)
	$app->options('/{catch:(.*)}', function () use ($app, $di) {
		$di->get('response')->setHeader('Access-Control-Allow-Credentials', 'true');
		$di->get('response')->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-Custom-Header, accept');
		$di->get('response')->setHeader("Access-Control-Allow-Methods", 'GET, PUT, PATCH, POST, OPTIONS');
		$di->get('response')->setHeader('Access-Control-Max-Age', '1728000');
		$di->get('response')->setHeader('Connection', 'keep-alive');
		$di->get('response')->setHeader('Content-Length', 0);
		$di->get('response')->setStatusCode(204, "No Content");
	});

	//Not found-handling
	$app->notFound(function () use ($app, $di) {
		$di->get('response')->setStatusCode(400, "Not Found");
		$di->get('response')->setContent('<h1>Bad request!</h1>');
	});

	$app->before(function () use ($app, $di) {

		//Always set Access-Control-Allow-Origin
		$origin = $app->request->getHeader("ORIGIN") ? $app->request->getHeader("ORIGIN") : '*';
		$di->get('response')->setHeader("Access-Control-Allow-Origin", $origin);

		//Default return is JSON in utf-8
		$di->get('response')->setHeader('Content-Type', 'application/json; charset=utf-8');

		//OPTIONS preflights are handled elsewhere
		if ($di->get('request')->isOptions()) {
			return;
		}

		//Set cache to zero if it is not set
		if (!$di->get('response')->getHeaders()->get('Cache-Control')) {
			$di->get('response')
				->setHeader("Cache-Control", "no-cache, no-store, must-revalidate")
				->setHeader("Pragma", "no-cache")
				->setHeader("Expires", "0");
		}

	});

	$app->handle();

	//Send any responses collected in the controllers
	$di->get('response')->send();

} catch (Exception $e) {
	//Saving system exception
	$exception = new SystemExceptions();
	$exception->save([
		'type' => 'global_exception',
		'details' => json_encode(['exception' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString()]),
	]);

	$di->get('response')->setStatusCode(500, "Server error");
	$di->get('response')->setJsonContent(['message' => "Global exception: " . $e->getMessage()]);
	$di->get('response')->send();
}

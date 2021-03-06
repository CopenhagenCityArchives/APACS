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

	include dirname(__DIR__) . "/vendor/autoload.php";

	//Create a DI
	$di = new Phalcon\DI\FactoryDefault();

	$di->setShared('response', function () {
		return new \Phalcon\Http\Response();
	});

	require __DIR__ . '/config/config.php';

	//TODO: Test if this works as well (better to use one autoloader than two) :
	/**
	 * Register Files, composer autoloader
	 */
// $loader->registerFiles(
	//     [
	//         APP_PATH . '/vendor/autoload.php'
	//     ]
	// );

	//Setup the configuration service
	$di->setShared('collectionsConfiguration', function () use ($di) {
		//Loading the almighty configuration array
		return new ConfigurationLoader('../../app/config/CollectionsConfiguration.php', $di);
	});

	//Setup the database service
	$di->setShared('db', function () use ($di) {
		return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
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
	$info->get('/units', 'GetUnits');

	$info->get('/pages', 'GetPages');
	$info->get('/pages/{page:[0-9]+}', 'GetPage');
	$info->get('/pages/unit/{unit_id:[0-9]+}/number/{page_number:[0-9]+}', 'GetPageFromNumber');
	$info->get('/pages/nextavailable', 'GetNextAvailablePage');

	$info->get('/posts/{post_id:[0-9]+}', 'GetPostEntries');
	$info->get('/posts/{post_id:[0-9]+}/areas', 'GetPostAreas');

	$info->patch('/subpost/{subpost_id:[0-9]+}', 'UpdateSubpost');
	$info->delete('/subpost/{subpost_id:[0-9]+}', 'DeleteSubpost');

	//Create post
	$info->post('/posts', 'CreateOrUpdatePost');

	$info->post('/posts/{post_id:[0-9]+}/subposts', 'CreateOrUpdateSubposts');

	//Update post
	$info->patch('/posts/{id:[0-9]+}', 'CreateOrUpdatePost');

	//Delete post
	$info->delete('/posts/{id:[0-9]+}', 'DeletePost');

	$info->get('/posts/{post:[0-9]+}/image', 'GetPostImage');

	$info->get('/taskschema', 'GetTaskFieldsSchema');
	$info->get('/searchconfig', 'GetSearchConfig');
	$info->get('/errorreportconfig', 'GetErrorReportConfig');

	$info->get('/tasks', 'GetTasks');
	$info->get('/tasks/{taskId:[0-9]+}', 'GetTask');
	$info->get('/collections2', 'GetCollections');
	$info->get('/collections2/{collectionId:[0-9]+}', 'GetCollection');

	$info->get('/entries/{entryId:[0-9]+}', 'GetEntry');
	$info->get('/entries', 'GetEntries');

	$info->get('/errorreports', 'GetErrorReports');

	$info->get('/exceptions', 'GetSystemExceptions');

	$info->get('/events', 'GetEventEntriesForLastWeek');
	$info->get('/events/{event_type}/{unix_time:[0-9]+}', 'GetEventEntries');

	//Add new collection
	$info->post('/collections', 'CreateOrUpdateCollection');

	//Change existing collection
	//$info->patch('/collection/{id:[0-9]+}', 'CreateOrUpdateCollection');

	//Add or change units
	$info->post('/units', 'CreateOrUpdateUnits');

	// Health check
	$info->get('/health', 'healthCheck');
	$info->head('/health', 'healthCheck');

	$app->mount($info);

	$files = new MicroCollection();
	$files->setHandler(new GetFileController());
	
	$files->get('/file/{id:[0-9]+}', 'GetFileById');
	$files->get('/file', 'GetFileByPath'); // example: /file?path={pathToFile}

	$app->mount($files);
	
	$users = new MicroCollection();
	$users->setHandler(new UsersController());
		
	$users->get('/useractivities', 'GetUserActivities');
	
	$users->get('/activeusers', 'GetActiveUsers');
	
	$users->get('/users/{id:[0-9]+}', 'GetUser');

	$users->patch('/user', 'UpdateUserProfile');

	$app->mount($users);

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

	$app->mount($indexing);

	// Cumulus asset SolrProxy
	$cumulus = new MicroCollection();
	$cumulus->setHandler(new CumulusAssetController());
	$cumulus->get('/asset/{assetId:[0-9]+}', 'AssetDownload');
	$app->mount($cumulus);

	// Administration Controller
	$admin = new MicroCollection();
	$admin->setHandler(new AdministrationController());
	$admin->post('/admin/taskunits', 'createTasksUnits');
	$admin->post('/admin/taskpages', 'createTasksPages');
	$app->mount($admin);

	//Catch all for preflight checks (always performed with an OPTIONS request)
	$app->options('/{catch:(.*)}', function () use ($app, $di) {
		$di->get('response')->setHeader('Access-Control-Allow-Credentials', 'true');
		$di->get('response')->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-Custom-Header, accept');
		$di->get('response')->setHeader("Access-Control-Allow-Methods", 'GET, PUT, PATCH, POST, OPTIONS, DELETE');
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
		$origin = '*';
		$di->get('response')->setHeader("Access-Control-Allow-Origin", $origin);

		if ($di->get('request')->getQuery('callback')) {
			$di->get('response')->setHeader('Content-Type', 'application/javascript; charset=utf-8');
		} else {
			//Default return is JSON in utf-8
			$di->get('response')->setHeader('Content-Type', 'application/json; charset=utf-8');
		}

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
	try
	{
		//Saving system exception
		$exception = new SystemExceptions();

		$mainCtrl = new MainController();

		$postData = $mainCtrl->GetAndValidateJsonPostData();

		if ($postData == false) {
			$postData = null;
		}

		$exception->save([
			'type' => 'global_exception',
			'details' => json_encode(['exception' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString(), 'postData' => $postData]),
		]);
	} catch (Exception $exp) {

	} 
	//Always set Access-Control-Allow-Origin on global exceptions
	$origin = '*';
	$di->get('response')->setHeader("Access-Control-Allow-Origin", $origin);
	$di->get('response')->setStatusCode(500, "Server error");
	$di->get('response')->setJsonContent(['message' => "Global exception: " . $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())]);
	$di->get('response')->send();
}

<?php


$envLocation = __DIR__ . '/';

if(file_exists($envLocation . '.env')){
	$dotenv = \Dotenv\Dotenv::create($envLocation, '.env');
	$dotenv->load();
	$dotenv->required('ENVIRONMENT');
}

if(getenv('ENVIRONMENT') == 'DEV'){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
}

$di->setShared('config', function () {
	return [
		"host" => getenv('APACS_DB_HOST'),
		"port" => getenv('APACS_DB_PORT'),
		"username" => getenv('APACS_DB_USER'),
		"password" => getenv('APACS_DB_PASSWORD'),
		"dbname" => getenv('APACS_DB_DATABASE'),
		"charset" => getenv('APACS_DB_CHARSET'),
	];
});

$di->setShared('solrConfig', function () {
		return [
			"scheme" => getenv('SOLR_SCHEME'),
			"host" => getenv('SOLR_HOST'),
			"dns" => getenv('SOLR_DNS'),
			"port" => getenv('SOLR_PORT'),
			"path" => getenv('SOLR_PATH'),
			"timeout" => getenv('SOLR_TIMEOUT'),
			"username" => getenv('SOLR_USERNAME'),
			"password" => getenv('SOLR_PASSWORD'),
		];
});

$di->setShared('cipConfig', function () {
	return [
		"host" => getenv('CUMULUS_HOST'),
		"port" => getenv('CUMULUS_PORT'),
		"user" => getenv('CUMULUS_USER'),
		"pass" => getenv('CUMULUS_PASS'),
		"location" => getenv('CUMULUS_LOCATION'),
		"catalog" => getenv('CUMULUS_CATALOG'),
	];
});

$di->setShared('pageImageLocation', function () {
	if(getenv('APACS_IMAGE_PATH') == 'local'){
		return [
			'path' => $_SERVER['DOCUMENT_ROOT'].'/../collections/',
			'type' => getenv('APACS_IMAGE_PROTOCOL')
		];
	}
	
	return [
		'path' => getenv('APACS_IMAGE_PATH'),
		'type' => getenv('APACS_IMAGE_PROTOCOL'),
	];
});

$di->setShared('auth0Config', function () {
	return [
		'client_id' => getenv('AUTH0_CLIENT_ID'),
		'client_secret' => getenv('AUTH0_CLIENT_SECRET'),
		'issuer' => getenv('AUTH0_ISSUER'),
		'audience' => getenv('AUTH0_AUDIENCE'),
		'mgmt_audience' => getenv('AUTH0_MANAGEMENT_AUDIENCE'),
		'domain' => getenv('AUTH0_DOMAIN'),
		'cacheLocation' => getenv('AUTH0_CACHE_LOCATION'),
		'cacheDuration' => getenv('AUTH0_CACHE_DURATION'),
	];
});

$di->setShared('AccessController', function () use ($di) {
		$className = getenv('APACS_ACCESS_CTRL_NAME');

		if(!class_exists($className)){
			throw new Exception("AccessController class name must be set (using APACS_ACCESS_CTRL_NAME)");
		}

		return new $className($di);
});

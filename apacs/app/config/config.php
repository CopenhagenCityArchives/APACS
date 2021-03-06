<?php


$envLocation = __DIR__ . '/';

if(file_exists($envLocation . '.env')){
	$dotenv = \Dotenv\Dotenv::create($envLocation, '.env');
	$dotenv->load();
	$dotenv->required('ENVIRONMENT');
}

if(getenv('ENVIRONMENT') == 'DEV'){
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
}
else{
	error_reporting(0);
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
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

$di->setShared('auth0Config', function () {
	return [
		'client_id' => getenv('AUTH0_CLIENT_ID'),
		'client_secret' => getenv('AUTH0_CLIENT_SECRET'),
		'issuer' => getenv('AUTH0_ISSUER'),
		'audience' => getenv('AUTH0_AUDIENCE'),
		'mgmt_audience' => getenv('AUTH0_MANAGEMENT_AUDIENCE'),
		'domain' => getenv('AUTH0_DOMAIN'),
		'jwks_uri' => getenv('AUTH0_JWKS_URI'),
		'cacheLocation' => getenv('AUTH0_CACHE_LOCATION'),
		'cacheDuration' => getenv('AUTH0_CACHE_DURATION'),
		'tokenSalt' => getenv('AUTH0_TOKEN_SALT')
	];
});

$di->setShared('AccessController', function () use ($di) {
	$className = getenv('APACS_ACCESS_CTRL_NAME');

	if(!class_exists($className)){
		throw new Exception("AccessController class name must be set (using APACS_ACCESS_CTRL_NAME)");
	}

	return new $className($di);
});

$di->setShared('s3Config', function () {
	return [
		'region' => 'eu-west-1',
		'version' => '2006-03-01',
		// credentials are loaded from environment
		'credentials' => [
			'key'    => getenv('AWS_S3_ACCESS_KEY_ID'),
			'secret' => getenv('AWS_S3_SECRET_ACCESS_KEY')
		],
	];
});

$di->setShared('apiUrl', function () {
	$protocol = strpos($_SERVER['HTTP_HOST'], 'localhost') === 0 ? 'http://' : 'https://';
	$subDir = str_replace('public/', '', str_replace('index.php', '', $_SERVER['PHP_SELF']));

	return $protocol . $_SERVER['HTTP_HOST'] . $subDir;
});

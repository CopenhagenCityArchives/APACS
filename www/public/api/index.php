<?php

    use Phalcon\Mvc\Micro\Collection as MicroCollection;

    try {
        $app = new Phalcon\Mvc\Micro();
        
        //Register an autoloader
        $loader = new \Phalcon\Loader();
        $loader->registerDirs(array(
            '../../kbh_backend/controllers/',
            '../../kbh_backend/models/'
        ))->register();

        //Create a DI
        $di = new Phalcon\DI\FactoryDefault();

        //Setup the database service
        $di->set('database', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "kbharkiv",
                "password" => "***REMOVED***",
                "dbname" => "kbharkiv"
            ));
        });        
        
        //Controller 1
        $posts = new MicroCollection();
        
        $metadataLevelsHandler = new MetadataLevelsController();
        //Loading the almighty configuration array
        $metadataLevelsHandler->configurationLocation = '../../kbh_backend/config/CollectionsConfiguration.php';
        
        //Set the main handler. ie. a controller instance
        $posts->setHandler($metadataLevelsHandler);

        //Set a common prefix for all routes
        //$posts->setPrefix('/data');
        
        //Collection info
        $posts->get('/collections', 'getcollectioninfo');
        $posts->get('/collections/{collection:[0-9]+}', 'getcollectioninfo');
        
        //Metadata levels
        $posts->get('/levels/{collection:[0-9]+}', 'getmetadatalevels');
        $posts->get('/levels/{collection:[0-9]+}/{metadatalevel}', 'getmetadatalevels');
        
        //Metadata
        $posts->get('/metadata/{collection:[0-9]+}/{metadatalevel}', 'getmetadata');
        
        //Mangler:
        //Object data
        $posts->get('/data/{collection:[0-9]+}', 'getobjectdata');
        $app->mount($posts);

        $app->notFound(function () use ($app) {
            $app->response->setStatusCode(404, "Not Found")->sendHeaders();
            echo '<h1>Page not found!</h1>';
        });        

        $app->handle();

    } catch(\Phalcon\Exception $e) {
        $app->response->setStatusCode(500, "Not Found")->sendHeaders();
        echo "PhalconException: ", $e->getMessage();
    }
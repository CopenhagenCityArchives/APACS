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
        $posts->setPrefix('/data');
        
        $posts->get('/getcollectioninfo/{collection:[0-9]+}', 'getcollectioninfo');
        $posts->get('/getmetadatalevels/{collection:[0-9]+}/{metadatalevel}', 'getmetadatalevels');
        $posts->get('/getmetadatalevels/{collection:[0-9]+}', 'getmetadatalevels');
        $posts->get('/getmetadata/{collection:[0-9]+}/{metadatalevel}', 'getmetadata');
        //Mangler:
        $posts->get('/getobjects/{collection:[0-9]+}', 'getobjectdata');
        $app->mount($posts);
/*
        //Default routes
        $app->get('/isup', function(){
            echo 'ok';
        });
        
        //Test
        $app->get('/test/:params', function() use ($app) {
            var_dump( $app->request->getQuery () );
            
        });

        $app->get('/say/welcome/{name}', function ($name) {
            echo "<h1>Welcome $name!</h1>";
        });
        */
        $app->notFound(function () use ($app) {
            $app->response->setStatusCode(404, "Not Found")->sendHeaders();
            echo 'Page not found!';
        });        

        $app->handle();

    } catch(\Phalcon\Exception $e) {
         echo "PhalconException: ", $e->getMessage();
    }
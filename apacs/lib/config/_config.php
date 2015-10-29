<?php

$di->setShared('config', function(){
        return [
		    "host" => "host",
		    "username" => "user",
		    "password" => "pass",
		    "dbname" => "database",
		    'charset' => 'utf8'
		];
});
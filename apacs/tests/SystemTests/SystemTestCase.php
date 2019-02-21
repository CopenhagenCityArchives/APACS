<?php
use \Phalcon\Di;


class SystemTestCase extends \UnitTestCase
{
    private $testDBManager;

    public static function setUpBeforeClass(){
        
        // Set config and db in DI
        $di = new Di();
		$di->setShared('config', function () {
            return [
                "host" => "database",
                "username" => "dev",
                "password" => "123456",
                "dbname" => "apacs",
                'charset' => 'utf8',
            ];
		});

		$di->setShared('db', function () use ($di) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
		});
        
        // Create database entries for entities and fields        
        $testDBManager = new Mocks\TestDatabaseManager($di);
        $testDBManager->createApacsStructure();
        $testDBManager->createEntitiesAndFieldsForTask1();
        $testDBManager->createApacsMetadataForEntryPost10000Task1();
        $testDBManager->createBurialDataForEntryPost1000Task1();
    }

    public static function tearDownAfterClass(){
        // Set config and db in DI
        $di = new Di();
		$di->setShared('config', function () {
            return [
                "host" => "database",
                "username" => "dev",
                "password" => "123456",
                "dbname" => "apacs",
                'charset' => 'utf8',
            ];
		});

		$di->setShared('db', function () use ($di) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
        });
        
        // Clear database
        $testDBManager = new Mocks\TestDatabaseManager($di);
        $testDBManager->cleanUpApacsStructure();
        $testDBManager->cleanUpBurialStructure();
    }

    public function setUp(Phalcon\DiInterface $di = NULL, ?Phalcon\Config $config = NULL)
    {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }
}
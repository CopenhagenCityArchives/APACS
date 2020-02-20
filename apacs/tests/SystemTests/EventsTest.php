<?php
use \Phalcon\Di;
class EventsTest extends \UnitTestCase
{
    private $testDBManager;
    private $http;

    public static function setUpBeforeClass(){
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
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
        $testDBManager->cleanUpApacsStructure();
        $testDBManager->createApacsStructure();
        $testDBManager->createEventEntries();

    }
       
    public static function tearDownAfterClass(){
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
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
    }

    public function setUp(Phalcon\DiInterface $di = NULL, ?Phalcon\Config $config = NULL)
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
    }

    public function tearDown() {
        $this->http = null;
        parent::tearDown();
    }

    public function test_GetEventEntries() {

        $response = $this->http->request('GET', 'events');
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertNotNull($responseData);
        $this->assertEquals(797, $responseData[0]['users_id']);
        $this->assertEquals(2, $responseData[0]['count(users_id)']);
        $this->assertEquals(798, $responseData[1]['users_id']);
        $this->assertEquals(1, $responseData[1]['count(users_id)']);

    }

    public function test_GetEventEntriesWithinput_5WeeksOld() {
        $response = $this->http->request('GET', 'events/create/5');
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertNotNull($responseData);
        $this->assertEquals('User_3', $responseData[2]['username']);
        $this->assertEquals(1, $responseData[2]['count(users_id)']);
        $this->assertEquals(800, $responseData[3]['users_id']);
        $this->assertEquals(2, $responseData[3]['count(users_id)']);
    }



}


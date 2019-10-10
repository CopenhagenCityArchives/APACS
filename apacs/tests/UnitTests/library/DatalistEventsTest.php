<?php

use \Phalcon\Di;
use \Phalcon\Mvc\Model\Manager;
use \Phalcon\Mvc\Model\MetaData\Memory;

use function GuzzleHttp\json_encode;

class DatalistEventsTest extends \UnitTestCase {

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
        $testDBManager->createDataListEventsStructure();
    }

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {

        if (is_null($di)) {
            $di = new Di();
        }

        $di->set(
            'modelsManager',
            function() {
                return new Manager();
            }
        );

        $di->set(
            'modelsMetadata',
            function() {
                return new Memory();
            }
        );

        parent::setUp($di, $config);
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);

	}

	public function tearDown() {
        parent::tearDown();
        $this->http = null;

    }
    
    public static function tearDownAfterClass() {
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
        #$testDBManager->cleanUpBurialStructure();        
    }

    public function test_createNewEvent(){
        $test = new DatalistEvents();
        $test->users_id = 9999;
        $test->datasource_id = 1;
        $test->event_type = 'create';
        $test->new_value = 'creationTest';
        $this->assertTrue($test->create());
    }

    public function test_getExistingEvent() {
        $test = DatalistEvents::findFirst();
        $this->assertNotNull($test->event_type);
    }

    public function test_changeExistingEvent() {
        $event = new DatalistEvents();
        $event->users_id = 9999;
        $event->datasource_id = 1;
        $event->event_type = 'test_changeExistingEntry';
        $event->new_value = 'changeExistingEntry1';
        $event->save();
        $event->setNewValue('changeExistingEntry2');
        $event->save();
        $result = DatalistEvents::findFirst("event_type = 'test_changeExistingEntry'");
        $this->assertEquals($result->new_value, 'changeExistingEntry2');
    }

    public function test_SavingWithInvalidInput() {
        $event = new DatalistEvents();
        $event->users_id = null;
        $event->datasource_id = null;
        $event->event_type = null;
        $event->new_value = 'invalidInput_Test';
        $this->assertFalse($event->save());
    }

    public function test_changeValidEventToInvalidEventRejection() {
        $test = new DatalistEvents();
        $test->users_id = 9999;
        $test->datasource_id = 1;
        $test->event_type = 'create';
        $test->new_value = 'test_changeValidEventToInvalidEventRejection';
        $this->assertTrue($test->save());

        $test->datasource_id = null;
        $test->event_type = null;
        $test->new_value = null;
        $this->assertFalse($test->save());
        $result = DatalistEvents::findFirst("new_value = 'test_changeValidEventToInvalidEventRejection'");
        $this->assertNotNull($result);
    }

    public function test_AssertCreateEventBySuccessfulAPICall() {      
        $options = ['json' => ['value' => 'CreateEventBySuccessfulAPICall']];
        $response = $this->http->request('POST', 'datasource/6', $options);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $this->assertEquals(200, $response->getStatusCode());
        $result = DatalistEvents::findFirst("new_value = 'CreateEventBySuccessfulAPICall'");
        $this->assertEquals($result->new_value, 'CreateEventBySuccessfulAPICall');
    }

    public function test_AssertInvalidInputErrorCreatesNoEntry() {
        $num1 = DatalistEvents::find();
        try {
            $this->expectExceptionCode(401);        
            $options = ['json' => ['value' => null]];
            $response = $this->http->request('POST', 'datasource/6', $options);
        } finally {
            //assert same number of entries
            $num2 = DatalistEvents::find();
            $this->assertEquals(count($num1), count($num2));
        }       
    }

    public function test_AssertAlreadyExistsExceptionCreatesNoEntry() {
        //create First Entry
        $options = ['json' => ['value' => 'Doublet Test']];
        $response = $this->http->request('POST', 'datasource/6', $options);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $num1 = DatalistEvents::find();
        try {
            $this->expectExceptionCode(401);        
            $options = ['json' => ['value' => 'Doublet Test']];
            $response = $this->http->request('POST', 'datasource/6', $options);
        } finally {
            //assert same number of entries
            $num2 = DatalistEvents::find();
            $this->assertEquals(count($num1), count($num2));
        }       
    }

    public function test_AssertUpdateEventBySucessfulAPICall() {
        //Create new Event
        $options = ['json' => ['value' => 'UpdateEventBySuccessfulAPICall']];
        $response = $this->http->request('POST', 'datasource/6', $options);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        //Find crated Event
        $event = $this->http->request('GET', 'datasource/6?q=UpdateEventBySuccessfulAPICall');
        $eventData = json_decode((string) $event->getBody(), true);

        //Update created Event
        $options2 = ['json' => [
            'id' => $eventData[0]['id'],
            'value' => 'UpdatedEvent',
            'oldValue' => $eventData[0]['chapel']
            ]
        ];
        //get Updated Event
        $response = $this->http->request('PATCH', 'datasource/6', $options2);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $this->assertEquals(200, $response->getStatusCode()); 
        $updatedEvent = DatalistEvents::findFirst("old_value = 'UpdateEventBySuccessfulAPICall'");

        $this->assertEquals($updatedEvent->new_value, 'UpdatedEvent');
        $this->assertEquals($updatedEvent->old_value, 'UpdateEventBySuccessfulAPICall');
    }

    public function test_AssertUpdateInvalidInputCreatesNoEvent() {
        $options = ['json' => ['value' => 'InvalidInputUpdateTest']];
        $response = $this->http->request('POST', 'datasource/6', $options);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");   
        $this->assertEquals(200, $response->getStatusCode()); 

        try {
            $num1 = DatalistEvents::find();
            $this->expectExceptionCode(401);        
            $options2 = ['json' => [
                'id' => null,
                'value' => null,
                'oldValue' => null
                ]
            ];
            $response = $this->http->request('PATCH', 'datasource/6', $options2);
        } finally {
            //assert same number of entries
            $num2 = DatalistEvents::find();
            $this->assertEquals(count($num1), count($num2));
        }      
    }

}
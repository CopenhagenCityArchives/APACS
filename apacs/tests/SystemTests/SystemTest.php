<?php
use \Phalcon\Di;
class SystemTest extends \UnitTestCase
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
        $testDBManager->createApacsStructure();
        $testDBManager->createEntitiesAndFieldsForTask1();
        $testDBManager->createApacsMetadataForEntryPost10000Task1();
        $testDBManager->createBurialDataForEntryPost1000Task1();
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
        $testDBManager->cleanUpBurialStructure()
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

    public function test_GetTaskSchema_Task1_ReturnValidSchema(){

        $response = $this->http->request('GET', 'taskschema?task_id=1');
        $this->assertEquals(200, $response->getStatusCode());
        //var_dump((string) $response->getBody());
        $validTaskSchema = json_decode(file_get_contents(__DIR__ . '/validTaskSchema_task1.json'),true);
    
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $responseData = json_decode((string) $response->getBody(), true,JSON_NUMERIC_CHECK);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $this->assertEquals($validTaskSchema, $responseData);
    }

    public function test_GetPost_Task1_ReturnValidData(){
        try{
            $response = $this->http->request('GET', 'posts/10000');
        }
        catch(Exception $e){
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            var_dump($responseBodyAsString);
        }

        $this->assertEquals(200, $response->getStatusCode());

        $validPost = json_decode(file_get_contents(__DIR__ . '/validPost_task1.json'),true);

        // Note that only data is tested, NOT metadata
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertTrue(isset($responseData['data']));
        $this->assertEquals($validPost['data'], $responseData['data']);
    }

    public function test_GetEntry_Task1_ReturnValidEntry(){

        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validResponse = json_decode(file_get_contents(__DIR__ . '/validEntry_task1.json'),true);
        
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertFalse(is_null($responseData));
        $this->assertEquals($validResponse, $responseData);
    }

    public function test_ReportError_RemoveError_ErrorReportCountCorrect(){
        $response = $this->http->request('GET', 'posts/10000');

        $this->assertEquals(200, $response->getStatusCode());

        // Get number of errors before reporting
        $post = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $originalErrorReportCount = count($post['error_reports']);

        //Report error
        // Required fields are 'task_id', 'post_id', 'comment', 'entity','add_metadata'
        $request = [
            'task_id' => 1,
            'post_id' => 10000,
            'entity' => 'persons',
            'comment' => 'system_test',
            'add_metadata' => true
        ];

        // Send error report
        $response = $this->http->request('POST', 'errorreports', [
            'json' => $request
        ]);


        // Assert that the reporting was saved
        $this->assertEquals(200, $response->getStatusCode());        

        // Get post with updated error reports
        $updatedResponse = $this->http->request('GET', 'posts/10000');
        $updatedPost = json_decode((string) $updatedResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        // Assert that the number of errors has increased with 1
        $this->assertEquals(count($updatedPost['error_reports']), $originalErrorReportCount+1);
        // Remove last reported error report
        $removeResponse = $this->http->request('PATCH', 'errorreports', [
            'json' => [
                [
                    'id'=>$updatedPost['error_reports'][count($updatedPost['error_reports'])-1]['id'],
                    'deleted' => 1
                ]
            ]
        ]);

        // Assert that the reporting was saved
        $this->assertEquals(200, $removeResponse->getStatusCode());

        // Get number of errors after removing
        $removedErrorReportResponse = $this->http->request('GET', 'posts/10000');

        $postWithErrorRemoved = json_decode((string) $removedErrorReportResponse->getBody(), true);
        $removedErrorReportCount = count($postWithErrorRemoved['error_reports']);
        $this->assertEquals($originalErrorReportCount, $removedErrorReportCount);
    }

    public function test_SaveEntry_ReturnValidEntry(){
        $entryRequest = file_get_contents(__DIR__ . '/validEntry_task1.json');
        $request = json_decode($entryRequest,true);

        // Send entry data
        $response = $this->http->request('POST', 'entries', [
            'json' => $request
        ]);

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(10000, $responseData['post_id']);
    }

    public function test_DeletePostWithGivenId() {
        //assert if the post exists
        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');
        $this->assertEquals(200, $response->getStatusCode());
        //run the delete
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        //Assert the delete worked and that the post is gone
        $this->expectExceptionCode(500);
        //This will faill
        $newResponse = $this->http->request('GET', 'entries?task_id=1&post_id=10000');
    }

    public function test_DeletedPostBurialPersons() {
        //refresh DB
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        //Get DB output and assert correct data
        $query = $this->getDI()->get('db')->query('SELECT * FROM burial_persons WHERE id = 9718');
        $person = $query->fetch();
        $this->assertEquals($person['firstnames'], 'Bartoline');
        //Run Deletion
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        //Assert null on same query
        $newQuery = $this->getDI()->get('db')->query('SELECT firstnames FROM burial_persons WHERE id = 9718');
        $newPerson = $newQuery->fetch();
        $this->assertNull($newPerson['firstnames']);
    }

    public function test_DeletedPostBurialAddresses() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $query = $this->getDI()->get('db')->query('SELECT * FROM burial_addresses WHERE persons_id = 9718');
        $person = $query->fetch();
        $this->assertEquals($person['streets_id'], 7782);
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        $newQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_addresses WHERE persons_id = 9718');
        $newPerson = $newQuery->fetch();
        $this->assertNull($newPerson['streets_id']);
    }

    public function test_DeletedPostBurialBurials() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $query = $this->getDI()->get('db')->query('SELECT * FROM burial_burials WHERE persons_id = 9718');
        $person = $query->fetch();
        $this->assertEquals($person['cemetaries_id'], 11);
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        $newQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_addresses WHERE persons_id = 9718');
        $newPerson = $newQuery->fetch();
        $this->assertNull($newPerson['cemetaries_id']);
    }

    public function test_DeletedPostBurialDeathcauses() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $query = $this->getDI()->get('db')->query('SELECT * FROM burial_persons_deathcauses WHERE persons_id = 9718');
        $person = $query->fetch();
        $this->assertEquals($person['deathcauses_id'], 15);
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        $newQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_persons_deathcauses WHERE persons_id = 9718');
        $newPerson = $newQuery->fetch();
        $this->assertNull($newPerson['deathcauses_id']);
    }

    public function test_DeletedPostBurialPositions() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $query = $this->getDI()->get('db')->query('SELECT * FROM burial_persons_positions WHERE persons_id = 9718');
        $person = $query->fetch();
        $this->assertEquals($person['positions_id'], 1718);
        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());
        $newQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_persons_positions WHERE persons_id = 9718');
        $newPerson = $newQuery->fetch();
        $this->assertNull($newPerson['positions_id']);
    }

    public function test_DeletePostInvalidPostId() {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('{"message":"no entries found for post 999999999999"}');
        $response = $this->http->request('DELETE', 'posts/999999999999');
    }

    public function test_DeletePostInvalidInput() {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('<h1>Bad request!</h1>');
        $response = $this->http->request('DELETE', 'posts/asd');
    }

    public function test_DeletePostNoInput() {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('<h1>Bad request!</h1>');
        $response = $this->http->request('DELETE', 'posts/');
    }
}

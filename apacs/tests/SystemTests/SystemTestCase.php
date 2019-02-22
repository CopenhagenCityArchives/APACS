<?php
use \Phalcon\Di;


class SystemTestCase extends \UnitTestCase
{
    private $testDBManager;

    public static function setUpBeforeClass(){
        echo 'SETUP';
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

    public function test_GetTaskSchema_Task1_ReturnValidSchema(){

        $response = $this->http->request('GET', 'taskschema?task_id=1');

        $this->assertEquals(200, $response->getStatusCode());

        $validTaskSchema = json_decode(file_get_contents(__DIR__ . '/validTaskSchema_task1.json'),true);

        $this->assertEquals($validTaskSchema, json_decode((string) $response->getBody(), true));
    }

    public function test_GetPost_Task1_ReturnValidData(){

        $response = $this->http->request('GET', 'posts/10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validPost = json_decode(file_get_contents(__DIR__ . '/validPost_task1.json'),true);

        // Note that only data is tested, NOT metadata
        $this->assertEquals($validPost['data'], json_decode((string) $response->getBody(), true)['data']);
    }

    public function test_GetEntry_Task1_ReturnValidEntry(){

        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validResponse = json_decode(file_get_contents(__DIR__ . '/validEntry_task1.json'),true);

        $this->assertEquals($validResponse, json_decode((string) $response->getBody(), true));
    }

    public function test_ReportError_RemoveError_ErrorReportCountCorrect(){

        $response = $this->http->request('GET', 'posts/10000');

        $this->assertEquals(200, $response->getStatusCode());

        // Get number of errors before reporting
        $post = json_decode((string) $response->getBody(), true);
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
}
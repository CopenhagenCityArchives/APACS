<?php

class ErrorReportingTest extends \UnitTestCase
{
    private $http;
    private $errorReportId;
    private $testDBManager;

    public function setUp(Phalcon\DiInterface $di = NULL, ?Phalcon\Config $config = NULL)
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx2/']);

        // Create database entries for entities and fields        
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->createApacsStructure();
    }

    public function tearDown() {
        $this->http = null;

        // Clear database
        $this->testDBManager->cleanUpApacsStructure();
		parent::tearDown();
    }

    public function test_ReportError_RemoveError_ErrorReportCountCorrect(){
        
        $this->testDBManager->createEntitiesAndFieldsForTask1();
        $this->testDBManager->createApacsMetadataForEntryPost10000Task1();
        $this->testDBManager->createBurialDataForEntryPost1000Task1();

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
<?php
use \Phalcon\Di;
class SystemTest extends \IntegrationTestCase
{
    private $http;

    public function setUp($di = null) : void
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
    }

    public function tearDown() : void {
        $this->http = null;
        parent::tearDown();
    }

    public function test_GetTaskSchema_Task1_ReturnValidSchema(){

        $response = $this->http->request('GET', 'taskschema?task_id=1');
        $this->assertEquals(200, $response->getStatusCode());
        $validTaskSchema = json_decode(file_get_contents(__DIR__ . '/TestData/validTaskSchema_task1.json'),true);
    
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $responseData = json_decode((string) $response->getBody(), true,JSON_NUMERIC_CHECK);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $this->assertEquals($validTaskSchema, $responseData);
    }

    public function test_GetPost_Task1_ReturnValidData(){
        $response = $this->http->request('GET', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());

        $validPost = json_decode(file_get_contents(__DIR__ . '/TestData/validPost_task1.json'),true);

        // Note that only data is tested, NOT metadata
        $responseData = json_decode((string) $response->getBody(), true);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertTrue(isset($responseData['data']));
        $this->assertEquals($validPost['data'], $responseData['data']);
    }

    public function test_GetEntry_Task1_ReturnValidEntry(){

        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validResponse = json_decode(file_get_contents(__DIR__ . '/TestData/validEntry_task1.json'),true);
        
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
        $entryRequest = file_get_contents(__DIR__ . '/TestData/validEntry_task1.json');
        $request = json_decode($entryRequest,true);

        // Send entry data
        $response = $this->http->request('POST', 'entries', [
            'json' => $request
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

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

        $addressQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_addresses WHERE id = 8544');
        $address = $addressQuery->fetch();
        $this->assertEquals($address['streets_id'], 7782);

        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());

        $countAddressQuery = $this->getDI()->get('db')->query('SELECT COUNT(*) as cnt FROM burial_addresses WHERE id = 8544');
        $this->assertEquals(0, $countAddressQuery->fetch()['cnt']);
    }

    public function test_DeletedPostBurialBurials() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();

        $burialQuery = $this->getDI()->get('db')->query('SELECT * FROM burial_burials WHERE id = 9132');
        $burial = $burialQuery->fetch();
        $this->assertEquals(11, $burial['cemetaries_id']);

        $response = $this->http->request('DELETE', 'posts/10000');
        $this->assertEquals(200, $response->getStatusCode());

        $newQuery = $this->getDI()->get('db')->query('SELECT COUNT(*) as cnt FROM burial_addresses WHERE id = 9132');
        $newPerson = $newQuery->fetch();
        $this->assertArrayNotHasKey('cemetaries_id',$newPerson);
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

    public function test_CreatePost() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $response = $this->http->request('POST', 'posts', [
            'json' => [
                'x' => "0.05",
                'y' => "0.11",
                'height' => "0.38",
                'width' => "0.43",
                'page_id' => 145054
            ],
            'query' => [ 'task_id' => 1 ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = (string) $response->getBody();
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON, got ". $responseBody);
        $postId = $responseData['post_id'];  
        $this->assertIsInt($postId);

        $post = $this->testDBManager->query('SELECT * FROM `apacs_posts` WHERE `id` = ' . $postId . ' LIMIT 1')->fetch();
        $this->assertEquals(0.05, $post['x']);
        $this->assertEquals(0.11, $post['y']);
        $this->assertEquals(0.38, $post['height']);
        $this->assertEquals(0.43, $post['width']);
    }

    public function test_UpdatePost_NewUpdated_SameCreated() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $postBefore = $this->testDBManager->query('SELECT * FROM `apacs_posts` WHERE `id` = 10000 LIMIT 1')->fetch();
        $response = $this->http->request('PATCH', 'posts/10000', [
            'json' => [
                'x' => "0.5",
                'y' => "0.7",
                'height' => "0.2",
                'width' => "0.5",
                'page_id' => $postBefore['pages_id']
            ],
            'query' => [ 'task_id' => 1 ]
        ]);
        $postAfter = $this->testDBManager->query('SELECT * FROM `apacs_posts` WHERE `id` = 10000 LIMIT 1')->fetch();
        
        $this->assertNotNull($postBefore['updated'], "Updated was NULL before update.");
        $this->assertNotNull($postAfter['updated'], "Updated was NULL after update.");
        $this->assertGreaterThan(strToTime($postBefore['updated']), strToTime($postAfter['updated']));
        $this->assertEquals($postBefore['created'], $postAfter['created']);
        $this->assertEquals(0.5, $postAfter['x']);
        $this->assertEquals(0.7, $postAfter['y']);
        $this->assertEquals(0.2, $postAfter['height']);
        $this->assertEquals(0.5, $postAfter['width']);
    }

    public function test_UpdateEntry_NewUpdated_SameCreated_NewLastUpdateUsersId() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        $entryBefore = $this->testDBManager->query('SELECT * FROM `apacs_entries` WHERE `posts_id` = 10000 LIMIT 1')->fetch();
        $entryId = $entryBefore['id'];
        $concreteEntryId = $entryBefore['concrete_entries_id'];
        $concreteEntryBefore = $this->testDBManager->query('SELECT * FROM `burial_persons` WHERE `id` = ' . $concreteEntryId . ' LIMIT 1')->fetch();

        // perform a change
        $updateRequest = json_decode(file_get_contents(__DIR__ . '/TestData/validEntry_task1.json'), true);
        $updateRequest['persons']['firstnames'] = 'Cirkeline';
        $updateResponse = $this->http->request('PUT', 'entries/' . $entryId, [ 'json' => $updateRequest ]);
        $this->assertEquals(200, $updateResponse->getStatusCode());
        $updateResponseData = json_decode((string) $updateResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        $entryAfter = $this->testDBManager->query('SELECT * FROM `apacs_entries` WHERE `posts_id` = 10000 LIMIT 1')->fetch();
        $concreteEntryAfter = $this->testDBManager->query('SELECT * FROM `burial_persons` WHERE `id` = ' . $concreteEntryId . ' LIMIT 1')->fetch();
        $this->assertGreaterThan(strToTime($entryBefore['updated']), strToTime($entryAfter['updated']));
        $this->assertEquals("Bartoline", $concreteEntryBefore['firstnames']);
        $this->assertEquals("Cirkeline", $concreteEntryAfter['firstnames']);
        $this->assertEquals($entryBefore['created'], $entryAfter['created']);
        $this->assertEquals(NULL, $entryBefore['last_update_users_id']);
        $this->assertEquals(1, $entryAfter['last_update_users_id']);
    }

    public function test_UpdateErrorReport_NewUpdated_SameCreated() {
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->refreshEntryForPost1000();
        
        $errorReportData = [
            "add_metadata" => true,
            "task_id" => 1,
            "post_id" => 10000,
            "comment" => "This is a test error report.",
            "entity" => "persons.firstnames"
        ];
        $createResponse = $this->http->request('POST', '/errorreports', [ 'json' => $errorReportData ]);
        $this->assertEquals(200, $createResponse->getStatusCode());

        $reportBefore = $this->testDBManager->query('SELECT * FROM `apacs_errorreports` LIMIT 1')->fetch();
        $errorReportUpdate = [
            "toSuperUser" => 1
        ];
        $this->http->request('PATCH', 'errorreports/' . $reportBefore['id'], [ 'json' => $errorReportUpdate ]);
        $reportAfter = $this->testDBManager->query('SELECT * FROM `apacs_errorreports` LIMIT 1')->fetch();
        $errorReportUpdate["toSuperUser"] = 0;
        sleep(1);
        $this->http->request('PATCH', 'errorreports/' . $reportBefore['id'], [ 'json' => $errorReportUpdate ]);
        $reportLast = $this->testDBManager->query('SELECT * FROM `apacs_errorreports` LIMIT 1')->fetch();
        
        $this->assertNull($reportBefore['updated']);
        $this->assertNotNull($reportAfter['updated']);
        $this->assertNotNull($reportBefore['created']);
        $this->assertNotNull($reportAfter['created']);
        $this->assertNotNull($reportLast['created']);
        $this->assertEquals($reportBefore['created'], $reportAfter['created']);
        $this->assertEquals($reportAfter['created'], $reportLast['created']);
        $this->assertGreaterThan($reportAfter['updated'], $reportLast['updated']);

        $this->assertEquals(0, $reportBefore['toSuperUser']);
        $this->assertEquals(1, $reportAfter['toSuperUser']);
        $this->assertEquals(0, $reportLast['toSuperUser']);
    }
}

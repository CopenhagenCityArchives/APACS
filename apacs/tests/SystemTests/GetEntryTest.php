<?php

class GetEntryTest extends \UnitTestCase
{
    private $http;
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
    }

    public function test_GetEntry_Task1_ReturnValidEntry(){
        
        $this->testDBManager->createEntitiesAndFieldsForTask1();
        $this->testDBManager->createApacsMetadataForEntryPost10000Task1();
        $this->testDBManager->createBurialDataForEntryPost1000Task1();

        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validResponse = json_decode(file_get_contents(__DIR__ . '/validEntry_task1.json'),true);

        $this->assertEquals($validResponse, json_decode((string) $response->getBody(), true));
    }
}
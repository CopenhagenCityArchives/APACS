<?php

class GetPostTest extends \UnitTestCase
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

    public function test_GetPost_Task1_ReturnValidData(){

        $this->testDBManager->createEntitiesAndFieldsForTask1();
        $this->testDBManager->createApacsMetadataForEntryPost10000Task1();

        $response = $this->http->request('GET', 'posts/10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validPost = json_decode(file_get_contents(__DIR__ . '/validPost_task1.json'),true);

        $this->assertEquals($validPost, json_decode((string) $response->getBody(), true));
    }
}
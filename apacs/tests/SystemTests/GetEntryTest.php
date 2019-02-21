<?php

class GetEntryTest extends \SystemTestCase
{
    private $http;

    public function setUp(Phalcon\DiInterface $di = NULL, ?Phalcon\Config $config = NULL)
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx2/']);
    }

    public function tearDown() {
        $this->http = null;
    }

    public function test_GetEntry_Task1_ReturnValidEntry(){

        $response = $this->http->request('GET', 'entries?task_id=1&post_id=10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validResponse = json_decode(file_get_contents(__DIR__ . '/validEntry_task1.json'),true);

        $this->assertEquals($validResponse, json_decode((string) $response->getBody(), true));
    }
}
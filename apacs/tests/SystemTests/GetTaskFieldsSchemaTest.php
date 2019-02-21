<?php

class GetTaskFieldsSchemaTest extends \SystemTestCase
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

    public function test_GetTaskSchema_Task1_ReturnValidSchema(){

        $response = $this->http->request('GET', 'taskschema?task_id=1');

        $this->assertEquals(200, $response->getStatusCode());

        $validTaskSchema = json_decode(file_get_contents(__DIR__ . '/validTaskSchema_task1.json'),true);

        $this->assertEquals($validTaskSchema, json_decode((string) $response->getBody(), true));
    }
}
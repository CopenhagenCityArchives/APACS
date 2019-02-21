<?php

class GetPostTest extends \SystemTestCase
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

    public function test_GetPost_Task1_ReturnValidData(){

        $response = $this->http->request('GET', 'posts/10000');

        $this->assertEquals(200, $response->getStatusCode());

        $validPost = json_decode(file_get_contents(__DIR__ . '/validPost_task1.json'),true);

        // Note that only data is tested, NOT metadata
        $this->assertEquals($validPost['data'], json_decode((string) $response->getBody(), true)['data']);
    }
}
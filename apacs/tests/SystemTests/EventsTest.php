<?php
use \Phalcon\Di;
class EventsTest extends \IntegrationTestCase
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

    public function test_GetEventEntries() {
        $response = $this->http->request('GET', 'events');
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertNotNull($responseData);
        $this->assertEquals(797, $responseData[0]['users_id']);
        $this->assertEquals(2, $responseData[0]['count']);
        $this->assertEquals(798, $responseData[1]['users_id']);
        $this->assertEquals(1, $responseData[1]['count']);

    }

    public function test_GetEventEntriesWithinput_5WeeksOld() {
        $Unix5WeeksAgo = strtotime("-5 week");
        $response = $this->http->request('GET', 'events/create/'. $Unix5WeeksAgo);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertNotNull($responseData);
        $this->assertEquals('User_1', $responseData[0]['username']);
        $this->assertEquals('User_4', $responseData[1]['username']);
        $this->assertEquals(2, $responseData[0]['count']);
        $this->assertEquals(800, $responseData[1]['users_id']);
        $this->assertEquals(2, $responseData[1]['count']);
    }
}


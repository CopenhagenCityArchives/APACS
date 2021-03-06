<?php
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

use \Phalcon\Di;
class DeleteSolrDataTest extends \IntegrationTestCase {

    private $solr;
    private $http;

    public function setUp($di = null) : void
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
        $this->solr = new GuzzleHttp\Client(['base_uri' => 'http://solr:8983/solr/apacs_core/']);
        $this->solr->request('POST', 'update?commit=true', [ 'json' => [ 'delete' => [ 'query' => '*:*' ]]]);
    }

    public function tearDown() : void {
        $this->http = null;
        parent::tearDown();
	}
	
	public function test_DeleteSolrEntry_GivenValidId() {
        //assert no initial posts in solrdb
        $initResponse = $this->solr->request('GET', 'select?q=*:*&wt=json');
        $initData = json_decode((string) $initResponse->getBody(), true);
        $this->assertEquals(0, $initData['response']['numFound']);

        //setup a test solr document
		$entryRequest = file_get_contents(__DIR__ . '/TestData/validEntry_task1.json');
        $request = json_decode($entryRequest,true);

        $response = $this->http->request('POST', 'entries', [
            'json' => $request
        ]);

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertNotNull($responseData['solr_id']);

        $solrResponse = $this->solr->request('GET', 'select?q=id:' . $responseData['solr_id'] . '&wt=json');
        $solrData = json_decode((string) $solrResponse->getBody(), true);
        $this->assertEquals(1, $solrData['response']['numFound']);
    
        $deleteResponse = $this->http->request('DELETE', 'posts/' . $responseData['post_id']);
        $this->assertEquals(200, $deleteResponse->getStatusCode());
        $this->assertEquals("Post Deleted", $deleteResponse->getReasonPhrase());

        $checkerResponse = $this->solr->request('GET', 'select?q=id:' . $responseData['solr_id'] . '&wt=json');
        $checkerData = json_decode((string) $checkerResponse->getBody(), true);

        $this->assertEquals(0, $checkerData['response']['numFound']);
    }
}
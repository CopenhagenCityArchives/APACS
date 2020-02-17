<?php

use \Phalcon\Di;
class DeleteSolrDataTest extends \SystemTest {

    private $solr;

	public static function setUpBeforeClass(){
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
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
        //TODO Hardcoded db credentials for tests
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
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
        $this->solr = new GuzzleHttp\Client(['base_uri' => 'http://solr:8983/solr/apacs_core/select']);
    }

    public function tearDown() {
        $this->http = null;
        parent::tearDown();
	}
	

	public function test_DeleteSolrEntry_GivenValidId() {
        //setup a test solr document
		$entryRequest = file_get_contents(__DIR__ . '/validEntry_task1.json');
        $request = json_decode($entryRequest,true);

        $response = $this->http->request('POST', 'entries', [
            'json' => $request
        ]);

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertNotNull($responseData['solr_id']);
        $solrResponse = $this->solr->request('GET', '?q=id:' . $responseData['solr_id'] . '&wt=json');
        $solrData = json_decode((string) $solrResponse->getBody(), true);
        //var_dump($solrData);
        $this->assertEquals(1, $solrData['response']['numFound']);

        $deleteResponse = $this->http->request('DELETE', 'posts/' . $responseData['post_id']);
        $this->assertEquals(200, $deleteResponse->getStatusCode());
        
        $checkerResponse = $this->solr->request('GET', '?q=id:' . $responseData['solr_id'] . '&wt=json');
        $checkerData = json_decode((string) $checkerResponse->getBody(), true);

        var_dump($checkerData);

        $this->assertEquals(0, $checkerData['response']['numFound']);


    }
}
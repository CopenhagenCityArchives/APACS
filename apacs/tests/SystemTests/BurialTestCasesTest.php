<?php
use \Phalcon\Di;

class BurialTestCasesTest extends \UnitTestCase
{
    private $http;
    private $solr;

    public static function setUpBeforeClass() : void {
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
		$di->setShared('config', function () {
            return [
                "host" => "mysql",
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
       
    public static function tearDownAfterClass() : void {
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
		$di->setShared('config', function () {
            return [
                "host" => "mysql",
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

    public function setUp($di = null) : void
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
        $this->solr = new GuzzleHttp\Client(['base_uri' => 'http://solr:8983/solr/apacs_core/']);
    }

    public function tearDown() : void {
        $this->http = null;
        $this->solr = null;
        parent::tearDown();
    }

    public function test_SavePost_1a() {
        /* Get the page data with the next post information */
        $pagesResponse = $this->http->request('GET', 'pages', [
            'query' => [
                'page_id' => 145054,
                'task_id' => 1
            ]
        ]);
        $this->assertEquals(200, $pagesResponse->getStatusCode());
        
        // Get response data
        $pagesResponseData = json_decode((string)$pagesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('next_post', $pagesResponseData);

        /* Create a new post */
        $newPostData = $pagesResponseData['next_post'];
        $newPostData['page_id'] = 145054;

        $postResponse = $this->http->request('POST', 'posts', [
            'json' => $newPostData,
            'query' => [ 'task_id' => 1 ]
        ]);
        $this->assertEquals(200, $postResponse->getStatusCode());
        $postResponseData = json_decode((string)$postResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('post_id', $postResponseData);

        $postId = $postResponseData['post_id'];
        $this->assertIsNumeric($postId);
        $this->assertGreaterThan(0, $postId);

        /* Create a new entry for the post */
        $postEntriesResponse = $this->http->request('POST', 'entries', [
            'json' => [
                "persons" => [
                    "firstnames" => "Jens",
                    "lastname" => "Jensen",
                    "addresses" => [
                        "streetAndHood" => "Falkoner Allé"
                    ],
                    "positions" => [
                        0 => [
                            "position" => "Skibsfører",
                            "relationtype" => "Eget erhverv"
                        ]
                    ],
                    "deathcauses" => [
                        0 => [
                            "deathcause" => "Ascites"
                        ]
                    ],
                    "burials" => [
                        "number" => 345
                    ]
                ],
                "task_id" => 1,
                "post_id" => $postId
            ]
        ]);
        $this->assertEquals(200, $postEntriesResponse->getStatusCode());

        // Get response data and assert it is valid
        $postEntriesResponseData = json_decode((string)$postEntriesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");

        // Assert that response contains a created post id
        $this->assertArrayHasKey('post_id', $postEntriesResponseData);
        $this->assertArrayHasKey('concrete_entry_id', $postEntriesResponseData);
        $this->assertArrayHasKey('entry_id', $postEntriesResponseData);
        $this->assertArrayHasKey('solr_id', $postEntriesResponseData);
        
        $entryPostId = $postEntriesResponseData['post_id'];
        $concreteEntryId = $postEntriesResponseData['concrete_entry_id'];
        $entryId = $postEntriesResponseData['entry_id'];
        $solrId = $postEntriesResponseData['solr_id'];

        $this->assertIsNumeric($entryPostId);
        $this->assertEquals($postId, $entryPostId);

        $this->assertIsNumeric($concreteEntryId);
        $this->assertGreaterThan(0, $concreteEntryId);

        $this->assertIsNumeric($entryId);
        $this->assertGreaterThan(0, $entryId);

        $this->assertIsString($solrId);

        /* Get the entry back from APACS and verify that is saved the information */
        $getEntriesResponse = $this->http->request('GET', 'entries/' . $entryId);
        $this->assertEquals(200, $getEntriesResponse->getStatusCode());
        $getEntriesResponseData = json_decode((string)$getEntriesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertEquals('Jens', $getEntriesResponseData['persons']['firstnames']);
        $this->assertEquals('Jensen', $getEntriesResponseData['persons']['lastname']);
        $this->assertEquals('Falkoner Allé', $getEntriesResponseData['persons']['addresses']['streetAndHood']);
        $this->assertEquals('Skibsfører', $getEntriesResponseData['persons']['positions'][0]['position']);
        $this->assertEquals('Eget erhverv', $getEntriesResponseData['persons']['positions'][0]['relationtype']);
        $this->assertEquals('Ascites', $getEntriesResponseData['persons']['deathcauses'][0]['deathcause']);
        $this->assertEquals(345, $getEntriesResponseData['persons']['burials']['number']);

        /* Query SOLR with the id, and assert indexing worked */
        $solrResponse = $this->solr->request('GET', 'select', [
            'query' => [
                'q' => 'id:"' . $solrId . '"',
                'wt' => 'json'
            ]
        ]);
        $this->assertEquals(200, $solrResponse->getStatusCode());
        $solrResponseData = json_decode((string)$solrResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('response', $solrResponseData);
        $this->assertEquals(1, $solrResponseData['response']['numFound']);
        $this->assertCount(1, $solrResponseData['response']['docs']);
        
        $solrDocument = $solrResponseData['response']['docs'][0];
        $this->assertArrayHasKey('firstnames', $solrDocument);
        $this->assertEquals("Jens", $solrDocument['firstnames']);
        
        $this->assertArrayHasKey('lastname', $solrDocument);
        $this->assertEquals("Jensen", $solrDocument['lastname']);

        $this->assertArrayHasKey('streets', $solrDocument);
        $this->assertEquals([ 0 => "Falkoner Allé" ], $solrDocument['streets']);

        $this->assertArrayHasKey('deathcauses', $solrDocument);
        $this->assertEquals([ 0 => 'Ascites' ], $solrDocument['deathcauses']);

        $this->assertArrayHasKey('record_number', $solrDocument);
        $this->assertEquals(345, $solrDocument['record_number']);
    }
}
?>
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
        $testDBManager->createTask3Configuration();
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

    public function test_SaveLoadLookupEditSearch_1a_3a_4() {
        $this->solr->request('GET', 'update?stream.body=<delete><query>*:*</query></delete>&commit=true');

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

        /* Edit the post */
        $getEntriesResponseData['persons']['positions'][0]['position'] = 'Skibskok';
        $getEntriesResponseData['persons']['positions'][0]['relationtype'] = 'Mors erhverv';
        $putEntriesEditResponse = $this->http->request('PUT', 'entries/' . $entryId, [
            'json' => $getEntriesResponseData
        ]);
        $this->assertEquals(200, $putEntriesEditResponse->getStatusCode());
        $putEntriesEditResponseData = json_decode((string)$putEntriesEditResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('solr_id', $putEntriesEditResponseData);
        $this->assertNotNull($putEntriesEditResponseData['solr_id']);

        /* Get the entry back from APACS and verify that is saved the information */
        $getEditedEntriesResponse = $this->http->request('GET', 'entries/' . $entryId);
        $this->assertEquals(200, $getEditedEntriesResponse->getStatusCode());
        $getEditedEntriesResponseData = json_decode((string)$getEditedEntriesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertEquals('Skibskok', $getEditedEntriesResponseData['persons']['positions'][0]['position']);
        $this->assertEquals('Mors erhverv', $getEditedEntriesResponseData['persons']['positions'][0]['relationtype']);

        /* Search in SOLR, and check that it is updated */
        $solrSearchResponse = $this->solr->request('GET', 'select', [
            'query' => [
                'q' => 'freetext_store:(*jens* AND *jensen*)',
                'wt' => 'json'
            ]
        ]);

        $this->assertEquals(200, $solrSearchResponse->getStatusCode());
        $solrSearchResponseData = json_decode((string)$solrSearchResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('response', $solrSearchResponseData);
        $this->assertEquals(1, $solrSearchResponseData['response']['numFound']);
        $this->assertCount(1, $solrSearchResponseData['response']['docs']);
        
        $solrSearchDocument = $solrSearchResponseData['response']['docs'][0];
        $this->assertArrayHasKey('positions', $solrSearchDocument);
        $this->assertContains("Skibskok", $solrSearchDocument['positions']);
    }

    public function test_SaveLoadLookupEditLoad_1b_3b() {
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
                    "firstnames" => "Anders",
                    "lastname" => "Andersen",
                    "addresses" => [
                        "streetAndHood" => "Vesterbrogade"
                    ],
                    "positions" => [
                        0 => [
                            "position" => "Fabrikant",
                            "relationtype" => "Eget erhverv"
                        ],
                        1 => [
                            "position" => "Skibskok",
                            "relationtype" => "Fars erhverv"
                        ]
                    ],
                    "deathcauses" => [
                        0 => [
                            "deathcause" => "Slag"
                        ],
                        1 => [
                            "deathcause" => "Ascites"
                        ]
                    ],
                    "burials" => [
                        "number" => 2045
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
        $this->assertEquals('Anders', $getEntriesResponseData['persons']['firstnames']);
        $this->assertEquals('Andersen', $getEntriesResponseData['persons']['lastname']);
        $this->assertEquals('Vesterbrogade', $getEntriesResponseData['persons']['addresses']['streetAndHood']);
        $this->assertEquals('Fabrikant', $getEntriesResponseData['persons']['positions'][0]['position']);
        $this->assertEquals('Eget erhverv', $getEntriesResponseData['persons']['positions'][0]['relationtype']);
        $this->assertEquals('Skibskok', $getEntriesResponseData['persons']['positions'][1]['position']);
        $this->assertEquals('Fars erhverv', $getEntriesResponseData['persons']['positions'][1]['relationtype']);
        $this->assertEquals('Slag', $getEntriesResponseData['persons']['deathcauses'][0]['deathcause']);
        $this->assertEquals('Ascites', $getEntriesResponseData['persons']['deathcauses'][1]['deathcause']);
        $this->assertEquals(2045, $getEntriesResponseData['persons']['burials']['number']);

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
        $this->assertEquals("Anders", $solrDocument['firstnames']);
        
        $this->assertArrayHasKey('lastname', $solrDocument);
        $this->assertEquals("Andersen", $solrDocument['lastname']);

        $this->assertArrayHasKey('streets', $solrDocument);
        $this->assertEquals([ 0 => "Vesterbrogade" ], $solrDocument['streets']);

        $this->assertArrayHasKey('deathcauses', $solrDocument);
        $this->assertEquals([ 0 => 'Slag', 1 => 'Ascites' ], $solrDocument['deathcauses']);

        $this->assertArrayHasKey('record_number', $solrDocument);
        $this->assertEquals(2045, $solrDocument['record_number']);

        /* Edit the post */
        $getEntriesResponseData['persons']['comment'] = 'Gosmer Kirke tjente som gravkirke for herskaberne på herregårdene Rathlousdal og Gersdorffslund. På tårnets nordside lå der indtil 1866 et gravkapel, som anvendtes af slægten Holstein-Rathlou. Kisterne her blev dog 1857 overført til et nyopført gravkapel i lystskoven ved Rathlousdal. // Se KB: https://www.sa.dk/ao-soegesider/billedviser?bsid=167458#167458,28115873, har også navnet Emil';
        $getEntriesResponseData['persons']['deathcauses'] = [
            0 => [ 'deathcause' => 'Furunkler' ]
        ];

        // simulate burial deleted by removing all fields
        $getEntriesResponseData['persons']['burials'] = [ 'id' => $getEntriesResponseData['persons']['burials']['id'] ];

        $putEntriesEditResponse = $this->http->request('PUT', 'entries/' . $entryId, [
            'json' => $getEntriesResponseData
        ]);
        $this->assertEquals(200, $putEntriesEditResponse->getStatusCode());

        $putEntriesEditResponseData = json_decode((string)$putEntriesEditResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('solr_id', $putEntriesEditResponseData);
        $this->assertNotNull($putEntriesEditResponseData['solr_id']);

        /* Get the entry back from APACS and verify that is saved the information */
        $getEditedEntriesResponse = $this->http->request('GET', 'entries/' . $entryId);
        $this->assertEquals(200, $getEditedEntriesResponse->getStatusCode());
        $getEditedEntriesResponseData = json_decode((string)$getEditedEntriesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $expectedComment = 'Gosmer Kirke tjente som gravkirke for herskaberne på herregårdene Rathlousdal og Gersdorffslund. På tårnets nordside lå der indtil 1866 et gravkapel, som anvendtes af slægten Holstein-Rathlou. Kisterne her blev dog 1857 overført til et nyopført gravkapel i lystskoven ved Rathlousdal. // Se KB: https://www.sa.dk/ao-soegesider/billedviser?bsid=167458#167458,28115873, har også navnet Emil';;
        $this->assertEquals($expectedComment, $getEditedEntriesResponseData['persons']['comment']);
        $this->assertCount(1, $getEditedEntriesResponseData['persons']['deathcauses']);
        $this->assertEquals('Furunkler', $getEditedEntriesResponseData['persons']['deathcauses'][0]['deathcause']);
        $this->assertNotContains('burials', $getEditedEntriesResponseData['persons']);
    }

    public function test_SaveLoadSearchEditErrorReport_1c_2_3c_5() {
        /* Get the page data with the next post information */
        $pagesResponse = $this->http->request('GET', 'pages', [
            'query' => [
                'page_id' => 114579,
                'task_id' => 3
            ]
        ]);
        $this->assertEquals(200, $pagesResponse->getStatusCode());
        
        // Get response data
        $pagesResponseData = json_decode((string)$pagesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('next_post', $pagesResponseData);

        /* Create a new post */
        $newPostData = $pagesResponseData['next_post'];
        $newPostData['page_id'] = 114579;

        $postResponse = $this->http->request('POST', 'posts', [
            'json' => $newPostData,
            'query' => [ 'task_id' => 3 ]
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
                    "firstnames" => "Johanne",
                    "lastname" => "Sørensen",
                    "addresses" => [
                        "streetAndHood" => "Købmagergade"
                    ],
                    "dateOfBirth" => "23-05-1923",
                    "dateOfDeath" => "12-07-1934",
                    "positions" => [
                        0 => [
                            "position" => "Barnepige",
                            "relationtype" => "Eget erhverv"
                        ]
                    ],
                    "deathplace" => "Gasværkshavnen",
                    "burials" => [
                        "number" => 546
                    ],
                    "comment" => "Gosmer Kirke tjente som gravkirke for herskaberne på herregårdene Rathlousdal og Gersdorffslund. På tårnets nordside lå der indtil 1866 et gravkapel, som anvendtes af slægten Holstein-Rathlou. Kisterne her blev dog 1857 overført til et nyopført gravkapel i lystskoven ved Rathlousdal. // Se KB: https://www.sa.dk/ao-soegesider/billedviser?bsid=167458#167458,28115873, har også navnet Emil"
                ],
                "task_id" => 3,
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
        $this->assertEquals('Johanne', $getEntriesResponseData['persons']['firstnames']);
        $this->assertEquals('Sørensen', $getEntriesResponseData['persons']['lastname']);
        $this->assertEquals('Købmagergade', $getEntriesResponseData['persons']['addresses']['streetAndHood']);
        $this->assertEquals('Barnepige', $getEntriesResponseData['persons']['positions'][0]['position']);
        $this->assertEquals('Eget erhverv', $getEntriesResponseData['persons']['positions'][0]['relationtype']);
        $this->assertEquals('Gasværkshavnen', $getEntriesResponseData['persons']['deathplace']);
        $this->assertEquals('23-05-1923', $getEntriesResponseData['persons']['dateOfBirth']);
        $this->assertEquals('12-07-1934', $getEntriesResponseData['persons']['dateOfDeath']);
        $this->assertEquals(546, $getEntriesResponseData['persons']['burials']['number']);
        $expectedComment = "Gosmer Kirke tjente som gravkirke for herskaberne på herregårdene Rathlousdal og Gersdorffslund. På tårnets nordside lå der indtil 1866 et gravkapel, som anvendtes af slægten Holstein-Rathlou. Kisterne her blev dog 1857 overført til et nyopført gravkapel i lystskoven ved Rathlousdal. // Se KB: https://www.sa.dk/ao-soegesider/billedviser?bsid=167458#167458,28115873, har også navnet Emil";
        $this->assertEquals($expectedComment, $getEntriesResponseData['persons']['comment']);

        /* Search in SOLR, and check that it is indexed */
        $solrSearchResponse = $this->solr->request('GET', 'select', [
            'query' => [
                'q' => 'freetext_store:(*johanne* AND *barnepige*)',
                'wt' => 'json'
            ]
        ]);

        $this->assertEquals(200, $solrSearchResponse->getStatusCode());
        $solrSearchResponseData = json_decode((string)$solrSearchResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('response', $solrSearchResponseData);
        $this->assertEquals(1, $solrSearchResponseData['response']['numFound']);
        $this->assertCount(1, $solrSearchResponseData['response']['docs']);

        $solrSearchDocument = $solrSearchResponseData['response']['docs'][0];
        $this->assertArrayHasKey('firstnames', $solrSearchDocument);
        $this->assertEquals("Johanne", $solrSearchDocument['firstnames']);
        
        $this->assertArrayHasKey('lastname', $solrSearchDocument);
        $this->assertEquals("Sørensen", $solrSearchDocument['lastname']);

        $this->assertArrayHasKey('streets', $solrSearchDocument);
        $this->assertEquals([ 0 => "Købmagergade" ], $solrSearchDocument['streets']);

        $this->assertArrayHasKey('record_number', $solrSearchDocument);
        $this->assertEquals(546, $solrSearchDocument['record_number']);

        /* Edit the post */
        $getEntriesResponseData['persons']['firstnames'] = 'Test';

        // simulate burial deleted completely
        $getEntriesResponseData['persons']['burials'] = null;

        $putEntriesEditResponse = $this->http->request('PUT', 'entries/' . $entryId, [
            'json' => $getEntriesResponseData
        ]);
        $this->assertEquals(200, $putEntriesEditResponse->getStatusCode());

        $putEntriesEditResponseData = json_decode((string)$putEntriesEditResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertArrayHasKey('solr_id', $putEntriesEditResponseData);
        $this->assertNotNull($putEntriesEditResponseData['solr_id']);

        /* Get the entry back from APACS and verify that is saved the information */
        $getEditedEntriesResponse = $this->http->request('GET', 'entries/' . $entryId);
        $this->assertEquals(200, $getEditedEntriesResponse->getStatusCode());
        $getEditedEntriesResponseData = json_decode((string)$getEditedEntriesResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertEquals('Test', $getEditedEntriesResponseData['persons']['firstnames']);
        $this->assertNotContains('burials', $getEditedEntriesResponseData['persons']);

        /* Create an error report */
        $reportData = [
            "task_id" => 3,
            "post_id" => $postId,

            "entity" => "persons",
            "field" => "firstnames",
            "comment" => "Det er en fejl",
            "add_metadata" => true,

            "collection_id" => 1
        ];

        $postErrorReportsResponse = $this->http->request('POST', 'errorreports', ['json' => $reportData]);
        $this->assertEquals(200, $postErrorReportsResponse->getStatusCode());

        /* Check that error report was saved. */
        $getErrorReportsResponse = $this->http->request('GET', 'errorreports', ['query' => [
            'task_id' => 3,
            'post_id' => $postId
        ]]);
        $this->assertEquals(200, $getErrorReportsResponse->getStatusCode());

        $getErrorReportsResponseData = json_decode((string)$getErrorReportsResponse->getBody(), true);
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE, "should be parsable JSON");
        $this->assertCount(1, $getErrorReportsResponseData);
        $this->assertEquals('persons', $getErrorReportsResponseData[0]['entity_name']);
        $this->assertEquals('firstnames', $getErrorReportsResponseData[0]['field_name']);
        $this->assertEquals('Det er en fejl', $getErrorReportsResponseData[0]['comment']);
    }
}
?>
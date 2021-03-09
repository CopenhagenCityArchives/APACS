<?php

use \Phalcon\Di;
class UnitsTest extends \IntegrationTest {

    private $http;

    public function setUp($di = null) : void
    {
        parent::setUp();
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://nginx/']);
        $this->di->get('db')->query("INSERT INTO `apacs_collections` (`id`,`name`) VALUES (2,'collection 2 name')");
    }

    public function tearDown() : void {
        $this->http = null;
        $this->di->get('db')->query("DELETE FROM apacs_units where id not in (1,2,123)");
        $this->di->get('db')->query("DELETE FROM apacs_collections where id not in (1)");
        parent::tearDown();
	}

    public function test_CreateUnitsOnNonExistingCollection_Return403()
    {
        $this->expectExceptionCode(403);

        // Incomplete unit object for test
        $newUnitRequest = [
            [
                "unit" => [
                    "col_id" => 10000
                ]
            ]
        ];

        $this->http->request('POST', 'units', [
            'json' => $newUnitRequest
        ]);
    }

    public function test_UpdateUnitsWithTaskUnits_Return403()
    {
        $this->expectExceptionCode(500);
        
        $newUnitRequest = [
            [
                "unit" => [
                    "col_id" => 1,
                    "a_id" => "1232",
                    "name" => "Magistratens Resolutionsprotokoller",
                    "info" => "",
                    "link" => "https://kbharkiv.dk/brug-samlingerne/kilder-paa-nettet/magistratens-resolutionsprotoller",
                    "level_count" => "2",
                    "level1_name" => "Periode",
                    "level1_info" => "Vælg periode",
                    "level2_name" => "Protokol",
                    "level2_info" => "Vælg protokol",
                    "level3_name" => "",
                    "level3_info" => "",
                    "link_text" => "Se protokollen i Kildeviseren",
                    "link_mouse_over" => "Link til digital udgave",
                    "date_create" => "2019-08-06 11:29:20",
                    "date_update" => "2021-03-09 17:38:25",
                    "status" => "0",
                    "date_public" => null
                ]
            ]
        ];

        $this->http->request('POST', '/units', [
            'json' => $newUnitRequest
        ]);
    }

    public function test_CreateNewUnitOnExistingCollection()
    {
        $unitsBefore = $this->sendGetRequestGetJSON('/units');
        $this->assertEquals(count($unitsBefore), 3);

        $newUnitRequest = [
            [
                "unit" => [
                        "col_unit_id" => "123871",
                        "col_id" => "2",
                        "a_id" => "1",
                        "level1_value" => "1713-1801",
                        "level1_order" => "1",
                        "level2_value" => "10.maj 1717 - 6.nov. 1719",
                        "level2_order" => "1",
                        "level3_value" => "",
                        "level3_order" => "1",
                        "date_update_unit" => "2021-03-09 17:38:21",
                        "link_id" => "140412",
                        "is_public" => "1",
                        "is_blocked" => "1",
                        "block_id" => "114016"
                ]
            ]
        ];

        $this->http->request('POST', '/units', [
            'json' => $newUnitRequest
        ]);

        $unitsAfter = $this->sendGetRequestGetJSON('/units');
        $this->assertEquals(count($unitsAfter), 4, 'should create a new unit');
    }

    public function test_CreateUnitWithExistingCollectionIdAndStarbasId_UpdateUnit()
    {
        // Unit for collection 2, with id 300
        $existingUnitRequest = [
            [
                "unit" => [
                        "col_unit_id" => "300",
                        "col_id" => "2",
                        "a_id" => "1",
                        "level1_value" => "1713-1801",
                        "level1_order" => "1",
                        "level2_value" => "10.maj 1717 - 6.nov. 1719",
                        "level2_order" => "1",
                        "level3_value" => "",
                        "level3_order" => "1",
                        "date_update_unit" => "2021-03-09 17:38:21",
                        "link_id" => "140412",
                        "is_public" => "1",
                        "is_blocked" => "1",
                        "block_id" => "114016"
                ]
            ]
        ];

        // Create unit
        $this->http->request('POST', '/units', [
            'json' => $existingUnitRequest
        ]);

        $unitsBeforeUpdate = $this->sendGetRequestGetJSON('/units?collection_id=2');
        $this->assertEquals(count($unitsBeforeUpdate), 1, 'should create a new unit');

        $this->http->request('POST', '/units', [
            'json' => $existingUnitRequest
        ]);
            
        $unitsAfterUpdate = $this->sendGetRequestGetJSON('/units?collection_id=2');
        $this->assertEquals(count($unitsAfterUpdate), 1, 'should update existing unit');
    }

    private function sendGetRequestGetJSON($url)
    {
        $response = $this->http->request('GET', $url);
        return json_decode((string) $response->getBody(), true);
    }
}
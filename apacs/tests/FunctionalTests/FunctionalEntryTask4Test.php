<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Di;

class FunctionalEntryTask4Test extends \UnitTestCase {
    private $testDBManager;
    private $concreteEntry;

	public function setUp($di = null) : void {

        // We need the modelsManager and therefore FactoryDefault DI to use Phalcons models in the tests
        $di = new FactoryDefault();    
        parent::setUp($di);

        // Create database entries for entities and fields        
        $this->testDBManager = new Mocks\TestDatabaseManager($this->getDI());
        $this->testDBManager->createApacsStructure();
        
        // Setup ConcreteEntries using its default CRUD
        $this->concreteEntry = new ConcreteEntries($this->getDI(), null);
        
        // Use transaction so we can roll back
        $this->concreteEntry->startTransaction();
	}

	public function tearDown() : void {
        
        // Dont save data
        $this->concreteEntry->rollbackTransaction();

        // Clear database
        $this->testDBManager->cleanUpApacsStructure();
        // TODO: How should the database be restored for task4?
		parent::tearDown();
    }
    
    public function test_SaveNewEntry_SavesData() {
        $saveData = [
            "cases" => [
                "complaint" => [
                    "witnesses" => true,
                    "attachments_mentioned" => true,
                    "verb" => "Verbum",
                    "subject" => "Emne",
                    "subject_category" => "Emnekategori",
                    "purpose" => "Formål"
                ],
                "attachments" => [
                    [
                        "attachment_type" => "Kopibog",
                        "reference" => "Henvisning"
                    ]
                ],
                "references" => [[]],
                "persons" => [
                    ["person_occupations" => [[]]]
                ],
                "places" => [
                    ["place_types" => [[]]]
                ],
                "resolutions" => [
                    [
                        "case_reopened" => false,
                        "party_reaction" => "Reaktion",
                        "magistrate_action" => "Jaja",
                        "resolution_type" => "Nej",
                        "date" => "17-01-1777"
                    ]
                ],
                "comments" => [[]],
                "case_type" => "Sagstype1",
                "date" => "10-10-1755",
                "start_page" => "1",
                "extent" => "1 side",
                "transcriptions" => [
                    [
                        "transcription_type" => "Vigtig transkription",
                        "transcription" => "Transkriberet tekst"
                    ]
                ]
            ],
            "page_id" => 55511,
            "task_id" => 4,
            "post_id" => 20100
        ];

        // Load task configuration
        $taskconfigLoader = new TaskConfigurationLoader(__DIR__);
		$taskConf = $taskconfigLoader->getConfig(4);
        $entity = new ConfigurationEntity($taskConf['entity']);
        
        // Save the entry
        $concreteEntryID = $this->concreteEntry->SaveEntriesForTask($entity, $saveData);
        $this->assertGreaterThan(0, $concreteEntryID);
        
        // Load the entry
        $loadedEntry = $this->concreteEntry->LoadEntry($entity, $concreteEntryID);

        $this->assertArrayHasKey('cases', $loadedEntry);
        $case = $loadedEntry['cases'];

        $this->assertEmpty($case['persons']);
        $this->assertEmpty($case['comments']);
        $this->assertEmpty($case['places']);
        $this->assertEmpty($case['references']);

        // Check case fields
        $this->assertEquals("Sagstype1", $case["case_type"]);
        $this->assertEquals("10-10-1755", $case["date"]);
        $this->assertEquals("1", $case["start_page"]);
        $this->assertEquals("1 side", $case["extent"]);

        // Check subentities
        unset($case['complaint']['id']);
        $this->assertEquals([
            "witnesses" => true,
            "attachments_mentioned" => true,
            "verb" => "Verbum",
            "subject" => "Emne",
            "subject_category" => "Emnekategori",
            "purpose" => "Formål"
        ], $case['complaint']);

        unset($case['attachments'][0]['id']);
        unset($case['attachments'][0]['cases_id']);
        $this->assertEquals([
            [
                "attachment_type" => "Kopibog",
                "reference" => "Henvisning",
                "starbas_id" => null
            ]
        ], $case['attachments']);

        unset($case['resolutions'][0]['id']);
        unset($case['resolutions'][0]['cases_id']);
        $this->assertEquals([
            [
                "case_reopened" => false,
                "party_reaction" => "Reaktion",
                "magistrate_action" => "Jaja",
                "resolution_type" => "Nej",
                "date" => "17-01-1777"
            ]
        ], $case['resolutions']);

        unset($case['transcriptions'][0]['id']);
        unset($case['transcriptions'][0]['cases_id']);
        $this->assertEquals([
            [
                "transcription_type" => "Vigtig transkription",
                "transcription" => "Transkriberet tekst"
            ]
        ], $case['transcriptions']);
    }
}
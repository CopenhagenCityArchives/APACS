<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Di;

class FunctionalEntryTask1Test extends \UnitTestCase {
    private $testDBManager;
    private $concreteEntry;

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {

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

	public function tearDown() {
        
        // Dont save data
        $this->concreteEntry->rollbackTransaction();

        // Clear database
        $this->testDBManager->cleanUpApacsStructure();
		parent::tearDown();
    }
    
    public function test_SaveNewEntry_SavesData(){

        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();
        
//        $this->testDBManager->createBurialDataForEntryPost1000Task1();

        // Get entities from database
		$entities = Entities::find(['conditions' => 'task_id = 1']);

        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/entry_save.json'), true);

        // Setup and save  
        $this->assertTrue($this->concreteEntry->SaveEntriesForTask($entities, $inputData)>0);
    }

    public function test_LoadEntry_ReturnEntry(){
        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();

        // Get entities from database
		$entities = Entities::find(['conditions' => 'task_id = 1']);

        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/entry_save.json'), true);

        // Save concrete entry
        $savedId = $this->concreteEntry->SaveEntriesForTask($entities, $inputData);

        // Load  
        $loadedEntry = $this->concreteEntry->LoadEntry($entities, $savedId, true); 
        
        // Is the person saved?
        $this->assertEquals($inputData['persons']['firstnames'], $loadedEntry['persons']['firstnames']);
        
        // Is the values saved for child entities?
        $this->assertEquals($inputData['persons']['deathcauses'][0]['deathcause'],$loadedEntry['persons']['deathcauses'][0]['deathcause']);
        
        // Is the person id set?
        $this->assertTrue(isset($loadedEntry['persons']['id']));
        
        // Is the person id set for child entities?
        $this->assertEquals($inputData['persons']['id'], $loadedEntry['deathcauses'][0]['persons_id']);
    }

    public function test_SaveExistingEntry_UpdatesData(){

        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();

        // Get entities from database
		$entities = Entities::find(['conditions' => 'task_id = 1']);

        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/entry_save.json'), true);

        // Save  
        $savedId = $this->concreteEntry->SaveEntriesForTask($entities, $inputData);

        // Load and modify entry
        $loadedEntry = $this->concreteEntry->LoadEntry($entities, $savedId, true); 
        $modifiedEntry = $loadedEntry;
        $modifiedEntry['persons']['deathcauses'][0]['deathcause'] = 'Absces (Abscessus)';

        //  Update entry
        $updatedId = $this->concreteEntry->SaveEntriesForTask($entities, $modifiedEntry);

        //  Should keep id
        $this->assertEquals($savedId, $updatedId);
        
        //  Load updated entry
        $updatedEntry = $this->concreteEntry->LoadEntry($entities, $savedId, true);

        // Should have update firstnames
        $this->assertEquals($modifiedEntry['persons']['deathcauses'][0]['deathcause'] , $updatedEntry['persons']['deathcauses'][0]['deathcause'] );
    }

  
}
<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Di;

class FunctionalEntryTask1Test extends \UnitTestCase {
    private $testDBManager;
    private $concreteEntry;

	public function setUp($di = null) : void {

        parent::setUp();

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
        $this->testDBManager->cleanUpBurialStructure();
		parent::tearDown();
    }
    
    public function test_SaveNewEntry_SavesData(){

        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();
        
        // Create burials data
        $this->testDBManager->createBurialDataForEntryPost1000Task1();

        // Get entities from database
        $taskconfigLoader = new TaskConfigurationLoader(__DIR__ . '/TestData');
		$taskConf = $taskconfigLoader->getConfig(1);
        $entity = new ConfigurationEntity($taskConf['entity']);
        
        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/TestData/entry_save.json'), true);

        // Setup and save  
        $this->assertTrue($this->concreteEntry->SaveEntriesForTask($entity, $inputData)>0);
    }

    public function test_LoadEntry_ReturnEntry(){

        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();

        // Create burials data
        $this->testDBManager->createApacsMetadataForEntryPost10000Task1();
        $this->testDBManager->createBurialDataForEntryPost1000Task1();


        // Get entities from database
        $taskconfigLoader = new TaskConfigurationLoader(__DIR__ . '/TestData');
        $taskConf = $taskconfigLoader->getConfig(1);
        $entity = new ConfigurationEntity($taskConf['entity']);

        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/TestData/entry_save.json'), true);

        // Save concrete entry
        $savedId = $this->concreteEntry->SaveEntriesForTask($entity, $inputData);

        // Load  
        $loadedEntry = $this->concreteEntry->LoadEntry($entity, $savedId, true); 

        // Is the person saved?
        $this->assertFalse(is_null($loadedEntry['persons']['firstnames']));
        $this->assertEquals($inputData['persons']['firstnames'], $loadedEntry['persons']['firstnames']);
        
        // Are the values saved for child entities?
        $this->assertEquals($inputData['persons']['deathcauses'][0]['deathcause'],$loadedEntry['persons']['deathcauses'][0]['deathcause']);
        
        // Is the person id set?
        $this->assertTrue(isset($loadedEntry['persons']['id']));
        
        // Is the person id set for child entities?
        $this->assertEquals($inputData['persons']['id'], $loadedEntry['deathcauses'][0]['persons_id']);
    }

    public function test_SaveExistingEntry_UpdatesData(){

        // Create task 1 config data
        $this->testDBManager->createEntitiesAndFieldsForTask1();

        // Create burials data
        $this->testDBManager->createBurialDataForEntryPost1000Task1();

        // Get entities from database
		//$entities = Entities::find(['conditions' => 'task_id = 1']);
        $taskconfigLoader = new TaskConfigurationLoader(__DIR__ . '/TestData');
		$taskConf = $taskconfigLoader->getConfig(1);
        $entity = new ConfigurationEntity($taskConf['entity']);
        
        // Input data
        $inputData = json_decode(file_get_contents(__DIR__ . '/TestData/entry_save.json'), true);
        // Save  
        $savedId = $this->concreteEntry->SaveEntriesForTask($entity, $inputData);

        // Load and modify entry
        $loadedEntry = $this->concreteEntry->LoadEntry($entity, $savedId, true); 
        $modifiedEntry = $loadedEntry;
        //Modifing date format, as this is done in frontend
        $modifiedEntry['persons']['dateOfDeath'] = date('d-m-Y', strtotime($modifiedEntry['persons']['dateOfDeath']));
        $modifiedEntry['persons']['deathcauses'][0]['deathcause'] = 'Absces (Abscessus)';

        //  Update entry
        $updatedId = $this->concreteEntry->SaveEntriesForTask($entity, $modifiedEntry);

        //  Should keep id
        $this->assertEquals($savedId, $updatedId);
        
        //  Load updated entry
        $updatedEntry = $this->concreteEntry->LoadEntry($entity, $savedId, true);

        // Should have update firstnames
        $this->assertEquals($modifiedEntry['persons']['deathcauses'][0]['deathcause'] , $updatedEntry['persons']['deathcauses'][0]['deathcause'] );
    }  
}
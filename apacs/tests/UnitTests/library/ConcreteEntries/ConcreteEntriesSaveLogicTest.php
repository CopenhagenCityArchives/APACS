<?php

class ConcreteEntriesSaveLogicTest extends \UnitTestCase {

	public function setUp() : void {
        parent::setUp();
      
        $this->crudMock = new Mocks\CrudMock();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // Save
	public function test_SaveEntries_SimpleEntity_CallCrudSave() {

        //Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        $inputData = [ $entity['name'] => ['field1' => 'value1']];
        $saveData = ['field1' => 'value1']; 
        
        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->method('save')
             ->willReturn(1);

        // We expect the save method to call crud->save with the table name,
        //and data corresponding to the input
        $crudMock->expects($this->once())
            ->method('save')
            ->with(
                $this->equalTo($entity['primaryTableName']), 
                $this->equalTo($saveData),
                null
            );

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    public function test_SaveEntries_WithSecondaryEntry_CallCrudSaveTwice(){
        //Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();

        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
        

        $inputData = [ 
            $entity['name'] => [
                'field1' => 'value1',
                $entity['entities'][0]['name'] => [
                    'field2' => 'value2',
                    'parentEntityReferenceField' => 'sd'
                ]
            ]
        ];

        $saveReturnId = 1;

        $saveData = [
            ['field1' => 'value1'],
            ['field2' => 'value2', 'parentEntityReferenceField' => $saveReturnId]
        ];


        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->method('save')
            ->willReturn($saveReturnId);

        // We expect the save method to call crud->save with the table name,
        //and data corresponding to the input
        $crudMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([
                $this->equalTo($entity['primaryTableName']), 
                $this->equalTo($saveData[0]),
                null
            ],[
                $this->equalTo($entity['entities'][0]['primaryTableName']), 
                $this->equalTo($saveData[1]),
                null
        ]);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);   
    }

     //Delete secondary empty object entries on save
     public function test_SaveSecondaryObjectEntity_WithNoDataButId_RemoveEntity(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entity['entities'][0]['AllEntityFieldsAreEmpty'] = true;

        //Return true to test empty data
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
   
        $idToDelete = 45;

        $inputData = [
            $entity['name'] => [
                'field1' => 'value1',
                $entity['entities'][0]['name'] => [
                    'id' => $idToDelete
                ]
            ]
        ];
        
        
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->once())
            ->method('delete')
            ->with(
                $this->equalTo($entity['primaryTableName']), 
                $idToDelete
            );

        $crudMock->expects($this->once())
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    //Dont delete or save secondary empty array entries on save
    public function test_SaveSecondaryArrayEntity_WithNoDataButId_SkipEntity(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        //Return true to test empty data
        $entity['entities'][0]['AllEntityFieldsAreEmpty'] = true;
        $entity['entities'][0]['type'] = 'array';

        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
       

        $inputData = [
            $entity['name'] => [
                'field1' => 'value1',
                $entity['entities'][0]['name'] => []
            ]
        ];
        
        
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->never())
            ->method('delete');

        $crudMock->expects($this->once())
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }
}
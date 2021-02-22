<?php

class ConcreteEntriesSaveLogicTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
      
        $this->crudMock = new Mocks\CrudMock();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // Save
	public function test_SaveEntries_SimpleEntity_CallCrudSave() {
        //Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityData);

        $inputData = [ $entityData['name'] => ['field1' => 'value1']];
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
                $this->equalTo($entityData['primaryTableName']), 
                $this->equalTo($saveData),
                null
            );

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    public function test_SaveEntries_WithSecondaryEntry_CallCrudSaveTwice(){
        //Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entity = new ConfigurationEntity($entityData);

        $inputData = [ 
            $entityData['name'] => [
                'field1' => 'value1',
                $entityData['entities'][0]['name'] => [
                    'field2' => '2',
                ]
            ]
        ];

        $saveData = [
            ['field2' => '2'],
            ['field1' => 'value1', 'parentEntityReferenceField' => "1"]
        ];


        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->method('save')
            ->willReturn(1);

        // We expect the save method to call crud->save with the table name,
        //and data corresponding to the input
        $crudMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([
                $this->equalTo($entityData['entities'][0]['primaryTableName']), 
                $this->equalTo($saveData[0]),
                null
            ], [
                $this->equalTo($entityData['primaryTableName']), 
                $this->equalTo($saveData[1]),
                null
            ]);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);   
    }

     //Delete secondary empty object entries on save
     public function test_SaveSecondaryObjectEntity_WithNoDataButId_RemoveEntity(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();

        $entity = new ConfigurationEntity($entityData);
   
        $idToDelete = 45;

        $inputData = [
            $entityData['name'] => [
                'field1' => 'value1',
                $entityData['entities'][0]['name'] => [
                    'id' => $idToDelete
                ]
            ]
        ];
        
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->once())
            ->method('delete')
            ->with(
                $this->equalTo($entityData['entities'][0]['primaryTableName']), 
                $idToDelete
            );

        $crudMock->expects($this->once())
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    //Dont delete or save secondary empty array entries on save
    public function test_SaveSecondaryArrayEntity_WithNoDataButId_SkipEntity(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        //Return true to test empty data
        $entityData['entities'][0]['UserEntryIsEmpty'] = true;
        $entityData['entities'][0]['type'] = 'array';

        $entity = new ConfigurationEntity($entityData);

        $inputData = [
            $entityData['name'] => [
                'field1' => 'value1',
                $entityData['entities'][0]['name'] => []
            ]
        ];
        
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->never())
            ->method('delete');

        $crudMock->expects($this->once())
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    public function test_SaveSecondaryArrayEntity_WithAFalseBooleanValue(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getObjectEntityWithTwoFields();
        
        //Return true to test empty data
        $entityData['entities'][0]['isPrimaryEntity'] = 0;
        $entityData['entities'][0]['type'] = 'array';
        $entittyData['entities'][0]['fields']['formFieldType'] = 'boolean';

        $entity = new ConfigurationEntity($entityData);

        $inputData = [
            $entityData['name'] => [
                'field1' => 'value1',
                $entityData['entities'][0]['name'] => [
                    [
                        'field1' => false,
                        'field2' => "value2"
                    ]
                ]
            ]
        ];

        
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([
                $this->equalTo($entityData['primaryTableName']), 
                $this->equalTo([ 'field1' => 'value1' ])
            ], [
                $this->equalTo($entityData['entities'][0]['primaryTableName']), 
                $this->equalTo([
                    'parentEntityReferenceField' => 1,
                    'field1' => false,
                    'field2' => 'value2',
                    'order' => 1
                ])
            ])
            ->willReturnOnConsecutiveCalls(1, 2); // mock crud->save returning with ids 1 and 2

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }
}
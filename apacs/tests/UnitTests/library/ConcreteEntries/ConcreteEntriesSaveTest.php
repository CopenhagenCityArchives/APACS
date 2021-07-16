<?php

class ConcreteEntriesSaveTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}


    //Tests:
    /*
    SaveEntriesForTask
    ConcatEntitiesAndData
    GetFieldsValuesArray
    LoadEntry
    convertDataFromHierarchy
    deleteConcreteEntries
    removeAdditionalDataFromNewData
    DeleteConcreteEntry
    GetSolrDataFromEntryContext
    GetSolrData
    */

    // Save
	public function test_SaveEntityObject_ShouldCallCrudSaveInputData() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['dataIsValid'] = true;
        $entity = new ConfigurationEntity($entityData);

        $dataToSave = ['field1' => 'value1'];

        
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
                $this->equalTo($dataToSave),
                null
            );

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $dataToSave, null);
    }
    
    // Update
    public function test_SaveWithIdInData_ShouldCallCrudWithId() {
        
        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['dataIsValid'] = true;

        $entity = new ConfigurationEntity($entityData);

       $dataToSaveWithId = ['id' => 1, 'field1' => 'value1'];
       
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
                $this->equalTo(['field1' => 'value1']),
                $dataToSaveWithId['id']
            );

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $dataToSaveWithId, null);
    }

    // Decode
    public function test_SaveWithDecodeField_ShouldCallCrudLoadWithDecoding() {
        
        // Set entity mock and data
        $entityData = EntitiesTestData::getDecodeEntityNewValuesAllowed();
        $entityData['dataIsValid'] = true;
        $entity = new ConfigurationEntity($entityData);


        $dataToSave = ['field1'=>'value1', 'decodeField1' => 'encodedValue'];
        $decodedValue = 'decodedValue';

        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);
        
        $crudMock->method('find')
             ->willReturn([['id'=>2]]);

        $crudMock->expects($this->once())
            ->method('find')
            ->with(
                 $this->equalTo($entityData['fields'][1]['decodeTable']), 
                 $this->equalTo($entityData['fields'][1]['decodeField']),
                 $this->equalTo('encodedValue')
             );


        // The save method will return an id.
        $crudMock->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $dataToSave, null);
    }

    // Decode, new value
    public function test_SaveWithDecodeFieldNewValue_ShouldCallCrudSaveWithNewValue() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getDecodeEntityNewValuesAllowed();
        $entityData['dataIsValid'] = true;
        $entity = new ConfigurationEntity($entityData);
    
        $codeId = 'codeId';
        $codeValue = 'codeValue';

        $inputData = ['field1'=>'value1', 'decodeField1'=>$codeValue];
        $dataToSave = ['field1' => 'value1', 'field_to_decode'=>$codeId];

        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);
        
        //find coded value does not return a result
        $crudMock->method('find')
             ->willReturn(null);

        // we expect that the new value will be saved in the decode table and field
        // we also expect that the entity will be saved with a reference to the new value
        $crudMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([
                $this->equalTo($entityData['fields'][1]['decodeTable']), 
                $this->equalTo([$entityData['fields'][1]['decodeField'] => 'codeValue']) 
            ],[
                $this->equalTo($entityData['primaryTableName']), 
                $this->equalTo($dataToSave)
            ])
            ->will($this->onConsecutiveCalls(
                $codeId,
                1
            ));

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $inputData, null);
    }

    //ordering
    public function test_SaveArrayEntity_AddOrderingField(){

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleArrayEntity();
        $entityData['dataIsValid'] = true;
        $entity = new ConfigurationEntity($entityData);
        
        $inputData = ['field1'=>'value1'];
        $dataToSave = ['field1' => 'value1', 'order'=>0];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->once())
        ->method('save')
        ->with(
             $this->equalTo($entityData['primaryTableName']), 
             $dataToSave
         )
         ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $inputData, null);
    }

    public function test_Save_Deletes_ObjectWithArray() {
        $entity = new ConfigurationEntity([
            'name' => 'PrimaryEntity',
            'primaryTableName' => 'primaryTable',
            'isPrimaryEntity' => 1,
            'entityKeyName' => 'referenceField',
            'type' => 'object',
            'fields' => [],
            'entities' => [
                [
                    'name' => 'SecondaryEntity',
                    'primaryTableName' => 'secondaryTable',
                    'entityKeyName' => 'referenceField',
                    'type' => 'array',
                    'fields' => [
                        [ 'fieldName' => 'field1', 'formFieldType' => 'string'  ]
                    ]
                ],
                [
                    'name' => 'TertiaryEntity',
                    'primaryTableName' => 'tertiaryTable',
                    'entityKeyName' => 'referenceField',
                    'type' => 'object',
                    'fields' => [
                        [ 'fieldName' => 'field2', 'formFieldType' => 'string' ]
                    ]
                ]
            ]
        ]);

        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);

        $crudMock->expects($this->exactly(6))
            ->method('save')
            ->willReturnOnConsecutiveCalls(12, 12, 12, 123, 12, 12);

        $crudMock->expects($this->exactly(8))
            ->method('delete')
            ->withConsecutive(
                ["secondaryTable", 123],

                ["secondaryTable", 123],

                ["secondaryTable", 124],
                ["secondaryTable", 125],

                ["secondaryTable", 123],
                ["secondaryTable", 124],
                ["secondaryTable", 125],

                ["tertiaryTable", 22]
            );
        
        // Array empty in new
        $concreteEntries->Save($entity, [
            'SecondaryEntity' => []
        ], [
            'id' => 12,
            'SecondaryEntity' => [
                [ 'id' => 123, 'field1' => 'value1' ]
            ]
        ]);

        // Array undefined in new
        $concreteEntries->Save($entity, [], [
            'id' => 12,
            'SecondaryEntity' => [
                [ 'id' => 123, 'field1' => 'value1' ]
            ]
        ]);

        // New array with 2 removed items and 1 kept item
        $concreteEntries->Save($entity, [
            'SecondaryEntity' => [
                [ 'id' => 123, 'field1' => 'new_value1' ]
            ]
        ], [
            'id' => 12,
            'SecondaryEntity' => [
                [ 'id' => 123, 'field1' => 'value1' ],
                [ 'id' => 124, 'field1' => 'value1' ],
                [ 'id' => 125, 'field1' => 'value1' ]
            ]
        ]);

        // Array entity is removed
        $concreteEntries->Save($entity, [], [
            'id' => 12,
            'SecondaryEntity' => [
                [ 'id' => 123, 'field1' => 'value1' ],
                [ 'id' => 124, 'field1' => 'value1' ],
                [ 'id' => 125, 'field1' => 'value1' ]
            ]
        ]);

        // Object entity is removed
        $concreteEntries->Save($entity, [], [
                'id' => 12,
                'TertiaryEntity' => [
                    'id' => 22,
                    'field2' => 'value2'
                ]
        ]);
    }


    public function test_Save_Deletes_ObjectWithNestedArrays() {
        $entity = new ConfigurationEntity([
            'name' => 'PrimaryEntity',
            'primaryTableName' => 'primaryTable',
            'isPrimaryEntity' => 1,
            'entityKeyName' => 'referenceField',
            'type' => 'object',
            'fields' => [],
            'entities' => [
                [
                    'name' => 'SecondaryEntity',
                    'primaryTableName' => 'secondaryTable',
                    'entityKeyName' => 'referenceField',
                    'type' => 'array',
                    'fields' => [
                        [ 'fieldName' => 'field1', 'formFieldType' => 'string' ]
                    ],
                    'entities' => [
                        [
                            'name' => 'TertiaryEntity',
                            'primaryTableName' => 'tertiaryTable',
                            'entityKeyName' => 'referenceField',
                            'type' => 'array',
                            'fields' => [
                                [ 'fieldName' => 'field2', 'formFieldType' => 'string' ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);

        $crudMock->expects($this->exactly(3))
            ->method('save')
            ->willReturnOnConsecutiveCalls(12, 123, 12);

        $crudMock->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ["tertiaryTable", 1234],

                ["tertiaryTable", 1234],
                ["secondaryTable", 123]
            );
        
        // Nested array empty in new
        $concreteEntries->Save($entity, [
            'id' => 12,
            'SecondaryEntity' => [
                [
                    'id' => 123,
                    'field1' => 'value1'
                ]
            ]
        ], [
            'id' => 12,
            'SecondaryEntity' => [
                [
                    'id' => 123,
                    'field1' => 'value1',
                    'TertiaryEntity' => [
                        [
                            'id' => 1234,
                            'field2' => 'value2'
                        ]
                    ]
                ]
            ]
        ]);

        // Top array empty in new
        $concreteEntries->Save($entity, [
            'id' => 12,
            'SecondaryEntity' => []
        ], [
            'id' => 12,
            'SecondaryEntity' => [
                [
                    'id' => 123,
                    'field1' => 'value1',
                    'TertiaryEntity' => [
                        [
                            'id' => 1234,
                            'field2' => 'value2'
                        ]
                    ]
                ]
            ]
        ]);
    }
}
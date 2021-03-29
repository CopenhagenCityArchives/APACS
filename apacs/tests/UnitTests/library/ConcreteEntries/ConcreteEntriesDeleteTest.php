<?php

class ConcreteEntriesDeleteTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
      
        $this->crudMock = new Mocks\CrudMock();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

	public function test_DeleteSingleEntry_SimpleEntity_CallCrudDelete() {
        //Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityData);

        $entry = ['id' => 100, 'field1' => 'value1'];
        
        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->expects($this->once())
            ->method('delete')
            ->with($entity->primaryTableName, 100);

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);
        $concreteEntries->DeleteSingleEntry($entity, $entry);
    }

	public function test_DeleteSingleEntry_ObjectChildEntity_CallCrudDeleteTwice() {
        //Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $secondaryEntityData = EntitiesTestData::getSimpleSecondaryEntity();
        $entityData['entities'] = [$secondaryEntityData];
        $entity = new ConfigurationEntity($entityData);

        $entry = [
            'id' => 100,
            'field1' => 'value1',
            'simpleSecondaryEntity' => [
                'id' => 200,
                'field2' => 'value2'
            ]
        ];
        
        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$entityData["primaryTableName"], 100],
                [$secondaryEntityData["primaryTableName"], 200]
            );

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);
        $concreteEntries->DeleteSingleEntry($entity, $entry);
    }

    public function test_DeleteSingleEntry_ArrayChildEntity_CallCrudDeleteTwice() {
        //Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $secondaryEntityData = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntityData['type'] = 'array';
        $entityData['entities'] = [$secondaryEntityData];
        $entity = new ConfigurationEntity($entityData);

        $entry = [
            'id' => 100,
            'field1' => 'value1',
            'simpleSecondaryEntity' => [[
                'id' => 200,
                'field2' => 'value2'
            ]]
        ];
        
        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // The save method will return an id.
        $crudMock->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$secondaryEntityData["primaryTableName"], 200],
                [$entityData["primaryTableName"], 100]
            );

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);
        $concreteEntries->DeleteSingleEntry($entity, $entry);
    }

    public function test_DeleteRemovedSubentries_ObjectWithArray() {
        $entity = new ConfigurationEntity([
            'name' => 'PrimaryEntity',
            'primaryTableName' => 'primaryTable',
            'isPrimaryEntity' => 1,
            'entityKeyName' => 'referenceField',
            'type' => 'object',
            'entities' => [
                [
                    'name' => 'SecondaryEntity',
                    'primaryTableName' => 'secondaryTable',
                    'entityKeyName' => 'referenceField',
                    'type' => 'array',
                    'fields' => [
                        [ 'fieldName' => 'field1' ]
                    ]
                ],
                [
                    'name' => 'TertiaryEntity',
                    'primaryTableName' => 'tertiaryTable',
                    'entityKeyName' => 'referenceField',
                    'type' => 'object',
                    'fields' => [
                        [ 'fieldName' => 'field2' ]
                    ]
                ]
            ]
        ]);

        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $concreteEntries = new ConcreteEntries($this->getDI(), $crudMock);

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
                ["primaryTable", 12],

                ["primaryTable", 12],
                ["tertiaryTable", 22]
            );
        
        // Array empty in new
        $concreteEntries->DeleteRemovedSubentries($entity, [
            'PrimaryEntity' => [
                'id' => 12,
                'SecondaryEntity' => [
                    [ 'id' => 123, 'field1' => 'value1' ]
                ]
            ] 
        ], [
            'PrimaryEntity' => [
                'SecondaryEntity' => []
            ]
        ]);

        // Array undefined in new
        $concreteEntries->DeleteRemovedSubentries($entity, [
            'PrimaryEntity' => [
                'id' => 12,
                'SecondaryEntity' => [
                    [ 'id' => 123, 'field1' => 'value1' ]
                ]
            ] 
        ], [
            'PrimaryEntity' => []
        ]);

        // New array with 2 removed items and 1 kept item
        $concreteEntries->DeleteRemovedSubentries($entity, [
            'PrimaryEntity' => [
                'id' => 12,
                'SecondaryEntity' => [
                    [ 'id' => 123, 'field1' => 'value1' ],
                    [ 'id' => 124, 'field1' => 'value1' ],
                    [ 'id' => 125, 'field1' => 'value1' ]
                ]
            ] 
        ], [
            'PrimaryEntity' => [
                'SecondaryEntity' => [
                    [ 'id' => 123, 'field1' => 'value1' ]
                ]
            ]
        ]);

        // Parent entity is removed
        $concreteEntries->DeleteRemovedSubentries($entity, [
            'PrimaryEntity' => [
                'id' => 12,
                'SecondaryEntity' => [
                    [ 'id' => 123, 'field1' => 'value1' ],
                    [ 'id' => 124, 'field1' => 'value1' ],
                    [ 'id' => 125, 'field1' => 'value1' ]
                ]
            ] 
        ], []);

        // // Parent entity is removed
        // $concreteEntries->DeleteRemovedSubentries($entity, [
        //     'PrimaryEntity' => [
        //         'id' => 12,
        //         'TertiaryEntity' => [
        //             'id' => 22,
        //             'field2' => 'value2'
        //         ]
        //     ] 
        // ], []);
    }
}
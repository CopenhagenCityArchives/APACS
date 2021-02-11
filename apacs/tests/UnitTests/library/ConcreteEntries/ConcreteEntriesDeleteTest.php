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
}
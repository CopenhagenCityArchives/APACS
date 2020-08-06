<?php

class ConcreteEntriesLoadTest_skip extends \UnitTestCase {

	public function setUp() : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // Save
	public function test_Load_ObjectEntity_ShouldCallCrudLoad_WithId() {
        $this->markTestIncomplete();
        //Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        $id = 46;
        
        // Create a stub for the CrudMock class.
        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // We expect the load method to be run once with id
        $crudMock->expects($this->once())
            ->method('load')
            ->with($entity->primaryTableName, 'id', $id);

        $entry = new ConcreteEntries($this->di, $crudMock);
        $entry->load($entity, 'id', $id);
    }
}
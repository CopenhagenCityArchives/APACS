<?php

class ConcreteEntriesInputValdationTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // secondary entities, throw exception on missing reference to parent
    public function test_SaveSecondaryEntity_WithNoReferenceToPrimaryEntity_ThrowException(){
        $entity = EntitiesTestData::getSimpleSecondaryEntity();

        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entititesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        $inputData = [
            'field2'=>'value2', 
            'parentEntityReferenceField'=>null
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entititesCollection->getPrimaryEntity(), $inputData);
    }

    
    //No data for primary entity
    public function test_SavePrimaryEntity_WithNoData_ThrowException(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
        
        $inputData = [];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }
    
    public function test_SavePrimaryEntity_WithInvalidData_ThrowException(){
        
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['isDataValid'] = false;
        
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        $inputData = [
            $entity->name => [
                'field1'=>'value1']
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    //Invalid data for primary entity 
    public function test_SaveSecondaryObjectEntity_WithInvalidData_ThrowException(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entity['entities'][0]['isDataValid'] = false;
        
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
        
        $inputData = [
            $entity['name'] => [
                'field1'=>'value1',
                $entity['entities'][0]['name'] => [
                ]
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithInvalidData_ThrowException(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entity['entities'][0]['isDataValid'] = false;
        $entity['entities'][0]['type'] = 'array';
        $entity['entities'][0]['AllEntityFieldsAreEmpty'] = false;
        
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
        
        $inputData = [
            $entity['name'] => [
                'field1'=>'value1',
                $entity['entities'][0]['name'] => [
                    [
                    'field2' => 'value2',
                    'parentEntityReferenceField' => null
                    ]
                ]
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithNoData_Ignore(){
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        //Return true to test empty data
        $entity['entities'][0]['isDataValid'] = true;
        $entity['entities'][0]['type'] = 'array';
        $entity['entities'][0]['AllEntityFieldsAreEmpty'] = true;

        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);
        
        $inputData = [
            $entity['name'] => [
                'field1'=>'value1'
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should happen exactly once
        //and should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entitiesCollection, $inputData);
    }

    // Obsolete. IEntities required
    public function xtest_SaveWithNoEntities_ThrowException(){
        $this->expectException(InvalidArgumentException::class);
        $crudMock = $this->createMock(Mocks\CrudMock::class);
        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask(null, null);
    }

    /*
    Empty secondary entity (object): Already tested in test_SaveSecondaryObjectEntity_WithNoDataButId_RemoveEntity
    */
}
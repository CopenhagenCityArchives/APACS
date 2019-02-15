<?php

class ConcreteEntriesInputValdationTest extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

    // secondary entities, throw exception on missing reference to parent
    public function test_SaveSecondaryEntity_WithNoReferenceToPrimaryEntity_ThrowException(){
        $entity = EntitiesDataMock::getSimpleSecondaryEntity();
        
        $inputData = [
            'field2'=>'value2', 
            'parentEntityReferenceField'=>null
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $inputData);
    }

    
    //No data for primary entity
    public function test_SavePrimaryEntity_WithNoData_ThrowException(){
        $entity = EntitiesDataMock::getSimpleEntity();
        
        $inputData = [];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask([$entity], $inputData);
    }
    
    public function test_SavePrimaryEntity_WithInvalidData_ThrowException(){

        $entity = EntitiesDataMock::getSimpleEntity();
        $entity->isDataValid = false;
        
        $inputData = [
            $entity->name => [
                'field1'=>'value1']
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask([$entity], $inputData);
    }

    //Invalid data for primary entity 
    public function test_SaveSecondaryObjectEntity_WithInvalidData_ThrowException(){
        $entities = [];
        $entities[] = EntitiesDataMock::getSimpleEntity();
        $entities[] = EntitiesDataMock::getSimpleSecondaryEntity();
        
        $entities[1]->isDataValid = false;
        
        $inputData = [
            $entities[0]->name => [
                'field1'=>'value1',
                $entities[1]->name => [
                ]
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entities, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithInvalidData_ThrowException(){
        $entities = [];
        $entities[] = EntitiesDataMock::getSimpleEntity();
        $entities[] = EntitiesDataMock::getSimpleSecondaryEntity();
        
        $entities[1]->isDataValid = false;
        $entities[1]->type = 'array';
        $entities[1]->AllEntityFieldsAreEmpty = false;
        
        $inputData = [
            $entities[0]->name => [
                'field1'=>'value1',
                $entities[1]->name => [
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
        $entry->SaveEntriesForTask($entities, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithNoData_Ignore(){
        $entities = [];
        $entities[] = EntitiesDataMock::getSimpleEntity();
        $entities[] = EntitiesDataMock::getSimpleSecondaryEntity();
        
        $entities[1]->isDataValid = true;
        $entities[1]->type = 'array';
        $entities[1]->AllEntityFieldsAreEmpty = true;
        
        $inputData = [
            $entities[0]->name => [
                'field1'=>'value1'
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should happen exactly once
        //and should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entities, $inputData);
    }

    public function test_SaveWithNoEntities_ThrowException(){
        $this->expectException(InvalidArgumentException::class);
        $crudMock = $this->createMock(Mocks\CrudMock::class);
        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask([], null);
    }

    /*
    Empty secondary entity (object): Already tested in test_SaveSecondaryObjectEntity_WithNoDataButId_RemoveEntity
    */
}
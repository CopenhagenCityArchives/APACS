<?php

class ConcreteEntriesInputValdationTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // secondary entities, throw exception on missing reference to parent
    public function test_SaveSecondaryEntity_AsArray_WithNoReferenceToPrimaryEntity_ThrowException() {
        $entityData = EntitiesTestData::getSimpleSecondaryEntity();
        $entityData['type'] = 'array'; 
        $entity = new ConfigurationEntity($entityData);

        $inputData = [
            'field2' => 'value2', 
            'parentEntityReferenceField'=>null
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->save($entity, $inputData);
    }

    
    //No data for primary entity
    public function test_SavePrimaryEntity_WithNoData_ThrowException(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        $entity = new ConfigurationEntity($entityData);
        $inputData = [];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }
    
    public function test_SavePrimaryEntity_WithInvalidData_ThrowException(){
        
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['isDataValid'] = false;
        
        $entity = new ConfigurationEntity($entityData);

        $inputData = [
            $entity->name => [
                'field1'=>'invalid_value1']
        ];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        $crudMock->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    //Invalid data for primary entity 
    public function test_SaveSecondaryObjectEntity_WithInvalidData_ThrowException(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entityData['entities'][0]['type'] = 'object';
        $entityData['entities'][0]['isDataValid'] = false;
        
        $entity = new ConfigurationEntity($entityData);
        
        $inputData = [
            $entityData['name'] => [
                'field1'=>'invalid_value1',
                $entityData['entities'][0]['name'] => [
                ]
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // Invalid data means that Save is never called
        $crudMock->expects($this->never())
            ->method('save')
            ->willReturn(1);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithInvalidData_ThrowException(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        $entityData['entities'][0]['isDataValid'] = false;
        $entityData['entities'][0]['type'] = 'array';
        $entityData['entities'][0]['UserEntryIsEmpty'] = false;
        
        $entity = new ConfigurationEntity($entityData);
        
        $inputData = [
            $entityData['name'] => [
                'field1'=>'invalid_value1',
                $entityData['entities'][0]['name'] => [
                    [
                    'field2' => 'value2',
                    'parentEntityReferenceField' => null
                    ]
                ]
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        // Never calls save
        $crudMock->expects($this->never())
            ->method('save')
            ->willReturn(1);

        $this->expectException(InvalidArgumentException::class);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }

    public function test_SaveSecondaryEntityArray_WithNoData_Ignore(){
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['entities'][] = EntitiesTestData::getSimpleSecondaryEntity();
        
        //Return true to test empty data
        $entityData['entities'][0]['isDataValid'] = true;
        $entityData['entities'][0]['type'] = 'array';
        $entityData['entities'][0]['UserEntryIsEmpty'] = true;

        $entity = new ConfigurationEntity($entityData);
        
        $inputData = [
            $entityData['name'] => [
                'field1'=>'value1'
        ]];

        $crudMock = $this->createMock(Mocks\CrudMock::class);

        //Saving should happen exactly once
        //and should be cancel with rollBack
        $crudMock->expects($this->exactly(1))
            ->method('save')
            ->willReturn(1);

        $entry = new ConcreteEntries($this->getDI(), $crudMock);
        $entry->SaveEntriesForTask($entity, $inputData);
    }
}
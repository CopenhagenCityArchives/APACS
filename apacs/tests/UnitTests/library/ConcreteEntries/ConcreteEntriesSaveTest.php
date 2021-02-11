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
        $entry->save($entity, $dataToSave);
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
        $entry->save($entity, $dataToSaveWithId);
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
        $entry->save($entity, $dataToSave);
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
        $entry->save($entity, $inputData);
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
        $entry->save($entity, $inputData);
    }
}
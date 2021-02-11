<?php

class ConcreteEntriesConcatDataTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // ConcatEntitiesAndData
	public function test_ConcatEntitiesAndData_SimpleEntity_ReturnConcattedData() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityData);
        
        // Input is an array of data related to en entity
        $inputData = ['id' => 10, 'field1' => 'value1'];

        // We expect an output consisting of various metadata
        // as well as an array of fields in the form [field_name, label, value, parent_id]
        $expectedData = [
            [
                'entity_name' => $entityData['name'],
                'label' => $entityData['guiName'],
                'entry_id' => -1,
                'concrete_entries_id' => 10,
                'fields' => [
                    [
                        'field_name' => 'field1',
                        'label' => $entityData['fields'][0]['formName'],
                        'value' => $inputData['field1'],
                        'parent_id' => 10
                    ]
                ]
            ]       
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->ConcatEntitiesAndData($entity, $inputData, -1);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    public function test_ConcatEntitiesAndData_WithSecondaryEntity_ReturnConcattedData() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $secondaryEntityData = EntitiesTestData::getSimpleSecondaryEntity();
        $entityData['entities'] = [$secondaryEntityData];
        $entity = new ConfigurationEntity($entityData);
        
        // Input is an array of data related to en entity
        $inputData = [
            'id' => 10,
            'field1' => 'value1',
            'simpleSecondaryEntity' => [
                'id' => 20,
                'field2' => 'value5'
            ]
        ];

        // We expect an output consisting of various metadata
        // as well as an array of fields in the form [field_name, label, value, parent_id]
        $expectedData = [
            [
                'entity_name' => $entityData['name'],
                'label' => $entityData['guiName'],
                'entry_id' => -1,
                'concrete_entries_id' => 10,
                'fields' => [
                    [
                        'field_name' => 'field1',
                        'label' => $entityData['fields'][0]['formName'],
                        'value' => $inputData['field1'],
                        'parent_id' => 10
                    ]
                ]
            ],
            [
                'entity_name' => $secondaryEntityData['name'],
                'label' => $secondaryEntityData['guiName'],
                'entry_id' => -1,
                'concrete_entries_id' => 20,
                'fields' => [
                    [
                        'field_name' => 'field2',
                        'label' => $secondaryEntityData['fields'][0]['formName'],
                        'value' => $inputData[$secondaryEntityData['name']]['field2'],
                        'parent_id' => 20
                    ]
                ]
            ]
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->ConcatEntitiesAndData($entity, $inputData, -1);
        
        $this->assertEquals($expectedData, $concattedData);
    }
}
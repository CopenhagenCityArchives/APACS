<?php

class ConcreteEntriesGetSolrTest extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

    // ConcatEntitiesAndData
	public function test_GetSolrData_SimpleEntityIncludeFields_ReturnFieldValuesWithSolrFieldNames() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['includeInSOLR'] = 0;
        $entityData['fields'][0]['includeInSOLR'] = 1;
        $entityData['fields'][0]['SOLRFieldName'] = 'SolrFieldName';
        $entity = new ConfigurationEntity($entityData);

        // Input is an array of data related to en entity
        $inputData = [
            $entityData['name'] => [
                'id' => 10, 
                'field1' => 'value1'
            ]
        ];

        // Output should match input but with SolrFieldName.
        // Only fields with includeinSOLR = 1 should be included
        $expectedData = [
            'SolrFieldName' => 'value1'       
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entity, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    // ConcatEntitiesAndData
	public function test_GetSolrData_TwoEntitiesIncludeFields_ReturnFieldValuesWithSolrFieldNames() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['includeInSOLR'] = 0;
        $entityData['fields'][0]['includeInSOLR'] = 1;
        $entityData['fields'][0]['SOLRFieldName'] = 'SolrFieldName';
        
        $secondaryEntityData = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntityData['includeInSOLR'] = 0;
        $secondaryEntityData['type'] = 'array';
        $secondaryEntityData['fields'][0]['includeInSOLR'] = 1;
        $secondaryEntityData['fields'][0]['SOLRFieldName'] = 'field2';

        $entityData['entities'][] = $secondaryEntityData;

        $entity = new ConfigurationEntity($entityData);

        // Input is an array of data related to the entities
        $inputData = [
            $entityData['name'] => [
                'id' => 10, 
                'field1' => 'value1',
                $secondaryEntityData['name'] => [
                    [
                        'field2' => 'value2'
                    ]
                ]
            ]
        ];

        // Output should match input but with SolrFieldName.
        // Only fields with includeinSOLR = 1 should be included
        // Array entities should be returned as arrays
        $expectedData = [
            'SolrFieldName' => 'value1',
            'field2' => ['value2']
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entity, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    // 
	public function test_GetSolrData_SimpleEntityIncludeEntity_ReturnConcattedFieldValues() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityData['includeInSOLR'] = 1;
        $entityData['type'] = 'object';
        $entityData['fields'][0]['includeInSOLR'] = 1;
        $entityData['fields'][0]['SOLRFieldName'] = 'SolrFieldName1';
        $entityData['fields'][1]['includeInSOLR'] = 1;
        $entityData['fields'][1]['SOLRFieldName'] = 'SolrFieldName2';
        $entity = new ConfigurationEntity($entityData);

        // Input is an array of data related to en entity
        $inputData = [
            $entityData['name'] => [
                'id' => 10, 
                'field1' => 'value1',
                'field2' => 'value2'
            ]
        ];

        // Output should match input but with SolrFieldName.
        // Only fields with includeinSOLR = 1 should be included
        $expectedData = [
            'SolrFieldName1' => 'value1',
            'SolrFieldName2' => 'value2',
            $entityData['name'] => 'value1 value2'      
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entity, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    public function test_GetSolrData_ArrayEntityIncludeEntity_ReturnConcattedFieldValues() {

        // Set entity mock and data
        $entityData = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityData['includeInSOLR'] = 1;
        $entityData['type'] = 'array';
        $entityData['fields'][0]['includeInSOLR'] = 1;
        $entityData['fields'][0]['SOLRFieldName'] = 'SolrFieldName1';
        $entityData['fields'][1]['includeInSOLR'] = 1;
        $entityData['fields'][1]['SOLRFieldName'] = 'SolrFieldName2';
        $entity = new ConfigurationEntity($entityData);

        // Input is an array of data related to en entity
        $inputData = [
            $entityData['name'] => [
                [
                    'id' => 10, 
                    'field1' => 'value1.1',
                    'field2' => 'value1.2'
                ],
                [
                    'id' => 11, 
                    'field1' => 'value2.1',
                    'field2' => 'value2.2'
                ]
            ]
        ];

        // We expect the output to be an array of concatted values as well as an array for each
        // field to be included
        $expectedData = [
            'SolrFieldName1' => ['value1.1', 'value2.1'],
            'SolrFieldName2' => ['value1.2', 'value2.2'],
            $entityData['name'] => ['value1.1 value1.2', 'value2.1 value2.2']     
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entity, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }
}
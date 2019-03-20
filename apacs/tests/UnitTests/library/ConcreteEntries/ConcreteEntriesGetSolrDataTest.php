<?php

class ConcreteEntriesGetSolrTest extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

    // ConcatEntitiesAndData
	public function test_GetSolrData_SimpleEntityIncludeFields_ReturnFieldValuesWithSolrFieldNames() {

        // Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['includeInSOLR'] = 0;
        $entity['fields'][0]['includeInSOLR'] = 1;
        $entity['fields'][0]['SOLRFieldName'] = 'SolrFieldName';
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        // Input is an array of data related to en entity
        $inputData = [
            $entity['name'] => [
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
        $concattedData = $entry->GetSolrData($entitiesCollection, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    // ConcatEntitiesAndData
	public function test_GetSolrData_TwoEntitiesIncludeFields_ReturnFieldValuesWithSolrFieldNames() {

        // Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        $entity['includeInSOLR'] = 0;
        $entity['fields'][0]['includeInSOLR'] = 1;
        $entity['fields'][0]['SOLRFieldName'] = 'SolrFieldName';
        
        $secondaryEntity = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntity['includeInSOLR'] = 0;
        $secondaryEntity['type'] = 'array';
        $secondaryEntity['fields'][0]['includeInSOLR'] = 1;
        $secondaryEntity['fields'][0]['SOLRFieldName'] = 'field2';

        $entity['entities'][] = $secondaryEntity;

        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        // Input is an array of data related to the entities
        $inputData = [
            $entity['name'] => [
                'id' => 10, 
                'field1' => 'value1',
                $secondaryEntity['name'] => [
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
        $concattedData = $entry->GetSolrData($entitiesCollection, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    // 
	public function test_GetSolrData_SimpleEntityIncludeEntity_ReturnConcattedFieldValues() {

        // Set entity mock and data
        $entity = EntitiesTestData::getObjectEntityWithTwoFields();
        $entity['includeInSOLR'] = 1;
        $entity['type'] = 'object';
        $entity['fields'][0]['includeInSOLR'] = 1;
        $entity['fields'][0]['SOLRFieldName'] = 'SolrFieldName1';
        $entity['fields'][1]['includeInSOLR'] = 1;
        $entity['fields'][1]['SOLRFieldName'] = 'SolrFieldName2';
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        // Input is an array of data related to en entity
        $inputData = [
            $entity['name'] => [
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
            $entity['name'] => 'value1 value2'      
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entitiesCollection, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }

    public function test_GetSolrData_ArrayEntityIncludeEntity_ReturnConcattedFieldValues() {

        // Set entity mock and data
        $entity = EntitiesTestData::getObjectEntityWithTwoFields();
        $entity['includeInSOLR'] = 1;
        $entity['type'] = 'array';
        $entity['fields'][0]['includeInSOLR'] = 1;
        $entity['fields'][0]['SOLRFieldName'] = 'SolrFieldName1';
        $entity['fields'][1]['includeInSOLR'] = 1;
        $entity['fields'][1]['SOLRFieldName'] = 'SolrFieldName2';
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        // Input is an array of data related to en entity
        $inputData = [
            $entity['name'] => [
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

        // Output should match input but with SolrFieldName.
        // Only fields with includeinSOLR = 1 should be included
        $expectedData = [
            'SolrFieldName1' => ['value1.1', 'value2.1'],
            'SolrFieldName2' => ['value1.2', 'value2.2'],
            $entity['name'] => ['value1.1 value1.2', 'value2.1 value2.2']     
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->GetSolrData($entitiesCollection, $inputData);
        
        $this->assertEquals($expectedData, $concattedData);
    }
}
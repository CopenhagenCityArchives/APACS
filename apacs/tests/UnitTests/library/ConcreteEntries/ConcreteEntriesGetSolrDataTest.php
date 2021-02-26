<?php

class ConcreteEntriesGetSolrDataTest extends \UnitTestCase {

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
        $parentEntityData = EntitiesTestData::getSimpleEntity();
        $entityData = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityData['includeInSOLR'] = 1;
        $entityData['type'] = 'array';
        $entityData['fields'][0]['includeInSOLR'] = 1;
        $entityData['fields'][0]['SOLRFieldName'] = 'SolrFieldName1';
        $entityData['fields'][1]['includeInSOLR'] = 1;
        $entityData['fields'][1]['SOLRFieldName'] = 'SolrFieldName2';
        $parentEntityData['entities'] = [$entityData];
        $entity = new ConfigurationEntity($parentEntityData);

        // Input contains the parent enttiy with a single property, the array of the nested entity
        $inputData = [
            $parentEntityData['name'] => [
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

    public function test_GetSolrData_DeepEntityStructure() {
        $entityConfig = [
            'name' => 'RootEntity',
            'primaryTableName' => 'root_entity_table',
            'isPrimaryEntity' => 1,
            'entityKeyName'=> 'root_entity_id',
            'type' => 'object',
            'fieldObjects' => [],
            'includeInSOLR' => 0,
            'fields' => [
                [
                    'fieldName' => 'root_field_1',
                    'decodeField' => null,
                    'hasDecode' => null,
                    'decodeTable' => null,
                    'codeAllowNewValue' => false,
                    'includeInSOLR' => 1,
                    'SOLRFieldName' => 'solr_field_1'
                ]
            ],
            'entities' => [
                [
                    'name' => 'SecondEntity',
                    'primaryTableName' => 'second_entity_table',
                    'isPrimaryEntity' => 0,
                    'entityKeyName'=> 'second_entity_id',
                    'type' => 'array',
                    'fieldObjects' => [],
                    'fields' => [
                        [
                            'fieldName' => 'second_field_1',
                            'decodeField' => null,
                            'hasDecode' => null,
                            'decodeTable' => null,
                            'codeAllowNewValue' => false,
                            'includeInSOLR' => 1,
                            'SOLRFieldName' => 'solr_field_2'
                        ]
                    ],
                    'entities' => [
                        [
                            'name' => 'ThirdEntity',
                            'primaryTableName' => 'third_entity_table',
                            'isPrimaryEntity' => 0,
                            'entityKeyName'=> 'third_entity_id',
                            'type' => 'array',
                            'fieldObjects' => [],
                            'fields' => [
                                [
                                    'fieldName' => 'third_field_1',
                                    'decodeField' => null,
                                    'hasDecode' => null,
                                    'decodeTable' => null,
                                    'codeAllowNewValue' => false,
                                    'includeInSOLR' => 1,
                                    'SOLRFieldName' => 'solr_field_3'
                                ],
                                [
                                    'fieldName' => 'third_field_2',
                                    'decodeField' => null,
                                    'hasDecode' => null,
                                    'decodeTable' => null,
                                    'codeAllowNewValue' => false,
                                    'includeInSOLR' => 1,
                                    'SOLRFieldName' => 'solr_field_4'
                                ]
                            ],
                            'entities' => [
                                [
                                    'name' => 'FourthEntity',
                                    'primaryTableName' => 'fourth_entity_table',
                                    'isPrimaryEntity' => 0,
                                    'entityKeyName'=> 'fourth_entity_id',
                                    'type' => 'array',
                                    'fieldObjects' => [],
                                    'fields' => [
                                        [
                                            'fieldName' => 'fourth_field_1',
                                            'decodeField' => null,
                                            'hasDecode' => null,
                                            'decodeTable' => null,
                                            'codeAllowNewValue' => false,
                                            'includeInSOLR' => 1,
                                            'SOLRFieldName' => 'solr_field_5'
                                        ]
                                    ],
                                    'entities' => []
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'FifthEntity',
                    'primaryTableName' => 'fifth_entity_table',
                    'isPrimaryEntity' => 0,
                    'entityKeyName'=> 'fifth_entity_id',
                    'type' => 'object',
                    'fieldObjects' => [],
                    'fields' => [
                        [
                            'fieldName' => 'fifth_field_1',
                            'decodeField' => null,
                            'hasDecode' => null,
                            'decodeTable' => null,
                            'codeAllowNewValue' => false,
                            'includeInSOLR' => 1,
                            'SOLRFieldName' => 'solr_field_6'
                        ]
                    ],
                    'entities' => []
                ]
            ]
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $inputData = [
            'RootEntity' => [
                'root_field_1' => 'value1',
                'SecondEntity' => [
                    [
                        'second_field_1' => 'value2',
                        'ThirdEntity' => []
                    ],
                    [
                        'second_field_1' => 'value3',
                        'ThirdEntity' => [
                            [
                                'third_field_1' => 'value4',
                                'third_field_2' => 'value5',
                                'FourthEntity' => [
                                    [ 'fourth_field_1' => 'value6' ],
                                    [ 'fourth_field_1' => 'value7' ]
                                ]
                            ],
                            [
                                'third_field_1' => 'value8',
                                'third_field_2' => 'value9',
                                'FourthEntity' => []
                            ],
                        ]
                    ],
                    [
                        'second_field_1' => 'value10',
                        'ThirdEntity' => [
                            [
                                'third_field_1' => 'value11',
                                'third_field_2' => 'value12',
                                'FourthEntity' => [
                                    [ 'fourth_field_1' => 'value13' ]
                                ]
                            ],
                        ]
                    ]
                ],
                "FifthEntity" => [
                    "fifth_field_1" => "value14"
                ]
            ]
        ];

        $this->assertEquals(
            [
                'solr_field_1' => 'value1',
                'solr_field_2' => ['value2', 'value3', 'value10'],
                'solr_field_3' => ['value4', 'value8', 'value11'],
                'solr_field_4' => ['value5', 'value9', 'value12'],
                'solr_field_5' => ['value6', 'value7', 'value13'],
                'solr_field_6' => 'value14'
            ],
            $entry->GetSolrData(new ConfigurationEntity($entityConfig), $inputData),
            "Values are flattened"
        );

        $entityConfig['entities'][0]['entities'][0]['includeInSOLR'] = 1;
        $entityConfig['entities'][0]['entities'][0]['entities'][0]['includeInSOLR'] = 1;
        $entityConfig['entities'][1]['includeInSOLR'] = 1;
        $this->assertEquals(
            [
                'solr_field_1' => 'value1',
                'solr_field_2' => ['value2', 'value3', 'value10'],
                'solr_field_3' => ['value4', 'value8', 'value11'],
                'solr_field_4' => ['value5', 'value9', 'value12'],
                'solr_field_5' => ['value6', 'value7', 'value13'],
                'solr_field_6' => 'value14',
                'FourthEntity' => ['value6', 'value7', 'value13'],
                'ThirdEntity' => ['value4 value5', 'value8 value9', 'value11 value12'],
                'FifthEntity' => 'value14'
            ],
            $entry->GetSolrData(new ConfigurationEntity($entityConfig), $inputData),
            "Included entities have only fields (not subentities) flattened/concatted."
        );
    }
}
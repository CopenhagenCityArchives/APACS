<?php

class ConcreteEntriesConcatDataTest extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

    // ConcatEntitiesAndData
	public function test_ConcatEntitiesAndData_SimpleEntity_ReturnConcattedData() {

        // Set entity mock and data
        $entity = EntitiesTestData::getSimpleEntity();
        
        $taskConfig = [];
        $taskConfig['entity'] = $entity;
        $entitiesCollection = new Mocks\EntitiesCollectionStub($taskConfig);

        // Input is an array of data related to en entity
        $inputData = [
            $entity['name'] => ['id' => 10, 'field1' => 'value1']
        ];

        // We expect an output consisting of various metadata
        // as well as an array of fields in the form [field_name, label, value, parent_id]
        $expectedData = [
            [
                'entity_name' => $entity['name'],
                'label' => $entity['guiName'],
                'entry_id' => -1,
                'task_id' => $taskConfig['id'],
                'concrete_entries_id' => 10,
                'fields' => [
                    [
                        'field_name' => 'field1',
                        'label' => $entity['fields'][0]['formName'],
                        'value' => $inputData[$entity['name']]['field1'],
                        'parent_id' => 10
                    ]
                ]
            ]       
        ];

        $entry = new ConcreteEntries($this->getDI(), null);
        $concattedData = $entry->ConcatEntitiesAndData($entitiesCollection, $inputData, -1);
        
        $this->assertEquals($expectedData, $concattedData);
    }
}
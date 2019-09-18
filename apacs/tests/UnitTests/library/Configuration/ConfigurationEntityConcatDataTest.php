<?php

class ConfigurationEntityConcatDataTest extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

    // GetSolrData
    public function test_ConcatDataForObjectEntity_ReturnEntityFieldsConcattedInString(){
        $entityConf = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityConf['type'] = 'object';
        $entity = new ConfigurationEntity($entityConf);
 


        $inputData = [
            'field1'=>'value1',
            'field2'=>'value2', 
            'parentEntityReferenceField'=>null
        ];

        $expectedData = 'value1 value2';

        $concattedData = $entity->ConcatDataByEntity($inputData);

        $this->assertEquals($expectedData,$concattedData);
    }

    public function test_ConcatDataForArrayEntity_ReturnEntityFieldsConcattedInArrayOfStrings(){
        $entityConf = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityConf['type'] = 'array';

        $entity = new ConfigurationEntity($entityConf);
 


        $inputData = [
            [
                'field1'=>'value1',
                'field2'=>'value2', 
                'parentEntityReferenceField'=>null
            ]
        ];

        $expectedData = ['value1 value2'];

        $concattedData = $entity->ConcatDataByEntity($inputData);

        $this->assertEquals($expectedData,$concattedData);
    }

    public function test_ConcatDataByField_ObjectEntity_ReturnRenamedDataArray(){
        $entityConf = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityConf['type'] = 'object';

        $entityConf['fields'][0]['includeInSOLR'] = 1;
        $entityConf['fields'][0]['SOLRFieldName'] = 'solrField1';

        $entityConf['fields'][1]['includeInSOLR'] = 1;
        $entityConf['fields'][1]['SOLRFieldName'] = 'solrField2';

        $entity = new ConfigurationEntity($entityConf);
 


        $inputData = [
            'field1'=>'value1',
            'field2'=>'value2', 
            'parentEntityReferenceField'=>null
        ];

        $expectedData = [
            'solrField1' => 'value1',
            'solrField2' => 'value2'
        ];

        $concattedData = $entity->ConcatDataByField($inputData);

        $this->assertEquals($expectedData,$concattedData);
    }

    public function test_ConcatDataByField_ArrayEntity_ReturnRenamedDataArray(){
        $entityConf = EntitiesTestData::getObjectEntityWithTwoFields();
        $entityConf['type'] = 'array';

        $entityConf['fields'][0]['includeInSOLR'] = 1;
        $entityConf['fields'][0]['SOLRFieldName'] = 'solrField1';

        $entityConf['fields'][1]['includeInSOLR'] = 1;
        $entityConf['fields'][1]['SOLRFieldName'] = 'solrField2';

        $entity = new ConfigurationEntity($entityConf);

        $inputData = [
            [
                'field1'=>'value1.1',
                'field2'=>'value2.1', 
                'parentEntityReferenceField'=>null
            ],
            [
                'field1'=>'value1.2',
                'field2'=>'value2.2', 
                'parentEntityReferenceField'=>null
            ]
        ];

        $expectedData = [
                'solrField1' => ['value1.1','value1.2'],
                'solrField2' => ['value2.1','value2.2']
        ];

        $concattedData = $entity->ConcatDataByField($inputData);

        $this->assertEquals($expectedData,$concattedData);
    }
}
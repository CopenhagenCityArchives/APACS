<?php

class ConfigurationEntityTest extends \UnitTestCase {

	public function setUp() : void {
		parent::setUp();

	}

	public function tearDown() : void {
		parent::tearDown();
	}

	public function test_AllEntityFieldsEmpty_EmptyDataStructure_ReturnTrue() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            'field1' => null
        ];

        $this->assertTrue($entity->AllEntityFieldsAreEmpty($emptyData));
    }
    
    public function test_isDataValid_InvalidData_ReturnFalse(){
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityConf);

        $invalidData = [
            'field1' => '3fd42'
        ];

        $this->assertFalse($entity->isDataValid($invalidData));
    }

    public function test_isDataValid_ValidData_ReturnTrue(){
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityConf);

        $validData = [
            'field1' => 2
        ];

        $this->assertTrue($entity->isDataValid($validData));
    }

    public function test_isDataValid_RequiredFieldNotSet_ReturnFalse(){
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entityConf['fields'][0]['isRequired'] = true;
        $entity = new ConfigurationEntity($entityConf);

        $validData = [
            'field1' => null
        ];

        $this->assertFalse($entity->isDataValid($validData));

        $validData = [
            'field1' => ''
        ];

        $this->assertFalse($entity->isDataValid($validData));
    }
}
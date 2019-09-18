<?php

class ConfigurationFieldsHolderTest extends \UnitTestCase {

	private $loader;
	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp();

	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_LoadFromExistingFile_ReturnConfigArray() {
		$fieldsArr = [
            [
                'fieldName' => 'field1'
            ]
        ];

        $ConfFieldHolder = new ConfigurationFieldsHolder($fieldsArr);

        $fields = $ConfFieldHolder->toArray();
        
        $this->assertEquals(count($fieldsArr), count($fields));
        
        $this->assertTrue(isset($fields[0]['fieldName']));
	}
}
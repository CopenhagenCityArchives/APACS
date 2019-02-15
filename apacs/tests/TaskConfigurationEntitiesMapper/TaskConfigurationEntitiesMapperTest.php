<?php

class TaskConfigurationEntitiesMapperTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_LoadEntities_ReturnArrayOfEntities() {
		$configArray = json_decode(file_get_contents(__DIR__ . '/entities_fields_task1.json'),true);
		$mapper = new TaskConfigurationEntitiesMapper($configArray);

		$entities = $mapper->getEntities();
		$this->assertEquals(count($entities), 5);

		$correctInterface = $entities[0] instanceof IEntitiesInfo ? true : false;
		$this->assertTrue($correctInterface);

		$correctFieldInterface = $entities[0]->getFields()[0] instanceof ConfigurationField ? true : false;
		$this->assertTrue($correctFieldInterface);
	}
}
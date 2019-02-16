<?php

class TaskConfigurationEntitiesMapperTest extends \UnitTestCase {

	private $mapper;
	private $configArray;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp();

		$configArray = json_decode(file_get_contents(__DIR__ . '/task1_config.json'),true);
		$this->mapper = new TaskConfigurationEntitiesMapper($configArray);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_GetEntities_ReturnMainEntity() {
		// Get main entity
		$mainEntity = $this->mapper->getEntities();
		
		// We expect type of main entity to implement IEntitiesInfo
		$correctInterfaceForMainEntity = $mainEntity instanceof IEntitiesInfo ? true : false;
		$this->assertTrue($correctInterfaceForMainEntity);

		// We expect that main entity has child entities
		$this->assertEquals(count($mainEntity->getEntities()), 4);
	}

	public function test_GetEntities_ReturnChildEntities(){
		// Get main entity
		$mainEntity = $this->mapper->getEntities();
		
		// We expect type of child entities to implement IEntitiesInfo
		$correctInterfaceForSecondaryEntity = $mainEntity->getEntities()[0] instanceof IEntitiesInfo ? true : false;
		$this->assertTrue($correctInterfaceForSecondaryEntity);
	}

	public function test_GetEntities_ReturnEntityWithFields(){
		// Get main entity
		$mainEntity = $this->mapper->getEntities();
		
		// We expect type of child entities to implement IEntitiesInfo
		$correctFieldInterface = $mainEntity->getEntities()[0]->getFields()[0] instanceof ConfigurationField ? true : false;
		$this->assertTrue($correctFieldInterface);
	}

	public function test_GetEntityByName_ShouldReturnEntityWithCorrectName(){
		// Get main entity
		$namedEntity = $this->mapper->getEntityByName('burials');

		$this->assertEquals('burials',$namedEntity->name);
	}
}
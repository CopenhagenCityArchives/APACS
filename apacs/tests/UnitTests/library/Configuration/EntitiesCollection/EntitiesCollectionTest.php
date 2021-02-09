<?php

class EntitiesCollectionTest extends \UnitTestCase {

	private $entitiesCollection;
	private $configArray;

	public function setUp($di = null) : void {
		parent::setUp();

		$configArray = json_decode(file_get_contents(__DIR__ . '/task1_config.json'),true);
		$this->entitiesCollection = new EntitiesCollection($configArray);
	}

	public function tearDown() : void {
		parent::tearDown();
	}

	public function test_GetEntities_ReturnMainEntity() {
		// Get main entity
		$mainEntity = $this->entitiesCollection->getEntities();
		
		// We expect type of main entity to be of type ConfigurationEntity
		$correctInterfaceForMainEntity = $mainEntity instanceof ConfigurationEntity ? true : false;
		$this->assertTrue($correctInterfaceForMainEntity);

		// We expect that main entity has child entities
		$this->assertEquals(count($mainEntity->getEntities()), 4);
	}

	public function test_GetEntities_ReturnChildEntities(){
		// Get main entity
		$mainEntity = $this->entitiesCollection->getEntities();
		
		// We expect type of child entities to be of type ConfigurationEntity
		$correctInterfaceForSecondaryEntity = $mainEntity->getEntities()[0] instanceof ConfigurationEntity ? true : false;
		$this->assertTrue($correctInterfaceForSecondaryEntity);
	}

	public function test_GetEntities_ReturnEntityWithFields(){
		// Get main entity
		$mainEntity = $this->entitiesCollection->getEntities();
		//var_dump($mainEntity->getEntities());die();
		// We expect type of child entities to implement IEntitiesInfo
		$correctFieldInterface = $mainEntity->getEntities()[0]->getFields()[0] instanceof ConfigurationField ? true : false;
		$this->assertTrue($correctFieldInterface);
	}

	public function test_GetEntityByName_ShouldReturnEntityWithCorrectName(){
		// Get main entity
		$namedEntity = $this->entitiesCollection->getEntityByName('burials');

		$this->assertEquals('burials',$namedEntity->name);
	}

	public function test_GetEntities_FlattenTree_ReturnFlatEntitiesArray(){
		$entitiesArray = $this->entitiesCollection->getEntities()->flattenTree();

		$this->assertEquals(5, count($entitiesArray));
	}
	
	public function test_GetSecondaryEntities_ReturnSecondaryEntitiesAsArray(){
		$secondaryEntities = $this->entitiesCollection->getSecondaryEntities();

		$this->assertEquals(count($this->entitiesCollection->getEntities()->flattenTree())-1, count($secondaryEntities));
		$this->assertTrue(isset($secondaryEntities[0]));
	}
}
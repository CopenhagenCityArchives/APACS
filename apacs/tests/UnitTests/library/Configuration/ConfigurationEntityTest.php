<?php

class ConfigurationEntityTest extends \UnitTestCase {

    private $taskConf;

	public function setUp($di = null) : void {
        parent::setUp();
        
        $this->taskConf = json_decode(file_get_contents(__DIR__ . '/task1_config.json'),true);
	}

	public function tearDown() : void {
		parent::tearDown();
	}

	public function test_UserEntryIsEmpty_TypeObject_EmptyDataStructure_ReturnTrue() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            'field1' => null
        ];

        $this->assertTrue($entity->UserEntryIsEmpty($emptyData));
    }

    public function test_UserEntryIsEmpty_TypeObject_WithContent_ReturnFalse() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            'field1' => "value5"
        ];

        $this->assertFalse($entity->UserEntryIsEmpty($emptyData));
    }

    public function test_UserEntryIsEmpty_TypeArray_EmptyDataStructure_ReturnTrue() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entityConf['type'] = "array";
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [[
            'field1' => null
        ]];

        $this->assertTrue($entity->UserEntryIsEmpty($emptyData));
    }

    public function test_UserEntryIsEmpty_TypeArray_EmptyDataStructure_WithSubArray_ReturnTrue() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entityConf['type'] = "array";
        $secondaryEntityConf = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntityConf['type'] = "array";
        $entityConf['entities'] = [$secondaryEntityConf];
        
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            [
                'field1' => null,
                'simpleSecondaryEntity' => [
                    [ 'field2' => null ],
                    [ 'field2' => null ]
                ],
            ],
            [
                'field1' => null,
                'simpleSecondaryEntity' => [
                    [ 'field2' => null ]
                ]
            ]
        ];

        $this->assertTrue($entity->UserEntryIsEmpty($emptyData));
    }

    public function test_UserEntryIsEmpty_TypeArray_FirstItemOneFieldSet_WithSubArray_ReturnFalse() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entityConf['type'] = "array";
        $secondaryEntityConf = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntityConf['type'] = "array";
        $entityConf['entities'] = [$secondaryEntityConf];
        
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            [
                'field1' => 'value1',
                'simpleSecondaryEntity' => [
                    [ 'field2' => null ],
                    [ 'field2' => null ]
                ],
            ],
            [
                'field1' => null,
                'simpleSecondaryEntity' => [
                    [ 'field2' => null ]
                ]
            ]
        ];

        $this->assertFalse($entity->UserEntryIsEmpty($emptyData));
    }

    public function test_UserEntryIsEmpty_TypeArray_SecondItemSet_WithSubArray_ReturnFalse() {
        $entityConf = EntitiesTestData::getSimpleEntity();
        $entityConf['type'] = "array";
        $secondaryEntityConf = EntitiesTestData::getSimpleSecondaryEntity();
        $secondaryEntityConf['type'] = "array";
        $entityConf['entities'] = [$secondaryEntityConf];
        
        $entity = new ConfigurationEntity($entityConf);

        $emptyData = [
            [
                'field1' => null,
                'simpleSecondaryEntity' => [
                    [ 'field2' => null ],
                    [ 'field2' => null ]
                ],
            ],
            [
                'field1' => 'value1',
                'simpleSecondaryEntity' => [
                    [ 'field2' => 'value2' ]
                ]
            ]
        ];

        $this->assertFalse($entity->UserEntryIsEmpty($emptyData));
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
            'field1' => "value2"
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

    public function test_getDenormalizedData() {
        $entityData = EntitiesTestData::getSimpleEntity();
        $entityData['fields'][0]['SOLRFieldName'] = "SOLRFieldName1";
        $entityData['fields'][0]['includeInSOLR'] = 1;

        $entity = new ConfigurationEntity($entityData);

        $denormalized = $entity->getDenormalizedData([ 
            "field1" => "value1"
        ]);

        $this->assertEquals(["SOLRFieldName1" => "value1"], $denormalized);
    }

    





    public function test_GetEntities_ReturnMainEntity() {
        // Get main entity
		$mainEntity = new ConfigurationEntity($this->taskConf['entity']);
		
		// We expect type of main entity to be of type ConfigurationEntity
		$correctInterfaceForMainEntity = $mainEntity instanceof ConfigurationEntity ? true : false;
		$this->assertTrue($correctInterfaceForMainEntity);

		// We expect that main entity has child entities
		$this->assertEquals(count($mainEntity->getChildren()), 4);
	}

	public function test_GetEntities_ReturnChildEntities(){
        // Get main entity
		$mainEntity = new ConfigurationEntity($this->taskConf['entity']);
		
		// We expect type of child entities to be of type ConfigurationEntity
		$correctInterfaceForSecondaryEntity = $mainEntity->getChildren()[0] instanceof ConfigurationEntity ? true : false;
		$this->assertTrue($correctInterfaceForSecondaryEntity);
	}

	public function test_GetEntities_ReturnEntityWithFields(){
        // Get main entity
		$mainEntity = new ConfigurationEntity($this->taskConf['entity']);

        // We expect type of child entities to implement IEntitiesInfo
		$correctFieldInterface = $mainEntity->getChildren()[0]->getFields()[0] instanceof ConfigurationField ? true : false;
		$this->assertTrue($correctFieldInterface);
	}

	public function test_GetEntityByName_ShouldReturnEntityWithCorrectName(){
        // Get main entity
		$mainEntity = new ConfigurationEntity($this->taskConf['entity']);
		
		$namedEntity = $mainEntity->getEntityByName('burials');

		$this->assertEquals('burials',$namedEntity->name);
	}

	public function test_GetEntities_FlattenTree_ReturnFlatEntitiesArray() {
		$mainEntity = new ConfigurationEntity($this->taskConf['entity']);
		$entitiesArray = $mainEntity->flattenTree();

		$this->assertEquals(5, count($entitiesArray));
	}
}
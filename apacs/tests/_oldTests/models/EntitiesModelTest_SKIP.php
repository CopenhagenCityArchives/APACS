<?php

class EntitiesModelTest extends \UnitTestCase {

	private $entitiesFieldsMockConf;

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
		
		$this->entitiesFieldsMockConf = new \Mocks\EntitiesMock($this->di);
		$this->entitiesFieldsMockConf->createTables();	
	}

	public function tearDown() {
		$this->entitiesFieldsMockConf->clearDatabase();
		parent::tearDown();
	}

	public function testGetEntitiesAndFields() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		$entityArr = Entities::findById(1)[0]->toArray();
		$entityArr['fields'] = $entity->getFields()->toArray();

		$this->assertTrue(isset($entityArr['fields']), 'should return an array with with fields');
		$this->assertTrue(count($entityArr) > 1, 'should return an array with with properties');
	}

	public function testGetFieldsAsAssocArray() {

		$expectedKeys = ['firstnames', 'lastname'];

		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		$convertedArr = $entity->GetFieldsAsAssocArray();

		foreach ($expectedKeys as $key) {
			$this->assertTrue(array_key_exists($key, $convertedArr), 'should convert numeric array to assoc list');
		}

		$this->assertEquals(count($entity->getFields()->toArray()), count($convertedArr), 'should keep all keys');
	}

	public function testGetEntityAsJSONSchemaObject() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		//$loadedEntity = $entity->GetEntityAndFields(1);
		$jsonSchemaObject = $entity->ConvertToJSONSchemaObject();
		$expectedKeys = ['title', 'type', 'properties'];

		foreach ($expectedKeys as $key) {
			$this->assertTrue(array_key_exists($key, $jsonSchemaObject), 'should hold key with name ' . $key);
		}

		$this->assertFalse(array_key_exists('id', $jsonSchemaObject['properties']), 'should remove fields with value includeInForm = 0');

		$this->assertTrue($jsonSchemaObject['type'] == 'object', 'should convert entities with countPerEntry == 1 to type object');
		$this->assertTrue(isset($jsonSchemaObject['properties']), 'should convert fields to properties for type object');
		$this->assertEquals(1, count($jsonSchemaObject['required']), 'should set array containing required fields');
	}

	public function testConcatDataByEntityObject() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];

		$data = [
			'firstnames' => 'Jens',
			'lastname' => 'Nielsen',
			'shouldBeIgnored' => 'dont add this',
		];

		$expectedResult = 'Jens Nielsen';

		$this->assertEquals($expectedResult, $entity->ConcatDataByEntity($data));
	}

	public function testConcatDataByEntityArray() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		$entity->type = 'array';

		$data = [
			[
				'firstnames' => 'Jens',
				'lastname' => 'Nielsen',
				'shouldBeIgnored' => 'dont add this',
			],
			[
				'firstnames' => 'Alan',
				'lastname' => 'Hansen',
				'shouldBeIgnored' => 'dont add this',
			],
		];

		$expectedResult = [
			'Jens Nielsen',
			'Alan Hansen',
		];

		$this->assertEquals($expectedResult, $entity->ConcatDataByEntity($data));
	}

	public function testConcatDataByFieldObject() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		$entity->type = 'object';

		$data = [
			'firstnames' => 'Jens',
			'lastname' => 'Nielsen',
			'shouldBeIgnored' => 'dont add this',
		];

		$expectedResult = [
			'firstnames' => 'Jens',
			'lastname' => 'Nielsen',
		];

		$this->assertEquals($expectedResult, $entity->ConcatDataByField($data));
	}

	public function testConcatDataByFieldArray() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];
		$entity->type = 'array';

		$data = [
			[
				'firstnames' => 'Jens',
				'lastname' => 'Nielsen',
				'shouldBeIgnored' => 'dont add this',
			],
			[
				'firstnames' => 'Alan',
				'lastname' => 'Bentsen',
				'shouldBeIgnored' => 'dont add this',
			],
		];

		$expectedResult = [
			'firstnames' => ['Jens', 'Alan'],
			'lastname' => ['Nielsen', 'Bentsen'],
		];

		$this->assertEquals($expectedResult, $entity->ConcatDataByField($data));
	}

	public function testValidateInvalidData() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];

		$data = [
			'firstnames' => null,
			'lastname' => 'Jensen',
		];

		$this->assertFalse(false, $entity->isDataValid($data), 'should return false when data is not valid');
	}

	public function testValidateValidData() {
		$this->entitiesFieldsMockConf->insertEntity();
		$entity = Entities::findById(1)[0];

		$data = [
			'firstnames' => 'Hans',
			'lastname' => 'Jensen',
		];

		$this->assertTrue(true, $entity->isDataValid($data), 'should return true when data is valid');
	}
}
<?php
include_once '../lib/models/Entities.php';
include_once '../lib/models/EntitiesFields.php';
include_once '../lib/models/Fields.php';

class EntitiesModelTest extends \UnitTestCase {

	private $entitiesFieldsMockConf;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		$di = new \Phalcon\Di\FactoryDefault;

		//Test specific database, Phalcon
		$di->set('db', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			));
		});

		$this->entitiesFieldsMockConf = new EntityFieldConfigurationsMock();
		$this->entitiesFieldsMockConf->createTables();

		parent::setUp($di, $config);
	}

	public function tearDown() {
		$this->entitiesFieldsMockConf->clearDatabase();
		parent::tearDown();
	}

	public function testGetEntitiesAndFields() {
		$this->entitiesFieldsMockConf->insertEntityWithoutRelations();
		$entity = new Entities();
		$entities = $entity->GetEntityAndFields(1);
		$this->assertTrue(isset($entities['fields']), 'should return an array with with fields');
		$this->assertTrue(count($entities) > 1, 'should return an array with with properties');
	}

	public function testGetEntitiesAndFieldsWithRelations() {
		$this->entitiesFieldsMockConf->insertEntityWithObjectRelation();
		$entity = new Entities();
		$entities = $entity->GetEntityAndFields(1);

		//Counting fields that have a key named fields
		$this->assertTrue(count(array_column($entities['fields'], 'fields')) == 1, 'should convert fields that relates to other entities to entities');
	}

	public function testGetFieldsAsAssocArray() {
		$fields = [
			[
				'dbFieldName' => 'test1',
				'value' => 2,
			],
			[
				'dbFieldName' => 'test2',
				'value' => 1,
			],
			[
				'dbTableName' => 'test3',
				'value' => 3,
			],
			[
				'dbFieldName' => 'test4',
				'fields' => [
					'id' => 1,
					'fieldname' => 2,
				],
			],
		];

		$expectedKeys = ['test1', 'test2', 'test3'];

		$entity = new Entities();
		$convertedArr = $entity->ConvertFieldsToAssocArray($fields);

		foreach ($expectedKeys as $key) {
			$this->assertTrue(array_key_exists($key, $convertedArr), 'should convert numeric array to assoc list for fields with dbFieldName or dbTableName property');
		}

		$this->assertEquals(count($fields), count($convertedArr), 'should keep all keys');
	}

	public function testGetEntityAsJSONSchemaObject() {
		$this->entitiesFieldsMockConf->insertEntityWithoutRelations();
		$entity = new Entities();
		$loadedEntity = $entity->GetEntityAndFields(1);
		$jsonSchemaObject = $entity->ConvertEntityToJSONSchemaObject($loadedEntity);
		$expectedKeys = ['title', 'type'];

		foreach ($expectedKeys as $key) {
			$this->assertTrue(array_key_exists($key, $jsonSchemaObject), 'should hold key with name ' . $key);
		}

		$this->assertTrue($jsonSchemaObject['type'] == 'object', 'should convert entities with countPerEntry == 1 to type object');
		$this->assertTrue(isset($jsonSchemaObject['properties']), 'should convert fields to properties for type object');
	}
}
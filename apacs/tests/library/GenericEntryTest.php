<?php
include_once '../lib/library/GenericEntry.php';
require_once './mockData/EntryConfMock.php';

class GenericEntryTest extends \UnitTestCase {

	private $ge;
	private $fields;
	private $tablename;
	protected $di;
	private $entitiesFieldsMockConf;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		$di = new \Phalcon\Di\FactoryDefault;
		$di->set('db', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			));
		}
		);

		$this->entitiesFieldsMockConf = new EntityFieldConfigurationsMock();

		$this->di = $di;
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();

		$this->ge = null;
		$this->entity = [];
	}

	public function getSimpleEntry() {
		return [
			'firstnames' => 'Jens',
			'lastname' => 'Larsen',
			'deathcause' => [
				'deathcause' => 'Hjertefejl',
			],
		];
	}

	public function getSimpleEntityWithError() {
		return [
			'firstnames' => 'Jens',
			'lastname' => '',
			'deathcause' => [
				'deathcause' => 'Hjertefejl',
			],
		];
	}

	public function testValidateEntry() {
		$this->entitiesFieldsMockConf->createTables();
		$this->entitiesFieldsMockConf->insertEntityWithObjectRelation();
		$entity = $this->entitiesFieldsMockConf->getDefaultEntity();

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$valuesAreValid = $this->ge->ValidateValues($this->getSimpleEntityWithError());

		$this->assertEquals(false, $valuesAreValid, 'should return false on invalid data');
	}

	public function testSaveEntry() {
		$this->entitiesFieldsMockConf->createTables();
		$this->entitiesFieldsMockConf->insertEntityWithObjectRelation();
		$entity = $this->entitiesFieldsMockConf->getDefaultEntity();

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$couldSave = $this->ge->Save($this->getSimpleEntry());

		$this->assertEquals(true, $couldSave, 'should save data');
	}

	public function testLoadEntry() {
		$this->entitiesFieldsMockConf->createTables();
		$this->entitiesFieldsMockConf->insertEntityWithObjectRelation();
		$entity = $this->entitiesFieldsMockConf->getDefaultEntity();

		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`) VALUES (1,'Jens','Nielsen');");

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$result = $this->ge->Load(1);
		$this->assertEquals(1, count($result), 'should return a row of data');
		$this->assertEquals(['id' => '1', 'firstnames' => 'Jens', 'lastname' => 'Nielsen'], $result[0], 'should return values of type value');
	}

	public function testUpdateEntry() {
		$this->entitiesFieldsMockConf->createTables();
		$this->entitiesFieldsMockConf->insertEntityWithObjectRelation();
		$entity = $this->entitiesFieldsMockConf->getDefaultEntity();

		//Inserting data to update
		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`) VALUES (1,'Jens','Nielsen');");

		//Updating
		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$updatedValues = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen', 'deathcause' => 'shouldnt have effect'];
		$this->ge->Update($updatedValues);

		$expectedValuesAfterUpdate = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen'];

		//Loading result
		$result = $this->di->get('db')->query("SELECT id,firstnames,lastname FROM `begrav_persons` WHERE id = 1;");
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$this->assertEquals($expectedValuesAfterUpdate, $result->fetchAll()[0], 'should update existing values');
	}
}
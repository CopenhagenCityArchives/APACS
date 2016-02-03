<?php
include_once '../lib/library/GenericEntry.php';

class GenericEntryTest extends \UnitTestCase {

	private $ge;
	private $fields;
	private $tablename;
	protected $di;
	private $entitiesMock;
	private $entriesMock;

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

		$this->entitiesMock = new Mocks\EntitiesMock();
		$this->entitiesMock->createTables();

		$this->entriesMock = new Mocks\EntriesMock();
		$this->entriesMock->createTables();

		$this->di = $di;
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();

		$this->ge = null;
		$this->entity = [];

		$this->entitiesMock->clearDatabase();
		$this->entriesMock->clearDatabase();
	}

	public function getSimpleEntry() {
		return [
			'firstnames' => 'Jens',
			'lastname' => 'Larsen',
			'begrav_deathcauses' => 1,
			'entry_id' => 1,
		];
	}

	public function getSimpleEntityWithError() {
		return [
			'firstnames' => 'Jens',
			'lastname' => '',
			'begrav_deathcauses' => [
				'begrav_deathcauses' => 'Hjertefejl',
			],
			'entry_id' => 1,
		];
	}

	public function testValidateEntry() {
		$this->entitiesMock->insertEntityWithObjectRelation();
		$entity = $this->entitiesMock->getDefaultEntity();

		$this->ge = new GenericEntry($entity, $entity['fields'], $this->di->get('db'));
		$valuesAreValid = $this->ge->ValidateValues($this->getSimpleEntityWithError());

		$this->assertEquals(false, $valuesAreValid, 'should return false on invalid data');
	}

	public function testSaveEntry() {
		$this->entitiesMock->insertEntityWithObjectRelation();
		$entity = $this->entitiesMock->getDefaultEntity();

		$this->ge = new GenericEntry($entity, $entity['fields'], $this->di->get('db'));
		$couldSave = $this->ge->Save($this->getSimpleEntry());

		$this->assertEquals(true, $couldSave, 'should save data');
	}

	public function testLoadEntry() {
		$this->entitiesMock->insertEntityWithObjectRelation();
		$entity = $this->entitiesMock->getDefaultEntity();

		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`, `entry_id`) VALUES (1,'Jens','Nielsen',1);");

		$this->ge = new GenericEntry($entity, $entity['fields'], $this->di->get('db'));
		$result = $this->ge->Load(1);
		$this->assertEquals(1, count($result), 'should return a row of data');
		$this->assertEquals(['id' => '1', 'firstnames' => 'Jens', 'lastname' => 'Nielsen', 'entry_id' => 1], $result[0], 'should return values of type value');
	}

	public function testUpdateEntry() {
		$this->entitiesMock->insertEntityWithObjectRelation();
		$entity = $this->entitiesMock->getDefaultEntity();

		//Inserting data to update
		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`, `entry_id`) VALUES (1,'Jens','Nielsen', 1);");

		//Updating
		$this->ge = new GenericEntry($entity, $entity['fields'], $this->di->get('db'));
		$updatedValues = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen', 'deathcauses' => 'shouldnt have effect', 'entry_id' => 1];
		$this->ge->Update($updatedValues);

		$expectedValuesAfterUpdate = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen', 'entry_id' => 1];

		//Loading result
		$result = $this->di->get('db')->query("SELECT id,firstnames,lastname, entry_id FROM `begrav_persons` WHERE id = 1;");
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$this->assertEquals($expectedValuesAfterUpdate, $result->fetchAll()[0], 'should update existing values');
	}
}
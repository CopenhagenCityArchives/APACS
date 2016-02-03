<?php
include_once '../lib/models/Entries.php';
include_once '../lib/models/Entities.php';

class EntriesModelTest extends \UnitTestCase {

	private $entitiesMock;
	private $entriesMock;

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

		$this->entitiesMock = new Mocks\EntitiesMock();
		$this->entitiesMock->createTables();

		$this->entriesMock = new Mocks\EntriesMock();
		$this->entriesMock->createTables();
		parent::setUp($di, $config);
	}

	public function tearDown() {
		$this->entitiesMock->clearDatabase();
		$this->entriesMock->clearDatabase();
		parent::tearDown();
	}

	public function testSave() {
		$values = [
			'firstnames' => 'Niels',
			'lastname' => 'Jensen',
			'begrav_deathcauses' => [
				'id' => 1,
				'begrav_deathcauses' => 'Lungebetændelse',
			],
			'entry_id' => 1,
		];

		$expectedValuesAfterUpdate = [
			'firstnames' => 'Niels',
			'lastname' => 'Jensen',
			'begrav_deathcauses' => 1,
			'entry_id' => '1',
		];

		$this->entitiesMock->insertEntityWithObjectRelation();

		$entity = $this->entitiesMock->getDefaultEntity();

		Entries::SaveEntryRecursively($entity, $values, $this->getDI()->get('db'));

		$result = $this->getDI()->get('db')->query('SELECT firstnames, lastname, begrav_deathcauses, entry_id FROM ' . $entity['dbTableName'] . ' WHERE id = 1');
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$this->assertEquals($expectedValuesAfterUpdate, $result->fetchAll()[0], 'should save data in database');
	}

	/**
	 * @expectedException Exception
	 */
	public function testValidation() {
		$values = [
			'firstnames' => null,
			'lastname' => null,
			'begrav_deathcauses' => [
				'id' => 1,
				'begrav_deathcauses' => 'test',
			],
			'entry_id' => 1,
		];

		$this->entitiesMock->insertEntityWithObjectRelation();

		$entity = $this->entitiesMock->getDefaultEntity();

		//Should throw exception when trying to save invalid data
		Entries::SaveEntryRecursively($entity, $values, $this->getDI()->get('db'));
	}

	public function testLoadEntry() {
		$this->entitiesMock->createTables();
		$this->entitiesMock->insertEntityWithObjectRelation();
		$entity = $this->entitiesMock->getDefaultEntity();
		$this->entriesMock->createEntryWithObjectRelation();

		$this->assertEquals(
			$this->entriesMock->getEntryWithObjectRelation(),
			Entries::LoadEntryRecursively('entry_id', 1, $entity, $this->getDI()->get('db')),
			'should return a complete array of the saved entry'
		);
	}
}
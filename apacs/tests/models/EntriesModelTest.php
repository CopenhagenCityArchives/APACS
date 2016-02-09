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

		//Config
		$di->set('config', function () {
			return [
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			];
		});

		$this->entitiesMock = new Mocks\EntitiesMock();
		$this->entitiesMock->createTables();

		$this->entriesMock = new Mocks\EntriesMock();
		$this->entriesMock->createTables();
		parent::setUp($di, $config);
	}

	public function tearDown() {
		//	$this->entitiesMock->clearDatabase();
		$this->entriesMock->clearDatabase();
		parent::tearDown();
	}

	public function testSave() {
		$this->entitiesMock->insertEntity();
		$entry = new Entries();
		$dataToSave = [
			'firstnames' => 'Jens',
			'lastname' => 'Jensen',
		];

		$entry->SaveEntry($this->entitiesMock->getEntity(), $dataToSave);

		$resultSet = $this->getDI()->get('db')->query('SELECT firstnames, lastname FROM burial_persons');
		$resultSet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$savedData = $resultSet->fetchAll()[0];
		$this->assertEquals($savedData, $dataToSave);
	}

	/*public function testSaveArray() {
		$this->entitiesMock->insertEntity();
		$entry = new Entries();
		$dataToSave = [
			[
				'firstnames' => 'Jens',
				'lastname' => 'Jensen',
			],
			[
				'firstnames' => 'Jens',
				'lastname' => 'Jensen',
			],
		];

		$entry->SaveEntry($this->entitiesMock->getEntity(), $dataToSave);

		$resultSet = $this->getDI()->get('db')->query('SELECT firstnames, lastname FROM burial_persons');
		$this->assertEquals(2, count($resultSet->fetchAll()));
	}*/

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveThrowErrorWhenEntityKeyNameValueIsNotSet() {
		$this->entitiesMock->insertEntity();
		$entry = new Entries();
		$dataToSaveWrong = [
			'persons_id' => null,
			'deathcause' => 'lungebetændelse',
		];

		//This should throw an exception, as the EntityKeyName is null
		$entry->SaveEntry($this->entitiesMock->getEntity(2), $dataToSaveWrong);
	}

	public function testSaveDecodeFields() {
		$this->entitiesMock->insertEntity();
		$entry = new Entries();
		$dataToSave = [
			'persons_id' => 1,
			'deathcause' => 'lungebetændelse',
		];

		$expectedResult = [
			'persons_id' => '1',
			'deathcauses_id' => '1',
		];

		$id = $entry->SaveEntry($this->entitiesMock->getEntity(2), $dataToSave);

		$this->assertTrue(is_numeric($id), 'should return id when saving entry');

		$resultSet = $this->getDI()->get('db')->query('SELECT persons_id, deathcauses_id FROM burial_persons_deathcauses');
		$resultSet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$savedData = $resultSet->fetchAll()[0];
		$this->assertEquals($expectedResult, $savedData);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveEntryThrowErrorOnEmptyData() {
		$this->entitiesMock->insertEntity();
		$entry = new Entries();
		$entry->SaveEntries([$this->entitiesMock->getEntity()], []);
	}

	public function testSaveEntries() {
		$this->entitiesMock->insertEntity();

		$data = [
			'persons' => [
				'firstnames' => 'Niels',
				'lastname' => 'Hansen',
				'deathcauses' => [
					'deathcause' => 'lungebetændelse',
				],
			],
		];
		$entry = new Entries();

		$this->assertTrue($entry->SaveEntries([0 => $this->entitiesMock->getEntity()], $data), 'should save data');

		$result = $this->di->get('db')->query('select * from burial_persons WHERE 1');
		$this->assertEquals(1, count($result), 'should save main entity');
		$result2 = $this->di->get('db')->query('select * from burial_deathcauses WHERE 1');
		$this->assertEquals(1, count($result2), 'should save related data');
	}

/*
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
/*	public function testValidation() {
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
}*/
}
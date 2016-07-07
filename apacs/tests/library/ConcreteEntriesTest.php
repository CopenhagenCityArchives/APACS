<?php

class ConcreteEntriesTest extends \UnitTestCase {

	private $entitiesMock;
	private $entriesMock;

	public function setUp() {
		parent::setUp();
		
		$this->di->set('config', function () {
			return [
				"host" => "database",
				"username" => "dev",
				"password" => "123456",
				"dbname" => "apacs",
				'charset' => 'utf8',
			];
		});

		$this->entitiesMock = new Mocks\EntitiesMock($this->di);
		$this->entitiesMock->insertEntities();

		$this->entriesMock = new Mocks\EntriesMock($this->di);
	}

	public function tearDown() {
		$this->entitiesMock->clearDatabase();
		//$this->entriesMock->clearDatabase();
		parent::tearDown();
	}

	public function testSave() {
		$entry = new ConcreteEntries($this->di);
		$dataToSave = [
			'firstnames' => 'Jens',
			'lastname' => 'Jensen',
		];

		$entry->Save($this->entitiesMock->getEntity(), $dataToSave);

		$resultSet = $this->getDI()->get('db')->query('SELECT firstnames, lastname FROM burial_persons');
		$resultSet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$savedData = $resultSet->fetchAll()[0];
		$this->assertEquals($savedData, $dataToSave);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveThrowErrorWhenEntityKeyNameValueIsNotSet() {
		$entry = new ConcreteEntries($this->di);
		$dataToSaveWrong = [
			'persons_id' => null,
			'deathcause' => 'lungebetændelse',
		];

		//This should throw an exception, as the EntityKeyName is null
		$entry->Save($this->entitiesMock->getEntity(2), $dataToSaveWrong);
	}

	public function testSaveDecodeFields() {
		$entry = new ConcreteEntries($this->di);
		$dataToSave = [
			'persons_id' => 1,
			'deathcause' => 'lungebetændelse',
		];

		$expectedResult = [
			'persons_id' => '1',
			'deathcauses_id' => '1',
		];

		$id = $entry->Save($this->entitiesMock->getEntity(2), $dataToSave);

		$this->assertTrue(is_numeric($id), 'should return id when saving entry');

		$resultSet = $this->getDI()->get('db')->query('SELECT persons_id, deathcauses_id FROM burial_persons_deathcauses');
		$resultSet->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$savedData = $resultSet->fetchAll()[0];
		$this->assertEquals($expectedResult, $savedData);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveThrowErrorOnEmptyData() {
		$entry = new ConcreteEntries($this->di);
		$entry->SaveEntriesForTask([$this->entitiesMock->getEntity()], []);
	}

	public function testSaveEntriesForTask() {
		$data = [
			'persons' => [
				'firstnames' => 'Niels',
				'lastname' => 'Hansen',
				'deathcauses' => [
					'deathcause' => 'lungebetændelse',
				],
			],
		];
		$entry = new ConcreteEntries($this->di);

		$this->assertTrue(is_numeric($entry->SaveEntriesForTask([0 => $this->entitiesMock->getEntity()], $data)), 'should return insert id of the primary entity');

		$result = $this->di->get('db')->query('select * from burial_persons WHERE 1');
		$this->assertEquals(1, count($result), 'should save main entity');
		$result2 = $this->di->get('db')->query('select * from burial_deathcauses WHERE 1');
		$this->assertEquals(1, count($result2), 'should save related data');
	}

	public function testConvertToSolr() {
		$taskId = 1;

		$data = [
			'persons' => [
				'firstnames' => 'Jens',
				'lastname' => 'Hansen',
				'deathcauses' => [
					['deathcause' => 'lungebetændelse'],
					['deathcause' => 'hjertestop'],
				],
			],
		];

		$solrData = [
			'persons' => 'Jens Hansen',
			'firstnames' => 'Jens',
			'lastname' => 'Hansen',
			'deathcauses' => ['lungebetændelse', 'hjertestop'],
		];

		$post = new ConcreteEntries($this->di);
		$resultSet = Entities::find(['conditions' => 'task_id = ' . '1']);
		$entities = [];
		foreach ($resultSet as $entity) {
			$entities[] = $entity;
		}

		$this->assertEquals($solrData, $post->GetSolrData($entities, $data), 'should convert data to SOLR');
	}

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

		$entity = $this->entitiesMock->getEntity();

		$this->assertFalse($entity->isDataValid(), 'should return false when data is invalid');
	}

	public function testLoad() {
		$this->entriesMock->createEntryWithObjectRelation();

		$entity = $this->entitiesMock->getEntity(2);

		$entry = new ConcreteEntries($this->di);
		$this->assertTrue(count($entry->Load($entity, 'id', 1)) > 0, 'should return array of data');
	}
}
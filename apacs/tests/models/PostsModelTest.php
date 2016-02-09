<?php

class PostsModelTest extends \UnitTestCase {

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
		$this->entitiesMock->clearDatabase();
		$this->entriesMock->clearDatabase();
		parent::tearDown();
	}

	public function testSavePost() {
		$this->entitiesMock->insertEntity();

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

		$post = new Posts();

		$post->SaveEntries(1, $taskId, $data);

		$resultSet = $this->getDI()->get('db')->query('SELECT firstnames, lastname FROM burial_persons');
		$this->assertEquals(1, count($resultSet->fetchAll()), 'should save main entry');

		$resultSet = $this->getDI()->get('db')->query('SELECT * FROM burial_persons_deathcauses');
		$this->assertEquals(2, count($resultSet->fetchAll()), 'should save array type entry');
	}

	public function testConvertToSolr() {
		$this->entitiesMock->insertEntity();

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

		$post = new Posts();
		$resultSet = Entities::find(['conditions' => 'task_id = ' . '1']);
		$entities = [];
		foreach ($resultSet as $entity) {
			$entities[] = $entity;
		}

		$this->assertEquals($solrData, $post->GetSolrData($entities, $data), 'should convert data to SOLR');
	}
}
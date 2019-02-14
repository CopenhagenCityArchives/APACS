<?php

class IndexDataControllerTest extends \UnitTestCase {

	private $entitiesMock;
	private $entriesMock;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);

		$this->di->set('configuration', function () {
			$conf = new TaskConfigurationLoader('./Mocks/MockCollectionsConfiguration.php');
			return $conf;
		});

		//Test specific database, Phalcon
		$this->di->setShared('db', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			));
		}
		);

		$this->entitiesMock = new Mocks\EntitiesMock($di);
		$this->entitiesMock->insertEntities();

		$this->entriesMock = new Mocks\EntriesMock();

		parent::setUp($di, $config);
	}

	public function tearDown() {
		$this->entitiesMock->clearDatabase();
		$this->entriesMock->clearDatabase();
		parent::tearDown();
	}

	public function testSaveEntryThrowErrorOnEmptyData() {
		$ctrl = new IndexDataController();
		$ctrl->SaveEntry(1);
		$this->assertEquals('401 Input error', $this->di->get('response')->getStatusCode(), 'should return 401 when no error is given');
	}

	public function testSaveEntryThrowErrorOnInvalidTask() {
		$ctrl = new IndexDataController();
		$ctrl->SaveEntry(9999);
		$this->assertEquals('401 Input error', $this->di->get('response')->getStatusCode(), 'should return 401 when no error is given');
	}

	public function testSaveEntryOnInvalidData() {
		$this->entitiesMock->insertEntity();

		$data = [
			'persons' => [
				'firstnames' => 'niels',
				'lastname' => 'hansen',
				'deathcauses' => null,
			],
		];

		$raw = json_encode($data);

		$ctrl = new IndexDataController();
		$ctrl->SaveEntry(1);
		$this->assertEquals('401 Input error', $this->di->get('response')->getStatusCode(), 'should return 401 when data is invalid');
	}

	/*public function testSaveEntry() {
		$this->entriesMock->createEntryWithObjectRelation();
		$this->entitiesMock->insertEntity();

		$data = [
			'persons' => [
				'firstnames' => 'niels',
				'lastname' => 'jensen',
			],
			'deathcauses' => [
				'deathcause' => 'lungebetændelse',
			],
		];

		$raw = json_encode($data);

		$mock = $this->getMock("\\Phalcon\\Http\\Request", array("getRawBody"));
		$mock->expects($this->once())
			->method("getRawBody")
			->will($this->returnValue($raw));

		$this->di->set('request', $mock, true);

		$ctrl = new IndexDataController();
		$ctrl->SaveEntry(1);
		//	var_dump($this->di->get('response')->getContent());
		$this->assertEquals('200 OK', $this->di->get('response')->getStatusCode());
	}*/
}
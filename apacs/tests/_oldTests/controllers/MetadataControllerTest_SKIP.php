<?php

class MetadataControllerTest extends \UnitTestCase {

	private $_controller;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);

		$this->di->set('configuration', function () {
			$conf = new ConfigurationLoader('./Mocks/MockCollectionsConfiguration.php');
			return $conf;
		});

		//Test specific database, Phalcon
		$this->di->set('database', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			));
		}
		);

		parent::setUp($di, $config);

		$this->_controller = new MetadataLevelsController();
	}

	public function tearDown() {
		$this->getDI()->get('db')->query('delete from PRB_registerblade');
		$this->getDI()->get('response')->setContent(null);
		unset($_GET);
		parent::tearDown();
		$this->_controller = null;
	}

	public function testInitialization() {
		$this->assertInstanceOf('MetadataLevelsController', $this->_controller);
	}

	public function testResponseOnFalseRequests() {
		$this->setExpectedException('Exception');
		$this->_controller->getMetadataLevels(false, false);
	}

	public function testGetObjectData() {
		$_GET['station'] = 1;
		$this->getDI()->get('db')->query('insert into PRB_registerblade (id, station) values (1,1)');

		$this->_controller->getObjectData(1);
		$this->assertEquals(1, count(json_decode($this->getDI()->get('response')->getContent())), 'should return an array of data');
	}

	public function testGetDataById() {
		$_GET['id'] = 10;
		$this->getDI()->get('db')->query('insert into PRB_registerblade (id, station) values (10,100)');

		$this->_controller->getObjectData(1);

		$this->assertEquals(1, count(json_decode($this->getDI()->get('response')->getContent())), 'should return an array of data');
	}
}
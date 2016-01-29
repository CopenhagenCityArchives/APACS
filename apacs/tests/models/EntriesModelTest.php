<?php
include_once '../lib/models/Entries.php';
include_once '../lib/models/Entities.php';

class EntriesModelTest extends \UnitTestCase {

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

		parent::setUp($di, $config);
	}

	public function tearDown() {
		$this->entitiesFieldsMockConf->clearDatabase();
		parent::tearDown();
	}

	public function testSave() {
		$values = [
			'firstname' => 'Niels',
			'lastname' => 'Jensen',
			'deathcause' => [
				'id' => 1,
				'deathcause' => 'Lungebetændelse',
			],
		];

		$entity = $this->entitiesFieldsMockConf->getDefaultEntity();
		Entries::SaveEntryRecursively($entity['table'], $entity['fields'], $values, $this->getDI()->get('db'));

		$result = $this->getDI()->get('db')->query('SELECT * FROM tableName WHERE id = 1');
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$this->assertEquals($expectedValuesAfterUpdate, $result->fetchAll()[0], 'should save data in database');
	}
}
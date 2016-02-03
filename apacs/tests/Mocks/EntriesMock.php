<?php
namespace Mocks;

class EntriesMock {
	private $di;
	public function __construct() {
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

		$this->di = $di;
	}

	public function clearDatabase() {
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_persons');
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_deathcauses');
	}

	public function createTables() {
		//Concrete table begrav_person
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_persons');
		$this->di->get('db')->query("CREATE TABLE `begrav_persons` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `firstnames` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `lastname` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `begrav_deathcauses` int(11) DEFAULT NULL,
			  `entry_id` int(11) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

		//Concrete table begrav_deathcause
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_deathcauses');
		$this->di->get('db')->query("CREATE TABLE `begrav_deathcauses` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `begrav_deathcauses` varchar(125) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);
	}

	public function createEntryWithObjectRelation() {
		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`, `begrav_deathcauses`,`entry_id`) VALUES (1,'Jens','Nielsen',1,1);");
		$this->di->get('db')->query("INSERT INTO `begrav_deathcauses` (`id`, `begrav_deathcauses`) VALUES (1,'lungebetændelse');");
	}

	public function getEntryWithObjectRelation() {
		return [
			'id' => '1',
			'firstnames' => 'Jens',
			'lastname' => 'Nielsen',
			'begrav_deathcauses' => [
				'id' => 1,
				'begrav_deathcauses' => 'lungebetændelse',
			],
			'entry_id' => 1,
		];
	}
}
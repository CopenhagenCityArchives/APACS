<?php
namespace Mocks;

class EntriesMock {
	private $di;
	public function __construct($di) {
		$this->di = $di;
	}

	public function clearDatabase() {
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_persons');
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_deathcauses');
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_persons_deathcauses');
	}

	public function createTables() {
		//Concrete table burial_persons
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_persons');
		$this->di->get('db')->query("CREATE TABLE `burial_persons` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `firstnames` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `lastname` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `burial_deathcauses` int(11) DEFAULT NULL,
			  `entry_id` int(11) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

		//Concrete table burial_persons_deathcauses
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_persons_deathcauses');
		$this->di->get('db')->query("CREATE TABLE `burial_persons_deathcauses` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `persons_id` int(11) DEFAULT NULL,
			  `deathcauses_id` int(11) DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

		//Concrete table burial_deathcauses
		$this->di->get('db')->query('DROP TABLE IF EXISTS burial_deathcauses');
		$this->di->get('db')->query("CREATE TABLE `burial_deathcauses` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `deathcause` varchar(125) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);
	}

	public function createEntryWithObjectRelation() {
		$this->di->get('db')->query("INSERT INTO `burial_persons` (`id`, `firstnames`, `lastname`, `burial_deathcauses`,`entry_id`) VALUES (1,'Jens','Nielsen',1,1);");
		$this->di->get('db')->query("INSERT INTO `burial_persons_deathcauses` (`persons_id`, `deathcauses_id`) VALUES (1,1);");
		$this->di->get('db')->query("INSERT INTO `burial_deathcauses` (`id`, `deathcause`) VALUES (1,'lungebetændelse');");
	}

	public function getEntryWithObjectRelation() {
		return [
			'id' => '1',
			'firstnames' => 'Jens',
			'lastname' => 'Nielsen',
			'burial_deathcauses' => [
				'id' => 1,
				'burial_deathcauses' => 'lungebetændelse',
			],
			'entry_id' => 1,
		];
	}
}
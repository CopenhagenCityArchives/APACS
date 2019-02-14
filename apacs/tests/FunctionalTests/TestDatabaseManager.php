<?php
namespace Mocks;

class TestDatabaseManager {
	private $di;
	private $apacsStructureCreated = false;
	private $burialsStructureCreated = false;

	public function __construct($di) {
		$this->di = $di;
	}

	public function createApacsStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/apacs-structure-setup.sql'));
		$this->apacsStructureCreated = true;
	}

	public function cleanUpApacsStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/apacs-structure-cleanup.sql'));
	}

	public function createEntitiesAndFieldsForTask1(){
		if(!$this->apacsStructureCreated){
			$this->createApacsStructure();
		}

		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/apacs-data-setup.sql'));
	}
}
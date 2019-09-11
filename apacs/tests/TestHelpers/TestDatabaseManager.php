<?php
namespace Mocks;

class TestDatabaseManager {
	private $di;
	private $apacsStructureCreated = false;
	private $burialsStructureCreated = false;

	public function __construct($di) {
		$this->di = $di;

		if($this->di->get('config')['host'] !== 'database'){
			throw new Exception("trying to connect to a database other than the test database. This will cause data loss in the database. Aborting.");
		}
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

		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/apacs-task1-config.sql'));
	}

	public function createApacsMetadataForEntryPost10000Task1(){
		if(!$this->apacsStructureCreated){
			$this->createApacsStructure();
		}
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/apacs-task1-entry-post-10000.sql'));
	}

	public function createBurialDataForEntryPost1000Task1(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/burials-structure-and-data-single-person.sql'));
	}

	public function cleanUpBurialStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/burials-structure-and-data-cleanup.sql'));
	}

	public function refreshEntryForPost1000() {
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/burials-refresh-post-entries.sql'));
	}
	
}
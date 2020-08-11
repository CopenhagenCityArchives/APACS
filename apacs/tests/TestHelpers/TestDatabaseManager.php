<?php
namespace Mocks;

class TestDatabaseManager {
	private $di;
	private $apacsStructureCreated = false;
	private $burialsStructureCreated = false;

	public function __construct($di) {
		$this->di = $di;

		if($this->di->get('config')['host'] !== 'mysql-tests'){
			throw new Exception("trying to connect to a database other than the test database. This will cause data loss in the database. Aborting.");
		}
	}

	public function query($sql) {
		return $this->di->get('db')->query($sql);
	}

	public function createApacsStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-apacs-structure-setup.sql'));
		$this->apacsStructureCreated = true;
	}

	public function cleanUpApacsStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-apacs-structure-cleanup.sql'));
	}

	public function createEntitiesAndFieldsForTask1(){
		if(!$this->apacsStructureCreated){
			$this->createApacsStructure();
		}

		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-apacs-task1-config.sql'));
	}

	public function createApacsMetadataForEntryPost10000Task1(){
		if(!$this->apacsStructureCreated){
			$this->createApacsStructure();
		}
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-apacs-task1-entry-post-10000.sql'));
	}

	public function createBurialDataForEntryPost1000Task1(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-burials-structure-and-data-single-person.sql'));
	}

	public function cleanUpBurialStructure(){
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-burials-structure-and-data-cleanup.sql'));
	}

	public function refreshEntryForPost1000() {
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/99-burials-refresh-post-entries.sql'));
	}
	
	public function createDataListEventsStructure() {
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/0-Datalist-Events-structure.sql'));

	}

	public function createEventEntries() {
		$this->di->get('db')->query(file_get_contents(__DIR__ . '/db-test-data/1-apacs-events-create-entries.sql'));
	}
}
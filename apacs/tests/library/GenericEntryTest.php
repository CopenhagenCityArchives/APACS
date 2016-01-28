<?php

class GenericEntryTest extends \UnitTestCase {

	private $ge;
	private $fields;
	private $tablename;
	protected $di;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
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
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
		$this->di->get('db')->query('DROP TABLE IF EXISTS entry_table');
		$this->di->get('db')->query('DROP TABLE IF EXISTS normalized_table');

		$this->ge = null;
		$this->entity = [];
	}

	public function createTables() {
		$this->di->get('db')->query('DROP TABLE IF EXISTS entry_table');
		$this->di->get('db')->query('DROP TABLE IF EXISTS normalized_table');
		$this->di->get('db')->query('CREATE TABLE entry_table (id INT(11) NOT NULL AUTO_INCREMENT, testFieldDb CHAR(50), normalizedFieldDb CHAR(50), post_id INT(11), PRIMARY KEY (`id`))');
		$this->di->get('db')->query('CREATE TABLE normalized_table (id INT(11) NOT NULL AUTO_INCREMENT, normalized_field CHAR(50), PRIMARY KEY (`id`))');
	}

	public function setSingleFieldAndTable() {

		$this->entity = [
			'tablename' => 'entry_table',
			'fields' => [
				[
					'name' => 'testField',
					'dbFieldName' => 'testFieldDb',
					'type' => '1',
					'codeTable' => null,
					'codeField' => null,
					'codeAllowNewValue' => null,
					'required' => true,
					'validationRegularExpression' => '/^\w{2,}$/',
					'validationErrorMessage' => 'Minimum to tegn',
					'maxLength' => 150,
				],
			],
		];
	}

	public function setNormalizedFieldsAndTable() {
		$this->entity = [
			'tablename' => 'entry_table',
			'fields' => [
				[
					'name' => 'testField',
					'dbFieldName' => 'testFieldDb',
					'type' => '1',
					'codeTable' => null,
					'codeField' => null,
					'codeAllowNewValue' => null,
					'required' => true,
					'validationRegularExpression' => '/^\w{2,}$/',
					'validationErrorMessage' => 'Minimum to tegn',
					'maxLength' => 150,
				],
				[
					'name' => 'normalizedField',
					'dbFieldName' => 'normalizedFieldDb',
					'type' => '1:m',
					'codeTable' => 'normalized_table',
					'codeField' => 'normalized_field',
					'codeAllowNewValue' => true,
					'required' => true,
					'validationRegularExpression' => '/^\w{2,}$/',
					'validationErrorMessage' => 'Minimum to tegn',
					'maxLength' => 150,
				],
			],
		];
	}

	public function invalidInputValues() {
		return [
			[
				'testFieldDb' => '',
				'normalizedFieldDb' => 'valueToNormalize',
			],
		];
	}

	public function inputValues() {
		return [
			[
				'testFieldDb' => 'testValue',
				'normalizedFieldDb' => 'valueToNormalize',
			],
		];
	}

	public function inputValuesDecodingExistingValues() {
		return [
			[
				'testFieldDb' => 'testValue',
				'normalizedFieldDb' => 'existing_value',
			],
		];
	}

	public function inputValuesDecodingNonexistingValues() {
		return [
			[
				'testFieldDb' => 'testValue',
				'normalizedFieldDb' => 'this does not exist',
			],
		];
	}

	public function testValidatation() {
		$this->createTables();
		$this->setSingleFieldAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->assertEquals(false, $this->ge->Save($this->invalidInputValues()), 'should return false when data is invalid');
	}

	public function testSaving() {
		$this->createTables();
		$this->setSingleFieldAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->assertEquals(true, $this->ge->Save($this->inputValues()), 'should return true when saving is done');
	}

	public function testSavedData() {
		$this->createTables();
		$this->setSingleFieldAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));

		$this->ge->save($this->inputValues());

		$resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $resultSet->fetchAll();

		$this->assertEquals('testValue', $results[0]['testFieldDb'], 'should insert data');
	}

	public function testValidationLogicOnNormalization() {
		$this->setNormalizedFieldsAndTable();
		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));

		$this->assertEquals(true, $this->ge->ValidateValues($this->inputValues()[0]), 'should ignore validation errors for normalization fields');
	}

	public function testSavingNormalized() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->ge->Save($this->inputValues());
		var_dump($this->ge->GetErrorMessages());
//		$this->assertEquals(true, , 'should return true when saving normalized fields');
	}

	public function testSavedNormalizedDataNewValue() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));

		$this->assertEquals(true, $this->ge->Save($this->inputValues()), 'should save the normalized value in the normalization table');

		$resultSet = $this->di->get('db')->query('select * from normalized_table limit 1');
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $resultSet->fetchAll();

		$this->assertEquals('valueToNormalize', $results[0]['normalized_field'], 'should save the normalized value in the normalization table');

		$resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$entryResults = $resultSet->fetchAll();

		$this->assertEquals($results[0]['id'], $entryResults[0]['normalizedFieldDb'], 'the inserted id should match the idin the normalization table');
	}

	public function testSavingNormalizedExistingValue() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->di->get('db')->execute('INSERT INTO normalized_table (normalized_field) VALUES ("existing_value")');

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->assertEquals(true, $this->ge->Save($this->inputValuesDecodingExistingValues()), 'should return true when saving is done');

		$resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $resultSet->fetchAll();

		$this->assertEquals(1, $results[0]['normalizedFieldDb'], 'should use the correct id from the normalization table');
	}

	public function testSavedNormalizedDataNewValuesNotAllowed() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();
		$this->entity['fields'][1]['codeAllowNewValue'] = false;

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));

		$this->assertEquals(false, $this->ge->Save($this->inputValuesDecodingNonexistingValues()), 'should return false when normalized data does not exist');
	}

	public function testGetData() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->ge->Save($this->inputValues());
		$data = $this->ge->Load(1);

		$this->assertEquals(1, count($data), 'should return array with fields and their values');
		$this->assertEquals('testValue', $data[0][$this->entity['fields'][0]['dbFieldName']], 'should save data');
	}

	public function testLoadData() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->di->get('db')->execute('INSERT INTO entry_table (testFieldDb) VALUES ("new_value")');

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->assertEquals(1, count($this->ge->Load(1)), 'should load row of data by id');
	}

	public function testLoadedDataStructure() {
		$this->createTables();
		$this->setNormalizedFieldsAndTable();

		$this->di->get('db')->execute('INSERT INTO entry_table (testFieldDb) VALUES ("new_value")');

		$this->ge = new GenericEntry($this->entity['tablename'], $this->entity['fields'], $this->di->get('db'));
		$this->assertEquals(['testFieldDb' => 'new_value'], $this->ge->Load(1)[0], 'should load row of data by id');
	}
}
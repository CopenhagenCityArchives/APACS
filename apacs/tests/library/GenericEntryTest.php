<?php

include '../lib/library/GenericEntry.php';

class GenericEntryTest extends \UnitTestCase {
    
    private $ge;
    private $fields;
    private $tablename;
    protected $di;

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault; 
        $di->set('db', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "root",
                "password" => "",
                "dbname" => "unit_tests",
                'charset' => 'utf8'
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
       	$_GET = array();
        $this->ge = null;
    }

    public function createTables()
    {
       $this->di->get('db')->query('DROP TABLE IF EXISTS entry_table');
       $this->di->get('db')->query('DROP TABLE IF EXISTS normalized_table');
       $this->di->get('db')->query('CREATE TABLE entry_table (id INT(11) NOT NULL AUTO_INCREMENT, testFieldDb CHAR(50), normalizedFieldDb CHAR(50), PRIMARY KEY (`id`))');
       $this->di->get('db')->query('CREATE TABLE normalized_table (id INT(11) NOT NULL AUTO_INCREMENT, normalized_field CHAR(50), PRIMARY KEY (`id`))');
    }

    public function setSingleFieldAndTable()
    {
        $this->tablename = 'entry_table';
        $this->fields = [
            [   
                'name' => 'testField', 
                'dbFieldName' => 'testFieldDb', 
                'type' => '1', 
                'required' => true,
                'validationRegularExpression' => '/\d{0,1}/',
                'validationErrorMessage' => 'Ét ciffer tilladt',
                'maxLength' => 150
            ]
        ];
    }

    public function setNormalizedFieldsAndTable()
    {
        $this->tablename = 'entry_table';
        $this->fields = [
            [   
                'name' => 'testField', 
                'dbFieldName' => 'testFieldDb', 
                'type' => '1', 
                'required' => true,
                'validationRegularExpression' => '/\d{0,1}/',
                'validationErrorMessage' => 'Ét ciffer tilladt',
                'maxLength' => 150
            ],
            [   
                'name' => 'normalizedField', 
                'dbFieldName' => 'normalizedFieldDb', 
                'type' => '1:m',
                'normalizedTable' => 'normalized_table', 
                'normalizedField' => 'normalized_field',
                'normalizedAllowNewValue' => true,
                'required' => true,
                'validationRegularExpression' => '/\d{0,1}/',
                'validationErrorMessage' => 'Ét ciffer tilladt',
                'maxLength' => 150
            ]            
        ];
    } 

    public function testValidatation()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->assertEquals(false, $this->ge->save(), 'should return false when data is invalid');
        $this->assertCount(1, $this->ge->GetErrorMessages(), 'should set error message if validation error');
    }

    public function testSaving()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $_POST['testField'] = 'testValue';
        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->assertEquals(true, $this->ge->save(), 'should return true when saving is done');
    }

    public function testSavedData()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $_POST['testField'] = 'testValue';
        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);

        $this->ge->save();

        $resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals('testValue', $results[0]['testFieldDb'], 'should insert data');
    } 

    public function testSavingNormalized()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $_POST['testField'] = 'testValue';
        $_POST['normalizedField'] = 'valueToNormalize';
        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->assertEquals(true, $this->ge->save(), 'should return true when saving is done');

    }      

    public function testSavedNormalizedDataNewValue()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $_POST['testField'] = 'testValue';
        $_POST['normalizedField'] = 'valueToNormalize';

        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->ge->save();

        $resultSet = $this->di->get('db')->query('select * from normalized_table limit 1');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals('valueToNormalize', $results[0]['normalized_field'], 'should save the normalized value in the normalization table');        

        $resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $entryResults = $resultSet->fetchAll();

        $this->assertEquals($results[0]['id'], $entryResults[0]['normalizedFieldDb'], 'the inserted id should match the idin the normalization table');        
    }

    public function testSavingNormalizedExistingValue()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $this->di->get('db')->execute('INSERT INTO normalized_table (normalized_field) VALUES ("existing_value")');

        $_POST['testField'] = 'testValue';
        $_POST['normalizedField'] = 'existing_value';

        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->assertEquals(true, $this->ge->save(), 'should return true when saving is done');

        $resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals(1, $results[0]['normalizedFieldDb'], 'should use the correct id from the normalization table');
    }

    public function testSavedNormalizedDataNewValuesNotAllowed()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();
        $this->fields[1]['normalizedAllowNewValue'] = false;

        $_POST['testField'] = 'testValue';
        $_POST['normalizedField'] = 'thisValueDoesNotExist';

        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);

        $this->assertEquals($this->ge->save(), false, 'should return false when normalized data does not exist');

        $this->assertCount(1, $this->ge->GetErrorMessages(), 'should return error message');
    }   

    public function testGetData()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $_POST['testField'] = 'testValue';
        $_POST['normalizedField'] = 'testValue2';

        $this->ge = new GenericEntry($this->tablename, $this->fields, $this->di);
        $this->ge->Save();
        $data = $this->ge->GetData();

        $this->assertEquals(2, count($data), 'should return array with fields and their values');
        $this->assertEquals('testValue', $data[$this->fields[0]['name']], 'should save data');
    }
}
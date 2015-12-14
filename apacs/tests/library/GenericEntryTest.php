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

        $this->ge = null;
        $this->entity = [];
    }

    public function createTables()
    {
       $this->di->get('db')->query('DROP TABLE IF EXISTS entry_table');
       $this->di->get('db')->query('DROP TABLE IF EXISTS normalized_table');
       $this->di->get('db')->query('CREATE TABLE entry_table (id INT(11) NOT NULL AUTO_INCREMENT, testFieldDb CHAR(50), normalizedFieldDb CHAR(50), post_id INT(11), PRIMARY KEY (`id`))');
       $this->di->get('db')->query('CREATE TABLE normalized_table (id INT(11) NOT NULL AUTO_INCREMENT, normalized_field CHAR(50), PRIMARY KEY (`id`))');
    }

    public function setSingleFieldAndTable()
    {
        
        $this->entity = [
            'tablename' => 'entry_table',
            'fields' => [
                [   
                    'name' => 'testField', 
                    'dbFieldName' => 'testFieldDb', 
                    'type' => '1', 
                    'required' => true,
                    'validationRegularExpression' => '/^(.{2,})$/',
                    'validationErrorMessage' => 'Minimum to tegn',
                    'maxLength' => 150
                ]
            ]
        ];
    }

    public function setNormalizedFieldsAndTable()
    {
        $this->entity = [
            'tablename' => 'entry_table',
            'fields' => [       
                [   
                    'name' => 'testField', 
                    'dbFieldName' => 'testFieldDb', 
                    'type' => '1', 
                    'required' => true,
                    'validationRegularExpression' => '/^(.{2,})$/',
                    'validationErrorMessage' => 'Minimum to tegn',
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
                    'validationRegularExpression' => '/^(.{2,})$/',
                    'validationErrorMessage' => 'Minimum to tegn',
                    'maxLength' => 150
                ]            
            ]
        ];
    } 

    public function invalidInputValues()
    {
        return [
            [
                'fieldname' => 'testFieldDb',
                'value' => ''
            ],
            [
                'fieldname' => 'normalizedField',
                'value' => 'valueToNormalize'
            ]
        ];
    }

    public function inputValues()
    {
        return [
            [
                'fieldname' => 'testFieldDb',
                'value' => 'testValue'
            ],
            [
                'fieldname' => 'normalizedFieldDb',
                'value' => 'valueToNormalize'
            ]
        ];
    }

    public function testValidatation()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $this->ge = new GenericEntry($this->entity, $this->invalidInputValues(), $this->di);
        $this->assertEquals(false, $this->ge->Save(), 'should return false when data is invalid');
    }

    public function testPartialValidation()
    {
        $this->setNormalizedFieldsAndTable();

        $this->ge = new GenericEntry($this->entity, [['fieldname' => 'testFieldDb', 'value' => 'v']], $this->di);
        $this->assertEquals(false, $this->ge->ValidateValues(true), 'should return false when data is invalid');
    }

    public function testSaving()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $this->ge = new GenericEntry($this->entity, $this->inputValues(), $this->di);
        $this->assertEquals(true, $this->ge->save(), 'should return true when saving is done');
    }

    public function testSavedData()
    {
        $this->createTables();
        $this->setSingleFieldAndTable();

        $this->ge = new GenericEntry($this->entity, $this->inputValues(), $this->di);

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

        $this->ge = new GenericEntry($this->entity, $this->inputValues(), $this->di);
        $this->assertEquals(true, $this->ge->Save(), 'should return true when saving is done');
    }      

    public function testSavedNormalizedDataNewValue()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $this->ge = new GenericEntry($this->entity, [[ 'fieldname' => 'testFieldDb', 'value' => 'testValue' ],[ 'fieldname' => 'normalizedFieldDb', 'value' => 'valueToNormalize' ]], $this->di);
        
        $this->assertEquals(true, $this->ge->Save(), 'should save the normalized value in the normalization table');   

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

        $this->ge = new GenericEntry( $this->entity, [[ 'fieldname' => 'testFieldDb', 'value' => 'testValue' ],[ 'fieldname' => 'normalizedFieldDb', 'value' => 'existing_value' ]], $this->di);
        $this->assertEquals(true, $this->ge->Save(), 'should return true when saving is done');

        $resultSet = $this->di->get('db')->query('select * from entry_table limit 1');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals(1, $results[0]['normalizedFieldDb'], 'should use the correct id from the normalization table');
    }

    public function testSavedNormalizedDataNewValuesNotAllowed()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();
        $this->entity['fields'][1]['normalizedAllowNewValue'] = false;

        $this->ge = new GenericEntry($this->entity, [[ 'fieldname' => 'testFieldDb', 'value' => 'testValue' ],[ 'fieldname' => 'normalizedFieldDb', 'value' => 'thisValueDoesNotExist' ]], $this->di);

        $this->assertEquals(false, $this->ge->Save(), 'should return false when normalized data does not exist');
    }   

    public function testGetData()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();

        $this->ge = new GenericEntry($this->entity, [[ 'fieldname' => 'testFieldDb', 'value' => 'testValue' ],[ 'fieldname' => 'normalizedFieldDb', 'value' => 'testValue2' ]], $this->di);
        $this->ge->Save();
        $data = $this->ge->GetData();

        $this->assertEquals(2, count($data), 'should return array with fields and their values');
        $this->assertEquals('testValue', $data[$this->entity[0]['dbFieldName']], 'should save data');
    }

    public function testLoadData()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();     

        $this->di->get('db')->execute('INSERT INTO entry_table (testFieldDb) VALUES ("new_value")');
        
        $this->ge = new GenericEntry($this->entity, [], $this->di);
        $this->assertEquals(1, count($this->ge->Load(1)), 'should load row of data by id');
    }

    public function testLoadedDataStructure()
    {
        $this->createTables();
        $this->setNormalizedFieldsAndTable();     

        $this->di->get('db')->execute('INSERT INTO entry_table (testFieldDb) VALUES ("new_value")');
        
        $this->ge = new GenericEntry($this->entity, [], $this->di);
        $this->assertEquals(['fieldname' => 'testFieldDb', 'value' => 'new_value'], $this->ge->Load(1)[0]['fields'][0], 'should load row of data by id');
    }    
}
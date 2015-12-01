<?php

include '../lib/models/Units.php';

class UnitsModelTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL)
    {
        $di = new \Phalcon\Di\FactoryDefault;

        $di->set('database', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "root",
                "password" => "",
                "dbname" => "unit_tests",
                'charset' => 'utf8'
                ));
            }
        );

        parent::setUp($di, $config);
    }
    
    public function tearDown()
    {
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS test_protocol');
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS apacs_units');
        parent::tearDown();
    }

    private function createTable()
    {
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS apacs_units');

        $createQuery2 = 'CREATE TABLE apacs_units (id INT(11) AUTO_INCREMENT PRIMARY KEY, description CHAR(50) NOT NULL, collection_id INT(11) NOT NULL, concrete_unit_id INT(11) NOT NULL, tablename CHAR(50) NOT NULL)';
        $this->getDI()->get('database')->execute($createQuery2);
    }

    private function createTestProtocols()
    {
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS test_protocol');
        
        $createQuery = 'CREATE TABLE test_protocol (id INT(11) AUTO_INCREMENT PRIMARY KEY, info CHAR(50) NOT NULL, collection_id INT(11) NOT NULL)';
        $this->getDI()->get('database')->execute($createQuery);
        
        $insert = 'INSERT INTO test_protocol (id, info, collection_id) VALUES (1, "desc1",1), (2, "desc2",1)';
        $this->getDI()->get('database')->execute($insert);
    }

    public function testGetUnits()
    {
        $cic = new Units();

       // $this->assertGreaterThan(count($cic->GetUnits(3,1)), 0, 'should load list of protocols');
    }

    public function testCreateUnits()
    {
        $this->createTable();
        $this->createTestProtocols();

        $imp = new Units();
        $imp->Import(Units::OPERATION_TYPE_CREATE, 1, 'id', 'info', 'test_protocol');
        
        $this->assertEquals(2,$imp->GetStatus()['affected_rows'], 'should return number of affected rows');

        
        $resultSet = $this->getDI()->get('database')->query('select * from apacs_units');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals(2, count($results), 'should create a list of protocols');
        $this->assertEquals('test_protocol', $results[0]['tablename'], 'should save original table name');
    }

    public function testUpdateUnits()
    {
        $this->createTable();
        $this->createTestProtocols();

        //Importing data
        $imp = new Units();
        $imp->Import(Units::OPERATION_TYPE_CREATE, 1, 'id', 'info', 'test_protocol');
        
        //Changing original data
        $this->getDI()->get('database')->execute('update test_protocol set info = "desc3" WHERE id = 1 LIMIT 1');
        
        //Updating data
        $this->assertTrue($imp->Import(Units::OPERATION_TYPE_UPDATE, 1, 'id', 'info', 'test_protocol'), 'should update without errors');

        //Getting updated data
        $resultSet = $this->getDI()->get('database')->query('select * from apacs_units');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals('desc3', $results[0]['description'], 'should update data');
    }

    public function testReturnErrorIfUnitsAlreadyImported()
    {
        $this->createTable();
        $this->createTestProtocols();

        $imp = new Units();
        $this->assertCount(0, $imp->GetStatus(), 'should have empty status before import');
        //Importing data
        $imp->Import(Units::OPERATION_TYPE_CREATE, 1, 'id', 'info', 'test_protocol');
        
        //Importing data again. This should fail
        $this->assertEquals(false, $imp->Import(Units::OPERATION_TYPE_CREATE, 1, 'id', 'info', 'test_protocol'), 'should return false when importing the same dataset twice');
        $this->assertNotEmpty($imp->GetStatus(), 'should return an error');
    }    
}
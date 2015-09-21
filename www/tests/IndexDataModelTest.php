<?php
include '../kbh_backend/models/IndexDataModel.php';
include './mockData/EntryConfMock.php';
include 'TestDatabaseConnection.php';

class IndexDataModelTest extends \UnitTestCase {
    private $testDatabase;

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

        //Test specific database, PHPUnit
        $this->testDatabase = new TestDatabaseConnection();
        $this->testDatabase->getConnection()->createQueryTable('insert_table', 'SELECT * FROM insert_table');

        //Test specific database, Phalcon
        $di->set('database', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "root",
                "password" => "",
                "dbname" => "unit_test",
                'charset' => 'utf8'
                ));
            }
        );

        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
    }
    public function testInsertData()
    {
        $idm = new IndexDataModel();
        
        //Expecting 0 rows
        $this->AssertEquals(0, $this->testDatabase->getConnection()->getRowCount('insert_table'), 'Test table should have 0 rows');
        
        $idm->insert(1,1,1, GetEntryConfMock());

        //Insert should give an extra row
      //  $this->AssertEquals(1, $this->testDatabase->getConnection()->getRowCount('insert_table'), 'Test table should have 1 row');
    }

    public function testValidateData()
    {
        $idm = new IndexDataModel();

 //       $idm->ValidateData();
    }
}
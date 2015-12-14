<?php

include '../lib/models/Pages.php';

class PagesModelsTest extends \UnitTestCase {
    
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
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS apacs_pages');
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS test_page');
        parent::tearDown();
    }

    private function createTable()
    {
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS apacs_pages');
        $createQuery = 'CREATE TABLE apacs_pages (id INT(11) AUTO_INCREMENT PRIMARY KEY, collection_id INT(11) NOT NULL, concrete_unit_id INT(11) NOT NULL, concrete_page_id INT(11) NOT NULL, tablename CHAR(50) NOT NULL, image_url CHAR(250) NOT NULL)';
        $this->getDI()->get('database')->execute($createQuery);
    }

    private function createTestPages()
    {
        $this->getDI()->get('database')->execute('DROP TABLE IF EXISTS test_page');
        $createQuery = 'CREATE TABLE test_page (id INT(11) AUTO_INCREMENT PRIMARY KEY, collection_id INT(11) NOT NULL, volume_id INT(11) NOT NULL, url CHAR(250) NOT NULL)';
        $this->getDI()->get('database')->execute($createQuery);
        
        $insert = 'INSERT INTO test_page (id,collection_id, volume_id, url) VALUES (1,1,43, "http://www.kbhkilder.dk/getfile?fileId=3"), (2, 1,44,"http://www.kbhkilder.dk/getfile?fileId=4")';
        $this->getDI()->get('database')->execute($insert);
    }

    public function testGetPages()
    {
        $cic = new Pages();

       // $this->assertGreaterThan(count($cic->GetUnits(3,1)), 0, 'should load list of protocols');
    }

    public function testCreatePages()
    {
        $this->createTable();
        $this->createTestPages();

        $imp = new Pages();
        $imp->Import(Pages::OPERATION_TYPE_CREATE, 1, 'id', 'volume_id', 'test_page', 'url');

        $this->assertEquals(2,$imp->GetStatus()['affected_rows'], 'should return number of affected rows');

        $resultSet = $this->getDI()->get('database')->query('select * from apacs_pages');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals(2, count($results), 'should create pages');
        $this->assertEquals('test_page', $results[0]['tablename'], 'should save original table name');
    }

    public function testUpdatePages()
    {
        $this->createTable();
        $this->createTestPages();

        //Importing data
        $imp = new Pages();
        $this->assertTrue($imp->Import(Pages::OPERATION_TYPE_CREATE, 1, 'id', 'volume_id', 'test_page', 'url'), 'should import data');

        //Changing original data
        $this->getDI()->get('database')->execute('update test_page set url = "213" WHERE id = 1 LIMIT 1');
      
        //Updating data
        $this->assertTrue($imp->Import(Pages::OPERATION_TYPE_UPDATE, 1, 'id', 'volume_id', 'test_page', 'url'), 'should update without errors');

        //Getting updated data
        $resultSet = $this->getDI()->get('database')->query('select * from apacs_pages');
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        $this->assertEquals('213', $results[0]['image_url'], 'should update data');
    }

    public function testReturnErrorIfPagesAlreadyImported()
    {
        $this->createTable();
        $this->createTestPages();

        $imp = new Pages();
        $this->assertCount(0, $imp->GetStatus(), 'should have empty status before import');
        //Importing data
        $imp->Import(Pages::OPERATION_TYPE_CREATE, 1, 'id', 'volume_id', 'test_page', 'url');
        
        //Importing data again. This should fail
        $this->assertEquals(false, $imp->Import(Pages::OPERATION_TYPE_CREATE, 1, 'id', 'info', 'volume_id', 'test_page'), 'should return false when importing the same dataset twice');
        $this->assertNotEmpty($imp->GetStatus(), 'should return an error');
    }    
}
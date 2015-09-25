<?php
include '../kbh_backend/models/GenericIndexModel.php';

class GenericIndexModelTest extends \UnitTestCase {
    private $testDatabase;

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

        //Test specific database, Phalcon
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

        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        $this->getDI()->get('db')->query('DELETE FROM insert_table');
        $this->getDI()->get('db')->query('DELETE FROM insert_table2');
        parent::tearDown();
    }

    public function testDatabaseAccess()
    {
        $this->getDI()->set('tableNameHolder', function(){
            return 'insert_table';
        });
        $model = new GenericIndexModel();
        $model->firstname = 'firstname1';
        $model->lastname = 'lastname1';
        $model->save();
        $this->assertEquals(1,count($model->find("firstname = 'firstname1'")), 'should retrieve data from insert_table');
        

        $this->getDI()->set('tableNameHolder', function(){
            return 'insert_table2';
        });
        $model = new GenericIndexModel();

        $model->firstname2 = 'firstname2';
        $model->lastname2 = 'lastname2';
        $model->save();
        $this->assertEquals(1,count($model->find("lastname2 = 'lastname2'")), 'should retrieve data from insert_table2');

    }
}
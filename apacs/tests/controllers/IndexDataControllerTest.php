<?php
include '../lib/controllers/IndexDataController.php';
include '../lib/library/ConfigurationLoader.php';

class IndexDataControllerTest extends \UnitTestCase {

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

        $di->set('collectionConfigurationLoader', function(){
            $conf = new ConfigurationLoader('./mockData/EntryConfMock.php');
            return $conf;
        });    

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

    public function testInsertNoErrors()
    {
        $ctrl = new IndexDataController();

        $_POST['firstname'] = 'firstname';
        $_POST['lastname'] = 'lastname';

        $this->assertEquals(true, $ctrl->insert(234), 'should return true on success');

        $_POST['firstname'] = 'niels';
        $_POST['lastname'] = 'lastname';
        $ctrl->insert(234);

        $model = new GenericIndex();
        $this->assertEquals(2, count($model->find("lastname = 'lastname'")),'GenericModel should be affiliated with the controller entity id');
    }

    public function testInsertErrors()
    {
        $ctrl = new IndexDataController();

        $_POST['firstname2'] = '123 123';
        $_POST['lastname2'] = 'lastname';

        $this->assertEquals(false, $ctrl->insert(235), 'should return false on error');

        $model = new GenericIndex();
        $this->assertEquals(0, count($model->find("lastname2 = 'lastname'")), 'should not save data on error');
    }
}
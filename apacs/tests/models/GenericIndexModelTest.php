<?php
include '../lib/models/GenericIndex.php';
include_once '../lib/library/ConfigurationLoader.php';

class GenericIndexModelTest extends \UnitTestCase {

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

    /**
     * @expectedException \Phalcon\Mvc\Model\Exception
     */
    public function testSingleDatabaseAccess()
    {

        $this->getDI()->set('currentEntityId', function(){
            return 234;
        });

        $model = new GenericIndex();
        $model->firstname = 'firstnameone';
        $model->lastname = 'lastnameone';
        if(!$model->save())
            throw new Exception("could not save data!");    

        $this->assertEquals(1,count($model->find("lastname = '" . $model->lastname . "'")), 'should retrieve data from insert_table');
        
        $this->getDI()->set('currentEntityId', function(){
            return 235;
        });

        $model2 = new GenericIndex();

        $model2->firstname2 = 'test';
        $model2->lastname2 = 'test';
        $model2->save();
        
        //Should throw exception, as two different models cannot be handled at the same time
        $model2->find("lastname2 = '" . $model2->lastname2 . "'");
    }

    public function testAnotherDatabaseAccess()
    {
        $this->getDI()->set('currentEntityId', function(){
            return 235;
        });

        $model = new GenericIndex();
        $model->firstname2 = 'firstnametwo';
        $model->lastname2 = 'lastnamatwo';
        if(!$model->save())
            throw new Exception("could not save data!");

        $this->assertEquals(1,count($model->find("lastname2 = '" . $model->lastname2 . "'")), 'should retrieve data from insert_table2');        

    }

    public function testDynamicValidation()
    {
        $this->getDI()->set('currentEntityId', function(){
            return 235;
        });

        $model = new GenericIndex();

        $model->firstname2 = '12 123 2';
        $model->lastname2 = 'lastname';
        $this->assertEquals($model->save(), false, 'should fail validation');
        $this->assertGreaterThan(0, strlen($model->getMessages()[0]), 'should create error message');
    }
}
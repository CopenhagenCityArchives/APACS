<?php

include '../kbh_backend/controllers/MetadataLevelsController.php';

class MetadataControllerTest extends \UnitTestCase {
    
    private $_controller;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault; 

        //Test specific database, Phalcon
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
        
        $this->_controller = new MetadataLevelsController();
        $this->_controller->configurationLocation = './mockData/MockCollectionsConfiguration.php';
    }
    
    public function tearDown() {
        $this->getDI()->getDatabase()->query('delete from PRB_registerblade');
        $this->getDI()->get('response')->setContent(null);
        parent::tearDown();
        $this->_controller = null;
    }
    
    public function testInitialization(){
        $this->assertInstanceOf('MetadataLevelsController', $this->_controller);
    }
    
    public function testResponseOnFalseRequests(){
        $this->setExpectedException('Exception');
        $this->_controller->getMetadataLevels(false,false);
    }
    
    public function testEmptyLocation(){
        //Should throw exception when location is not loaded
        $this->setExpectedException('Exception');
        $tester = new MetadataLevelsController();
        $tester->getMetadataLevels(1,false);
    }

    public function testGetObjectData(){
        $_GET['station'] = 1;
        $this->getDI()->getDatabase()->query('insert into PRB_registerblade (id, station) values (1,1)');

        $this->_controller->getObjectData(1);
        $this->assertEquals(1,count(json_decode($this->getDI()->get('response')->getContent())), 'should return an array of data');
    }

    public function testGetDataById(){
        $_GET['id'] = 10;
        $this->getDI()->getDatabase()->query('insert into PRB_registerblade (id, station) values (10,100)');

        $this->_controller->getObjectData(1);
var_dump($this->getDI()->get('response')->getContent());
        $this->assertEquals(1,count(json_decode($this->getDI()->get('response')->getContent())), 'should return an array of data');   
    }
}
<?php

include '../kbh_backend/controllers/MetadataLevelsController.php';

class MetadataControllerTest extends \UnitTestCase {
    
    private $_controller;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
        $this->_controller = new MetadataLevelsController();
        $this->_controller->configurationLocation = './mockData/MockCollectionsConfiguration.php';
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->_controller = null;
    }
    
    public function testInitialization() 
    {
        $this->assertInstanceOf('MetadataLevelsController', $this->_controller);
    }
    
    public function testResponseOnFalseRequests()
    {
        $return = $this->_controller->getMetadataLevels(false,false);
        $this->assertEmpty($return, 'Return should be empty');
        
        $return = $this->_controller->getMetadataLevels(-1,false);
        $this->assertEmpty($return, 'Return should be empty');
        
        $return = $this->_controller->getMetadataLevels(-1,'falsedsf');
        $this->assertEmpty($return, 'Return should be empty');
    }
    
    public function testEmptyLocation(){
        $tester = new MetadataLevelsController();
        
        //Should throw exception when location is not loaded
        $this->setExpectedException('Exception');
        $tester->getMetadataLevels(1,false);
    }
}
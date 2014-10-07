<?php

include '../kbh_backend/models/ObjectsModel.php';

class ObjectsModelTest extends \UnitTestCase {
    
    private $_model;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
        $this->_model = new ObjectsModel();
        //$this->_model->loadConfig(include "./mockData/MockCollectionsConfiguration.php");
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->_model = null;
    }
    
    public function testInitialization() 
    {
        $this->assertInstanceOf('ObjectsModel', $this->_model);
    }
    
    public function testRequiredLevelChecker(){
        $allFilters = array('streetname','noname');
        $requiredFilter = array('streetname', 'year');
        
        //Should throw an exception when required filters are not set
        $this->setExpectedException('Exception');
        $this->_model->getFilters($allFilters, $requiredFilter);
    }
    
    public function testCreateObjectQuery(){
        $filters = array(
            'station' => '1',
            'roll' => '23'
        );
        $sql = 'SELECT * FROM PRB_registerblade WHERE :query';
        $expectedQuery = 'SELECT * FROM PRB_registerblade WHERE station LIKE \'%1%\' AND roll LIKE \'%23%\'';
        $this->assertEquals(
            $expectedQuery,
            $this->_model->createObjectQuery($sql, $filters),
            'should create a query based on filters and data sql'
        );
    }
    
    public function testConvertResultToObjects(){
        $metadataLevels = array('station', 'roll');
        $results = array(
            array('id' => 341, 'station' => 1, 'roll' => '23', 'imageURL' => 'test/0001.jpg'),
            array('id' => 341, 'station' => 1, 'roll' => '23', 'imageURL' => 'test/0002.jpg'),
            array('id' => 2, 'station' => 3, 'roll' => '24', 'imageURL' => 'test/0003.jpg'),
            array('id' => 2, 'station' => 3, 'roll' => '24', 'imageURL' => 'test/0004.jpg')
        );
        $expectedResult = array(
            '341' => array(
                'id' => 341,
                'metadata' => array('station' => 1, 'roll' => '23'),
                'images' => array('test/0001.jpg', 'test/0002.jpg')
            ),
            '2' => array(
                'id' => 2,
                'metadata' => array('station' => 3, 'roll' => '24'),
                'images' => array('test/0003.jpg', 'test/0004.jpg')                
            )
        );
        
        $this->assertEquals(
            $expectedResult,
            $this->_model->convertResultToObjects($results, $metadataLevels),                
            'should convert two dimensional result set to multidimensional array'
        );
    }
}
<?php

include '../kbh_backend/models/MetadataModel.php';

class MetadataModelTest extends \UnitTestCase {
    
    private $_model;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
        $this->_model = new MetadataModel();
        //$this->_model->loadConfig(include "./mockData/MockCollectionsConfiguration.php");
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->_model = null;
    }
    
    public function testInitialization() 
    {
        $this->assertInstanceOf('MetadataModel', $this->_model);
    }
    
    public function testMetadataSearchSQLCreation()
    {
        $testConfigTypeahead = array('type'=>'typeahead', 'data_sql' => "select id, gadenavn from MAND_gader WHERE gadenavn LIKE \'%s%%\'");
        
        $this->assertEquals(
                "select id, gadenavn from MAND_gader WHERE gadenavn LIKE \'adelga%\' LIMIT 10",
                $this->_model->createMetadataSearchQuery($testConfigTypeahead, array('adelga')),
                'should create typeahead sql based on sql_data and searchstring with a limit'
        );
        
        $testConfigGetallbyfilter = array('type'=>'getallbyfilter', 'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d', 'required_filters' => array('station'));
        
        $this->assertEquals(
                "SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = 1",
                $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array('1')),
                'should create getallbyfilter sql based on sql_data, searchstring and required fields'
        );
        
        $testConfigGetallbyfilter = array('type'=>'getallbyfilter', 'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d AND filmrulle = %d', 'required_filters' => array('station', 'roll'));
        
        $this->assertEquals(
                "SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = 1 AND filmrulle = 2",
                $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array(1,2)),
                'should create getallbyfilter sql based on sql_data, searchstring and required fields'
        );
        
        $this->setExpectedException('Exception');
        $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array(1));      
        
        $this->setExpectedException('Exception');
        $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array(1,2,3));        
    }
    
    public function testMetadataSearchQueryGeneration(){
        
    }
    /*
    public function testMetadataDatabaseService()
    {
        $this->_model->getData(null);
    }*/
}
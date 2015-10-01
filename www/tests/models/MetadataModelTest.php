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
        $testConfigTypeahead = array('gui_type'=>'typeahead', 'data_sql' => "select id, gadenavn from MAND_gader WHERE gadenavn LIKE \'%s%%\'");
        
        $this->assertEquals(
                "select id, gadenavn from MAND_gader WHERE gadenavn LIKE \'adelga%\'",
                $this->_model->createMetadataSearchQuery($testConfigTypeahead, array('adelga')),
                'should create typeahead sql based on sql_data and searchstring with a limit'
        );
        
        $testConfigGetallbyfilter = array('gui_type'=>'getallbyfilter', 'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d', 'required_levels' => array('station'));
        
        $this->assertEquals(
                "SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = 1",
                $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array('1')),
                'should create getallbyfilter sql based on sql_data, searchstring and required fields'
        );
        
        $testConfigGetallbyfilter = array('gui_type'=>'getallbyfilter', 'data_sql' => 'SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = %d AND filmrulle = %d', 'required_levels' => array('station', 'name' => 'roll'));
        
        $this->assertEquals(
                "SELECT id, filmrulle_navn from PRB_filmrulle WHERE station_id = 1 AND filmrulle = 2",
                $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array('station' => 1,'filmrulle' => 2)),
                'should create getallbyfilter sql based on sql_data, searchstring and required fields'
        );
        
  //      $this->setExpectedException('Exception');
        $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array('station' => '1'));      
        
    //    $this->setExpectedException('Exception');
        $this->_model->createMetadataSearchQuery($testConfigGetallbyfilter, array('station' => 1,'filmrulle' => 2, 'andet' => 3));        
    }
    
    public function testGetMetadataSearchParameters(){
        $_GET['required_level_data'] = 'value';
        $metadataLevel = array();
        $metadataLevel['required_levels'] = array('required_level_data');
        $this->assertEquals(
                $this->_model->getMetadataSearchParameters($metadataLevel),
                array('required_level_data' => 'value'),
                'should return an array with all required fields given in the metadata level configuration'
        );
    }    
    
    public function testGetMetadataSearchParametersEmptyFiltersException(){
        $this->setExpectedException('Exception');
        $metadataLevel = array();
        //Should throw exception when metadataLevel['required_levels'] is not set
        $this->_model->getMetadataSearchParameters($metadataLevel);         
    }
    
    public function testGetMetadataSearchParametersCountMismatchException(){
        $this->setExpectedException('Exception');
        $_GET['required_level_data'] = 'value';
        $metadataLevel = array();
        $metadataLevel['required_filters'] = array('required_level_data', 'non_existing_get_var');
        //Should throw exception when metadataLevel['required_levels'] is not set
        $this->_model->getMetadataSearchParameters($metadataLevel);         
    }    
}
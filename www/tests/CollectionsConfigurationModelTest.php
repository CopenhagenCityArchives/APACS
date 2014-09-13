<?php

include '../kbh_backend/models/CollectionsConfigurationModel.php';

class CollectionsConfigurationModelTest extends \UnitTestCase {
    
    private $_model;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
        $this->_model = new CollectionsConfigurationModel("inputtest");
        $this->_model->loadConfig(include "./mockData/MockCollectionsConfiguration.php");
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->_model = null;
    }
    
    public function testInitialization() 
    {
        $this->assertInstanceOf('CollectionsConfigurationModel', $this->_model);
        
        //Should throw exception when running loadConfig without input
        $this->setExpectedException('Exception');
        $testModel = new CollectionsConfigurationModel();
        $testModel->loadConfig();
    }
    
    public function testLoadOfConfiguration()
    {       
        $this->assertNotEmpty(
            $this->_model->getConfigurationForCollection(1), 
            'Should return metadatalevels for an existing collection'
        );
        
        $publicConfig = $this->_model->getConfigurationForCollection(1, true);
        $this->assertFalse(isset($publicConfig[0]['config']));
        
        $this->setExpectedException('Exception');
        $this->_model->getConfigurationForCollection(-1);        
    }
    
    public function testLoadOfMetadataLevels()
    {
        $configuration = $this->_model->getConfigurationForCollection(1);
        
        $this->assertEquals(
            $this->_model->getMetadataLevels(1), 
            $configuration[0]['config']['metadataLevels'], 
            'should retrieve all metadatalevels when no level name is given'
        );
        
        $this->assertEquals(
            $this->_model->getMetadataLevels(1, 'roll'), 
            $configuration[0]['config']['metadataLevels']['levels'][0],
            'should retrieve a concrete level when level name is set'
        );  
    }
    
    public function testLoadOfMetadatalevelsOnEmptyConfiguration(){
        //Should throw error when loading metadata levels without loading the configuration
        $this->setExpectedException('Exception');
        $model = new MetadataModel();
        $model->CollectionsConfigurationModel(1);        
    }
    
    public function testLoadOfMetadatalevelOnEmptyConfiguration(){
        //Should throw error when loading a metadata level without loading the configuration
        $this->setExpectedException('Exception');
        $model = new MetadataModel();
        $model->CollectionsConfigurationModel(1, 'rolls');          
    }
    
    public function testLoadOfNonexistingMetadatalevel(){
        //Should throw exception when loading a non-existing metadatalevel
        $this->setExpectedException('Exception');
        $this->_model->getMetadataLevels(1, 'this level does not exist');        
    }
    
    public function testLoadOfAllFilters()
    {
        $allFilters = array('roll','station');
        
        $this->assertEquals(
            $this->_model->getAllFilters(1),
            $allFilters,
            'should get names of all filters'
        );
    }
    
    public function testLoadOfRequiredFilters()
    {
        $requiredFilters = array('station');
        
        $this->assertEquals(
            $this->_model->getRequiredFilters(1),
            $requiredFilters,
            'should get names of required filters'
        );
    }
    
    public function testLoadData(){
        $this->_model->getConfigurationForCollection(1);
        $this->assertEquals(
            count($this->_model->getDataLevel(1)),
            4
        );
    }
}
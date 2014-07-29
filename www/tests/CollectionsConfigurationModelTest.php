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
        
        $this->assertEquals(
                false,
                $this->_model->getConfigurationForCollection(-1), 
                'Should return empty array for nonexisting collection'
        );
        
        $this->assertNotEquals(
                $this->_model->getPublicMetadataLevels(1), 
                $this->_model->getConfigurationForCollection(1), 
                'should return reduced metadatalevels for public use'
        );
    }
    
    public function testLoadOfMetadataLevels()
    {
        $configuration = $this->_model->getConfigurationForCollection(1);
        
        $this->assertEquals(
                $this->_model->getMetadataLevels(1), 
                $configuration['config']['metadataLevels'], 
                'should retrieve all metadatalevels when no level name is given'
        );
        
        $this->assertEquals(
                $this->_model->getMetadataLevels(1, 'roll'), 
                $configuration['config']['metadataLevels']['levels'][0],
                'should retrieve a concrete level when level name is set'
        );
        
        //Should throw error when loading metadata levels without loading the configuration
        $this->setExpectedException('Exception');
        $model = new MetadataModel();
        $model->CollectionsConfigurationModel(1);
        
        //Should throw error when loading a metadata level without loading the configuration
        $this->setExpectedException('Exception');
        $model = new MetadataModel();
        $model->CollectionsConfigurationModel(1, 'rolls');        
        
        //Should throw exception when level doesn't exist
        $this->setExpectedException('Exception');
        $this->_model->getMetadataLevels(1, 'this level does not exist');
        
        //Should throw exception when loading level for a non-existing collection
        $this->setExpectedException('Exception');
        $this->assertEquals($this->_model->getMetadataLevels(-1, 'roll'));         
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
}
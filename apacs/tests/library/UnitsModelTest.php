<?php

include '../lib/models/UnitsModel.php';

class UnitsModelTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

        $di->set('collectionConfigurationLoader', function(){
            $conf = new ConfigurationLoader('./mockData/EntryConfMock.php');
            return $conf;
        }); 

        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
    }

    public function testGetUnits()
    {
        $cic = new UnitsModel();

        $this->assertGreaterThan(count($cic->GetUnits(1,1)), 0, 'should load list of protocols');
    }
}
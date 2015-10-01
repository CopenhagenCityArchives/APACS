<?php

include '../kbh_backend/library/DataReceiver.php';

class DataReceiverTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
       	$_GET = array();
    }

    public function testGetData()
    {
    	$_GET['id'] = 'testId';
		$dr = new DataReceiver(new Phalcon\Http\Request());
		
		$expectedData = ['id' => 'testId'];
		
		$this->assertEquals( $dr->GetDataFromFields('get', [['name' => 'id']]), $expectedData, 'should receive an array of the field name and the received value');

    	$_GET['id'] = 'testId';
    	$_GET['id2'] = 'testId2';

    	$expectedData = ['id' => 'testId', 'id2' => 'testId2'];
    	
    	$this->assertEquals( $dr->GetDataFromFields('GET', [['name' => 'id'], ['name' => 'id2']]), $expectedData, 'should receive and array of names and values for all the fields');		
    }

    public function testReturnNullWhenNoDataInRequest()
    {
    	$_POST['id'] = 'thisDataIsIrrelevant';

		$dr = new DataReceiver(new Phalcon\Http\Request());
		$expectedData = ['id' => null];
		
		$this->assertEquals( $dr->GetDataFromFields('get', [['name' => 'id']]), $expectedData, 'should return an array with the value of null if no data is given for the field');
    }

    public function testGetSingleField()
    {
        $_POST['id'] = 'test_id';

        $dr = new DataReceiver(new Phalcon\Http\Request());
        $expectedData = 'test_id';

        $this->assertEquals($dr->Get('post', 'id'), $expectedData, 'should retrieve data for single fields');
    }
}
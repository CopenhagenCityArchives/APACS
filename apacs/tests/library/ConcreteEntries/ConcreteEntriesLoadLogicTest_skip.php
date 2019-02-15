<?php

class ConcreteEntriesLoadLogicTest_skip extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
      
        $this->crudMock = new Mocks\CrudMock();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_LoadEntry_ShouldCallCrud(){
		$this->markTestIncomplete();
	}

}
<?php

class ConcreteEntriesLoadLogicTest_skip extends \UnitTestCase {

	public function setUp($di = null) : void {
        parent::setUp();
      
        $this->crudMock = new Mocks\CrudMock();
	}

	public function tearDown() : void {
		parent::tearDown();
	}

	public function test_LoadEntry_ShouldCallCrud(){
		$this->markTestIncomplete();
	}

}
<?php

use \Phalcon\Di;
use \Phalcon\Mvc\Model\Manager;
use \Phalcon\Mvc\Model\MetaData\Memory;

class DatalistEventTest extends \UnitTestCase {

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        if (is_null($di)) {
            $di = new Di();
        }

        $di->set(
            'modelsManager',
            function() {
                return new Manager();
            }
        );

        $di->set(
            'modelsMetadata',
            function() {
                return new Memory();
            }
        );

        parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

    public function test_bla(){
        $entry = Entries::findFirst();
        $this->assertNotNull($entry);
    }
}
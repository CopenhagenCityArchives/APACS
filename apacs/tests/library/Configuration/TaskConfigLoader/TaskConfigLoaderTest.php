<?php

class TaskConfigLoaderTest extends \UnitTestCase {

	private $loader;
	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp();
		$testConfigPath = dirname(__DIR__) . '/TaskConfigLoader';

		$this->loader = new TaskConfigurationLoader2($testConfigPath);
	}

	public function tearDown() {
		parent::tearDown();
		$this->loader = null;
	}

	public function test_LoadFromExistingFile_ReturnConfigArray() {
		$conf = $this->loader->getConfig(1);

		$this->assertTrue(is_array($conf));
		$this->assertTrue(count($conf)>0);
	}

	public function test_LoadConfigWithParent_ReturnParentOverridenWithChild(){
		$conf1 = $this->loader->getConfig(1);
		$conf2 = $this->loader->getConfig(2);
		
		//The only thing changed is persons description. These should not match
		$this->assertTrue($conf1['schema']['properties']['persons']['description'] != $conf2['schema']['properties']['persons']['description']);
		
		//Other properties should match
		$this->assertTrue($conf1['schema']['properties']['persons']['properties'] === $conf2['schema']['properties']['persons']['properties']);
	}

	/**
	* @expectedException Exception
	*/
	public function test_LoadFromNonExistingFile_ThrowException() {
		$conf = $this->loader->getConfig(1000000);
	}
}
<?php

class TaskConfigLoaderTest extends \UnitTestCase {

	private $loader;
	public function setUp($di = null) : void {
		parent::setUp();
		$testConfigPath = dirname(__DIR__) . '/TaskConfigLoader';

		$this->loader = new TaskConfigurationLoader($testConfigPath);
	}

	public function tearDown() : void {
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

	public function test_override() {
		$c = $this->loader->getConfig(6);

		// overridden in root and intermediate
		$this->assertEquals(6, $c['intval']);

		// non-overridden (only exists in root)
		$this->assertEquals("string value", $c['strval']);

		// list concatenation
		$this->assertEquals([ "a", "b", "c", "d", "e", "f" ], $c['listval']);
		$this->assertEquals([[1,2,3], ["a", "b", "c"], ["d", "e", "f"], [4,5,6]], $c['objectval']['_listlistval']);
		$this->assertCount(3, $c['objectval']['_objectlistval']);

		// nested, missing in leaf, overridden from intermediate to root
		$this->assertEquals("overridden", $c['objectval']['_strval']);
		$this->assertEquals("overridden", $c['objectval']['_objectval']['__strval']);

		// missing in root and intermediate
		$this->assertEquals("inserted", $c['newstrval']);

		// missing in intermediate, overridden in root
		$this->assertEquals(10, $c['intval2']);

		// steps special case
		$this->assertCount(1, $c['steps']);
		$this->assertEquals("only step that matters", $c['steps'][0]['strval']);
	}

	public function test_LoadFromNonExistingFile_ThrowException() {
		$this->expectException(Exception::class);
		$conf = $this->loader->getConfig(1000000);
	}
}
<?php
include_once '../lib/library/IStatementBuilder.php';
include_once '../lib/library/LoadStatementBuilder.php';

class LoadStatementBuilderTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testBuildLoadQuery() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'type' => 'value', 'fieldName' => 'field1']];

		$qb1 = new LoadStatementBuilder($tableName, $fields);
		$qb1->BuildStatement();
		$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.id = :id", $qb1->GetStatement(), 'should build statement with single field');
	}

	public function testBuildLoadQueryCustomKey() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'type' => 'value', 'fieldName' => 'field1']];

		$qb1 = new LoadStatementBuilder($tableName, $fields, 'another_key');
		$qb1->BuildStatement();
		$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.another_key = :id", $qb1->GetStatement(), 'should build statement with custom key name');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testThrowExceptionOnNoMatchingFields() {
		$tableName2 = 'tableName2';
		$fields2 = [];

		$qb2 = new LoadStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
	}
}
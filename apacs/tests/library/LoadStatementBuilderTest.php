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
		$fields = [['name' => 'field1', 'type' => 'value', 'dbFieldName' => 'field1']];

		$qb1 = new LoadStatementBuilder($tableName, $fields);
		$qb1->BuildStatement();
		$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.id = :id", $qb1->GetStatement(), 'should build statement with single field');
	}

	public function testBuildLoadQueryCustomKey() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'type' => 'value', 'dbFieldName' => 'field1']];

		$qb1 = new LoadStatementBuilder($tableName, $fields, 'another_key');
		$qb1->BuildStatement();
		$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.another_key = :id", $qb1->GetStatement(), 'should build statement with custom key name');
	}

	public function testBuildQueryIgnoreFieldWhereTypeIsNotValue() {
		$tableName = 'tableName';
		$fields = [
			['name' => 'field1', 'type' => 'value', 'dbFieldName' => 'field1'],
			['name' => 'field2', 'type' => 'object', 'dbFieldName' => 'field2'],
		];

		$qb1 = new LoadStatementBuilder($tableName, $fields);
		$qb1->BuildStatement();
		$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.id = :id", $qb1->GetStatement(), 'should ignore fields that are not of type value');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testThrowExceptionOnNoMatchingFields() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'wrongType'], ['name' => 'field3', 'dbFieldName' => 'field3', 'type' => 'wrongType']];
		$qb2 = new LoadStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
	}
}
<?php
include_once '../lib/library/IStatementBuilder.php';
include_once '../lib/library/UpdateStatementBuilder.php';

class UpdateStatementBuilderTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testBuildUpdateQuery() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'fieldName' => 'field1', 'type' => 'value']];

		$qb1 = new UpdateStatementBuilder($tableName, $fields);
		$qb1->BuildStatement();
		$this->assertEquals("UPDATE tableName SET `field1` = :field1 WHERE id = :id;", $qb1->GetStatement(), 'should build statement with single field');
	}

	public function testBuildInsertQueryMultipleFields() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'fieldName' => 'field1', 'type' => 'value'], ['name' => 'field2', 'fieldName' => 'field2', 'type' => 'value']];
		$qb2 = new UpdateStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
		$this->assertEquals("UPDATE tableName2 SET `field1` = :field1, `field2` = :field2 WHERE id = :id;", $qb2->GetStatement(), 'should return an update query with multiple fields');
	}

	public function testBuildInsertQueryCustomKeyName() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'fieldName' => 'field1', 'type' => 'value'], ['name' => 'field2', 'fieldName' => 'field2', 'type' => 'value']];
		$qb2 = new UpdateStatementBuilder($tableName2, $fields2, 'custom_key');
		$qb2->BuildStatement();
		$this->assertEquals("UPDATE tableName2 SET `field1` = :field1, `field2` = :field2 WHERE custom_key = :id;", $qb2->GetStatement(), 'should return an update query with multiple fields');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testThrowExceptionOnNoMatchingFields() {
		$tableName2 = 'tableName2';
		$fields2 = [];

		$qb2 = new UpdateStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
	}
}
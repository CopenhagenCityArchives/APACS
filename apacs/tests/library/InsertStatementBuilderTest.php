<?php
include_once '../lib/library/IStatementBuilder.php';
include_once '../lib/library/InsertStatementBuilder.php';

class InsertStatementBuilderTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testBuildInsertQuery() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'string']];

		$qb1 = new InsertStatementBuilder($tableName, $fields);
		$qb1->BuildStatement();
		$this->assertEquals("INSERT INTO tableName (`field1`) VALUES (:field1)", $qb1->GetStatement(), 'should build statement with single field');
	}

	public function testBuildInsertQueryMultipleFields() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'string'], ['name' => 'field2', 'dbFieldName' => 'field2', 'type' => 'string']];
		$qb2 = new InsertStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
		$this->assertEquals("INSERT INTO tableName2 (`field1`, `field2`) VALUES (:field1, :field2)", $qb2->GetStatement(), 'should return an insert query');
	}

	public function testOnlyAddFieldsOfTypeStringAndObject() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'string'], ['name' => 'field2', 'dbFieldName' => 'field2', 'type' => 'object'], ['name' => 'field3', 'dbFieldName' => 'field3', 'type' => 'array']];
		$qb2 = new InsertStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
		$this->assertEquals("INSERT INTO tableName2 (`field1`, `field2`) VALUES (:field1, :field2)", $qb2->GetStatement(), 'should return a query without fields of type array');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testThrowExceptionOnNoMatchingFields() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'wrongType'], ['name' => 'field3', 'dbFieldName' => 'field3', 'type' => 'wrongType']];
		$qb2 = new InsertStatementBuilder($tableName2, $fields2);
		$qb2->BuildStatement();
	}
}
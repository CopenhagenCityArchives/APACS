<?php
include_once '../lib/library/IStatementBuilder.php';
include_once '../lib/library/FindStatementBuilder.php';

class FindStatementBuilderTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testBuildFindQuery() {
		$tableName = 'tableName';
		$fields = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'value']];
		$values = ['field1' => 1];
		$qb1 = new FindStatementBuilder($tableName, $fields, $values);
		$qb1->BuildStatement();
		$this->assertEquals("SELECT * FROM tableName WHERE field1 = \"1\"", $qb1->GetStatement(), 'should build statement with single field');
	}

	public function testBuildInsertQueryMultipleFields() {
		$tableName2 = 'tableName2';
		$fields2 = [['name' => 'field1', 'dbFieldName' => 'field1', 'type' => 'value'], ['name' => 'field2', 'dbFieldName' => 'field2', 'type' => 'value']];
		$values = ['field1' => 1, 'field2' => 2];

		$qb2 = new FindStatementBuilder($tableName2, $fields2, $values);
		$qb2->BuildStatement();
		$this->assertEquals("SELECT * FROM tableName2 WHERE field1 = \"1\" AND field2 = \"2\"", $qb2->GetStatement(), 'should return an insert query');
	}
}
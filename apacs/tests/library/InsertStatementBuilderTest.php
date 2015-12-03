<?php

include '../lib/library/InsertStatementBuilder.php';

class InsertStatementBuilderTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
    }

    public function testBuildInsertQuery()
    {
        $tableName = 'tableName';
        $fields = [['name' => 'field1', 'dbFieldName' => 'field1']];

    	$qb1 = new InsertStatementBuilder($tableName, $fields);
        $qb1->BuildStatement();
    	$this->assertEquals("INSERT INTO tableName (`field1`) VALUES (:field1)", $qb1->statement, 'should build statement with single field');

        $tableName2 = 'tableName2';
        $fields2 = [['name' => 'field1', 'dbFieldName' => 'field1'], ['name' => 'field2', 'dbFieldName' => 'field2']];
    	$qb2 = new InsertStatementBuilder($tableName2, $fields2);
        $qb2->BuildStatement();
    	$this->assertEquals("INSERT INTO tableName2 (`field1`, `field2`) VALUES (:field1, :field2)", $qb2->statement, 'should return an insert query');
    }
}
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
    	$qb1 = new InsertStatementBuilder(['dbTableName' => 'tableName', 'fields' => [['name' => 'field1']]]);
        $qb1->BuildStatement();
    	$this->assertEquals("INSERT INTO tableName (`field1`) VALUES (:field1)", $qb1->statement, 'should build statement with single field');

    	$qb2 = new InsertStatementBuilder(['dbTableName' => 'tableName2', 'fields' => [['name' => 'field1'], ['name' => 'field2']]]);
        $qb2->BuildStatement();
    	$this->assertEquals("INSERT INTO tableName2 (`field1`, `field2`) VALUES (:field1, :field2)", $qb2->statement, 'should return an insert query');
    }
}
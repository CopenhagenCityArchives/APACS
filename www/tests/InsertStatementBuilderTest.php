<?php

include '../kbh_backend/library/InsertStatementBuilder.php';

class InsertStatementBuilderTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
    }

    public function testBuildInsertQuery()
    {

    	$qb1 = new InsertStatementBuilder("tableName", ['field1']);

    	$this->assertEquals("INSERT INTO tableName `field1` VALUES (:field1)", $qb1->GetStatement(), 'should build statement with single field');

    	$qb2 = new InsertStatementBuilder("tableName", ['field1', 'field2']);

    	$this->assertEquals("INSERT INTO tableName `field1`, `field2` VALUES (:field1, :field2)", $qb2->GetStatement(), 'should return an insert query');
    }
}
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

    public function testBuildLoadQuery()
    {
        $tableName = 'tableName';
        $fields = [['name' => 'field1', 'dbFieldName' => 'field1']];

    	$qb1 = new LoadStatementBuilder($tableName, $fields);
        $qb1->BuildStatement();
    	$this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.id = :id", $qb1->GetStatement(), 'should build statement with single field');
    }

    public function testBuildLoadQueryCustomKey()
    {
        $tableName = 'tableName';
        $fields = [['name' => 'field1', 'dbFieldName' => 'field1']];

        $qb1 = new LoadStatementBuilder($tableName, $fields, 'another_key');
        $qb1->BuildStatement();
        $this->assertEquals("SELECT `field1` FROM tableName WHERE tableName.another_key = :id", $qb1->GetStatement(), 'should build statement with custom key name');
    }

    public function testBuildLoadQueryWithJoins()
    {
        $tableName = 'tableName';
        $fields = [
            ['name' => 'field1', 'dbFieldName' => 'field1'], 
            ['name' => 'field2', 'dbFieldName' => 'field2', 'normalizationTable' => 'stillinger', 'normalizationField' => 'stilling', 'normalizationPrimaryKey' => 'stilling_id']
        ];
        $qb2 = new LoadStatementBuilder($tableName, $fields);
        $qb2->BuildStatement();
        $this->assertEquals("SELECT `field1`, `field2` as field2_id, stillinger.`stilling` as field2_value FROM tableName LEFT JOIN stillinger ON tableName.field2 = stillinger.stilling_id WHERE tableName.id = :id", $qb2->GetStatement(), 'should return an insert query');
    }
}
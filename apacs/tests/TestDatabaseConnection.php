<?php

/**
 * This class sets up a connection to a test database.
 * GetConnection returns a connection based on parameters set in phpunit.xml
 * GetDataSet returns an empty dataset based the connection.
 */
class TestDataBaseConnection extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;
    }

    final public function getDataSet(){
        $ds = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
       // $ds->addTable('insert_table');

        return $ds;
    }
}
?>
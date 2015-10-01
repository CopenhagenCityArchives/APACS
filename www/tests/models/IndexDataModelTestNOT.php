<?php
/**
 *
 * This test is not run at the moment. The IndexDataModel may be obsolete (GenericIndexModel)
 */
include '../kbh_backend/models/IndexDataModel.php';
include 'TestDatabaseConnection.php';

class IndexDataModelTest extends \UnitTestCase {
    private $testDatabase;

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

        //Test specific database, PHPUnit
        $this->testDatabase = new TestDatabaseConnection();
        $this->testDatabase->getConnection()->createQueryTable('insert_table', 'SELECT * FROM insert_table');

        //Test specific database, Phalcon
        $di->set('db', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "root",
                "password" => "",
                "dbname" => "unit_tests",
                'charset' => 'utf8'
                ));
            }
        );

        $di->set('collectionConfigurationLoader', function(){
            $conf = new CollectionsConfigurationModel();
            $conf->loadConfig(require('./mockData/EntryConfMock.php'));
            return $conf;
        });

        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        $this->getDI()->get('db')->query('DELETE FROM insert_table');
        parent::tearDown();
    }

    public function testReturnErrorOnValidationError()
    {
        $idm = new IndexDataModel();

        $done = $idm->Insert(1,1,1, $this->configuration);

        $this->assertEquals(false, $done, 'should return false when no data added');
        $this->assertEquals(2, count($idm->GetErrors()), 'should return a number of errors corresponding with number of required fields');
    }

    public function testInsertData()
    {
        $idm = new IndexDataModel();
        
        //Expecting 0 rows
        $this->assertEquals(0, $this->testDatabase->getConnection()->getRowCount('insert_table'), 'Test table should have 0 rows');
        
        $_GET['firstname'] = 'jens';
        $_GET['lastname'] = 'hansen';

        $this->assertEquals(true, $idm->Insert(0,0,0, $this->configuration), 'should return true');

        //Insert should give an extra row
        $this->assertEquals(1, $this->testDatabase->getConnection()->getRowCount('insert_table'), 'Test table should have 1 row');
    }
}
<?php
include '../kbh_backend/models/GenericIndexModel.php';

class GenericIndexModelTest extends \UnitTestCase {
    private $testDatabase;

    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;

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
            $conf->loadConfig('./mockData/MockCollectionsConfiguration.php');
            return $conf;
        });    

        $di->set('currentEntityId', function(){
            return 1;
        });

        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        $this->getDI()->get('db')->query('DELETE FROM insert_table');
        $this->getDI()->get('db')->query('DELETE FROM insert_table2');
        parent::tearDown();
    }

    public function testDatabaseAccess()
    {
        $this->getDI()->set('tableNameHolder', function(){
            return 'insert_table';
        });

        $this->getDI()->set('conf', function(){
            return [
                [
                    'name' => 'firstname2',
                    'validationRegularExpression' => '/\w{0,}/',
                    'validationErrorMessage' => 'Skalbestå af mindst ét tegn'
                ]
            ];
        });

        $model = new GenericIndexModel();
        $model->firstname = 'firstname1';
        $model->lastname = 'lastname1';
        if(!$model->save()){
            echo ($model->getMessages()[0]->getMessage());
        }
        $this->assertEquals(1,count($model->find("firstname = 'firstname1'")), 'should retrieve data from insert_table');
        

        $this->getDI()->set('tableNameHolder', function(){
            return 'insert_table2';
        });
        $model = new GenericIndexModel();

        $model->firstname2 = 'firstname2';
        $model->lastname2 = 'lastname2';
        $model->save();
        $this->assertEquals(1,count($model->find("lastname2 = 'lastname2'")), 'should retrieve data from insert_table2');

    }

    public function testDynamicValidation()
    {
        $this->getDI()->set('tableNameHolder', function(){
            return 'insert_table2';
        });

        $model = new GenericIndexModel();

        $model->firstname2 = 'firstname2';
        $model->lastname2 = 'lastname2';
        $this->assertEquals($model->save(), false, 'should fail validation');
    }
}
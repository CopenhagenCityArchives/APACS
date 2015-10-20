<?php

include '../kbh_backend/models/ObjectsModel.php';

class ObjectsModelTest extends \UnitTestCase {
    
    private $_model;
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        $di = new \Phalcon\Di\FactoryDefault;   

        //Test specific database, Phalcon
        $di->set('database', function(){
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => "localhost",
                "username" => "root",
                "password" => "",
                "dbname" => "unit_tests",
                'charset' => 'utf8'
                ));
            }
        ); 

        parent::setUp($di, $config);

        $this->_model = new ObjectsModel();
    }
    
    public function tearDown() {
        $this->getDI()->get('database')->query('DELETE FROM insert_table');
        parent::tearDown();
        $this->_model = null;
    }
    
    public function testInitialization() 
    {
        $this->assertInstanceOf('ObjectsModel', $this->_model);
    }
    
    public function testRequiredLevelChecker(){
        $allFilters = array(array('name' => 'streetname'),array('name' => 'noname'));
        $requiredFilter = array(['name' => 'streetname'], ['name' => 'year']);
        
        //Should throw an exception when required filters are not set
        //$this->setExpectedException('Exception');
        //$this->_model->getFilters($allFilters, $requiredFilter);
    }
    
    public function testCreateObjectQuery(){
        $filters = array(
            array('name' => 'station', 'value' => '1'),
            array('name' => 'roll', 'value' => '23')
        );
        $sql = 'SELECT * FROM PRB_registerblade WHERE :query';
        $expectedQuery = 'SELECT * FROM PRB_registerblade WHERE station = \'1\' AND roll = \'23\'';
        $this->assertEquals(
            $expectedQuery,
            $this->_model->createObjectQuery($sql, $filters),
            'should create a query based on filters and data sql'
        );
    }
    
    public function testConvertResultToObjects(){
        $metadataLevels = array(array('name' => 'station'),array('name' => 'roll'));
        $results = array(
            //array('id' => 341, 'station' => 1, 'roll' => '23', 'imageURL' => '/test/0001.jpg'),
            array('id' => 341, 'station' => 1, 'roll' => '23', 'imageURL' => '/test/0002.jpg'),
           // array('id' => 2, 'station' => 3, 'roll' => '24', 'imageURL' => '/test/0003.jpg'),
            array('id' => 2, 'station' => 3, 'roll' => '24', 'imageURL' => '/test/0004.jpg')
        );
        $expectedResult = array(
            array(
                'id' => 341,
                'metadata' => array('station' => 1, 'roll' => '23'),
                'images' => array('http://www.kbhkilder.dk/test/0002.jpg')
            ),
            array(
                'id' => 2,
                'metadata' => array('station' => 3, 'roll' => '24'),
                'images' => array('http://www.kbhkilder.dk/test/0004.jpg')                
            )
        );

        $this->assertEquals(
            $expectedResult,
            $this->_model->convertResultToObjects($results, $metadataLevels),                
            'should convert two dimensional result set to multidimensional array'
        );
    }

    public function testSetHeightAndWidthIfInResult()
    {
        $metadataLevels = [['name' => 'station'], ['name' => 'roll']];
        $results = [
            ['id' => 1, 'station' => 1, 'roll' => 2, 'width' => '1024', 'height' => '960', 'imageURL' => '/url']
        ];
        $expectedResult = [
            [
                'id' => 1, 
                'metadata' => ['station' => 1, 'roll' => 2, 'width' => '1024', 'height' => '960'], 
                'images' => ['http://www.kbhkilder.dk/url']
            ]
        ];

        $this->assertEquals($expectedResult, $this->_model->convertResultToObjects($results, $metadataLevels), 'should add width and height as metadata');
    }

    public function testGetQueryParameters()
    {
        $potentialFilters = [['name' => 'name'], ['name' => 'street']];
        $requiredFilters = [['name' => 'name']];
        $_GET['name'] = 'name_var';
        $_GET['street'] = 'street_var';

        $this->assertEquals([['name' => 'name', 'value' => 'name_var'],['name' => 'street', 'value' => 'street_var']], $this->_model->getFilters($potentialFilters, $requiredFilters), 'should retrieve GET parameters for all filters');
    }

    public function testGetQueryRequiredNotSet()
    {
        $potentialFilters = [['name' => 'name'], ['name' => 'street']];
        $requiredFilters = [['name' => 'name']];
        unset($_GET['name']);
        $_GET['street'] = 'street_var';

        $this->assertEquals([], $this->_model->getFilters($potentialFilters, $requiredFilters), 'should return empty array when required filters are not set');
    }

    public function testGetData()
    {
        $this->getDI()->get('database')->query("INSERT INTO insert_table (firstname) VALUES ('firstnameOne')");

        $sql = $this->_model->createObjectQuery('select * from insert_table WHERE :query', [['name' => 'firstname', 'value' => 'firstnameOne']]);

        $result = $this->_model->getData($sql);
        $this->assertEquals(1, count($result), 'should retrieve row from database');
    }
}
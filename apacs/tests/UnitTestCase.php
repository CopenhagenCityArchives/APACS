<?php

include dirname(__DIR__) . "/vendor/autoload.php";

use \Phalcon\Di;
use \PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase {

	/**
	 * @var \Phalcon\Config
	 */
	protected $_config;

	/**
	 * @var bool
	 */
	private $_loaded = false;

	private $di;

	protected function getDI(){
		return $this->di;
	}

	protected function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
        parent::setUp();
		
		
		// Use default DI if non given in concrete tests
		
		if(is_null($di)){
			$this->di = new Di();
		}
		else{
			$this->di = $di;
		}
		
		// Set config and db in DI
		//TODO Hardcoded db credentials for tests
		$this->di->setShared('config', function () {
            return [
                "host" => "database",
                "username" => "dev",
                "password" => "123456",
                "dbname" => "apacs",
                'charset' => 'utf8',
            ];
		});

		$this->di->setShared('db', function () {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($this->get('config'));
		});

		// Set DI as default (used in Phalcon Models)
		Di::setDefault($this->di);

		
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname'] . ';charset=utf8;');
		ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('id_column', 'id');
		
		$this->_config = $config;

        $this->_loaded = true;
	}

	/**
	 * Check if the test case is setup properly
	 * @throws \PHPUnit_Framework_IncompleteTestError;
	 */
	public function __destruct() {
	   if(!$this->_loaded) {
            throw new \PHPUnit_Framework_IncompleteTestError('Please run parent::setUp().');
		}

		//$di = Di::getDefault();
		//$di->get('db')->execute(file_get_contents('../init/cleanup.sql'));
	}
}
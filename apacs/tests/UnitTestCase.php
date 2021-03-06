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

	protected $di;

	protected function getDI()
	{
		return $this->di;
	}

	public static function createDI()
	{
		$di = new Di();

		// Set config and db in DI
		//TODO Hardcoded db credentials for tests
		$di->setShared('config', function () {
            return [
                "host" => "mysql",
                "username" => "dev",
                "password" => "123456",
                "dbname" => "apacs",
                'charset' => 'utf8',
            ];
		});
		
		$di->setShared('db', function () use ($di) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
		});

		return $di;
	}

	protected function setUp($di = null) : void {
        parent::setUp();
		
		// Use default DI if non given in concrete tests
		if (is_null($di)) {
			$this->di = $this::createDI();
		} else {
			$this->di = $di;
		}
		
		// Set DI as default (used in Phalcon Models)
		Di::setDefault($this->di);

		
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname'] . ';charset=utf8;');
		ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('id_column', 'id');

        $this->_loaded = true;
	}

	/**
	 * Check if the test case is setup properly
	 * @throws \Exception;
	 */
	public function __destruct() {
	   if(!$this->_loaded) {
            throw new Exception('Please run parent::setUp().');
		}

		//$di = Di::getDefault();
		//$di->get('db')->execute(file_get_contents('../init/cleanup.sql'));
	}
}
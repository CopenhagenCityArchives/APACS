<?php
use \Phalcon\Di;
use \Phalcon\Test\UnitTestCase as PhalconTestCase;

abstract class UnitTestCase extends PhalconTestCase {

	/**
	 * @var \Voice\Cache
	 */
	protected $_cache;

	/**
	 * @var \Phalcon\Config
	 */
	protected $_config;

	/**
	 * @var bool
	 */
	private $_loaded = false;

	public function setUp() {
        parent::setUp();

        // Load any additional services that might be required during testing
        $di = Di::getDefault();

        //Sets DI components.

		//Connection to the test database
		$di->setShared('db', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql([
				"host" => "database",
				"username" => "dev",
				"password" => "123456",
				"dbname" => "apacs",
				'charset' => 'utf8',
			]);
		});

		//Creating the database
		$di->get('db')->execute(file_get_contents("../init/init.sql"));

        //Setting the di which will be available for all descending test classes
        $this->di = $di;

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

		$di = Di::getDefault();
		$di->get('db')->execute(file_get_contents('../init/cleanup.sql'));
	}
}
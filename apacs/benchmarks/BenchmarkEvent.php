<?php
namespace Benchmark;
require_once './vendor/autoload.php';
require_once './lib/library/TestClass.php';

use Athletic\AthleticEvent;


class TestClassEvent extends AthleticEvent
{
	private $test;
	public function setUp()
	{
		$this->test = new TestClass();
	}

	/**
	 * @iterations 1000
	 */
	public function test()
	{
		$this->test->hello();
	}
}
<?php

class CommonInformationsController extends \Phalcon\Mvc\Controller
{
	private $config;
	private $response;

	public function onConstruct()
	{
	//	$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
	}
	
	public function GetProtocols()
	{

	}

	public function GetProtocol($id)
	{

	}
}
<?php

class CommonInformationsController extends \Phalcon\Mvc\Controller
{
	private $config;
	private $response;

	public function onConstruct()
	{
		$this->config = $this->getDI()->get('collectionConfigurationLoader');
		$this->response = $this->getDI()->get('response');
	}
	
	public function GetProtocols()
	{
		
	}

	public function GetProtocol($id)
	{

	}
}
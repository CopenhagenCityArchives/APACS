<?php

class DataReceiver
{

	private $_requestObj;

	/**
	 * 
	 * Constructor. Takes an request object
	 * @param Phalcon\Http\Request $requestObject A class used to receive http request data
	 */
	function __construct(Phalcon\Http\Request $requestObject)
	{
		$this->_requestObj = $requestObject;
	}

	/**
	 * Receives data from HTTP request based on request type and a list of fields
	 * @param string $requestType The name of the request type (POST, PUT, GET)
	 * @param Array  $fields      A list of names of the fields to receive
	 */
	public function GetDataFromFields($requestType, Array $fields){
		$methodName = $this->setGetMethodName($requestType);			

		$values = [];

		foreach($fields as $field){
			$values[$field['name']] = $this->_requestObj->$methodName($field['name'], null, null);
		}

		return $values;
	}

	public function Get($requestType, $field){
		$methodName = $this->setGetMethodName($requestType);

		return $this->_requestObj->$methodName($field, null, null);
	}

	private function setGetMethodName($requestType){
		$methodName = "";

		switch(strtolower($requestType)){
			case 'post':
				$methodName = 'getPost';
				break;

			case 'get':
				$methodName = 'getQuery';
				break;

			case 'put':
				$methodName = 'getPut';
				break;
			default:
				throw new Exception('Could not get data in DataReceiver. Request type not found: ' . $requestType);
		}

		return $methodName;		
	}
}
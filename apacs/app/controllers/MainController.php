<?php

use Phalcon\Mvc\Controller;

class MainController extends \Phalcon\Mvc\Controller {
	protected $config;
	protected $response;
	protected $request;
	protected $auth;

	public function onConstruct() {
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
		$this->auth = $this->getDI()->get('AccessController');
	}

	/**
	 * Checks if the user is logged in.
	 * Dies if authentication is required when the user is not logged in
	 * @param boolean $authenticationRequired Is authentication required?
	 */
	public function RequireAccessControl($authenticationRequired = true) {
		if (!$this->auth->AuthenticateUser() && $authenticationRequired == true) {
			$this->response->setStatusCode(401, 'Unauthorized access');
			$this->response->setJsonContent(['message' => $this->auth->GetMessage()]);
			$this->response->send();
			die();
		}
	}

	public function CheckFields($input, $requiredFields) {
		$error = false;
		array_map(function ($field) use ($input, &$error) {
			if (!isset($input[$field]) || is_null($input[$field])) {
				$error = true;
			}
		}, $requiredFields);

		if ($error) {
			$this->returnError(400, 'Necessary fields not set', 'the fields ' . implode($requiredFields, ', ') . ' are required');
			$this->response->send();
			die();
		}
	}

	/**
	 * Validates and returns JSON data from the raw body of a request
	 * An error is returned to the requester on invalid JSON format
	 * Otherwise the parsed JSON data is returned
	 * @return Array An array of data based on the raw input. Returns false on error.
	 */
	public function GetAndValidateJsonPostData() {
		$jsonData = json_decode($this->request->getRawBody(), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->SetResponse(401, 'Input error', ['Invalid JSON format']);
			return false;
		}

		if (is_null($jsonData) || count($jsonData) == 0) {
			$this->SetResponse(401, 'Input error', ['No data given']);
			return false;
		}

		return $jsonData;
	}
	/**
	 * Set the Response object with a return code and a message
	 * @param int responseCode The response code. Defaults to 200 (OK)
	 * @param string responseMessage The response message
	 * @param string or array responseData Additional data returned as JSON

	 */
	public function SetResponse($responseCode = 200, $responseMessage = null, $responseData = null) {
		if ($responseMessage == null) {
			$this->response->setStatusCode($responseCode, null);
		} else {
			$this->response->setStatusCode($responseCode, $responseMessage);
		}

		if ($responseData != null) {
			$this->response->setJsonContent($responseData);
		}
	}

	/**
	 * Returns an error
	 * @param int Error code. Defaults to 404 (not found)
	 * * @param string Errorcode message. Message to return in header
	 * @param string Error message. Defaults to blank
	 */
	public function returnError($errorCode = 404, $errorCodeMessage = '', $errorMessage = '') {

		//Set status code
		$this->response->setStatusCode($errorCode, $errorCodeMessage);

		//Set the content of the response
		$this->response->setContent($errorMessage);
	}

	/**
	 * Converts input data to JSON and and returns JSON
	 * @param  Array $data The array to return as JSON
	 */
	public function returnJson($data) {
		$request = new Phalcon\Http\Request();
		$callback = $request->get('callback');

		//Converts single item arrays to object
		/*  if(count($data) == 1){
	            $data = $data[0];
*/
		try {
			//Set the content of the response
			if ($callback) {
				$this->response->setContent($callback . '(' . json_encode($data) . ')');
			} else {
				$this->response->setContent(json_encode($data));
			}
		} catch (Exception $e) {
			$this->returnError(500, 'Could not load data: ' . $e);
		}
	}
}
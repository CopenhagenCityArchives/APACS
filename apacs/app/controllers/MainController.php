<?php

use Phalcon\Mvc\Controller;

class MainController extends \Phalcon\Mvc\Controller {
	/**
	 * Set the Response object with a return code and a message
	 * @param int responseCode The response code. Defaults to 200 (OK)
	 * @param string responseMessage The response message
	 * @param string or array responseData Additional data returned as JSON

	 */
	public function SetResponse($responseCode = 200, $responseMessage = null, $responseData = null) {
		if ($responseMessage == null) {
			$this->getDI()->get('response')->setStatusCode($responseCode, null);
		} else {
			$this->getDI()->get('response')->setStatusCode($responseCode, $responseMessage);
		}

		if ($responseData != null) {
			$this->getDI()->get('response')->setJsonContent($responseData);
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

		if (count($jsonData) == 0) {
			$this->SetResponse(401, 'Input error', ['No data given']);
			$this->response->setStatusCode(401, 'Input error');
			return false;
		}

		return $jsonData;
	}

	/**
	 * Returns an error
	 * @param int Error code. Defaults to 404 (not found)
	 * * @param string Errorcode message. Message to return in header
	 * @param string Error message. Defaults to blank
	 */
	public function returnError($errorCode = 404, $errorCodeMessage = '', $errorMessage = '') {
		//Getting a response instance
		$response = $this->getDI()->get('response');

		//Set status code
		$response->setStatusCode($errorCode, $errorCodeMessage);

		//Set the content of the response
		$response->setContent($errorMessage);
	}

	/**
	 * Converts input data to JSON and and returns JSON
	 * @param  Array $data The array to return as JSON
	 */
	public function returnJson($data) {
		//Create a response instance
		$response = $this->getDI()->get('response');

		$request = new Phalcon\Http\Request();
		$callback = $request->get('callback');

		//Converts single item arrays to object
		/*  if(count($data) == 1){
	            $data = $data[0];
*/
		try {
			//Set the content of the response
			if ($callback) {
				$response->setContent($callback . '(' . json_encode($data) . ')');
			} else {
				$response->setContent(json_encode($data));
			}
		} catch (Exception $e) {
			$this->returnError(500, 'Could not load data: ' . $e);
		}
	}
}
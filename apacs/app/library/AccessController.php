<?php

class AccessController {
	private $authResponse;
	private $message;
	private $request;

	public function __construct($request) {
		$this->request = $request;
	}

	public function AuthenticateUser() {

		$accessToken = $this->getAccessToken();

		if ($accessToken == false) {
			$this->message = 'access denied: No token given';
			return false;
		}

		$this->authResponse = null;

		$url = 'http://kbharkiv.bo.intern.redweb.dk/index.php?option=profile&api=oauth2&access_token=' . $accessToken;

		$response = $this->getWebPage($url);

		if ($response == false) {
			return false;
		}

		$this->authResponse = json_decode($response, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->message = 'could not decode response from auth server';
			return false;
		}

		$this->SyncronizeUser();

		return true;
	}

	public function GetMessage() {
		return $this->message;
	}

	private function getWebPage($url) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true, // return web page
			CURLOPT_HEADER => false, // don't return headers
			CURLOPT_FOLLOWLOCATION => true, // follow redirects
			CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
			CURLOPT_ENCODING => "", // handle compressed
			CURLOPT_USERAGENT => "test", // name of client
			CURLOPT_AUTOREFERER => true, // set referrer on redirect
			CURLOPT_CONNECTTIMEOUT => 10, // time-out on connect
			CURLOPT_TIMEOUT => 10, // time-out on response
		);

		$ch = curl_init($url);
		curl_setopt_array($ch, $options);

		$content = curl_exec($ch);

		if (curl_error($ch)) {
			$this->message = 'Could not concat auth server';
			return false;
		}

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '401') {
			$this->message = 'Invalid token';
			return false;
		}

		curl_close($ch);

		return $content;
	}

	private function getAccessToken() {

		$authHeader = $this->request->getServer('HTTP_AUTHORIZATION');

		//Checks the REDIRECT prefix. It is set if the server redirects the request before landing
		//on the executing page
		if (is_null($authHeader)) {
			$authHeader = $this->request->getServer('REDIRECT_HTTP_AUTHORIZATION');
		}

		if ($authHeader !== null) {
			$matches = array();
			preg_match('/Bearer (.*)/', $authHeader, $matches);

			if (isset($matches[1])) {
				return $matches[1];
			}
		}

		return false;
	}

	public function GetUserId() {

		if (is_null($this->authResponse)) {
			if ($this->AuthenticateUser() == false);
			{
				return null;
			}
		}

		return $this->authResponse['profile']['id'];
	}

	public function GetUserName() {
		return $this->authResponse['profile']['username'];
	}

	private function SyncronizeUser() {
		$user = new Users();

		$user->id = $this->authResponse['profile']['id'];
		$user->username = $this->authResponse['profile']['username'];
		$user->save();
	}

	public function UserCanEdit($userId, $taskId) {
		//Is the user the same as the one asking for permission?
		if ($this->GetUserId() == $userId) {
			return true;
		}

		//Is the user a super user?
		return count(SuperUsers::find(['conditions' => 'users_id = ' . $this->GetUserId() . ' AND tasks_id = ' . $taskId])) == 1;
	}
}
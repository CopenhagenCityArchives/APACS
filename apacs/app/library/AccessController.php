<?php

class AccessController implements IAccessController {
	private $authResponse;
	private $message = '';
	private $request;

	public function __construct($request) {
		$this->request = $request;
		$this->authResponse = null;
		$this->message = 'No access error message given';
	}

	public function AuthenticateUser() {

		if (is_null($this->authResponse)) {
			$accessToken = $this->getAccessToken();

			if ($accessToken == false) {
				$this->message = 'access denied: No token given';
				return false;
			}
			
			$encrypted_token =  crypt($accessToken, 'lkj34kvpndd943f');

			// Validate token in db
			$dbToken = Tokens::findFirst(['conditions' => 'expires > ' . time() . ' AND token = \'' . $encrypted_token . '\'']);

			if($dbToken){
				$this->authResponse['access_token'] = $dbToken->token;
				$this->authResponse['expires'] = $dbToken->expires;
				$this->authResponse['profile'] = [];
				$this->authResponse['profile']['id'] = $dbToken->user_id;
				$this->authResponse['profile']['username'] = $dbToken->user_name;
				return true;
			}

			// If token not in db, get from url

			$url = 'https://www.kbharkiv.dk/index.php?option=profile&api=oauth2&access_token=' . $accessToken;

			$response = $this->getWebPage($url);

			if ($response == false) {
				$this->message = 'Token not authorized on auth server';
				return false;
			}

			$this->authResponse = json_decode($response, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				$this->message = 'could not decode response from auth server';
				$this->authResponse = null;
				return false;
			}

			// Set token in db
			$token = new Tokens();
			$token->token = crypt($this->authResponse['access_token'], 'lkj34kvpndd943f');;
			$token->expires = $this->authResponse['expires'];
			$token->user_id = $this->authResponse['profile']['id'];
			$token->user_name = $this->authResponse['profile']['username'];
			$token->save();

			// When loaded from url, syncronize user data in db
			$this->SyncronizeUser();
		}

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
		$err = curl_error($ch);
		if ($err) {
			$this->message = 'Could not contact auth server: ' . $err;
			return false;
		}

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '401') {
			$this->message = 'Invalid token (401 from auth server)';
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
		if (!$this->AuthenticateUser()) {
			return -1;
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

	//user_id, task_id, timestamp
	/*public function UserCanEdit($userId, $timestamp, $taskId) {
		//Is the user the same as the one asking for permission?
		if ($this->GetUserId() == $userId) {
			return true;
		}

		if ($this->GetUserId()) {
			$isSuperUser = count(SuperUsers::find('users_id = ' . $this->GetUserId() . ' AND tasks_id = ' . $taskId)) == 1;

			if ($isSuperUser > 0) {
				//The user is a super user, no time limit given, so grant edit rights
				if (is_null($timestamp)) {
					return true;
				}

				$time = strtotime($timestamp);
				$one_week_ago = strtotime('-1 week');

				return $time < $one_week_ago;
			}
		}

		return false;
	}*/

	public function UserCanEdit($entry) {
		/**
		 * Who can edit when:
		 * 1) Users who created the post, at any time, and super users, at any time.
		 * No relevant by the time (commented out in the code):
		 * 2) Super users if no error reports are present
		 * 3) Superusers, if an error report are present, a specified amount of time after the error has been reported
		 */

		$attemptingUser = $this->GetUserId();

		//Creating user can always edit
		if ($entry->users_id == $attemptingUser || $this->IsSuperUser($entry->tasks_id)) {
			return true;
		}
/*
		$errorReport = ErrorReports::findFirst(['conditions' => 'entries_id = :entriesId: AND deleted = 0', 'bind' => ['entriesId' => $entry->id], 'order' => 'last_update']);

		$attemptingUserIsSuperUser = $this->IsSuperUser($attemptingUser);

		//If no error reports are given and the user is super user
		if ($attemptingUserIsSuperUser && !$errorReport) {
			return true;
		}

		//If error reports are given and the error report are older than a week and the user is super user
		if ($errorReport &&
			$attemptingUserIsSuperUser &&
			strtotime($errorReport->last_update) < strtotime('-1 week')) {
			return true;
		} else {
			$this->message = 'Du har ikke rettighed til at rette indtastningen, da det er under 7 dage siden, den er blevet fejlmeldt';
		}

		if ($this->message == '') {
			$this->message = 'Du har ikke rettighed til at tilføje eller rette';
		}
		*/

		return false;
	}

	/**
	 * Check if the authorized user is a superuser for:
	 *   1) The specified task, if given, or
	 *   2) Any task, if none is given.
	 * @param int taskId The id to check for superuser status for.
	 * @return bool Is the authorized user superuser.
	 */ 
	public function IsSuperUser($taskId = null){
		if ($taskId === null) {
			return SuperUsers::count([
				"conditions" => "user_id = :userId:",
				"bind" => [
					"userId" => $this->GetUserId()
				]
			]) > 0;
		} else {
			return SuperUsers::count([
				"conditions" => "users_id = :userId: AND tasks_id = :taskId:",
				"bind" => [
					"userId" => $this->GetUserId(),
					"taskId" => $taskId
				]
			]) > 0;
		}
	}
}

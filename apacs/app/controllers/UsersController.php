<?php

use \Auth0\SDK\API\Authentication;
use \Auth0\SDK\API\Management;

class UsersController extends MainController {

	public function GetActiveUsers() {
		$collectionId = $this->request->query('collection_id', 'int', false);
		$unitId = $this->request->query('unit_id', 'int', false);
		$pageId = $this->request->query('page_id', 'int', false);

		if ($collectionId == false && $unitId == false && $pageId == false) {
			$this->response->setStatusCode('400', 'Wrong parameter');
			$this->response->setJsonContent(['error_message' => 'collection_id, unit_id or page_id is required']);
		}

		$conditions = '';
		if ($collectionId !== false) {
			$conditions = 'e.collection_id = ' . $collectionId;
		} else if ($unitId !== false) {
			$conditions = 'e.unit_id = ' . $unitId;
		} else {
			$conditions = 'e.page_id  = ' . $pageId;
		}

		//When is a user active? Right now it's 15*60 seconds = 15 minutes
		$activeSessionDuration = time() - (15 * 60);

		$conditions = $conditions . ' AND timestamp < ' . $activeSessionDuration;

		$query = $this->modelsManager->createQuery('SELECT DISTINCT u.id, u.userName, u.profileImageUrl, p.page_number FROM Users as u LEFT JOIN Entries as e ON u.id = e.user_id LEFT JOIN Pages p ON e.page_id = p.id WHERE ' . $conditions);

		$results = $query->execute();
		if (count($results) == 1) {
			$this->response->setJsonContent($results[0]);
		} else {
			$this->response->setJsonContent($results);
		}
	}

	private function getManagementAccessToken() {
		$auth0_api = new Authentication(
			$this->getDI()->get('auth0Config')['domain'],
			$this->getDI()->get('auth0Config')['client_id']
		);

		$config = [
			'client_secret' => $this->getDI()->get('auth0Config')['client_secret'],
			'client_id' => $this->getDI()->get('auth0Config')['client_id'],
			'audience' => $this->getDI()->get('auth0Config')['mgmt_audience'],
		];

		try {
			$result = $auth0_api->client_credentials($config);
			return $result['access_token'];
		} catch (Exception $e) {
			echo $e->getMessage();
			return false;
		}
	}

	public function UpdateUserProfile() {
		$this->RequireAccessControl();
		$user = Users::findFirst($this->auth->GetUserId());

		if ($user == null) {
			$this->returnError(400, 'Bad Request', 'Could not find user', 'Could not find user');
			return;
		}

		$data = $this->GetAndValidateJsonPostData();
		if ($data == false) {
			$this->returnError(400, 'Bad Request', 'Missing or invalid data', 'Missing or invalid data');
			return;
		}

		$profile = [];
		if (array_key_exists('nickname', $data)) {
			$profile['nickname'] = $data['nickname'];

			if (Users::findFirst([
				'conditions' => 'username = :username:',
				'bind' => ['username' => $profile['nickname']]
			]) != null) {
				$this->returnError(400, 'Username Exists', 'Username already exists');
				return;
			}
		}

		if (array_key_exists('email', $data)) {
			$profile['email'] = $data['email'];
		}

		if (array_key_exists('password', $data)) {
			$profile['password'] = $data['password'];
		}

		if ($profile == []) {
			$this->returnError(400, 'Bad Request', 'Must update email, nickname or password.');
			return;
		}

		$access_token = $this->getManagementAccessToken();
		if (!$access_token) {
			$this->returnError(500, 'Internal Server Error');
			return;
		}

		$mgmt_api = new Management($access_token, $this->getDI()->get('auth0Config')['domain']);

		$updateResponse = $mgmt_api->users()->update($user->auth0_user_id, $profile);

		if (array_key_exists('email', $data)) {
			$mgmt_api->jobs()->sendVerificationEmail($user->auth0_user_id);
		}

		$this->returnJson($updateResponse);
	}
}
<?php

use \Auth0\SDK\API\Authentication;
use \Auth0\SDK\API\Management;

class UsersController extends MainController {

	public function GetActiveUsers() {
		$taskId = $this->request->getQuery('task_id', 'int');
		$unitId = $this->request->getQuery('unit_id', 'int');

		if (is_null($taskId) || is_null($unitId)) {
			$this->error('task_id and unit_id are required');
			return;
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetActiveUsersForTaskAndUnit($taskId, $unitId));
	}

	public function GetUser($userId) {
		$user = Users::findFirst($userId);

		if(!$user){
			$this->error("User with id " . $userId . " not found");
			return;
		}

		$user = $user->toArray();

		$user['super_user_tasks'] = SuperUsers::find(['conditions' => 'users_id = :userId:', 'bind' => ['userId' => $user['id']], 'columns' => ['tasks_id']])->toArray();

		#$this->response->setHeader("Cache-Control", "max-age=600");
		$this->response->setJsonContent($user, JSON_NUMERIC_CHECK);
	}

	public function GetUserActivities() {
		$userId = $this->request->getQuery('user_id', "int");

		if (is_null($userId)) {
			$this->error('user_id is required');
			return;
		}

		$events = new Events();
		$this->response->setJsonContent($events->GetUserActivitiesForUnits($userId)->toArray(), JSON_NUMERIC_CHECK);
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
<?php

/**
 * FakeAccessController is used to fake access control
 */
class FakeAccessController implements IAccessController {

	public function __construct($request) {
		$this->request = $request;
	}

	public function AuthenticateUser() {
		return true;
	}

	public function GetMessage() {
		return "Fake message";
	}

	public function GetUserId() {
		return 1;
	}

	public function GetUserName() {
		return "FakeUserName";
	}

	//user_id, task_id, timestamp
	public function UserCanEdit($userId, $timestamp, $taskId) {
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
	}
}
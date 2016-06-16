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

	public function UserCanEdit(int $userId, int $taskId) {
		return 1;
	}
}
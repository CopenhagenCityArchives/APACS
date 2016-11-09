<?php

interface IAccessController {

	public function __construct($request);

	public function AuthenticateUser(); //: bool;

	public function GetMessage(); //: string;

	public function GetUserId(); //: int;

	public function GetUserName(); //: string;

	public function UserCanEdit($userId, $timestamp, $taskId); //: bool;
}
<?php

interface IAccessController {

	public function __construct($di);

	public function AuthenticateUser(); //: bool;

	public function GetMessage(); //: string;

	public function GetUserId(); //: int;

	public function GetUserName(); //: string;

	public function UserCanEdit($entry); //: bool;

	public function IsSuperUser(); //: bool;
}

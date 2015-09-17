<?php

class Authorizer implements IAccessController
{
	private $_user;

	public function setUser(User $user)
	{
		$this->_user = $user;
	}

	public function hasAccess(AccessContext $context)
	{
		return true;
	}
}
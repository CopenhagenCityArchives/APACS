<?php

interface IAccessController
{
	/**
	 * Checks if a given user has access to a given action.
	 * The check is based on the user object and a AccessContext object, describing the context
	 * @return boolean true if access is granted, false if not
	 */
	public function hasAccess(Context $context);
	public function setUser(User $user);
}
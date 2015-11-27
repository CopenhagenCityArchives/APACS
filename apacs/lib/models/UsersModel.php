<?php

class UsersModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $name;
	protected $password;

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'reporting_user_id');
        $this->hasMany('id', 'TasksUsers', 'user_id');
    }
}
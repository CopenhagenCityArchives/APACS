<?php

class Users extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $name;
	protected $password;

    public function getSource()
    {
        return 'apacs_' . 'users';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'reporting_user_id');
        $this->hasMany('id', 'TasksUsers', 'user_id');
    }
}
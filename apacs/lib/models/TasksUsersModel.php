<?php

class TasksUsersModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $isActive;
    protected $isSuperuser;
    protected $usersId;
    protected $tasksId;

    public function getSource()
    {
        return 'apacs_' . 'tasks_users';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Users', 'users_id');
        $this->hasMany('id', 'Tasks', 'tasks_id');
    }
}
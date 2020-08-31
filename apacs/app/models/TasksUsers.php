<?php

class TasksUsers extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $isActive;
    protected $isSuperuser;
    protected $usersId;
    protected $tasksId;

    public function getSource()
    {
        return 'apacs_tasks_users';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Users', 'user_id');
        $this->hasMany('id', 'Tasks', 'task_id');
    }
}
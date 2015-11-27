<?php

class TasksModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $name;
    protected $description;

    public function initialize()
    {
        $this->hasMany('id', 'TasksUnits', 'tasks_id');
        $this->hasMany('id', 'Entries', 'tasks_id');
        $this->hasMany('id', 'TasksPages', 'tasks_id');
    }
}
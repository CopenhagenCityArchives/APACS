<?php

class TasksPages extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesDone;
    protected $isActive;
    protected $entryLayout;

    public function initialize()
    {
        $this->hasMany('id', 'Units', 'units_id');
        $this->hasMany('id', 'Tasks', 'tasks_id');
    }
}
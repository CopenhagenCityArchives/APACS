<?php

class TasksPages extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $isActive;
    protected $isDone;
    protected $pagesId;
    protected $tasksId;

    public function getSource()
    {
        return 'apacs_' . 'tasks_pages';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Pages', 'page_id');
        $this->hasMany('id', 'Tasks', 'task_id');
    }
}
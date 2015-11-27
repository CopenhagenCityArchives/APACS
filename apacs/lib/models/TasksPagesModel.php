<?php

class TasksPagesModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $isActive;
    protected $isDone;
    protected $pagesId;
    protected $tasksId;

    public function initialize()
    {
        $this->hasMany('id', 'Pages', 'pages_id');
        $this->hasMany('id', 'Tasks', 'tasks_id');
    }
}
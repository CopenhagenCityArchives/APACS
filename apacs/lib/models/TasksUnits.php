<?php

class TasksUnits extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesDone;
    protected $isActive;
    protected $entryLayout;
    protected $unitId;
    protected $taskId;

    public function getSource()
    {
        return 'apacs_' . 'tasks_units';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Units', 'unit_id');
        $this->hasMany('id', 'Tasks', 'task_id');
    }
}
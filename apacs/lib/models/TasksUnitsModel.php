<?php

class TasksUnitsModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesDone;
    protected $isActive;
    protected $entryLayout;
    protected $unitsId;
    protected $tasksId;

    public function getSource()
    {
        return 'apacs_' . 'tasks_units';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Units', 'units_id');
        $this->hasMany('id', 'Tasks', 'tasks_id');
    }
}
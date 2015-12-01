<?php

class TasksFields extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $helpText;

    public function getSource()
    {
        return 'apacs_' . 'tasks_fields';
    }

    public function initialize()
    {
        $this->belongsTo('fields_id', 'Fields', 'id');
        $this->belongsTo('tasks_id', 'Tasks', 'id');
    }
}
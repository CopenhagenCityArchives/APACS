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
        $this->belongsTo('field_id', 'Fields', 'id');
        $this->belongsTo('task_id', 'Tasks', 'id');
    }
}
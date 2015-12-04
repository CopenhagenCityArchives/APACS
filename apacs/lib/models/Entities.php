<?php

class Entities extends \Phalcon\Mvc\Model
{
	public $id;
	public $task_id;

    public function getSource()
    {
        return 'apacs_' . 'entities';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'EntitiesFields', 'entities_id');
    	$this->belongsTo('task_id', 'Task', 'id');
    }
}
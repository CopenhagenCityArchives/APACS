<?php

class Entities extends \Phalcon\Mvc\Model
{
    public static $publicFields = ['id', 'required', 'countPerEntry', 'isMarkable','guiName', 'task_id'];

    public function getSource()
    {
        return 'apacs_' . 'entities';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'EntitiesFields', 'entity_id');
    	$this->belongsTo('task_id', 'Task', 'id');
    }
}
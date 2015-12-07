<?php

class Tasks extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'tasks';
    }

    public function initialize()
    {
        $this->hasMany('id', 'TasksUnits', 'task_id');
        $this->hasMany('id', 'Entries', 'task_id');
        $this->hasMany('id', 'TasksPages', 'task_id');
        $this->hasMany('id', 'Fieldgroups', 'task_id');
        $this->belongsTo('collection_id', 'Collections', 'id');
    }
}
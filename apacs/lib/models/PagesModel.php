<?php

class PagesModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $unitsId;
	protected $collectionId;

    public function initialize()
    {
        $this->hasMany('id', 'Entries', 'pages_id');
        $this->hasMany('id', 'TasksPages', 'pages_id');
        $this->belongsTo('unit_id', 'Units', 'id');
    }
}
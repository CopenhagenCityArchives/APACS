<?php

class Entries extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;

    public function getSource()
    {
        return 'apacs_' . 'entries';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'entries_id');
        $this->belongsTo('pages_id', 'Pages', 'id');
        $this->belongsTo('tasks_id', 'Tasks', 'id');
    }
}